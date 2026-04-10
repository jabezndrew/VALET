<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GuestAccess;
use Carbon\Carbon;

class GuestAccessController extends Controller
{
    /**
     * GET /api/guest-access
     * List all guest passes (optionally filter by status or plate)
     */
    public function index(Request $request)
    {
        $query = GuestAccess::with('creator')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('plate')) {
            $plate = strtoupper(str_replace(' ', '', $request->plate));
            $query->where('vehicle_plate', $plate);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('vehicle_plate', 'like', "%{$search}%")
                  ->orWhere('guest_id', 'like', "%{$search}%");
            });
        }

        $passes = $query->get()->map(fn($g) => $this->format($g));

        return response()->json([
            'success' => true,
            'count'   => $passes->count(),
            'data'    => $passes,
        ]);
    }

    /**
     * POST /api/guest-access
     * Create a new guest pass
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:100',
            'vehicle_plate' => 'required|string|max:20',
            'phone'         => 'nullable|string|max:20',
            'purpose'       => 'required|string|max:255',
            'notes'         => 'nullable|string|max:500',
            'valid_hours'   => 'nullable|integer|min:1|max:720',
        ]);

        $plate    = strtoupper(str_replace(' ', '', $validated['vehicle_plate']));
        $hours    = $validated['valid_hours'] ?? 24;
        $guestId  = $this->generateGuestId();

        $pass = GuestAccess::create([
            'guest_id'      => $guestId,
            'name'          => $validated['name'],
            'vehicle_plate' => $plate,
            'phone'         => $validated['phone'] ?? null,
            'purpose'       => $validated['purpose'],
            'notes'         => $validated['notes'] ?? null,
            'valid_from'    => Carbon::now(),
            'valid_until'   => Carbon::now()->addHours($hours),
            'status'        => 'active',
            'created_by'    => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Guest pass created.',
            'data'    => $this->format($pass),
        ], 201);
    }

    /**
     * GET /api/guest-access/{id}
     * Show a single guest pass
     */
    public function show($id)
    {
        $pass = GuestAccess::with('creator')->find($id);

        if (!$pass) {
            return response()->json(['success' => false, 'message' => 'Guest pass not found.'], 404);
        }

        return response()->json(['success' => true, 'data' => $this->format($pass)]);
    }

    /**
     * PUT /api/guest-access/{id}
     * Update a guest pass
     */
    public function update(Request $request, $id)
    {
        $pass = GuestAccess::find($id);

        if (!$pass) {
            return response()->json(['success' => false, 'message' => 'Guest pass not found.'], 404);
        }

        $validated = $request->validate([
            'name'          => 'sometimes|string|max:100',
            'vehicle_plate' => 'sometimes|string|max:20',
            'phone'         => 'nullable|string|max:20',
            'purpose'       => 'sometimes|string|max:255',
            'notes'         => 'nullable|string|max:500',
            'status'        => 'sometimes|in:active,expired,cancelled,used',
            'valid_until'   => 'sometimes|date',
        ]);

        if (isset($validated['vehicle_plate'])) {
            $validated['vehicle_plate'] = strtoupper(str_replace(' ', '', $validated['vehicle_plate']));
        }

        $pass->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Guest pass updated.',
            'data'    => $this->format($pass->fresh()),
        ]);
    }

    /**
     * DELETE /api/guest-access/{id}
     * Delete a guest pass
     */
    public function destroy($id)
    {
        $pass = GuestAccess::find($id);

        if (!$pass) {
            return response()->json(['success' => false, 'message' => 'Guest pass not found.'], 404);
        }

        $pass->delete();

        return response()->json(['success' => true, 'message' => 'Guest pass deleted.']);
    }

    /**
     * GET /api/guest-access/verify/{plate}
     * Check if a plate has a currently valid guest pass
     */
    public function verifyByPlate($plate)
    {
        $plate = strtoupper(str_replace(' ', '', $plate));

        $pass = GuestAccess::where('vehicle_plate', $plate)
            ->where('status', 'active')
            ->where('valid_from', '<=', now())
            ->where('valid_until', '>=', now())
            ->latest()
            ->first();

        if (!$pass) {
            $expired = GuestAccess::where('vehicle_plate', $plate)->latest()->first();

            return response()->json([
                'valid'   => false,
                'message' => $expired ? 'No active guest pass. Last pass expired or cancelled.' : 'No guest pass found for this plate.',
                'plate'   => $plate,
            ]);
        }

        return response()->json([
            'valid'   => true,
            'message' => 'Active guest pass found.',
            'plate'   => $plate,
            'data'    => $this->format($pass),
        ]);
    }

    private function generateGuestId(): string
    {
        $year = date('Y');
        $last = GuestAccess::whereYear('created_at', $year)->orderBy('id', 'desc')->first();
        $seq  = $last ? ((int) substr($last->guest_id, -4)) + 1 : 1;
        return 'GUEST-' . $year . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    private function format(GuestAccess $g): array
    {
        return [
            'id'            => $g->id,
            'guest_id'      => $g->guest_id,
            'name'          => $g->name,
            'vehicle_plate' => $g->vehicle_plate,
            'phone'         => $g->phone,
            'purpose'       => $g->purpose,
            'notes'         => $g->notes,
            'status'        => $g->status,
            'valid_from'    => $g->valid_from?->toISOString(),
            'valid_until'   => $g->valid_until?->toISOString(),
            'created_by'    => $g->creator->name ?? null,
            'created_at'    => $g->created_at->toISOString(),
        ];
    }
}
