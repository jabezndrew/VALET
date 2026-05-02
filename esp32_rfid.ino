/*
  VALET - Combined Entrance + Exit Gate
  One ESP32, two MFRC522 readers on shared SPI bus.

  Wiring:
  ─────────────────────────────────────────────────────────────────
  Shared SPI Bus:
    SCK  → GPIO 18
    MISO → GPIO 19
    MOSI → GPIO 23

  Entrance RFID (MFRC522):
    SDA/SS → GPIO 5
    RST    → GPIO 22

  Exit RFID (MFRC522):
    SDA/SS → GPIO 21
    RST    → GPIO 4

  Entrance Servo:
    Signal → GPIO 25

  Libraries required:
    - MFRC522     v1.4.12  (GithubCommunity)
    - ArduinoJson v6.21.5  (Benoit Blanchon) ← must be v6, NOT v7
    - ESP32Servo  (Kevin Harrington)
  ─────────────────────────────────────────────────────────────────
*/

#include <SPI.h>
#include <MFRC522.h>
#include <ESP32Servo.h>
#include <WiFi.h>
#include <HTTPClient.h>
#include <WiFiClientSecure.h>
#include <ArduinoJson.h>

// ── Enable/disable each reader ────────────────────────────────────
#define ENABLE_ENTRANCE true
#define ENABLE_EXIT     true

// ── Pin definitions ───────────────────────────────────────────────
#define SS_ENTRANCE   5
#define RST_ENTRANCE  22
#define SERVO_PIN     25
#define SS_EXIT       21
#define RST_EXIT      4

// ── WiFi credentials ──────────────────────────────────────────────
const char* ssid     = "Hushwa";
const char* password = "raveloravelo3";

// ── API endpoints ─────────────────────────────────────────────────
const char* entranceApiUrl = "https://valet.up.railway.app/api/public/rfid/verify";
const char* exitApiUrl     = "https://valet.up.railway.app/api/public/rfid/exit";

// ── Hardware ──────────────────────────────────────────────────────
MFRC522 rfidEntrance(SS_ENTRANCE, RST_ENTRANCE);
MFRC522 rfidExit(SS_EXIT, RST_EXIT);
Servo   gateServo;

bool entranceReady = false;
bool exitReady     = false;

String gateMacAddress = "";

// Entrance servo state
unsigned long servoCloseTime = 0;
bool          servoIsOpen    = false;

// Exit scan cooldown
unsigned long lastExitScanTime = 0;
String        lastExitUid      = "";

// Timers
unsigned long lastRfidCheck = 0;
unsigned long lastWiFiCheck = 0;
unsigned long lastRfidReset = 0;

const unsigned long RFID_CHECK_INTERVAL = 100;
const unsigned long WIFI_CHECK_INTERVAL = 30000;
const unsigned long SAME_CARD_COOLDOWN  = 2000;

// ─────────────────────────────────────────────────────────────────
void setup() {
  delay(1000);
  Serial.begin(115200);
  Serial.println("\n=== VALET Gate Booting ===");

  SPI.begin();

  // ── Init entrance reader ──────────────────────────────────────
#if ENABLE_ENTRANCE
  rfidEntrance.PCD_Init();
  delay(50);
  byte ver = rfidEntrance.PCD_ReadRegister(rfidEntrance.VersionReg);
  if (ver == 0x00 || ver == 0xFF) {
    Serial.println("Entrance MFRC522: NOT DETECTED (check wiring)");
  } else {
    entranceReady = true;
    rfidEntrance.PCD_SetAntennaGain(rfidEntrance.RxGain_max);
    Serial.print("Entrance MFRC522 v"); Serial.print(ver, HEX); Serial.println(" - OK (gain: MAX)");
  }
#else
  Serial.println("Entrance reader: DISABLED");
#endif

  // ── Init exit reader ──────────────────────────────────────────
#if ENABLE_EXIT
  rfidExit.PCD_Init();
  delay(50);
  byte ver2 = rfidExit.PCD_ReadRegister(rfidExit.VersionReg);
  if (ver2 == 0x00 || ver2 == 0xFF) {
    Serial.println("Exit MFRC522: NOT DETECTED (check wiring)");
  } else {
    exitReady = true;
    rfidExit.PCD_SetAntennaGain(rfidExit.RxGain_max);
    Serial.print("Exit MFRC522 v"); Serial.print(ver2, HEX); Serial.println(" - OK (gain: MAX)");
  }
#else
  Serial.println("Exit reader: DISABLED");
#endif

  // ── Servo init ────────────────────────────────────────────────
  gateServo.attach(SERVO_PIN);
  gateServo.write(0);
  Serial.println("Servo: initialized at 0 deg (closed)");

  // ── WiFi ──────────────────────────────────────────────────────
  WiFi.mode(WIFI_STA);
  WiFi.begin(ssid, password);

  Serial.print("Connecting to WiFi");
  int attempts = 0;
  while (WiFi.status() != WL_CONNECTED && attempts < 40) {
    delay(500);
    Serial.print(".");
    attempts++;
  }
  Serial.println();

  gateMacAddress = WiFi.macAddress();

  Serial.println("=== VALET Gate (Entrance + Exit) ===");
  Serial.print("MAC: "); Serial.println(gateMacAddress);

  if (WiFi.status() == WL_CONNECTED) {
    Serial.print("IP:  "); Serial.println(WiFi.localIP());
  } else {
    Serial.println("WiFi FAILED - will retry in loop");
  }

  Serial.println("Ready.\n");
}

// ─────────────────────────────────────────────────────────────────
void loop() {
  unsigned long now = millis();
  yield();

  // WiFi reconnect watchdog
  if (now - lastWiFiCheck >= WIFI_CHECK_INTERVAL) {
    lastWiFiCheck = now;
    if (WiFi.status() != WL_CONNECTED) {
      Serial.println("WiFi lost. Reconnecting...");
      WiFi.disconnect();
      WiFi.begin(ssid, password);
      int attempts = 0;
      while (WiFi.status() != WL_CONNECTED && attempts < 20) {
        delay(500); attempts++; yield();
      }
      if (WiFi.status() == WL_CONNECTED) {
        gateMacAddress = WiFi.macAddress();
        Serial.print("Reconnected. IP: "); Serial.println(WiFi.localIP());
        Serial.print("MAC: "); Serial.println(gateMacAddress);
      } else {
        Serial.println("Reconnect failed.");
      }
    }
  }

  // Auto-close entrance gate
  if (servoIsOpen && now >= servoCloseTime) {
    gateServo.write(0);
    servoIsOpen = false;
    Serial.println("Gate closed.");
  }

  // Throttle RFID polling
  if (now - lastRfidCheck < RFID_CHECK_INTERVAL) return;
  lastRfidCheck = now;

  // Periodic RFID reader reset every 60s
  if (now - lastRfidReset > 60000) {
    lastRfidReset = now;
    if (entranceReady) {
      rfidEntrance.PCD_Init();
      rfidEntrance.PCD_SetAntennaGain(rfidEntrance.RxGain_max);
    }
    if (exitReady) {
      rfidExit.PCD_Init();
      rfidExit.PCD_SetAntennaGain(rfidExit.RxGain_max);
    }
  }

  // ── Poll entrance reader ──────────────────────────────────────
  if (entranceReady && rfidEntrance.PICC_IsNewCardPresent() && rfidEntrance.PICC_ReadCardSerial()) {
    String uid = readUID(rfidEntrance);
    rfidEntrance.PICC_HaltA();
    rfidEntrance.PCD_StopCrypto1();
    Serial.print("ENTRANCE UID: "); Serial.println(uid);
    verifyRFID(uid);
    rfidEntrance.PCD_Init();
    rfidEntrance.PCD_SetAntennaGain(rfidEntrance.RxGain_max);
  }

  // ── Poll exit reader ──────────────────────────────────────────
  if (exitReady && rfidExit.PICC_IsNewCardPresent() && rfidExit.PICC_ReadCardSerial()) {
    String uid = readUID(rfidExit);
    rfidExit.PICC_HaltA();
    rfidExit.PCD_StopCrypto1();

    if (uid == lastExitUid && (now - lastExitScanTime < SAME_CARD_COOLDOWN)) return;
    lastExitUid      = uid;
    lastExitScanTime = now;

    Serial.print("EXIT UID: "); Serial.println(uid);
    logExit(uid);
    rfidExit.PCD_Init();
    rfidExit.PCD_SetAntennaGain(rfidExit.RxGain_max);
  }
}

// ─────────────────────────────────────────────────────────────────
String readUID(MFRC522 &reader) {
  String uid = "";
  for (byte i = 0; i < reader.uid.size; i++) {
    uid += String(reader.uid.uidByte[i] < 0x10 ? "0" : "");
    uid += String(reader.uid.uidByte[i], HEX);
  }
  uid.toUpperCase();
  return uid;
}

// ─────────────────────────────────────────────────────────────────
bool ensureWiFi() {
  if (WiFi.status() == WL_CONNECTED) return true;
  Serial.println("No WiFi! Reconnecting...");
  WiFi.begin(ssid, password);
  int attempts = 0;
  while (WiFi.status() != WL_CONNECTED && attempts < 20) {
    delay(500); attempts++;
  }
  if (WiFi.status() == WL_CONNECTED) {
    gateMacAddress = WiFi.macAddress();
    Serial.print("Reconnected. MAC: "); Serial.println(gateMacAddress);
    return true;
  }
  Serial.println("WiFi reconnect failed.");
  return false;
}

// ─────────────────────────────────────────────────────────────────
void verifyRFID(String uid) {
  if (!ensureWiFi()) {
    Serial.println("Cannot verify - no WiFi.");
    return;
  }

  Serial.printf("Free heap before entrance request: %u bytes\n", ESP.getFreeHeap());

  WiFiClientSecure client;
  client.setInsecure();
  client.setTimeout(30); // 30s SSL handshake timeout — fixes Railway TLS

  HTTPClient http;
  http.setTimeout(15000);
  http.begin(client, entranceApiUrl);
  http.addHeader("Content-Type", "application/json");

  StaticJsonDocument<200> doc;
  doc["uid"]      = uid;
  doc["gate_mac"] = gateMacAddress;
  String body;
  serializeJson(doc, body);

  Serial.print("POST entrance | body: "); Serial.println(body);

  int code = http.POST(body);

  if (code > 0) {
    String response = http.getString();
    Serial.print("Response ["); Serial.print(code); Serial.print("]: ");
    Serial.println(response);

    StaticJsonDocument<512> res;
    if (deserializeJson(res, response)) {
      Serial.println("JSON parse error.");
      http.end();
      return;
    }

    bool valid = res["valid"];
    if (valid) {
      const char* name = res["user"]["name"];
      int duration     = res["duration"];
      Serial.print("GRANTED: "); Serial.println(name);
      Serial.print("Gate open for: "); Serial.print(duration); Serial.println("s");
      gateServo.write(90);
      servoIsOpen    = true;
      servoCloseTime = millis() + (duration * 1000UL);
    } else {
      Serial.print("DENIED: "); Serial.println((const char*)res["message"]);
      if (servoIsOpen) {
        gateServo.write(0);
        servoIsOpen = false;
      }
    }
  } else {
    Serial.print("HTTP ERR: "); Serial.println(code);
    if (code == -1) {
      WiFi.disconnect();
      delay(1000);
      WiFi.begin(ssid, password);
    }
  }

  http.end();
}

// ─────────────────────────────────────────────────────────────────
void logExit(String uid) {
  if (!ensureWiFi()) {
    Serial.println("Cannot log exit - no WiFi.");
    return;
  }

  Serial.printf("Free heap before exit request: %u bytes\n", ESP.getFreeHeap());

  WiFiClientSecure client;
  client.setInsecure();
  client.setTimeout(30); // 30s SSL handshake timeout — fixes Railway TLS

  HTTPClient http;
  http.setTimeout(15000);
  http.begin(client, exitApiUrl);
  http.addHeader("Content-Type", "application/json");

  StaticJsonDocument<200> doc;
  doc["uid"]      = uid;
  doc["gate_mac"] = gateMacAddress;
  String body;
  serializeJson(doc, body);

  Serial.print("POST exit | body: "); Serial.println(body);

  int code = http.POST(body);

  if (code > 0) {
    String response = http.getString();
    Serial.print("Response ["); Serial.print(code); Serial.print("]: ");
    Serial.println(response);

    StaticJsonDocument<256> res;
    if (deserializeJson(res, response)) {
      Serial.println("JSON parse error.");
      http.end();
      return;
    }

    bool success = res["success"];
    if (success) {
      int duration = res["duration_minutes"];
      Serial.print("EXIT OK: "); Serial.print(duration); Serial.println(" min parked");
    } else {
      Serial.print("INFO: "); Serial.println((const char*)res["message"]);
    }
  } else {
    Serial.print("HTTP ERR: "); Serial.println(code);
    if (code == -1) {
      WiFi.disconnect();
      delay(1000);
      WiFi.begin(ssid, password);
    }
  }

  http.end();
}
