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

  // Configure watchdog timer (ESP32 has built-in WDT)
  // The default WDT timeout is ~5 seconds, we'll feed it regularly

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

  Serial.println("System ready. Waiting for RFID tags...\n");
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

  // Check if servo should be closed
  if (servoIsOpen && currentMillis >= servoCloseTime) {
    closeGate();
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

  // Check for new RFID card
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

  Serial.print("RFID Detected: ");
  Serial.println(uid);

  // Halt PICC before verification to prevent multiple reads
  rfid.PICC_HaltA();
  rfid.PCD_StopCrypto1();

  // Verify with API
  verifyRFID(uid);

  delay(1000); // Prevent multiple scans
}

void verifyRFID(String uid) {
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
      http.end();
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
