<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ParkingSpace;

class GetRouteCoordinates extends Command
{
    protected $signature = 'parking:route-coords';
    protected $description = 'Get coordinates for route planning';

    public function handle()
    {
        $this->info('Getting coordinates for Entrance -> A -> E -> J route...');
        $this->newLine();

        // Get Section A coordinates
        $this->info('Section A (1st Floor):');
        $sectionA = ParkingSpace::where('column_code', 'A')
            ->where('floor_number', 1)
            ->get(['slot_name', 'x_position', 'y_position']);

        foreach ($sectionA as $space) {
            $this->line("  {$space->slot_name}: x={$space->x_position}, y={$space->y_position}");
        }

        $this->newLine();
        $this->info('Section E (1st Floor):');
        $sectionE = ParkingSpace::where('column_code', 'E')
            ->where('floor_number', 1)
            ->get(['slot_name', 'x_position', 'y_position']);

        foreach ($sectionE as $space) {
            $this->line("  {$space->slot_name}: x={$space->x_position}, y={$space->y_position}");
        }

        $this->newLine();
        $this->info('Section J (1st Floor):');
        $sectionJ = ParkingSpace::where('column_code', 'J')
            ->where('floor_number', 1)
            ->get(['slot_name', 'x_position', 'y_position']);

        foreach ($sectionJ as $space) {
            $this->line("  {$space->slot_name}: x={$space->x_position}, y={$space->y_position}");
        }

        $this->newLine();
        $this->info('Entrance location (from blade): right: 135px, top: 390px');
        $this->info('(Convert to left position: assuming 1200px width, left ≈ 1065px)');

        return 0;
    }
}
