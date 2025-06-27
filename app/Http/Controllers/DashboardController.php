<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Ensure table exists
     */
    private function ensureTableExists()
    {
        DB::statement("CREATE TABLE IF NOT EXISTS parking_spaces (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sensor_id INT UNIQUE NOT NULL,
            is_occupied BOOLEAN NOT NULL DEFAULT FALSE,
            distance_cm INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB");
    }

    /**
     * Show the parking dashboard
     */
    public function index()
    {
        try {
            $this->ensureTableExists();
            
            // Get initial data for the dashboard
            $spaces = DB::table('parking_spaces')
                ->orderBy('sensor_id')
                ->get();

            $stats = [
                'total' => $spaces->count(),
                'occupied' => $spaces->where('is_occupied', true)->count(),
                'available' => $spaces->where('is_occupied', false)->count()
            ];

            return view('dashboard.index', compact('spaces', 'stats'));
        } catch (\Exception $e) {
            return view('dashboard.index', [
                'spaces' => collect(),
                'stats' => ['total' => 0, 'occupied' => 0, 'available' => 0],
                'error' => 'Failed to load parking data: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * API endpoint for real-time dashboard updates
     */
    public function apiData()
    {
        try {
            $this->ensureTableExists();
            
            $spaces = DB::table('parking_spaces')
                ->orderBy('sensor_id')
                ->get();

            return response()->json($spaces);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch data: ' . $e->getMessage()], 500);
        }
    }
}