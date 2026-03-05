/*
  VALET - Combined Entrance + Exit Gate
  One ESP32, two MFRC522 readers on shared SPI bus.

  Wiring:
  ─────────────────────────────────────────
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
  ─────────────────────────────────────────
*/

#include <SPI.h>
#include <MFRC522.h>
#include <ESP32Servo.h>
#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>

// ── Enable/disable each reader ───────────────────────────────────
// Set to false to skip a reader entirely (e.g. not yet wired up)
#define ENABLE_ENTRANCE true
#define ENABLE_EXIT     true

// ── Pin definitions ───────────────────────────────────────────────
#define SS_ENTRANCE   5
#define RST_ENTRANCE  22
#define SERVO_PIN     25

#define SS_EXIT       21
#define RST_EXIT      4

// ── WiFi credentials ─────────────────────────────────────────────
const char* ssid = "Aguspina 2";
const char* password = "cebucity";

// ── API endpoints ─────────────────────────────────────────────────
const char* entranceApiUrl = "https://valet.up.railway.app/api/public/rfid/verify";
const char* exitApiUrl     = "https://valet.up.railway.app/api/public/rfid/exit";

// ── Hardware ──────────────────────────────────────────────────────
MFRC522 rfidEntrance(SS_ENTRANCE, RST_ENTRANCE);
MFRC522 rfidExit(SS_EXIT, RST_EXIT);
Servo   gateServo;

// Runtime availability flags (set during setup based on hardware detection)
bool entranceReady = false;
bool exitReady     = false;

String gateMacAddress;

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

  SPI.begin();

  // ── Init entrance reader ─────────────────────────────────────
#if ENABLE_ENTRANCE
  rfidEntrance.PCD_Init();
  byte ver = rfidEntrance.PCD_ReadRegister(rfidEntrance.VersionReg);
  if (ver == 0x00 || ver == 0xFF) {
    Serial.println("Entrance MFRC522: NOT DETECTED (check wiring)");
  } else {
    entranceReady = true;
    Serial.print("Entrance MFRC522 v"); Serial.print(ver, HEX); Serial.println(" - OK");
  }
#else
  Serial.println("Entrance reader: DISABLED");
#endif

  // ── Init exit reader ─────────────────────────────────────────
#if ENABLE_EXIT
  rfidExit.PCD_Init();
  ver = rfidExit.PCD_ReadRegister(rfidExit.VersionReg);
  if (ver == 0x00 || ver == 0xFF) {
    Serial.println("Exit MFRC522: NOT DETECTED (check wiring)");
  } else {
    exitReady = true;
    Serial.print("Exit MFRC522 v"); Serial.print(ver, HEX); Serial.println(" - OK");
  }
#else
  Serial.println("Exit reader: DISABLED");
#endif

  gateServo.attach(SERVO_PIN);
  gateServo.write(0);

  gateMacAddress = WiFi.macAddress();

  Serial.println("=== VALET Gate (Entrance + Exit) ===");
  Serial.print("MAC: "); Serial.println(gateMacAddress);

  WiFi.mode(WIFI_STA);
  WiFi.begin(ssid, password);

  int attempts = 0;
  while (WiFi.status() != WL_CONNECTED && attempts < 40) {
    delay(500); Serial.print("."); attempts++;
  }

  if (WiFi.status() == WL_CONNECTED) {
    Serial.print("\nIP: "); Serial.println(WiFi.localIP());
  } else {
    Serial.println("\nWiFi failed!");
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
      WiFi.disconnect();
      WiFi.begin(ssid, password);
      int attempts = 0;
      while (WiFi.status() != WL_CONNECTED && attempts < 20) {
        delay(500); attempts++; yield();
      }
    }
  }

  // Auto-close entrance gate
  if (servoIsOpen && now >= servoCloseTime) {
    gateServo.write(0);
    servoIsOpen = false;
  }

  // Throttle RFID polling
  if (now - lastRfidCheck < RFID_CHECK_INTERVAL) return;
  lastRfidCheck = now;

  // Periodic RFID reader reset
  if (now - lastRfidReset > 60000) {
    lastRfidReset = now;
    if (entranceReady) rfidEntrance.PCD_Init();
    if (exitReady)     rfidExit.PCD_Init();
  }

  // ── Poll entrance reader ───────────────────────────────────────
  if (entranceReady && rfidEntrance.PICC_IsNewCardPresent() && rfidEntrance.PICC_ReadCardSerial()) {
    String uid = readUID(rfidEntrance);
    rfidEntrance.PICC_HaltA();
    rfidEntrance.PCD_StopCrypto1();
    Serial.print("ENTRANCE: "); Serial.println(uid);
    verifyRFID(uid);
    rfidEntrance.PCD_Init();
  }

  // ── Poll exit reader ──────────────────────────────────────────
  if (exitReady && rfidExit.PICC_IsNewCardPresent() && rfidExit.PICC_ReadCardSerial()) {
    String uid = readUID(rfidExit);
    rfidExit.PICC_HaltA();
    rfidExit.PCD_StopCrypto1();

    if (uid == lastExitUid && (now - lastExitScanTime < SAME_CARD_COOLDOWN)) return;
    lastExitUid      = uid;
    lastExitScanTime = now;

    Serial.print("EXIT: "); Serial.println(uid);
    logExit(uid);
    rfidExit.PCD_Init();
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
  delay(5000);
  return WiFi.status() == WL_CONNECTED;
}

// ─────────────────────────────────────────────────────────────────
void verifyRFID(String uid) {
  if (!ensureWiFi()) return;

  HTTPClient http;
  http.setTimeout(10000);
  http.begin(entranceApiUrl);
  http.addHeader("Content-Type", "application/json");

  StaticJsonDocument<200> doc;
  doc["uid"]      = uid;
  doc["gate_mac"] = gateMacAddress;
  String body;
  serializeJson(doc, body);

  int code = http.POST(body);

  if (code > 0) {
    String response = http.getString();
    StaticJsonDocument<512> res;
    if (deserializeJson(res, response)) { http.end(); return; }

    bool valid = res["valid"];
    if (valid) {
      const char* name = res["user"]["name"];
      int duration     = res["duration"];
      Serial.print("GRANTED: "); Serial.println(name);
      gateServo.write(90);
      servoIsOpen    = true;
      servoCloseTime = millis() + (duration * 1000);
    } else {
      Serial.print("DENIED: "); Serial.println((const char*)res["message"]);
      if (servoIsOpen) { gateServo.write(0); servoIsOpen = false; }
    }
  } else {
    Serial.print("HTTP ERR: "); Serial.println(code);
    if (code == -1) { WiFi.disconnect(); delay(1000); WiFi.begin(ssid, password); }
  }

  http.end();
}

// ─────────────────────────────────────────────────────────────────
void logExit(String uid) {
  if (!ensureWiFi()) return;

  HTTPClient http;
  http.setTimeout(10000);
  http.begin(exitApiUrl);
  http.addHeader("Content-Type", "application/json");

  StaticJsonDocument<200> doc;
  doc["uid"]      = uid;
  doc["gate_mac"] = gateMacAddress;
  String body;
  serializeJson(doc, body);

  int code = http.POST(body);

  if (code > 0) {
    String response = http.getString();
    StaticJsonDocument<256> res;
    if (deserializeJson(res, response)) { http.end(); return; }

    bool success = res["success"];
    if (success) {
      int duration = res["duration_minutes"];
      Serial.print("EXIT OK: "); Serial.print(duration); Serial.println(" min");
    } else {
      Serial.print("INFO: "); Serial.println((const char*)res["message"]);
    }
  } else {
    Serial.print("HTTP ERR: "); Serial.println(code);
    if (code == -1) { WiFi.disconnect(); delay(1000); WiFi.begin(ssid, password); }
  }

  http.end();
}
