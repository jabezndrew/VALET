#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>

const char* ssid = "Hushwa";
const char* password = "raveloravelo3";
const char* serverURL = "https://valet.up.railway.app/api/public/parking";
const char* assignmentURL = "https://valet.up.railway.app/api/public/sensor/assignment";
const char* registerURL = "https://valet.up.railway.app/api/public/sensor/register";
const char* FIRMWARE_VERSION = "v3.0.1-MULTI";
const int NUM_SENSORS = 5;

String macAddress = "";
bool isRegistered = false;

struct SensorAssignment {
    bool isAssigned = false;
    String spaceCode = "";
    bool identifyMode = false;
};
SensorAssignment sensorAssignments[NUM_SENSORS];

int trigPins[NUM_SENSORS]     = {5,  19, 22, 27, 32};
int echoPins[NUM_SENSORS]     = {18, 21, 23, 14, 33};
int redLedPins[NUM_SENSORS]   = {4,  17, 26, 12, 15};
int greenLedPins[NUM_SENSORS] = {2,  16, 25, 13,  0};

bool currentStatus[NUM_SENSORS]             = {false, false, false, false, false};
bool previousStatus[NUM_SENSORS]            = {false, false, false, false, false};
unsigned long lastStatusChange[NUM_SENSORS] = {0, 0, 0, 0, 0};

const unsigned long DEBOUNCE_DELAY              = 2000;
const unsigned long ASSIGNMENT_CHECK_INTERVAL   = 10000;
const unsigned long REGISTRATION_RETRY_INTERVAL = 10000;
unsigned long lastAssignmentCheck     = 0;
unsigned long lastRegistrationAttempt = 0;

void setup() {
    Serial.begin(9600);

    for (int i = 0; i < NUM_SENSORS; i++) {
        pinMode(redLedPins[i], OUTPUT);
        pinMode(greenLedPins[i], OUTPUT);
        pinMode(trigPins[i], OUTPUT);
        pinMode(echoPins[i], INPUT);
        digitalWrite(redLedPins[i], LOW);
        digitalWrite(greenLedPins[i], LOW);
    }

    WiFi.begin(ssid, password);
    Serial.print("Connecting to WiFi");
    while (WiFi.status() != WL_CONNECTED) {
        delay(500);
        Serial.print(".");
    }
    Serial.println();
    Serial.print("Connected! IP: ");
    Serial.println(WiFi.localIP());

    macAddress = WiFi.macAddress();
    Serial.println("=========================================");
    Serial.println("VALET SMART SENSOR SYSTEM v3.0.1");
    Serial.println("MULTI-SENSOR WITH AUTO-REGISTRATION");
    Serial.println("=========================================");
    Serial.print("MAC Address: ");
    Serial.println(macAddress);
    Serial.print("Firmware: ");
    Serial.println(FIRMWARE_VERSION);
    Serial.println("=========================================");

    registerDevice();
    checkAssignments();

    for (int i = 0; i < NUM_SENSORS; i++) {
        if (!sensorAssignments[i].isAssigned) {
            for (int j = 0; j < 3; j++) {
                digitalWrite(redLedPins[i], HIGH);
                delay(300);
                digitalWrite(redLedPins[i], LOW);
                delay(300);
            }
        } else {
            digitalWrite(greenLedPins[i], HIGH);
        }
    }
}

void loop() {
    if (!isRegistered) {
        if (millis() - lastRegistrationAttempt > REGISTRATION_RETRY_INTERVAL) {
            registerDevice();
            lastRegistrationAttempt = millis();
        }
        static unsigned long lastBlink = 0;
        static bool ledState = false;
        if (millis() - lastBlink > 500) {
            ledState = !ledState;
            for (int i = 0; i < NUM_SENSORS; i++) {
                digitalWrite(redLedPins[i],   ledState ? HIGH : LOW);
                digitalWrite(greenLedPins[i], LOW);
            }
            lastBlink = millis();
        }
        delay(100);
        return;
    }

    if (millis() - lastAssignmentCheck > ASSIGNMENT_CHECK_INTERVAL) {
        checkAssignments();
        lastAssignmentCheck = millis();
    }

    for (int i = 0; i < NUM_SENSORS; i++) {
        if (sensorAssignments[i].identifyMode) {
            handleIdentifyMode(i);
            continue;
        }
        if (!sensorAssignments[i].isAssigned) {
            static unsigned long lastBlink[NUM_SENSORS] = {0, 0, 0, 0, 0};
            static bool ledState[NUM_SENSORS] = {false, false, false, false, false};
            if (millis() - lastBlink[i] > 1000) {
                ledState[i] = !ledState[i];
                digitalWrite(redLedPins[i],   ledState[i] ? HIGH : LOW);
                digitalWrite(greenLedPins[i], LOW);
                lastBlink[i] = millis();
            }
        } else {
            long distance = readUltrasonicSensor(trigPins[i], echoPins[i]);
            processSensor(i, distance);
        }
        delay(20);
    }
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
        doc["mac_address"]      = macAddress;
        doc["num_sensors"]      = NUM_SENSORS;
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
                bool success       = responseDoc["success"] | false;
                const char* status = responseDoc["status"]  | "";
                if (success || strcmp(status,"registered")==0 || strcmp(status,"already_registered")==0) {
                    isRegistered = true;
                    Serial.println("✓ Device registered successfully!");
                } else {
                    Serial.println("✗ Registration failed. Will retry...");
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

            // buffer increased from 2048 to 8192 — response includes full parking_space objects
            DynamicJsonDocument responseDoc(8192);
            DeserializationError error = deserializeJson(responseDoc, response);

            if (!error) {
                const char* status = responseDoc["status"] | "";
                if (strcmp(status, "not_registered") == 0) {
                    Serial.println("Device not registered! Attempting registration...");
                    isRegistered = false;
                    registerDevice();
                    http.end();
                    return;
                }

                if (responseDoc.containsKey("sensors")) {
                    JsonArray sensors = responseDoc["sensors"];
                    for (JsonObject sensor : sensors) {
                        int sensorIndex = sensor["sensor_index"];
                        if (sensorIndex >= 1 && sensorIndex <= NUM_SENSORS) {
                            int idx = sensorIndex - 1;

                            sensorAssignments[idx].isAssigned   = sensor["is_assigned"]   | false;
                            sensorAssignments[idx].spaceCode    = sensor["space_code"]     | "";
                            sensorAssignments[idx].identifyMode = sensor["identify_mode"]  | false;
                            bool malfunctioned = sensor["malfunctioned"] | false;

                            if (malfunctioned) {
                                digitalWrite(redLedPins[idx],   HIGH);
                                digitalWrite(greenLedPins[idx], HIGH);
                            } else if (!sensorAssignments[idx].isAssigned) {
                                digitalWrite(redLedPins[idx],   LOW);
                                digitalWrite(greenLedPins[idx], LOW);
                            } else {
                                bool physicalOccupied = currentStatus[idx];
                                if (physicalOccupied) {
                                    digitalWrite(redLedPins[idx],   HIGH);
                                    digitalWrite(greenLedPins[idx], LOW);
                                } else {
                                    digitalWrite(redLedPins[idx],   LOW);
                                    digitalWrite(greenLedPins[idx], HIGH);
                                }
                                long distance = readUltrasonicSensor(trigPins[idx], echoPins[idx]);
                                sendToDatabase(idx, currentStatus[idx], distance);
                            }

                            Serial.print("  Sensor ");
                            Serial.print(sensorIndex);
                            Serial.print(": ");
                            if (sensorAssignments[idx].isAssigned) {
                                Serial.print("ASSIGNED to ");
                                Serial.println(sensorAssignments[idx].spaceCode);
                            } else {
                                Serial.println("UNASSIGNED");
                            }
                            if (sensorAssignments[idx].identifyMode)
                                Serial.println("    IDENTIFY MODE: ACTIVE");
                        }
                    }
                }
            } else {
                Serial.println("JSON parse error — buffer may still be too small");
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
        if (ledState[sensorIndex]) {
            digitalWrite(redLedPins[sensorIndex],   HIGH);
            digitalWrite(greenLedPins[sensorIndex], HIGH);
        } else {
            digitalWrite(redLedPins[sensorIndex],   LOW);
            digitalWrite(greenLedPins[sensorIndex], LOW);
        }
        lastBlink[sensorIndex] = millis();
    }
}

// 40ms timeout + 150us noise filter
long readUltrasonicSensor(int trigPin, int echoPin) {
    digitalWrite(trigPin, LOW);
    delayMicroseconds(4);
    digitalWrite(trigPin, HIGH);
    delayMicroseconds(10);
    digitalWrite(trigPin, LOW);
    long duration = pulseIn(echoPin, HIGH, 40000);
    if (duration == 0 || duration < 150) return 0;
    return duration * 0.034 / 2;
}

// threshold 12cm — floor reads 11-12 cm
void processSensor(int sensorIndex, long distance) {
    bool physicalStatus = (distance > 0 && distance <= 12);
    currentStatus[sensorIndex] = physicalStatus;

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
            previousStatus[sensorIndex]   = currentStatus[sensorIndex];
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
        doc["mac_address"]      = macAddress;
        doc["sensor_index"]     = sensorIndex + 1;
        doc["is_occupied"]      = isOccupied;
        doc["distance_cm"]      = distance;
        doc["firmware_version"] = FIRMWARE_VERSION;
        if (sensorAssignments[sensorIndex].isAssigned)
            doc["space_code"] = sensorAssignments[sensorIndex].spaceCode;

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

            StaticJsonDocument<512> responseDoc;
            DeserializationError error = deserializeJson(responseDoc, response);
            if (!error) {
                const char* status  = responseDoc["status"];
                bool serverOccupied = responseDoc["data"]["is_occupied"]  | currentStatus[sensorIndex];
                bool malfunctioned  = responseDoc["data"]["malfunctioned"] | false;

                if (malfunctioned) {
                    digitalWrite(redLedPins[sensorIndex],   HIGH);
                    digitalWrite(greenLedPins[sensorIndex], HIGH);
                } else if (serverOccupied) {
                    digitalWrite(redLedPins[sensorIndex],   HIGH);
                    digitalWrite(greenLedPins[sensorIndex], LOW);
                } else {
                    digitalWrite(redLedPins[sensorIndex],   LOW);
                    digitalWrite(greenLedPins[sensorIndex], HIGH);
                }

                if (status) {
                    if (strcmp(status,"assigned")==0 && !sensorAssignments[sensorIndex].isAssigned) {
                        Serial.println("✓ Sensor was assigned! Refreshing...");
                        checkAssignments();
                    } else if (strcmp(status,"unassigned")==0 && sensorAssignments[sensorIndex].isAssigned) {
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
