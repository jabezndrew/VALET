<?php

namespace App\Livewire;

use App\Models\GuardIncident;
use Livewire\Component;

class IncidentLog extends Component
{
    public $statusFilter   = 'all';
    public $categoryFilter = 'all';
    public $dateFilter     = '';

    public $showDeleteModal    = false;
    public $deletingIncidentId = null;

    public function mount(): void
    {
        abort_unless(
            in_array(auth()->user()->role, ['security', 'admin', 'ssd']),
            403
        );
    }

    public function canManage(): bool
    {
        return in_array(auth()->user()->role, ['admin', 'ssd']);
    }

    public function resolve(int $id): void
    {
        $incident = GuardIncident::find($id);
        if (!$incident) {
            return;
        }

        $incident->update([
            'status'      => 'resolved',
            'resolved_at' => now(),
            'resolved_by' => auth()->user()->name,
        ]);

        session()->flash('success', "Incident #{$id} marked as resolved.");
    }

    public function confirmDelete(int $id): void
    {
        if (!$this->canManage()) {
            return;
        }

        $this->deletingIncidentId = $id;
        $this->showDeleteModal    = true;
    }

    public function deleteIncident(): void
    {
        if (!$this->canManage() || !$this->deletingIncidentId) {
            return;
        }

        GuardIncident::find($this->deletingIncidentId)?->delete();

        session()->flash('success', 'Incident deleted.');
        $this->showDeleteModal    = false;
        $this->deletingIncidentId = null;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal    = false;
        $this->deletingIncidentId = null;
    }

    public function render()
    {
        $query = GuardIncident::query();

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->categoryFilter !== 'all') {
            $query->where('category', $this->categoryFilter);
        }

        if ($this->dateFilter) {
            $query->whereDate('created_at', $this->dateFilter);
        }

        $incidents = $query->orderBy('created_at', 'desc')->get();

        $stats = GuardIncident::selectRaw("
            COUNT(*) as total,
            COUNT(CASE WHEN status = 'open' THEN 1 END) as open,
            COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress,
            COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved
        ")->first();

        return view('livewire.incident-log', compact('incidents', 'stats'))
            ->layout('layouts.app');
    }
}
