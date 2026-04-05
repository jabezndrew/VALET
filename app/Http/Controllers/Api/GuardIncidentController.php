<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GuardIncident;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuardIncidentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (!$request->user()->tokenCan('incidents:view')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $query = GuardIncident::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('floor_level')) {
            $query->where('floor_level', $request->floor_level);
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $incidents = $query->orderBy('created_at', 'desc')
            ->paginate($request->integer('per_page', 20));

        return response()->json([
            'success' => true,
            'data'    => $incidents->items(),
            'meta'    => [
                'total'        => $incidents->total(),
                'per_page'     => $incidents->perPage(),
                'current_page' => $incidents->currentPage(),
                'last_page'    => $incidents->lastPage(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if (!$request->user()->tokenCan('incidents:create')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'space_code'     => 'nullable|string|max:20',
            'floor_level'    => 'required|string|max:50',
            'incident_at'    => 'nullable|date',
            'category'       => 'required|in:debris,damaged,blocked,light_issue,sensor_issue,other',
            'notes'          => 'nullable|string|max:1000',
            'involved_party' => 'nullable|string|max:255',
            'action_taken'   => 'nullable|string|max:500',
        ]);

        $incident = GuardIncident::create([
            ...$data,
            'status'      => 'open',
            'reported_by' => $request->user()->name,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Incident reported successfully.',
            'data'    => $incident,
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        if (!$request->user()->tokenCan('incidents:view')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $incident = GuardIncident::find($id);

        if (!$incident) {
            return response()->json(['success' => false, 'message' => 'Incident not found.'], 404);
        }

        return response()->json(['success' => true, 'data' => $incident]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        if (!$request->user()->tokenCan('incidents:manage')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $incident = GuardIncident::find($id);

        if (!$incident) {
            return response()->json(['success' => false, 'message' => 'Incident not found.'], 404);
        }

        $data = $request->validate([
            'status'       => 'sometimes|in:open,in_progress,resolved',
            'action_taken' => 'sometimes|nullable|string|max:500',
            'notes'        => 'sometimes|nullable|string|max:1000',
        ]);

        if (isset($data['status']) && $data['status'] === 'resolved' && $incident->status !== 'resolved') {
            $data['resolved_at'] = now();
            $data['resolved_by'] = $request->user()->name;
        }

        $incident->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Incident updated.',
            'data'    => $incident->fresh(),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        if (!$request->user()->tokenCan('incidents:manage')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $incident = GuardIncident::find($id);

        if (!$incident) {
            return response()->json(['success' => false, 'message' => 'Incident not found.'], 404);
        }

        $incident->delete();

        return response()->json(['success' => true, 'message' => 'Incident deleted.']);
    }
}
