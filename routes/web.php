<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

Route::get('/', function () {
    return redirect('/dashboard');
});

Route::get('/dashboard', function () {
    try {
        // Ensure table exists
        DB::statement("CREATE TABLE IF NOT EXISTS parking_spaces (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sensor_id INT UNIQUE NOT NULL,
            is_occupied BOOLEAN NOT NULL DEFAULT FALSE,
            distance_cm INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB");
        
        $spaces = DB::table('parking_spaces')->orderBy('sensor_id')->get();
        $total = $spaces->count();
        $occupied = $spaces->where('is_occupied', true)->count();
        $available = $total - $occupied;
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <title>VALET Parking Dashboard</title>
            <meta name='viewport' content='width=device-width, initial-scale=1'>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
                .container { max-width: 1200px; margin: 0 auto; }
                .header { text-align: center; margin-bottom: 30px; }
                .stats { display: flex; gap: 20px; margin-bottom: 30px; justify-content: center; }
                .stat { background: rgba(255,255,255,0.1); padding: 20px; border-radius: 10px; text-align: center; }
                .stat-number { font-size: 2em; font-weight: bold; }
                .spaces { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
                .space { background: white; color: black; padding: 20px; border-radius: 10px; border-left: 6px solid; }
                .available { border-left-color: #28a745; }
                .occupied { border-left-color: #dc3545; }
                .badge { padding: 5px 15px; border-radius: 20px; color: white; font-weight: bold; }
                .badge-available { background: #28a745; }
                .badge-occupied { background: #dc3545; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🚗 VALET Smart Parking</h1>
                    <p>Real-time Cloud Dashboard</p>
                </div>
                
                <div class='stats'>
                    <div class='stat'>
                        <div class='stat-number' style='color: #28a745;'>$available</div>
                        <div>Available</div>
                    </div>
                    <div class='stat'>
                        <div class='stat-number' style='color: #dc3545;'>$occupied</div>
                        <div>Occupied</div>
                    </div>
                    <div class='stat'>
                        <div class='stat-number' style='color: #007bff;'>$total</div>
                        <div>Total</div>
                    </div>
                </div>
                
                <div class='spaces'>
        ";
        
        if ($spaces->count() > 0) {
            foreach ($spaces as $space) {
                $statusClass = $space->is_occupied ? 'occupied' : 'available';
                $statusText = $space->is_occupied ? 'OCCUPIED' : 'AVAILABLE';
                $badgeClass = $space->is_occupied ? 'badge-occupied' : 'badge-available';
                $icon = $space->is_occupied ? '🚗' : '✅';
                
                $html = "
                    <div class='space $statusClass'>
                        <div style='display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;'>
                            <h3>Sensor {$space->sensor_id}</h3>
                            <span class='badge $badgeClass'>$icon $statusText</span>
                        </div>
                        <div>
                            <p><strong>Distance:</strong> {$space->distance_cm} cm</p>
                            <p><strong>Last Updated:</strong> {$space->updated_at}</p>
                        </div>
                    </div>
                ";
            }
        } else {
            return $html . "
                    <div class='space available'>
                        <h3>No Sensors Detected</h3>
                        <p>Waiting for ESP32 to send data...</p>
                        <p><strong>API Status:</strong> ✅ Ready</p>
                        <p><strong>Database:</strong> ✅ Connected</p>
                    </div>
                </div>
            </div>
            
            <script>
                // Auto-refresh every 5 seconds
                setTimeout(() => {
                    window.location.reload();
                }, 5000);
            </script>
        </body>
        </html>";
        }
        
        return $html . "
                </div>
            </div>
            
            <script>
                // Auto-refresh every 5 seconds
                setTimeout(() => {
                    window.location.reload();
                }, 5000);
            </script>
        </body>
        </html>";
        
    } catch (\Exception $e) {
        return "Dashboard Error: " . $e->getMessage();
    }
});

Route::get('/api/dashboard-data', function () {
    try {
        $spaces = DB::table('parking_spaces')->orderBy('sensor_id')->get();
        return response()->json($spaces);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()]);
    }
});