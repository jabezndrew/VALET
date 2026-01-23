#include <SPI.h>
#include <MFRC522.h>
#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>

// Pin definitions
#define SS_PIN 5
#define RST_PIN 22

// WiFi credentials
const char* ssid = "Aguspina 2";
const char* password = "cebucity";

// API endpoint
const char* apiUrl = "https://valet.up.railway.app/api/public/rfid/exit";

MFRC522 rfid(SS_PIN, RST_PIN);

String gateMacAddress;
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
    Serial.println("WARNING: Communication failure, check MFRC522 wiring!");
  } else {
    Serial.print("MFRC522 Software Version: 0x");
    Serial.println(version, HEX);
  }

  // Get MAC address
  gateMacAddress = WiFi.macAddress();

  // Connect to WiFi
  Serial.println("\n=== VALET RFID Exit Gate ===");
  Serial.print("MAC Address: ");
  Serial.println(gateMacAddress);
  Serial.print("Connecting to WiFi: ");
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
    Serial.println("\nWiFi connected!");
    Serial.print("IP Address: ");
    Serial.println(WiFi.localIP());
  } else {
    Serial.println("\nWiFi connection failed! Will retry...");
  }

  Serial.println("System ready. Logging exits...\n");
}

void loop() {
  unsigned long currentMillis = millis();

  // Feed watchdog timer by yielding
  yield();

  // Periodically check WiFi connection
  if (currentMillis - lastWiFiCheck >= WIFI_CHECK_INTERVAL) {
    lastWiFiCheck = currentMillis;
    if (WiFi.status() != WL_CONNECTED) {
      Serial.println("WiFi disconnected! Reconnecting...");
      WiFi.disconnect();
      WiFi.begin(ssid, password);

      int attempts = 0;
      while (WiFi.status() != WL_CONNECTED && attempts < 20) {
        delay(500);
        Serial.print(".");
        attempts++;
        yield(); // Feed watchdog
      }

      if (WiFi.status() == WL_CONNECTED) {
        Serial.println("\nWiFi reconnected!");
      }
    }
  }

  // Throttle RFID checks to prevent overwhelming the reader
  if (currentMillis - lastRfidCheck < RFID_CHECK_INTERVAL) {
    return;
  }
  lastRfidCheck = currentMillis;

  // Periodically reset RFID reader to prevent lockups
  static unsigned long lastRfidReset = 0;
  if (currentMillis - lastRfidReset > 60000) { // Reset every 60 seconds
    lastRfidReset = currentMillis;
    rfid.PCD_Init(); // Re-initialize RFID reader
    Serial.println("RFID reader reset (periodic maintenance)");
  }

  // Check for RFID card
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

  Serial.print("Exit RFID Detected: ");
  Serial.println(uid);

  // Halt PICC before logging to prevent multiple reads
  rfid.PICC_HaltA();
  rfid.PCD_StopCrypto1();

  // Log exit with API
  logExit(uid);

  delay(1000); // Prevent multiple scans
}

void logExit(String uid) {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("ERROR: WiFi not connected!");
    // Try to reconnect
    WiFi.begin(ssid, password);
    delay(5000);
    if (WiFi.status() != WL_CONNECTED) {
      return;
    }
  }

  HTTPClient http;
  http.setTimeout(10000); // 10 second timeout
  http.begin(apiUrl);
  http.addHeader("Content-Type", "application/json");

  // Prepare JSON payload
  StaticJsonDocument<200> doc;
  doc["uid"] = uid;
  doc["gate_mac"] = gateMacAddress;

  String requestBody;
  serializeJson(doc, requestBody);

  Serial.println("Logging exit to API...");
  int httpResponseCode = http.POST(requestBody);

  if (httpResponseCode > 0) {
    String response = http.getString();
    Serial.print("Response code: ");
    Serial.println(httpResponseCode);
    Serial.print("Response: ");
    Serial.println(response);

    // Parse JSON response
    StaticJsonDocument<256> responseDoc;
    DeserializationError error = deserializeJson(responseDoc, response);

    if (error) {
      Serial.print("JSON parse error: ");
      Serial.println(error.c_str());
      http.end();
      return;
    }

    bool success = responseDoc["success"];
    const char* message = responseDoc["message"];

    if (success) {
      int durationMinutes = responseDoc["duration_minutes"];
      Serial.println("\n✓ EXIT LOGGED");
      Serial.print("Parking duration: ");
      Serial.print(durationMinutes);
      Serial.println(" minutes");
    } else {
      Serial.println("\n✗ EXIT FAILED");
      Serial.print("Reason: ");
      Serial.println(message);
    }

  } else {
    Serial.print("ERROR: HTTP request failed, code: ");
    Serial.println(httpResponseCode);

    // Reset HTTP connection on persistent errors
    if (httpResponseCode == -1) {
      WiFi.disconnect();
      delay(1000);
      WiFi.begin(ssid, password);
    }
  }

  http.end();
  Serial.println("------------------------\n");

  // Clear any buffered data
  while (Serial.available()) {
    Serial.read();
  }
}
