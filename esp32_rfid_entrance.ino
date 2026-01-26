#include <SPI.h>
#include <MFRC522.h>
#include <ESP32Servo.h>
#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>

// Pin definitions
#define SS_PIN 5
#define RST_PIN 22
#define SERVO_PIN 25

// WiFi credentials
const char* ssid = "Aguspina 2";
const char* password = "cebucity";

// API endpoint
const char* apiUrl = "https://valet.up.railway.app/api/public/rfid/verify";

MFRC522 rfid(SS_PIN, RST_PIN);
Servo gateServo;

String gateMacAddress;
unsigned long servoCloseTime = 0;
bool servoIsOpen = false;
unsigned long lastRfidCheck = 0;
unsigned long lastWiFiCheck = 0;
const unsigned long RFID_CHECK_INTERVAL = 100; // Check RFID every 100ms
const unsigned long WIFI_CHECK_INTERVAL = 30000; // Check WiFi every 30 seconds

void setup() {
  delay(1000);
  Serial.begin(115200);

  // Initialize SPI and RFID
  SPI.begin();
  rfid.PCD_Init();

  // Verify RFID reader initialization
  byte version = rfid.PCD_ReadRegister(rfid.VersionReg);
  if (version == 0x00 || version == 0xFF) {
    Serial.println("WARNING: MFRC522 wiring issue!");
  } else {
    Serial.print("MFRC522 v");
    Serial.println(version, HEX);
  }

  // Initialize servo
  gateServo.attach(SERVO_PIN);
  gateServo.write(0); // Start closed

  // Get MAC address
  gateMacAddress = WiFi.macAddress();

  // Connect to WiFi
  Serial.println("=== VALET Entrance Gate ===");
  Serial.print("MAC: ");
  Serial.println(gateMacAddress);
  Serial.print("WiFi: ");
  Serial.println(ssid);

  WiFi.mode(WIFI_STA);
  WiFi.begin(ssid, password);

  int attempts = 0;
  while (WiFi.status() != WL_CONNECTED && attempts < 40) {
    delay(500);
    Serial.print(".");
    attempts++;
  }

  if (WiFi.status() == WL_CONNECTED) {
    Serial.print("\nIP: ");
    Serial.println(WiFi.localIP());
  } else {
    Serial.println("\nWiFi failed!");
  }

  Serial.println("Ready.\n");
}

void loop() {
  unsigned long currentMillis = millis();

  // Feed watchdog
  yield();

  // Check WiFi every 30 seconds
  if (currentMillis - lastWiFiCheck >= WIFI_CHECK_INTERVAL) {
    lastWiFiCheck = currentMillis;
    if (WiFi.status() != WL_CONNECTED) {
      WiFi.disconnect();
      WiFi.begin(ssid, password);
      int attempts = 0;
      while (WiFi.status() != WL_CONNECTED && attempts < 20) {
        delay(500);
        attempts++;
        yield();
      }
    }
  }

  // Auto-close gate
  if (servoIsOpen && currentMillis >= servoCloseTime) {
    gateServo.write(0);
    servoIsOpen = false;
  }

  // Throttle RFID checks
  if (currentMillis - lastRfidCheck < RFID_CHECK_INTERVAL) {
    return;
  }
  lastRfidCheck = currentMillis;

  // Reset RFID reader every 60 seconds
  static unsigned long lastRfidReset = 0;
  if (currentMillis - lastRfidReset > 60000) {
    lastRfidReset = currentMillis;
    rfid.PCD_Init();
  }

  // Check for new card
  if (!rfid.PICC_IsNewCardPresent()) {
    return;
  }

  if (!rfid.PICC_ReadCardSerial()) {
    return;
  }

  // Read UID
  String uid = "";
  for (byte i = 0; i < rfid.uid.size; i++) {
    uid += String(rfid.uid.uidByte[i] < 0x10 ? "0" : "");
    uid += String(rfid.uid.uidByte[i], HEX);
  }
  uid.toUpperCase();

  // Halt card
  rfid.PICC_HaltA();
  rfid.PCD_StopCrypto1();

  Serial.print("RFID: ");
  Serial.println(uid);

  // Verify with API
  verifyRFID(uid);
}

void verifyRFID(String uid) {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("No WiFi!");
    WiFi.begin(ssid, password);
    delay(5000);
    if (WiFi.status() != WL_CONNECTED) {
      return;
    }
  }

  HTTPClient http;
  http.setTimeout(10000);
  http.begin(apiUrl);
  http.addHeader("Content-Type", "application/json");

  StaticJsonDocument<200> doc;
  doc["uid"] = uid;
  doc["gate_mac"] = gateMacAddress;

  String requestBody;
  serializeJson(doc, requestBody);

  int httpResponseCode = http.POST(requestBody);

  if (httpResponseCode > 0) {
    String response = http.getString();

    StaticJsonDocument<512> responseDoc;
    DeserializationError error = deserializeJson(responseDoc, response);

    if (error) {
      Serial.println("JSON error");
      http.end();
      return;
    }

    bool valid = responseDoc["valid"];
    const char* message = responseDoc["message"];
    int duration = responseDoc["duration"];

    if (valid) {
      const char* userName = responseDoc["user"]["name"];
      Serial.print("GRANTED: ");
      Serial.println(userName);

      // Open gate
      gateServo.write(90);
      servoIsOpen = true;
      servoCloseTime = millis() + (duration * 1000);
    } else {
      Serial.print("DENIED: ");
      Serial.println(message);

      // Close gate immediately on invalid scan
      if (servoIsOpen) {
        gateServo.write(0);
        servoIsOpen = false;
      }
    }

  } else {
    Serial.print("HTTP ERR: ");
    Serial.println(httpResponseCode);

    if (httpResponseCode == -1) {
      WiFi.disconnect();
      delay(1000);
      WiFi.begin(ssid, password);
    }
  }

  http.end();

  // Reinitialize RFID reader to allow immediate rescan of same card
  rfid.PCD_Init();
}
