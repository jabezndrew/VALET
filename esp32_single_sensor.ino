#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>

const char* ssid = "PLDTHOMEFIBRgHN6U";
const char* password = "PLDTWIFIJz7TD";
const char* serverURL = "https://valetdevelop.up.railway.app/api/public/parking";
const char* assignmentURL = "https://valetdevelop.up.railway.app/api/public/sensor/assignment";
const char* registerURL = "https://valetdevelop.up.railway.app/api/public/sensor/register";
const char* FIRMWARE_VERSION = "v3.2.0-SINGLE";

// MAC address will be auto-detected
String macAddress = "";

// Sensor assignment (fetched from server)
bool isAssigned = false;
bool wasJustAssigned = false;  // Track if sensor was just assigned
String spaceCode = "";
bool identifyMode = false;

// Registration status
bool isRegistered = false;

// Pin configurations (SINGLE SENSOR)
const int TRIG_PIN = 5;
const int ECHO_PIN = 18;
const int RED_LED_PIN = 4;
const int GREEN_LED_PIN = 2;

// Status tracking
bool currentStatus = false;
bool previousStatus = false;
unsigned long lastStatusChange = 0;

const unsigned long DEBOUNCE_DELAY = 2000;
const unsigned long ASSIGNMENT_CHECK_INTERVAL = 5000;  // Check every 5 seconds (faster response)
const unsigned long REGISTRATION_RETRY_INTERVAL = 10000;
unsigned long lastAssignmentCheck = 0;
unsigned long lastRegistrationAttempt = 0;

// Animation timers
unsigned long assignmentBlinkStart = 0;
bool isBlinkingGreen = false;

void setup() {
    Serial.begin(9600);

    // Set pin modes
    pinMode(RED_LED_PIN, OUTPUT);
    pinMode(GREEN_LED_PIN, OUTPUT);
    pinMode(TRIG_PIN, OUTPUT);
    pinMode(ECHO_PIN, INPUT);

    // Initialize LEDs (start with both OFF during setup)
    digitalWrite(RED_LED_PIN, LOW);
    digitalWrite(GREEN_LED_PIN, LOW);

    // Connect to WiFi
    WiFi.begin(ssid, password);
    Serial.print("Connecting to WiFi");

    while (WiFi.status() != WL_CONNECTED) {
        delay(500);
        Serial.print(".");
    }

    Serial.println();
    Serial.print("Connected! IP: ");
    Serial.println(WiFi.localIP());

    // Get MAC address
    macAddress = WiFi.macAddress();
    Serial.println("=========================================");
    Serial.println("VALET SMART SENSOR SYSTEM v3.2.0");
    Serial.println("SINGLE SENSOR MODE - Fast Response");
    Serial.println("=========================================");
    Serial.print("MAC Address: ");
    Serial.println(macAddress);
    Serial.print("Firmware: ");
    Serial.println(FIRMWARE_VERSION);
    Serial.println("=========================================");

    // Try to register the device first
    registerDevice();

    // Check assignment from server
    checkAssignment();

    // Initialize LED based on assignment status
    if (!isAssigned) {
        // Blink red if unassigned
        for (int j = 0; j < 3; j++) {
            digitalWrite(RED_LED_PIN, HIGH);
            delay(300);
            digitalWrite(RED_LED_PIN, LOW);
            delay(300);
        }
    } else {
        // Set to green if assigned and ready
        digitalWrite(GREEN_LED_PIN, HIGH);
    }
}

void loop() {
    // If not registered, keep trying to register
    if (!isRegistered) {
        if (millis() - lastRegistrationAttempt > REGISTRATION_RETRY_INTERVAL) {
            registerDevice();
            lastRegistrationAttempt = millis();
        }

        // Blink red LED to indicate not registered
        static unsigned long lastBlink = 0;
        static bool ledState = false;

        if (millis() - lastBlink > 500) {
            ledState = !ledState;
            digitalWrite(RED_LED_PIN, ledState ? HIGH : LOW);
            digitalWrite(GREEN_LED_PIN, LOW);
            lastBlink = millis();
        }

        delay(100);
        return;
    }

    // Periodically check assignment status
    if (millis() - lastAssignmentCheck > ASSIGNMENT_CHECK_INTERVAL) {
        checkAssignment();
        lastAssignmentCheck = millis();
    }

    // Handle identify mode (yellow LED blinking) - HIGHEST PRIORITY
    if (identifyMode) {
        handleIdentifyMode();
        return;
    }

    // Handle green blink animation when newly assigned
    if (isBlinkingGreen) {
        handleAssignmentBlink();
        return;
    }

    // If not assigned, blink red LED slowly
    if (!isAssigned) {
        static unsigned long lastBlink = 0;
        static bool ledState = false;

        if (millis() - lastBlink > 1000) {
            ledState = !ledState;
            digitalWrite(RED_LED_PIN, ledState ? HIGH : LOW);
            digitalWrite(GREEN_LED_PIN, LOW);
            lastBlink = millis();
        }
    } else {
        // Process assigned sensor normally
        long distance = readUltrasonicSensor();
        processSensor(distance);
    }

    delay(100);
}

void registerDevice() {
    Serial.println("=========================================");
    Serial.println("Registering device with server...");

    if (WiFi.status() == WL_CONNECTED) {
        HTTPClient http;
        http.begin(registerURL);
        http.addHeader("Content-Type", "application/json");
        http.addHeader("Accept", "application/json");

        StaticJsonDocument<300> doc;
        doc["mac_address"] = macAddress;
        doc["firmware_version"] = FIRMWARE_VERSION;

        String jsonString;
        serializeJson(doc, jsonString);

        Serial.print("Sending registration: ");
        Serial.println(jsonString);

        int httpResponseCode = http.POST(jsonString);

        if (httpResponseCode > 0) {
            String response = http.getString();
            Serial.print("Registration response (");
            Serial.print(httpResponseCode);
            Serial.print("): ");
            Serial.println(response);

            DynamicJsonDocument responseDoc(1024);
            DeserializationError error = deserializeJson(responseDoc, response);

            if (!error) {
                bool success = responseDoc["success"] | false;

                if (success) {
                    isRegistered = true;
                    Serial.println("[OK] Device registered successfully!");
                } else {
                    Serial.println("[ERROR] Registration failed. Will retry...");
                }
            }
        } else {
            Serial.print("HTTP Error during registration: ");
            Serial.println(httpResponseCode);
        }

        http.end();
    } else {
        Serial.println("WiFi not connected!");
    }

    Serial.println("=========================================");
}

void checkAssignment() {
    Serial.println("Checking sensor assignment from server...");

    if (WiFi.status() == WL_CONNECTED) {
        HTTPClient http;
        http.begin(assignmentURL);
        http.addHeader("Content-Type", "application/json");

        StaticJsonDocument<200> doc;
        doc["mac_address"] = macAddress;

        String jsonString;
        serializeJson(doc, jsonString);

        int httpResponseCode = http.POST(jsonString);

        if (httpResponseCode > 0) {
            String response = http.getString();
            Serial.print("Assignment response (");
            Serial.print(httpResponseCode);
            Serial.print("): ");
            Serial.println(response);

            DynamicJsonDocument responseDoc(2048);
            DeserializationError error = deserializeJson(responseDoc, response);

            if (!error) {
                const char* status = responseDoc["status"] | "";

                if (strcmp(status, "not_registered") == 0) {
                    Serial.println("Device not registered! Attempting registration...");
                    isRegistered = false;
                    registerDevice();
                    return;
                }

                if (responseDoc.containsKey("sensors")) {
                    JsonArray sensors = responseDoc["sensors"];

                    // Look for sensor index 1 (single sensor mode)
                    for (JsonObject sensor : sensors) {
                        int sensorIndex = sensor["sensor_index"];

                        if (sensorIndex == 1) {
                            bool newIsAssigned = sensor["is_assigned"] | false;
                            String newSpaceCode = sensor["space_code"] | "";
                            bool newIdentifyMode = sensor["identify_mode"] | false;

                            // Detect if sensor was just assigned
                            if (!isAssigned && newIsAssigned) {
                                Serial.println("[OK] SENSOR JUST ASSIGNED! Starting green blink animation...");
                                isBlinkingGreen = true;
                                assignmentBlinkStart = millis();
                            }

                            isAssigned = newIsAssigned;
                            spaceCode = newSpaceCode;
                            identifyMode = newIdentifyMode;

                            Serial.print("Sensor 1: ");
                            if (isAssigned) {
                                Serial.print("ASSIGNED to ");
                                Serial.println(spaceCode);
                            } else {
                                Serial.println("UNASSIGNED");
                            }

                            if (identifyMode) {
                                Serial.println("  [!] IDENTIFY MODE: ACTIVE (Yellow LED blinking)");
                            }
                            break;
                        }
                    }
                }
            }
        } else {
            Serial.print("Error checking assignment: ");
            Serial.println(httpResponseCode);
        }

        http.end();
    }
}

void handleIdentifyMode() {
    static unsigned long lastBlink = 0;
    static bool ledState = false;

    if (millis() - lastBlink > 500) {
        ledState = !ledState;

        if (ledState) {
            // Yellow = Red + Green both ON
            digitalWrite(RED_LED_PIN, HIGH);
            digitalWrite(GREEN_LED_PIN, HIGH);
        } else {
            // OFF
            digitalWrite(RED_LED_PIN, LOW);
            digitalWrite(GREEN_LED_PIN, LOW);
        }

        lastBlink = millis();
    }
}

void handleAssignmentBlink() {
    // Blink green for 3 seconds when newly assigned
    const unsigned long BLINK_DURATION = 3000;
    static unsigned long lastBlink = 0;
    static bool ledState = false;

    // Check if animation should end
    if (millis() - assignmentBlinkStart > BLINK_DURATION) {
        isBlinkingGreen = false;
        digitalWrite(GREEN_LED_PIN, HIGH);  // Leave it on green
        digitalWrite(RED_LED_PIN, LOW);
        Serial.println("Green blink animation complete!");
        return;
    }

    // Fast green blink
    if (millis() - lastBlink > 200) {
        ledState = !ledState;
        digitalWrite(GREEN_LED_PIN, ledState ? HIGH : LOW);
        digitalWrite(RED_LED_PIN, LOW);
        lastBlink = millis();
    }
}

long readUltrasonicSensor() {
    digitalWrite(TRIG_PIN, LOW);
    delayMicroseconds(2);
    digitalWrite(TRIG_PIN, HIGH);
    delayMicroseconds(10);
    digitalWrite(TRIG_PIN, LOW);

    long duration = pulseIn(ECHO_PIN, HIGH);
    long distance = duration * 0.034 / 2;

    return distance;
}

void processSensor(long distance) {
    if (distance <= 9 && distance > 0) {
        currentStatus = true;
        digitalWrite(RED_LED_PIN, HIGH);
        digitalWrite(GREEN_LED_PIN, LOW);
    } else {
        currentStatus = false;
        digitalWrite(RED_LED_PIN, LOW);
        digitalWrite(GREEN_LED_PIN, HIGH);
    }

    if (currentStatus != previousStatus) {
        if (millis() - lastStatusChange > DEBOUNCE_DELAY) {
            Serial.print("* Sensor STATUS CHANGED: ");
            Serial.print(currentStatus ? "OCCUPIED" : "AVAILABLE");
            Serial.print(" (");
            Serial.print(distance);
            Serial.println(" cm) - Sending to CLOUD! *");

            sendToDatabase(currentStatus, distance);

            previousStatus = currentStatus;
            lastStatusChange = millis();
        }
    }
}

void sendToDatabase(bool isOccupied, int distance) {
    if (WiFi.status() == WL_CONNECTED) {
        HTTPClient http;
        http.begin(serverURL);
        http.addHeader("Content-Type", "application/json");
        http.addHeader("Accept", "application/json");

        StaticJsonDocument<300> doc;
        doc["mac_address"] = macAddress;
        doc["sensor_index"] = 1;  // Always sensor 1 in single sensor mode
        doc["is_occupied"] = isOccupied;
        doc["distance_cm"] = distance;
        doc["firmware_version"] = FIRMWARE_VERSION;

        // Include space_code if assigned
        if (isAssigned) {
            doc["space_code"] = spaceCode;
        }

        String jsonString;
        serializeJson(doc, jsonString);

        Serial.print("Sending to CLOUD: ");
        Serial.println(jsonString);

        int httpResponseCode = http.POST(jsonString);

        if (httpResponseCode > 0) {
            String response = http.getString();
            Serial.print("CLOUD response (");
            Serial.print(httpResponseCode);
            Serial.print("): ");
            Serial.println(response);

            // Parse response to check assignment status
            StaticJsonDocument<512> responseDoc;
            DeserializationError error = deserializeJson(responseDoc, response);
            if (!error) {
                const char* status = responseDoc["status"];

                // If status changed, refresh assignment
                if (status) {
                    if (strcmp(status, "assigned") == 0 && !isAssigned) {
                        Serial.println("[OK] Sensor was assigned! Refreshing...");
                        checkAssignment();
                    } else if (strcmp(status, "unassigned") == 0 && isAssigned) {
                        Serial.println("[WARNING] Sensor was unassigned! Refreshing...");
                        checkAssignment();
                    }
                }
            }
        } else {
            Serial.print("Error sending to CLOUD: ");
            Serial.println(httpResponseCode);
        }

        http.end();
    } else {
        Serial.println("WiFi not connected!");
    }
}
