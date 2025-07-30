<!-- resources/views/livewire/pending-account-manager.blade.php -->
<div>
   <!-- Alert container for dynamic alerts -->
   <div id="alert-container"></div>

   <div class="container mt-4">
       <!-- Header -->
       <div class="d-flex justify-content-between align-items-center mb-4">
           <div>
               <h2 class="fw-bold mb-1">
                   Pending Account Approvals
               </h2>
               <p class="text-muted mb-0">Review and approve accounts created by SSD personnel</p>
           </div>
           <a href="{{ route('admin.users') }}" class="btn btn-outline-secondary" wire:navigate>
               <i class="fas fa-users me-2"></i>Back to Users
           </a>
       </div>

       <!-- Stats -->
       <div class="row mb-4">
           <div class="col-md-3">
               <div class="card card-total">
                   <div class="card-body text-center">
                       <h3>{{ $stats['total'] }}</h3>
                       <p class="mb-0">Total Requests</p>
                   </div>
               </div>
           </div>
           <div class="col-md-3">
               <div class="card card-types">
                   <div class="card-body text-center">
                       <h3>{{ $stats['pending'] }}</h3>
                       <p class="mb-0">Pending</p>
                   </div>
               </div>
           </div>
           <div class="col-md-3">
               <div class="card card-active">
                   <div class="card-body text-center">
                       <h3>{{ $stats['approved'] }}</h3>
                       <p class="mb-0">Approved</p>
                   </div>
               </div>
           </div>
           <div class="col-md-3">
               <div class="card card-inactive">
                   <div class="card-body text-center">
                       <h3>{{ $stats['rejected'] }}</h3>
                       <p class="mb-0">Rejected</p>
                   </div>
               </div>
           </div>
       </div>

       <!-- Filters -->
       <div class="card mb-4">
           <div class="card-body">
               <div class="row">
                   <div class="col-md-3">
                       <select wire:model.live="statusFilter" class="form-select">
                           <option value="all">All Status</option>
                           <option value="pending">Pending</option>
                           <option value="approved">Approved</option>
                           <option value="rejected">Rejected</option>
                       </select>
                   </div>
               </div>
           </div>
       </div>

       <!-- Pending Accounts List -->
       <div class="card">
           <div class="card-body p-0">
               <div class="table-responsive">
                   <table class="table table-hover mb-0">
                       <thead class="bg-light">
                           <tr>
                               <th>Requested User</th>
                               <th>Role</th>
                               <th>User ID</th>
                               <th>Category</th>
                               <th>Created By</th>
                               <th>Status</th>
                               <th>Requested</th>
                               <th>Actions</th>
                           </tr>
                       </thead>
                       <tbody>
                           @forelse($pendingAccounts as $account)
                               <tr class="{{ $account->status === 'rejected' ? 'table-danger' : ($account->status === 'approved' ? 'table-success' : '') }}">
                                   <td>
                                       <div>
                                           <strong>{{ $account->name }}</strong>
                                           <br>
                                           <small class="text-muted">{{ $account->email }}</small>
                                       </div>
                                   </td>
                                   <td>
                                       <span class="badge 
                                           @switch($account->role)
                                               @case('admin') bg-danger @break
                                               @case('ssd') @break
                                               @case('security') bg-warning @break
                                               @default
                                           @endswitch
                                       " style="
                                           @switch($account->role)
                                               @case('ssd') background-color: #3A3A3C; color: white; @break
                                               @default background-color: #A0A0A0; color: white;
                                           @endswitch
                                       ">
                                           {{ ucfirst($account->role) }}
                                       </span>
                                   </td>
                                   <td class="font-monospace">{{ $account->employee_id ?: '-' }}</td>
                                   <td>{{ $account->department ?: '-' }}</td>
                                   <td>
                                       {{ $account->created_by_name }}
                                       <br>
                                       <small class="badge bg-primary">{{ ucfirst($account->created_by_role) }}</small>
                                   </td>
                                   <td>
                                       <span class="badge 
                                           @switch($account->status)
                                               @case('pending') bg-warning @break
                                               @case('approved') bg-success @break
                                               @case('rejected') bg-danger @break
                                           @endswitch
                                       ">
                                           {{ ucfirst($account->status) }}
                                       </span>
                                   </td>
                                   <td>
                                       <small class="text-muted">
                                           {{ \Carbon\Carbon::parse($account->created_at)->format('M j, Y') }}
                                       </small>
                                   </td>
                                   <td>
                                       <div class="btn-group btn-group-sm">
                                           <button wire:click="viewAccount({{ $account->id }})" 
                                                   class="btn btn-outline-secondary">
                                               <i class="fas fa-eye"></i>
                                           </button>
                                           <button wire:click="deleteAccount({{ $account->id }})" 
                                                   wire:confirm="Are you sure you want to delete this request?"
                                                   class="btn btn-outline-danger">
                                               <i class="fas fa-trash"></i>
                                           </button>
                                       </div>
                                   </td>
                               </tr>
                           @empty
                               <tr>
                                   <td colspan="8" class="text-center py-5">
                                       <i class="fas fa-user-clock text-muted mb-3" style="font-size: 3rem; opacity: 0.3;"></i>
                                       <h5 class="text-muted">No pending accounts</h5>
                                       <p class="text-muted">All account requests have been processed</p>
                                   </td>
                               </tr>
                           @endforelse
                       </tbody>
                   </table>
               </div>
           </div>
       </div>
   </div>

   <!-- Account Details Modal -->
   @if($showModal && $selectedAccount)
   <div class="modal fade show" style="display: block;" tabindex="-1">
       <div class="modal-dialog modal-lg">
           <div class="modal-content">
               <div class="modal-header">
                   <h5 class="modal-title">
                       Account Request Details
                   </h5>
                   <button type="button" class="btn-close" wire:click="closeModal"></button>
               </div>
               <div class="modal-body">
                   <div class="row">
                       <div class="col-md-6">
                           <h6 class="fw-bold">User Information</h6>
                           <table class="table table-sm">
                               <tr>
                                   <td><strong>Name:</strong></td>
                                   <td>{{ $selectedAccount->name }}</td>
                               </tr>
                               <tr>
                                   <td><strong>Email:</strong></td>
                                   <td>{{ $selectedAccount->email }}</td>
                               </tr>
                               <tr>
                                   <td><strong>Role:</strong></td>
                                   <td>
                                       <span class="badge bg-secondary">{{ ucfirst($selectedAccount->role) }}</span>
                                   </td>
                               </tr>
                               <tr>
                                   <td><strong>User ID:</strong></td>
                                   <td>{{ $selectedAccount->employee_id ?: '-' }}</td>
                               </tr>
                               <tr>
                                   <td><strong>Category:</strong></td>
                                   <td>{{ $selectedAccount->department ?: '-' }}</td>
                               </tr>
                               <tr>
                                   <td><strong>Status:</strong></td>
                                   <td>
                                       <span class="badge 
                                           @switch($selectedAccount->status)
                                               @case('pending') bg-warning @break
                                               @case('approved') bg-success @break
                                               @case('rejected') bg-danger @break
                                           @endswitch
                                       ">
                                           {{ ucfirst($selectedAccount->status) }}
                                       </span>
                                   </td>
                               </tr>
                           </table>
                       </div>
                       <div class="col-md-6">
                           <h6 class="fw-bold">Request Information</h6>
                           <table class="table table-sm">
                               <tr>
                                   <td><strong>Created By:</strong></td>
                                   <td>{{ $selectedAccount->created_by_name }}</td>
                               </tr>
                               <tr>
                                   <td><strong>Creator Role:</strong></td>
                                   <td>
                                       <span class="badge bg-primary">{{ ucfirst($selectedAccount->created_by_role) }}</span>
                                   </td>
                               </tr>
                               <tr>
                                   <td><strong>Requested On:</strong></td>
                                   <td>{{ \Carbon\Carbon::parse($selectedAccount->created_at)->format('M j, Y g:i A') }}</td>
                               </tr>
                               @if($selectedAccount->reviewed_at)
                               <tr>
                                   <td><strong>Reviewed By:</strong></td>
                                   <td>{{ $selectedAccount->reviewed_by_name }}</td>
                               </tr>
                               <tr>
                                   <td><strong>Reviewed On:</strong></td>
                                   <td>{{ \Carbon\Carbon::parse($selectedAccount->reviewed_at)->format('M j, Y g:i A') }}</td>
                               </tr>
                               @endif
                           </table>
                       </div>
                   </div>

                   @if($selectedAccount->status === 'pending')
                   <div class="mt-3">
                       <label class="form-label fw-bold">Admin Notes</label>
                       <textarea wire:model="adminNotes" class="form-control" rows="3" 
                                 placeholder="Optional notes about this decision..."></textarea>
                   </div>
                   @elseif($selectedAccount->admin_notes)
                   <div class="mt-3">
                       <label class="form-label fw-bold">Admin Notes</label>
                       <div class="alert alert-info">{{ $selectedAccount->admin_notes }}</div>
                   </div>
                   @endif
               </div>
               <div class="modal-footer">
                   @if($selectedAccount->status === 'pending')
                       <button type="button" class="btn btn-danger" wire:click="rejectAccount">
                           <i class="fas fa-times me-1"></i> Reject
                       </button>
                       <button type="button" class="btn btn-success" wire:click="approveAccount">
                           <i class="fas fa-check me-1"></i> Approve
                       </button>
                   @endif
                   <button type="button" class="btn btn-secondary" wire:click="closeModal">Close</button>
               </div>
           </div>
       </div>
   </div>
   <div class="modal-backdrop fade show"></div>
   @endif

   <!-- Alert handling script -->
   <script>
       document.addEventListener('livewire:init', () => {
           Livewire.on('show-alert', (event) => {
               const alertContainer = document.getElementById('alert-container');
               const alertId = 'alert-' + Date.now();
               
               const alertHtml = `
                   <div class="container mt-3">
                       <div id="${alertId}" class="alert alert-${event.type} alert-dismissible fade show" role="alert">
                           <i class="fas fa-${event.type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                           ${event.message}
                           <button type="button" class="btn-close" onclick="document.getElementById('${alertId}').remove()"></button>
                       </div>
                   </div>
               `;
               
               alertContainer.innerHTML = alertHtml;
               
               setTimeout(() => {
                   const alert = document.getElementById(alertId);
                   if (alert) alert.remove();
               }, 5000);
           });
       });
   </script>
</div>