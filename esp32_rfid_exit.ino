#include <SPI.h>
#include <MFRC522.h>
#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>

// Pin definitions
#define SS_PIN 5
#define RST_PIN 22

// WiFi credentials - CHANGE THESE
const char* ssid = "YOUR_WIFI_SSID";
const char* password = "YOUR_WIFI_PASSWORD";

// API endpoint - CHANGE THIS TO YOUR VALET API URL
const char* apiUrl = "https://your-valet-api.com/api/public/rfid/exit";

MFRC522 rfid(SS_PIN, RST_PIN);

String gateMacAddress;

void setup() {
  delay(1000);
  Serial.begin(115200);

  // Initialize SPI and RFID
  SPI.begin();
  rfid.PCD_Init();

  // Get MAC address
  gateMacAddress = WiFi.macAddress();

  // Connect to WiFi
  Serial.println("\n=== VALET RFID Exit Gate ===");
  Serial.print("MAC Address: ");
  Serial.println(gateMacAddress);
  Serial.print("Connecting to WiFi: ");
  Serial.println(ssid);

  WiFi.begin(ssid, password);

  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }

  Serial.println("\nWiFi connected!");
  Serial.print("IP Address: ");
  Serial.println(WiFi.localIP());
  Serial.println("System ready. Logging exits...\n");
}

void loop() {
  // Check for RFID card
  if (!rfid.PICC_IsNewCardPresent() || !rfid.PICC_ReadCardSerial()) {
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

  // Log exit with API
  logExit(uid);

  rfid.PICC_HaltA();
  rfid.PCD_StopCrypto1();

  delay(1000); // Prevent multiple scans
}

void logExit(String uid) {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("ERROR: WiFi not connected!");
    return;
  }

  HTTPClient http;
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
  }

  http.end();
  Serial.println("------------------------\n");
}
