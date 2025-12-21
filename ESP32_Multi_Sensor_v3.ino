#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>

const char* ssid = "rxtn";
const char* password = "rxtn7536";
const char* serverURL = "https://valet.up.railway.app/api/public/parking";
const char* assignmentURL = "https://valet.up.railway.app/api/public/sensor/assignment";
const char* FIRMWARE_VERSION = "v3.0.0-MULTI";
const int NUM_SENSORS = 5;

// MAC address will be auto-detected
String macAddress = "";

// Sensor assignments (fetched from server)
struct SensorAssignment {
    bool isAssigned = false;
    String spaceCode = "";
    bool identifyMode = false;
};
SensorAssignment sensorAssignments[NUM_SENSORS];

// Pin configurations
int trigPins[NUM_SENSORS] = {5, 19, 22, 27, 32};
int echoPins[NUM_SENSORS] = {18, 21, 23, 14, 33};
int redLedPins[NUM_SENSORS] = {4, 17, 26, 12, 15};
int greenLedPins[NUM_SENSORS] = {2, 16, 25, 13, 0};

// Blue LED pins for identify feature (optional)
int blueLedPins[NUM_SENSORS] = {-1, -1, -1, -1, -1};

// Status tracking
bool currentStatus[NUM_SENSORS] = {false, false, false, false, false};
bool previousStatus[NUM_SENSORS] = {false, false, false, false, false};
unsigned long lastStatusChange[NUM_SENSORS] = {0, 0, 0, 0, 0};

const unsigned long DEBOUNCE_DELAY = 2000;
const unsigned long ASSIGNMENT_CHECK_INTERVAL = 30000;
unsigned long lastAssignmentCheck = 0;

void setup() {
    Serial.begin(9600);

    // Set pin modes for all sensors and LEDs
    for (int i = 0; i < NUM_SENSORS; i++) {
        pinMode(redLedPins[i], OUTPUT);
        pinMode(greenLedPins[i], OUTPUT);
        pinMode(trigPins[i], OUTPUT);
        pinMode(echoPins[i], INPUT);

        if (blueLedPins[i] != -1) {
            pinMode(blueLedPins[i], OUTPUT);
            digitalWrite(blueLedPins[i], LOW);
        }

        // Initialize LEDs (start with both OFF during setup)
        digitalWrite(redLedPins[i], LOW);
        digitalWrite(greenLedPins[i], LOW);
    }

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
    Serial.println("VALET SMART SENSOR SYSTEM v3.0");
    Serial.println("MULTI-SENSOR WITH IDENTIFY FEATURE");
    Serial.println("=========================================");
    Serial.print("MAC Address: ");
    Serial.println(macAddress);
    Serial.print("Firmware: ");
    Serial.println(FIRMWARE_VERSION);
    Serial.println("=========================================");

    // Check assignments from server
    checkAssignments();

    // Initialize LEDs based on assignment status
    for (int i = 0; i < NUM_SENSORS; i++) {
        if (!sensorAssignments[i].isAssigned) {
            // Blink red if unassigned
            for (int j = 0; j < 3; j++) {
                digitalWrite(redLedPins[i], HIGH);
                delay(300);
                digitalWrite(redLedPins[i], LOW);
                delay(300);
            }
        } else {
            // Set to green if assigned and ready
            digitalWrite(greenLedPins[i], HIGH);
        }
    }
}

void loop() {
    // Periodically check assignment status
    if (millis() - lastAssignmentCheck > ASSIGNMENT_CHECK_INTERVAL) {
        checkAssignments();
        lastAssignmentCheck = millis();
    }

    // Process each sensor
    for (int i = 0; i < NUM_SENSORS; i++) {
        // Handle identify mode (blue LED blinking)
        if (sensorAssignments[i].identifyMode) {
            handleIdentifyMode(i);
            continue;
        }

        // If not assigned, blink red LED slowly
        if (!sensorAssignments[i].isAssigned) {
            static unsigned long lastBlink[NUM_SENSORS] = {0, 0, 0, 0, 0};
            static bool ledState[NUM_SENSORS] = {false, false, false, false, false};

            if (millis() - lastBlink[i] > 1000) {
                ledState[i] = !ledState[i];
                digitalWrite(redLedPins[i], ledState[i] ? HIGH : LOW);
                lastBlink[i] = millis();
            }
        } else {
            // Process assigned sensor normally
            long distance = readUltrasonicSensor(trigPins[i], echoPins[i]);
            processSensor(i, distance);
        }

        delay(20);
    }
}

void checkAssignments() {
    Serial.println("Checking sensor assignments from server...");

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

            if (!error && responseDoc.containsKey("sensors")) {
                JsonArray sensors = responseDoc["sensors"];

                // If sensors array is empty, it means not registered yet
                if (sensors.size() == 0) {
                    Serial.println("  No sensors registered yet. Send data to register.");
                    return;
                }

                // Process each sensor assignment
                for (JsonObject sensor : sensors) {
                    int sensorIndex = sensor["sensor_index"];

                    if (sensorIndex >= 1 && sensorIndex <= NUM_SENSORS) {
                        int idx = sensorIndex - 1;

                        sensorAssignments[idx].isAssigned = sensor["is_assigned"] | false;
                        sensorAssignments[idx].spaceCode = sensor["space_code"] | "";
                        sensorAssignments[idx].identifyMode = sensor["identify_mode"] | false;

                        Serial.print("  Sensor ");
                        Serial.print(sensorIndex);
                        Serial.print(": ");

                        if (sensorAssignments[idx].isAssigned) {
                            Serial.print("ASSIGNED to ");
                            Serial.println(sensorAssignments[idx].spaceCode);
                        } else {
                            Serial.println("UNASSIGNED");
                        }

                        if (sensorAssignments[idx].identifyMode) {
                            Serial.println("    IDENTIFY MODE: ACTIVE");
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

void handleIdentifyMode(int sensorIndex) {
    static unsigned long lastBlink[NUM_SENSORS] = {0, 0, 0, 0, 0};
    static bool ledState[NUM_SENSORS] = {false, false, false, false, false};

    if (millis() - lastBlink[sensorIndex] > 500) {
        ledState[sensorIndex] = !ledState[sensorIndex];

        if (blueLedPins[sensorIndex] != -1) {
            digitalWrite(blueLedPins[sensorIndex], ledState[sensorIndex] ? HIGH : LOW);
            digitalWrite(redLedPins[sensorIndex], LOW);
            digitalWrite(greenLedPins[sensorIndex], LOW);
        } else {
            // Alternate red and green for identify effect
            if (ledState[sensorIndex]) {
                digitalWrite(redLedPins[sensorIndex], HIGH);
                digitalWrite(greenLedPins[sensorIndex], LOW);
            } else {
                digitalWrite(redLedPins[sensorIndex], LOW);
                digitalWrite(greenLedPins[sensorIndex], HIGH);
            }
        }

        lastBlink[sensorIndex] = millis();
    }
}

long readUltrasonicSensor(int trigPin, int echoPin) {
    digitalWrite(trigPin, LOW);
    delayMicroseconds(2);
    digitalWrite(trigPin, HIGH);
    delayMicroseconds(10);
    digitalWrite(trigPin, LOW);

    long duration = pulseIn(echoPin, HIGH);
    long distance = duration * 0.034 / 2;

    return distance;
}

void processSensor(int sensorIndex, long distance) {
    if (distance <= 9 && distance > 0) {
        currentStatus[sensorIndex] = true;
        digitalWrite(redLedPins[sensorIndex], HIGH);
        digitalWrite(greenLedPins[sensorIndex], LOW);
    } else {
        currentStatus[sensorIndex] = false;
        digitalWrite(redLedPins[sensorIndex], LOW);
        digitalWrite(greenLedPins[sensorIndex], HIGH);
    }

    if (currentStatus[sensorIndex] != previousStatus[sensorIndex]) {
        if (millis() - lastStatusChange[sensorIndex] > DEBOUNCE_DELAY) {

            Serial.print("* Sensor ");
            Serial.print(sensorIndex + 1);
            Serial.print(" (");
            Serial.print(sensorAssignments[sensorIndex].spaceCode);
            Serial.print(") STATUS CHANGED: ");
            Serial.print(currentStatus[sensorIndex] ? "OCCUPIED" : "AVAILABLE");
            Serial.print(" (");
            Serial.print(distance);
            Serial.println(" cm) - Sending to CLOUD! *");

            sendToDatabase(sensorIndex, currentStatus[sensorIndex], distance);

            previousStatus[sensorIndex] = currentStatus[sensorIndex];
            lastStatusChange[sensorIndex] = millis();
        }
    }
}

void sendToDatabase(int sensorIndex, bool isOccupied, int distance) {
    if (WiFi.status() == WL_CONNECTED) {
        HTTPClient http;
        http.begin(serverURL);
        http.addHeader("Content-Type", "application/json");
        http.addHeader("Accept", "application/json");

        StaticJsonDocument<300> doc;
        doc["mac_address"] = macAddress;
        doc["sensor_index"] = sensorIndex + 1;
        doc["is_occupied"] = isOccupied;
        doc["distance_cm"] = distance;
        doc["firmware_version"] = FIRMWARE_VERSION;

        // Include space_code if assigned
        if (sensorAssignments[sensorIndex].isAssigned) {
            doc["space_code"] = sensorAssignments[sensorIndex].spaceCode;
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

                // If status changed, refresh assignments
                if (status) {
                    if (strcmp(status, "assigned") == 0 && !sensorAssignments[sensorIndex].isAssigned) {
                        Serial.println("✓ Sensor was assigned! Refreshing...");
                        checkAssignments();
                    } else if (strcmp(status, "unassigned") == 0 && sensorAssignments[sensorIndex].isAssigned) {
                        Serial.println("⚠ Sensor was unassigned! Refreshing...");
                        checkAssignments();
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

/*
=================================
VALET SMART SENSOR SYSTEM v3.0
MULTI-SENSOR WITH IDENTIFY FEATURE
=================================

CHANGELOG v3.0:
✅ Fixed: Parking spaces now populate automatically when sensors send data
✅ Improved: Better assignment status detection in API responses
✅ Added: Automatic assignment refresh when status changes
✅ Removed: space_code from unassigned sensor payloads (server generates temp codes)

Features:
✅ Auto-detects MAC address on boot
✅ Supports 5 sensors on one ESP32
✅ Individual sensor assignments per sensor index
✅ Fetches assignments from cloud API
✅ Visual LED feedback for assignment status
✅ Periodic assignment checks (every 30 seconds)
✅ IDENTIFY MODE - Blue LED blinking controlled from web interface
✅ Handles reassignment without reflashing
✅ Firmware version tracking
✅ Parking spaces auto-populate in database

LED Behavior:
- Slow Blinking Red: Sensor unassigned (waiting for assignment)
- Solid Green: Sensor assigned and space available
- Solid Red: Sensor assigned and space occupied
- Fast Blinking Blue/Alternating: IDENTIFY MODE active

API Endpoints Used:
1. POST /api/public/sensor/assignment - Check/fetch assignments for all 5 sensors
2. POST /api/public/parking - Send sensor data (creates parking_spaces automatically)
3. POST /api/sensors/identify/start - Start identify mode (from web UI)
4. POST /api/sensors/identify/stop - Stop identify mode (from web UI)

Pin Configuration:
Sensor 1: Trig=5, Echo=18, Red=4, Green=2
Sensor 2: Trig=19, Echo=21, Red=17, Green=16
Sensor 3: Trig=22, Echo=23, Red=26, Green=25
Sensor 4: Trig=27, Echo=14, Red=12, Green=13
Sensor 5: Trig=32, Echo=33, Red=15, Green=0

Blue LED (Optional - for identify feature):
If you have blue LEDs, update blueLedPins array with actual pin numbers.
If not, the system will alternate red/green LEDs for identify mode.

Usage:
1. Flash this code to ESP32
2. ESP32 will auto-register all 5 sensors with temporary space codes (e.g., TEEFF1)
3. Parking spaces will be created automatically in the database
4. Assign sensors via web interface to real parking spaces (e.g., 4B1, 3A2)
5. Use "Identify" button in web UI to blink blue LED and find which sensor is which
6. Stop identify mode via web UI when done
7. To reassign, just update via web - no reflashing needed!

What's Different in v3.0:
- Server now handles unassigned sensors properly
- parking_spaces table gets populated immediately (with temp codes like "TEEFF1")
- When you assign sensors via web, temp code gets replaced with real code (e.g., "4B1")
- ESP32 automatically detects assignment changes and refreshes
- No need to manually create parking spaces - everything is automatic!
*/
