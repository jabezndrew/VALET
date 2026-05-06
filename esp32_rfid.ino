/*
  VALET - Combined Entrance + Exit Gate
  One ESP32, two MFRC522 readers on shared SPI bus.
  Offline fallback:
  - Local UID cache (LittleFS)
  - Offline queue for logs
  - Auto sync when internet returns
*/

#include <SPI.h>
#include <MFRC522.h>
#include <ESP32Servo.h>
#include <WiFi.h>
#include <HTTPClient.h>
#include <WiFiClientSecure.h>
#include <ArduinoJson.h>
#include <LittleFS.h>

#define ENABLE_ENTRANCE true
#define ENABLE_EXIT     true

#define SS_ENTRANCE   5
#define RST_ENTRANCE  22
#define SS_EXIT       21
#define RST_EXIT      4
#define SERVO_PIN     25

const char* ssid     = "Hushwa";
const char* password = "raveloravelo3";

const char* entranceApiUrl    = "https://valet.up.railway.app/api/public/rfid/verify";
const char* exitApiUrl        = "https://valet.up.railway.app/api/public/rfid/exit";
const char* registeredUidsUrl = "https://valet.up.railway.app/api/public/rfid/registered";

MFRC522 rfidEntrance(SS_ENTRANCE, RST_ENTRANCE);
MFRC522 rfidExit(SS_EXIT, RST_EXIT);
Servo gateServo;

bool entranceReady = false;
bool exitReady     = false;
bool servoIsOpen   = false;

String gateMacAddress = "";

unsigned long servoCloseTime       = 0;
unsigned long lastEntranceScanTime = 0;
String        lastEntranceUid      = "";
unsigned long lastExitScanTime     = 0;
String        lastExitUid          = "";

unsigned long lastRfidCheck = 0;
unsigned long lastWiFiCheck = 0;
unsigned long lastRfidReset = 0;
unsigned long lastUidSync   = 0;

bool wifiWasPreviouslyConnected = false;

// ── Timing ────────────────────────────────────────────────────
const unsigned long RFID_CHECK_INTERVAL = 100;
const unsigned long WIFI_CHECK_INTERVAL = 15000;
const unsigned long SAME_CARD_COOLDOWN  = 3000;
const unsigned long UID_SYNC_INTERVAL   = 300000;

// ── Forward declarations ──────────────────────────────────────
String readUID(MFRC522 &reader);
void verifyRFID(String uid);
void logExit(String uid);
void syncRegisteredUIDs();
void flushOfflineQueue();
void queueOfflineEvent(const String& type, const String& uid);
bool isRegisteredLocally(const String& uid);
void reinitReadersAfterHTTP();
WiFiClientSecure* makeClient();

// ── Reusable secure client factory ───────────────────────────
WiFiClientSecure* makeClient() {
    WiFiClientSecure* client = new WiFiClientSecure();
    client->setInsecure();
    client->setTimeout(15);
    return client;
}

// ── Local UID cache ───────────────────────────────────────────
bool isRegisteredLocally(const String& uid) {
    if (!LittleFS.exists("/uids.txt")) return false;
    File f = LittleFS.open("/uids.txt", "r");
    if (!f) return false;
    while (f.available()) {
        String line = f.readStringUntil('\n');
        line.trim();
        if (line == uid) { f.close(); return true; }
    }
    f.close();
    return false;
}

// ── Reinit readers after HTTPS ────────────────────────────────
void reinitReadersAfterHTTP() {
    SPI.begin();
    if (entranceReady) {
        rfidEntrance.PCD_Init();
        delay(50);
        rfidEntrance.PCD_SetAntennaGain(rfidEntrance.RxGain_max);
    }
    if (exitReady) {
        rfidExit.PCD_Init();
        delay(50);
        rfidExit.PCD_SetAntennaGain(rfidExit.RxGain_max);
    }
}

// ── Sync UID cache from server ────────────────────────────────
void syncRegisteredUIDs() {
    if (WiFi.status() != WL_CONNECTED) return;
    Serial.println("Syncing UID cache...");

    WiFiClientSecure* client = makeClient();
    HTTPClient http;
    http.setTimeout(15000);
    http.begin(*client, String(registeredUidsUrl) + "?gate_mac=" + gateMacAddress);

    int code = http.GET();
    if (code == 200) {
        String response = http.getString();
        DynamicJsonDocument doc(8192);
        if (!deserializeJson(doc, response) && doc.containsKey("uids")) {
            File f = LittleFS.open("/uids.txt", "w");
            if (f) {
                JsonArray arr = doc["uids"].as<JsonArray>();
                for (JsonVariant v : arr) f.println(v.as<String>());
                f.close();
                Serial.printf("UID cache synced: %d UIDs\n", (int)arr.size());
            }
        }
    } else {
        Serial.printf("UID sync failed: HTTP %d\n", code);
    }

    http.end();
    delete client;
    reinitReadersAfterHTTP();
}

// ── Queue offline event ───────────────────────────────────────
void queueOfflineEvent(const String& type, const String& uid) {
    File f = LittleFS.open("/queue.ndjson", "a");
    if (!f) { Serial.println("Queue open failed."); return; }
    StaticJsonDocument<200> doc;
    doc["type"]     = type;
    doc["uid"]      = uid;
    doc["gate_mac"] = gateMacAddress;
    serializeJson(doc, f);
    f.println();
    f.close();
    Serial.printf("Queued offline %s for %s\n", type.c_str(), uid.c_str());
}

// ── Flush queued events ───────────────────────────────────────
void flushOfflineQueue() {
    if (!LittleFS.exists("/queue.ndjson")) return;
    if (WiFi.status() != WL_CONNECTED) return;
    Serial.println("Flushing offline queue...");

    File f = LittleFS.open("/queue.ndjson", "r");
    if (!f) return;

    bool allSent = true;
    while (f.available()) {
        String line = f.readStringUntil('\n');
        line.trim();
        if (line.isEmpty()) continue;

        StaticJsonDocument<200> event;
        if (deserializeJson(event, line)) continue;

        String type    = event["type"].as<String>();
        String uid     = event["uid"].as<String>();
        String gateMac = event["gate_mac"].as<String>();

        const char* url = (type == "entrance") ? entranceApiUrl : exitApiUrl;

        WiFiClientSecure* client = makeClient();
        HTTPClient http;
        http.setTimeout(15000);
        http.begin(*client, url);
        http.addHeader("Content-Type", "application/json");

        StaticJsonDocument<200> body;
        body["uid"]      = uid;
        body["gate_mac"] = gateMac;
        body["offline"]  = true;

        String bodyStr;
        serializeJson(body, bodyStr);
        int code = http.POST(bodyStr);
        http.end();
        delete client;

        if (code > 0) {
            Serial.printf("Flushed %s for %s [HTTP %d]\n", type.c_str(), uid.c_str(), code);
        } else {
            Serial.printf("Flush failed for %s [%d]\n", uid.c_str(), code);
            allSent = false;
            break;
        }
    }
    f.close();

    if (allSent) {
        LittleFS.remove("/queue.ndjson");
        Serial.println("Offline queue cleared.");
    }

    reinitReadersAfterHTTP();
}

// ── Setup ─────────────────────────────────────────────────────
void setup() {
    delay(1000);
    Serial.begin(115200);
    Serial.println("\n=== VALET Gate Booting ===");

    SPI.begin();

#if ENABLE_ENTRANCE
    rfidEntrance.PCD_Init();
    delay(50);
    {
        byte ver = rfidEntrance.PCD_ReadRegister(rfidEntrance.VersionReg);
        if (ver == 0x00 || ver == 0xFF) {
            Serial.println("Entrance MFRC522: NOT DETECTED");
        } else {
            entranceReady = true;
            rfidEntrance.PCD_SetAntennaGain(rfidEntrance.RxGain_max);
            Serial.printf("Entrance MFRC522 v%X OK\n", ver);
        }
    }
#endif

#if ENABLE_EXIT
    rfidExit.PCD_Init();
    delay(50);
    {
        byte ver2 = rfidExit.PCD_ReadRegister(rfidExit.VersionReg);
        if (ver2 == 0x00 || ver2 == 0xFF) {
            Serial.println("Exit MFRC522: NOT DETECTED");
        } else {
            exitReady = true;
            rfidExit.PCD_SetAntennaGain(rfidExit.RxGain_max);
            Serial.printf("Exit MFRC522 v%X OK\n", ver2);
        }
    }
#endif

    gateServo.attach(SERVO_PIN);
    gateServo.write(0);

    if (!LittleFS.begin(true)) {
        Serial.println("LittleFS FAILED");
    } else {
        Serial.println("LittleFS OK");
    }

    WiFi.mode(WIFI_STA);
    WiFi.begin(ssid, password);
    Serial.print("Connecting WiFi");
    int attempts = 0;
    while (WiFi.status() != WL_CONNECTED && attempts < 20) {
        delay(500);
        Serial.print(".");
        attempts++;
    }
    Serial.println();

    gateMacAddress = WiFi.macAddress();

    if (WiFi.status() == WL_CONNECTED) {
        Serial.print("IP: ");
        Serial.println(WiFi.localIP());
    } else {
        Serial.println("WiFi not connected — running in offline mode.");
    }

    wifiWasPreviouslyConnected = (WiFi.status() == WL_CONNECTED);
    Serial.println("System ready.");
}

// ── Main loop ─────────────────────────────────────────────────
void loop() {
    unsigned long now = millis();
    yield();

    // WiFi watchdog
    if (now - lastWiFiCheck >= WIFI_CHECK_INTERVAL) {
        lastWiFiCheck = now;
        if (WiFi.status() != WL_CONNECTED) {
            Serial.println("WiFi reconnecting...");
            WiFi.disconnect();
            WiFi.begin(ssid, password);
        }
    }

    // Detect WiFi restoration — flush queue and sync UIDs immediately
    bool wifiNow = (WiFi.status() == WL_CONNECTED);
    if (wifiNow && !wifiWasPreviouslyConnected) {
        Serial.println("WiFi restored.");
        flushOfflineQueue();
        syncRegisteredUIDs();
        lastUidSync = now;
    }
    wifiWasPreviouslyConnected = wifiNow;

    // Periodic UID sync
    if (wifiNow) {
        bool firstSync    = (lastUidSync == 0 && now >= 3000);
        bool periodicSync = (lastUidSync > 0 && (now - lastUidSync >= UID_SYNC_INTERVAL));
        if (firstSync || periodicSync) {
            lastUidSync = now;
            syncRegisteredUIDs();
        }
    }

    // Auto close gate
    if (servoIsOpen && now >= servoCloseTime) {
        gateServo.write(0);
        servoIsOpen = false;
        Serial.println("Gate closed.");
    }

    // RFID polling throttle
    if (now - lastRfidCheck < RFID_CHECK_INTERVAL) return;
    lastRfidCheck = now;

    // Periodic RFID reader recovery
    if (now - lastRfidReset > 60000) {
        lastRfidReset = now;
        reinitReadersAfterHTTP();
    }

    // Entrance scan
    if (entranceReady &&
        rfidEntrance.PICC_IsNewCardPresent() &&
        rfidEntrance.PICC_ReadCardSerial()) {
        String uid = readUID(rfidEntrance);
        rfidEntrance.PICC_HaltA();
        rfidEntrance.PCD_StopCrypto1();
        if (uid == lastEntranceUid && (now - lastEntranceScanTime < SAME_CARD_COOLDOWN)) return;
        lastEntranceUid      = uid;
        lastEntranceScanTime = now;
        Serial.print("ENTRANCE UID: ");
        Serial.println(uid);
        verifyRFID(uid);
    }

    // Exit scan
    if (exitReady &&
        rfidExit.PICC_IsNewCardPresent() &&
        rfidExit.PICC_ReadCardSerial()) {
        String uid = readUID(rfidExit);
        rfidExit.PICC_HaltA();
        rfidExit.PCD_StopCrypto1();
        if (uid == lastExitUid && (now - lastExitScanTime < SAME_CARD_COOLDOWN)) return;
        lastExitUid      = uid;
        lastExitScanTime = now;
        Serial.print("EXIT UID: ");
        Serial.println(uid);
        logExit(uid);
    }
}

// ── Read UID ──────────────────────────────────────────────────
String readUID(MFRC522 &reader) {
    String uid = "";
    for (byte i = 0; i < reader.uid.size; i++) {
        if (reader.uid.uidByte[i] < 0x10) uid += "0";
        uid += String(reader.uid.uidByte[i], HEX);
    }
    uid.toUpperCase();
    return uid;
}

// ── Entrance verification ─────────────────────────────────────
void verifyRFID(String uid) {

    // OFFLINE MODE
    if (WiFi.status() != WL_CONNECTED) {
        Serial.println("OFFLINE: checking cache...");
        if (isRegisteredLocally(uid)) {
            Serial.println("OFFLINE ACCESS GRANTED");
            gateServo.write(90);
            servoIsOpen    = true;
            servoCloseTime = millis() + 5000UL;
            queueOfflineEvent("entrance", uid);
        } else {
            Serial.println("OFFLINE ACCESS DENIED");
        }
        return;
    }

    // ONLINE MODE
    WiFiClientSecure* client = makeClient();
    HTTPClient http;
    http.setTimeout(15000);
    http.begin(*client, entranceApiUrl);
    http.addHeader("Content-Type", "application/json");

    StaticJsonDocument<200> doc;
    doc["uid"]      = uid;
    doc["gate_mac"] = gateMacAddress;
    String body;
    serializeJson(doc, body);

    int code = http.POST(body);

    if (code > 0) {
        String response = http.getString();
        Serial.printf("Verify response [%d]: %.120s\n", code, response.c_str());

        StaticJsonDocument<512> res;
        if (!deserializeJson(res, response)) {
            bool valid = res["valid"] | false;
            if (valid) {
                int duration = res["duration"] | 7;
                gateServo.write(90);
                servoIsOpen    = true;
                servoCloseTime = millis() + (duration * 1000UL);
                Serial.println("ACCESS GRANTED");
            } else {
                Serial.printf("ACCESS DENIED: %s\n", res["message"].as<const char*>());
            }
        } else {
            // Server returned HTML (Railway cold start / unhandled error) — fall back to cache
            Serial.println("Bad server response — falling back to cache");
            if (isRegisteredLocally(uid)) {
                Serial.println("CACHE FALLBACK ACCESS GRANTED");
                gateServo.write(90);
                servoIsOpen    = true;
                servoCloseTime = millis() + 5000UL;
                queueOfflineEvent("entrance", uid);
            } else {
                Serial.println("CACHE FALLBACK ACCESS DENIED");
            }
        }
    } else {
        Serial.printf("HTTP error %d — falling back to cache\n", code);
        if (isRegisteredLocally(uid)) {
            Serial.println("CACHE FALLBACK ACCESS GRANTED");
            gateServo.write(90);
            servoIsOpen    = true;
            servoCloseTime = millis() + 5000UL;
            queueOfflineEvent("entrance", uid);
        } else {
            Serial.println("CACHE FALLBACK ACCESS DENIED");
        }
    }

    http.end();
    delete client;
    reinitReadersAfterHTTP();
}

// ── Exit logging ──────────────────────────────────────────────
void logExit(String uid) {

    // OFFLINE MODE
    if (WiFi.status() != WL_CONNECTED) {
        Serial.println("OFFLINE: queueing exit");
        queueOfflineEvent("exit", uid);
        return;
    }

    // ONLINE MODE
    WiFiClientSecure* client = makeClient();
    HTTPClient http;
    http.setTimeout(15000);
    http.begin(*client, exitApiUrl);
    http.addHeader("Content-Type", "application/json");

    StaticJsonDocument<200> doc;
    doc["uid"]      = uid;
    doc["gate_mac"] = gateMacAddress;
    String body;
    serializeJson(doc, body);

    int code = http.POST(body);

    if (code > 0) {
        Serial.printf("Exit logged [HTTP %d]\n", code);
    } else {
        Serial.printf("Exit HTTP error %d — queuing for retry\n", code);
        queueOfflineEvent("exit", uid);
    }

    http.end();
    delete client;
    reinitReadersAfterHTTP();
}
