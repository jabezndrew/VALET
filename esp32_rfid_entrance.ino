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

// WiFi credentials - CHANGE THESE
const char* ssid = "YOUR_WIFI_SSID";
const char* password = "YOUR_WIFI_PASSWORD";

// API endpoint - CHANGE THIS TO YOUR VALET API URL
const char* apiUrl = "https://your-valet-api.com/api/public/rfid/verify";

MFRC522 rfid(SS_PIN, RST_PIN);
Servo gateServo;

String gateMacAddress;
unsigned long servoCloseTime = 0;
bool servoIsOpen = false;

void setup() {
  delay(1000);
  Serial.begin(115200);

  // Initialize SPI and RFID
  SPI.begin();
  rfid.PCD_Init();

  // Initialize servo
  gateServo.attach(SERVO_PIN);
  gateServo.write(0); // Start with gate closed

  // Get MAC address
  gateMacAddress = WiFi.macAddress();

  // Connect to WiFi
  Serial.println("\n=== VALET RFID Entrance Gate ===");
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
  Serial.println("System ready. Waiting for RFID tags...\n");
}

void loop() {
  // Check if servo should be closed
  if (servoIsOpen && millis() >= servoCloseTime) {
    closeGate();
  }

  // Check for new RFID card
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

  Serial.print("RFID Detected: ");
  Serial.println(uid);

  // Verify with API
  verifyRFID(uid);

  rfid.PICC_HaltA();
  rfid.PCD_StopCrypto1();

  delay(1000); // Prevent multiple scans
}

void verifyRFID(String uid) {
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

  Serial.println("Sending request to API...");
  int httpResponseCode = http.POST(requestBody);

  if (httpResponseCode > 0) {
    String response = http.getString();
    Serial.print("Response code: ");
    Serial.println(httpResponseCode);
    Serial.print("Response: ");
    Serial.println(response);

    // Parse JSON response
    StaticJsonDocument<512> responseDoc;
    DeserializationError error = deserializeJson(responseDoc, response);

    if (error) {
      Serial.print("JSON parse error: ");
      Serial.println(error.c_str());
      return;
    }

    bool valid = responseDoc["valid"];
    const char* message = responseDoc["message"];
    int duration = responseDoc["duration"];

    Serial.print("Valid: ");
    Serial.println(valid ? "YES" : "NO");
    Serial.print("Message: ");
    Serial.println(message);

    if (valid) {
      // VALID RFID - Open gate
      const char* userName = responseDoc["user"]["name"];
      const char* vehiclePlate = responseDoc["user"]["vehicle_plate"];

      Serial.println("\n✓ ACCESS GRANTED");
      Serial.print("User: ");
      Serial.println(userName);
      Serial.print("Vehicle: ");
      Serial.println(vehiclePlate);

      openGate(duration);
    } else {
      // INVALID RFID - Keep gate closed
      Serial.println("\n✗ ACCESS DENIED");
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

void openGate(int duration) {
  Serial.print("Opening gate for ");
  Serial.print(duration);
  Serial.println(" seconds...");

  gateServo.write(90); // Open gate
  servoIsOpen = true;
  servoCloseTime = millis() + (duration * 1000); // Convert seconds to milliseconds
}

void closeGate() {
  Serial.println("Closing gate...");
  gateServo.write(0); // Close gate
  servoIsOpen = false;
}
