<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\RfidTag;
use App\Models\SysUser;
use App\Models\Vehicle;
use Carbon\Carbon;

class RfidManagement extends Component
{
    use WithPagination;

    public $showModal = false;
    public $editMode = false;
    public $selectedTag;

    // Form fields
    public $uid = '';
    public $user_id = '';
    public $vehicle_id = '';
    public $status = 'active';
    public $expiry_date = '';
    public $notes = '';

    // Filters
    public $filterStatus = '';
    public $searchUid = '';

    protected $rules = [
        'uid' => 'required|string|unique:rfid_tags,uid',
        'user_id' => 'required|exists:sys_users,id',
        'vehicle_id' => 'nullable|exists:vehicles,id',
        'status' => 'required|in:active,expired,suspended,lost',
        'expiry_date' => 'nullable|date',
        'notes' => 'nullable|string|max:500'
    ];

    public function openCreateModal()
    {
        $this->resetForm();
        $this->editMode = false;
        $this->showModal = true;
    }

    public function openEditModal($tagId)
    {
        $tag = RfidTag::findOrFail($tagId);

        $this->selectedTag = $tag;
        $this->uid = $tag->uid;
        $this->user_id = $tag->user_id;
        $this->vehicle_id = $tag->vehicle_id;
        $this->status = $tag->status;
        $this->expiry_date = $tag->expiry_date ? $tag->expiry_date->format('Y-m-d') : '';
        $this->notes = $tag->notes;

        $this->editMode = true;
        $this->showModal = true;
    }

    public function save()
    {
        if ($this->editMode) {
            $this->validate(array_merge($this->rules, [
                'uid' => 'required|string|unique:rfid_tags,uid,' . $this->selectedTag->id
            ]));

            $this->selectedTag->update([
                'uid' => strtoupper($this->uid),
                'user_id' => $this->user_id,
                'vehicle_id' => $this->vehicle_id,
                'status' => $this->status,
                'expiry_date' => $this->expiry_date ?: null,
                'notes' => $this->notes
            ]);

            session()->flash('success', 'RFID tag updated successfully');
        } else {
            $this->validate();

            RfidTag::create([
                'uid' => strtoupper($this->uid),
                'user_id' => $this->user_id,
                'vehicle_id' => $this->vehicle_id,
                'status' => $this->status,
                'expiry_date' => $this->expiry_date ?: null,
                'notes' => $this->notes
            ]);

            session()->flash('success', 'RFID tag registered successfully');
        }

        $this->closeModal();
    }

    public function delete($tagId)
    {
        $tag = RfidTag::findOrFail($tagId);
        $tag->delete();

        session()->flash('success', 'RFID tag deleted successfully');
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->uid = '';
        $this->user_id = '';
        $this->vehicle_id = '';
        $this->status = 'active';
        $this->expiry_date = '';
        $this->notes = '';
        $this->resetValidation();
    }

    public function render()
    {
        $query = RfidTag::with(['user', 'vehicle']);

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        if ($this->searchUid) {
            $query->where('uid', 'like', '%' . $this->searchUid . '%');
        }

        $tags = $query->latest()->paginate(15);
        $users = SysUser::orderBy('name')->get();
        $vehicles = Vehicle::with('user')->orderBy('plate_number')->get();

        return view('livewire.rfid-management', [
            'tags' => $tags,
            'users' => $users,
            'vehicles' => $vehicles
        ]);
    }
}
