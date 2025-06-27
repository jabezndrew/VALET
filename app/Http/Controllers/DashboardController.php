<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Show the parking dashboard
     */
    public function index()
    {
        try {
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
                'error' => 'Failed to load parking data'
            ]);
        }
    }

    /**
     * API endpoint for real-time dashboard updates
     */
    public function apiData()
    {
        try {
            $spaces = DB::table('parking_spaces')
                ->orderBy('sensor_id')
                ->get();

            return response()->json($spaces);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch data'], 500);
        }
    }
}