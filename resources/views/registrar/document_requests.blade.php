@extends('layouts.app')

@section('content')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="docreq-container py-4" x-data="{ search: '', statusFilter: 'all' }">

    <div class="docreq-header">
        <div>
            <h2 class="docreq-title">Student Document Requests</h2>
            <p class="docreq-subtitle">Manage and update status of student document requests directly from the table.</p>
        </div>
        <div class="docreq-search">
            <i class="fa-solid fa-search"></i>
            <input type="text" x-model="search" placeholder="Search student, ID, or document...">
        </div>
    </div>

    @if(session('success') || session('Success') || session('status'))
        <div class="docreq-alert docreq-alert-success">
            <i class="fa-solid fa-circle-check"></i> {{ session('success') ?? session('Success') ?? session('status') }}
        </div>
    @endif
    @if(session('error') || session('Error'))
        <div class="docreq-alert docreq-alert-danger">
            <i class="fa-solid fa-triangle-exclamation"></i> {{ session('error') ?? session('Error') }}
        </div>
    @endif

    <div class="docreq-card">
        <div class="docreq-card-header">
            <i class="fa-solid fa-folder-open"></i> List of Student Requests
            <span class="docreq-count-badge">{{ count($requests) }}</span>
        </div>

        <div class="docreq-filter-tabs">
            <button type="button" class="docreq-filter-tab" :class="statusFilter === 'all' ? 'docreq-filter-tab-active' : ''" @click="statusFilter = 'all'">
                All <span class="docreq-filter-count">{{ count($requests) }}</span>
            </button>
            <button type="button" class="docreq-filter-tab" :class="statusFilter === 'pending' ? 'docreq-filter-tab-active' : ''" @click="statusFilter = 'pending'">
                Pending <span class="docreq-filter-count">{{ $requests->where('status', 'Pending')->count() }}</span>
            </button>
            <button type="button" class="docreq-filter-tab" :class="statusFilter === 'ready' ? 'docreq-filter-tab-active' : ''" @click="statusFilter = 'ready'">
                Ready to Claim <span class="docreq-filter-count">{{ $requests->where('status', 'Ready to Claim')->count() }}</span>
            </button>
            <button type="button" class="docreq-filter-tab" :class="statusFilter === 'completed' ? 'docreq-filter-tab-active' : ''" @click="statusFilter = 'completed'">
                Completed <span class="docreq-filter-count">{{ $requests->where('status', 'Completed')->count() }}</span>
            </button>
        </div>

        <div class="table-responsive">
            <table class="docreq-table">
                <thead>
                    <tr>
                        <th style="width: 12%;">Date Requested</th>
                        <th style="width: 10%;">Student ID</th>
                        <th style="width: 16%;">Student Name</th>
                        <th style="width: 18%;">Requested Document</th>
                        <th style="width: 12%;">Purpose</th>
                        <th style="width: 32%;" class="text-center">Update Status &amp; Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $req)
                        @php
                            $docName = $req->document_name ?? $req->document_type ?? $req->document ?? $req->requested_document ?? 'Transcript of Records (TOR)';
                            $searchBlob = strtolower($req->last_name.' '.$req->first_name.' '.$req->student_id.' '.$docName);
                            $statusKey = strtolower(str_replace(' ', '', $req->status));
                        @endphp
                        <tr x-data="{ selectedStatus: '{{ $req->status }}' }" 
                            x-show="(search === '' || '{{ $searchBlob }}'.includes(search.toLowerCase())) && (statusFilter === 'all' || statusFilter === '{{ $statusKey }}' || (statusFilter === 'ready' && '{{ $req->status }}' === 'Ready to Claim'))">
                            
                            <td class="text-muted docreq-cell-sm">{{ date('M d, Y h:i A', strtotime($req->created_at)) }}</td>
                            <td class="fw-bold">{{ $req->student_id }}</td>
                            <td>
                                <div class="docreq-student-name">{{ $req->last_name }}, {{ $req->first_name }}</div>
                                <div class="docreq-student-email">{{ $req->email }}</div>
                            </td>
                            <td>
                                <span class="docreq-doc-badge">
                                    <i class="fa-solid fa-file-lines"></i> {{ $docName }}
                                </span>
                            </td>
                            <td class="docreq-purpose-text">{{ $req->purpose }}</td>
                            
                            <td class="text-center">
                                <form action="{{ route('registrar.update_document_status', $req->request_id) }}" method="POST" class="docreq-inline-form">
                                    @csrf
                                    
                                    <select name="status" class="docreq-status-select-sm" x-model="selectedStatus">
                                        <option value="Pending" {{ $req->status == 'Pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="Processing" {{ $req->status == 'Processing' ? 'selected' : '' }}>Processing</option>
                                        <option value="Ready to Claim" {{ $req->status == 'Ready to Claim' ? 'selected' : '' }}>Ready to Claim</option>
                                        <option value="Completed" {{ $req->status == 'Completed' ? 'selected' : '' }}>Completed</option>
                                    </select>

                                    <button type="submit" class="docreq-btn-save-sm">
                                        <i class="fa-solid fa-check"></i> Save
                                    </button>

                                    <template x-if="selectedStatus === 'Ready to Claim'">
                                        <button type="submit" name="notify_email" value="1" class="docreq-btn-email-sm">
                                            <i class="fa-solid fa-paper-plane"></i> Send Email
                                        </button>
                                    </template>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="docreq-empty-row">
                                <i class="fa-regular fa-folder-open"></i>
                                <span>No document requests found at the moment.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<style>
    .docreq-container { max-width: 1280px; margin: 0 auto; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }

    .docreq-header { display: flex; justify-content: space-between; align-items: center; gap: 20px; margin-bottom: 24px; flex-wrap: wrap; }
    .docreq-title { font-size: 24px; font-weight: 800; color: #1e2a5a; margin: 0 0 4px; }
    .docreq-subtitle { font-size: 13.5px; color: #64748b; margin: 0; }

    .docreq-search { position: relative; width: 320px; max-width: 100%; }
    .docreq-search i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 13px; }
    .docreq-search input {
        width: 100%; padding: 10px 14px 10px 36px; border-radius: 8px; border: 1px solid #cbd5e1;
        font-size: 13.5px; outline: none; transition: 0.2s; box-sizing: border-box;
    }
    .docreq-search input:focus { border-color: #1e2a5a; box-shadow: 0 0 0 3px rgba(30,42,90,0.08); }

    .docreq-alert { padding: 13px 18px; border-radius: 8px; margin-bottom: 18px; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 10px; }
    .docreq-alert-success { background: #f0fdf4; color: #166534; border-left: 4px solid #22c55e; border-top: 1px solid #bbf7d0; border-right: 1px solid #bbf7d0; border-bottom: 1px solid #bbf7d0; }
    .docreq-alert-danger { background: #fef2f2; color: #991b1b; border-left: 4px solid #ef4444; border-top: 1px solid #fecaca; border-right: 1px solid #fecaca; border-bottom: 1px solid #fecaca; }

    .docreq-card { background: white; border-radius: 14px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.03); }
    .docreq-card-header {
        padding: 16px 22px; border-bottom: 1px solid #e2e8f0; font-weight: 700; color: #1e2a5a;
        font-size: 14px; display: flex; align-items: center; gap: 10px; background: #f8fafc;
    }
    .docreq-count-badge { background: #1e2a5a; color: white; font-size: 11px; padding: 2px 10px; border-radius: 20px; margin-left: auto; }

    .docreq-filter-tabs { display: flex; gap: 6px; padding: 14px 22px 0; background: #f8fafc; }
    .docreq-filter-tab {
        background: transparent; border: none; padding: 9px 16px; font-size: 13px; font-weight: 600;
        color: #64748b; cursor: pointer; border-radius: 8px 8px 0 0; display: flex; align-items: center; gap: 7px;
        transition: 0.15s; border-bottom: 2px solid transparent;
    }
    .docreq-filter-tab:hover { color: #1e2a5a; }
    .docreq-filter-tab-active { color: #1e2a5a; background: white; border-bottom: 2px solid #1e2a5a; }
    .docreq-filter-count {
        background: #e2e8f0; color: #475569; font-size: 10.5px; font-weight: 700; padding: 1px 8px; border-radius: 20px;
    }
    .docreq-filter-tab-active .docreq-filter-count { background: #1e2a5a; color: white; }

    .docreq-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    .docreq-table thead th {
        background: #f8fafc; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px;
        color: #64748b; padding: 14px 16px; border-bottom: 2px solid #e2e8f0; text-align: left; vertical-align: middle;
    }
    .docreq-table tbody td { padding: 14px 16px; font-size: 13.5px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
    .docreq-table tbody tr:hover { background: #fafbfc; }
    .docreq-table tbody tr:last-child td { border-bottom: none; }
    .docreq-cell-sm { font-size: 12.5px; }

    .docreq-student-name { font-weight: 700; color: #1e293b; }
    .docreq-student-email { font-size: 11.5px; color: #94a3b8; }
    
    .docreq-doc-badge {
        display: inline-flex; align-items: center; gap: 6px; font-weight: 600; color: #1e2a5a;
        background: #f1f5f9; padding: 5px 10px; border-radius: 6px; font-size: 12.5px; border: 1px solid #e2e8f0;
    }
    .docreq-doc-badge i { color: #0284c7; }

    .docreq-purpose-text { display: block; max-width: 100%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #475569; }

    .docreq-inline-form { display: flex; align-items: center; justify-content: center; gap: 8px; flex-wrap: nowrap; min-width: max-content; }
    
    .docreq-status-select-sm {
        border-radius: 20px; font-size: 12.5px; font-weight: 600; padding: 6px 12px; border: 1px solid #cbd5e1;
        outline: none; color: #1e293b; background: white; cursor: pointer; transition: 0.2s; min-width: 135px;
    }
    .docreq-status-select-sm:focus { border-color: #1e2a5a; }

    .docreq-btn-save-sm {
        background: #1e2a5a; border: none; color: white; font-size: 12px; font-weight: 700; padding: 7px 14px;
        border-radius: 20px; cursor: pointer; transition: 0.15s; display: inline-flex; align-items: center; gap: 5px; white-space: nowrap;
    }
    .docreq-btn-save-sm:hover { background: #16204a; }

    .docreq-btn-email-sm {
        background: #0284c7; border: none; color: white; font-size: 12px; font-weight: 700; padding: 7px 14px;
        border-radius: 20px; cursor: pointer; transition: 0.15s; display: inline-flex; align-items: center; gap: 5px; white-space: nowrap;
    }
    .docreq-btn-email-sm:hover { background: #0369a1; }

    .docreq-empty-row { text-align: center; padding: 50px 20px; color: #94a3b8; }
    .docreq-empty-row i { font-size: 28px; display: block; margin-bottom: 10px; }
</style>
@endsection