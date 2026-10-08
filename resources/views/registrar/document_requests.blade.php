@extends('layouts.app')

@section('content')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="rg-page" x-data="{ search: '', statusFilter: 'all' }">

    <header class="rg-header">
        <div>
            <h2 class="rg-title">Student Document Requests</h2>
            <p class="rg-subtitle">Manage and update status of student document requests directly from the table.</p>
        </div>
        <div class="search-input-wrapper" style="width: 320px; max-width: 100%;">
            <i class="fa-solid fa-search search-icon"></i>
            <input type="text" x-model="search" class="rg-input" placeholder="Search student, ID, or document...">
        </div>
    </header>

    @if(session('success') || session('Success') || session('status'))
        <div class="rg-alert rg-alert-success" role="status">
            <i class="fa-solid fa-circle-check"></i>
            <span>{{ session('success') ?? session('Success') ?? session('status') }}</span>
        </div>
    @endif

    @if(session('error') || session('Error'))
        <div class="rg-alert rg-alert-error" role="alert">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span>{{ session('error') ?? session('Error') }}</span>
        </div>
    @endif

    <div class="rg-card">
        <div class="card-bar card-bar-split">
            <span><i class="fa-solid fa-folder-open"></i> List of Student Requests</span>
            <span class="rg-tab-count" style="background: var(--rg-blue); color: #fff;">{{ count($requests) }}</span>
        </div>

        <div class="docreq-filter-tabs">
            <button type="button" class="docreq-filter-tab" :class="statusFilter === 'all' ? 'docreq-filter-tab-active' : ''" @click="statusFilter = 'all'">
                All <span class="docreq-filter-count">{{ count($requests) }}</span>
            </button>
            <button type="button" class="docreq-filter-tab" :class="statusFilter === 'pending' ? 'docreq-filter-tab-active' : ''" @click="statusFilter = 'pending'">
                Pending <span class="docreq-filter-count">{{ collect($requests)->where('status', 'Pending')->count() }}</span>
            </button>
            <button type="button" class="docreq-filter-tab" :class="statusFilter === 'ready' ? 'docreq-filter-tab-active' : ''" @click="statusFilter = 'ready'">
                Ready to Claim <span class="docreq-filter-count">{{ collect($requests)->where('status', 'Ready to Claim')->count() }}</span>
            </button>
            <button type="button" class="docreq-filter-tab" :class="statusFilter === 'completed' ? 'docreq-filter-tab-active' : ''" @click="statusFilter = 'completed'">
                Completed <span class="docreq-filter-count">{{ collect($requests)->where('status', 'Completed')->count() }}</span>
            </button>
        </div>

        <div class="rg-table-wrap" style="border: none; border-top: 1px solid var(--rg-line); border-radius: 0 0 10px 10px;">
            <table class="rg-table">
                <thead>
                    <tr>
                        <th style="width: 14%;">Date Requested</th>
                        <th style="width: 12%;">Student ID</th>
                        <th style="width: 20%;">Student Name</th>
                        <th style="width: 20%;">Requested Document</th>
                        <th style="width: 10%;">Purpose</th>
                        <th style="width: 24%;" class="rg-text-center">Update Status &amp; Action</th>
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
                            
                            <td style="color: var(--rg-muted); font-size: 12.5px;">{{ date('M d, Y h:i A', strtotime($req->created_at)) }}</td>
                            <td><span class="student-id-tag">{{ $req->student_id }}</span></td>
                            <td>
                                <strong style="color: var(--rg-blue-dark); text-transform: uppercase; font-size: 13.5px;">{{ $req->last_name }}</strong>, {{ $req->first_name }}
                                <div style="font-size: 11.5px; color: var(--rg-muted); margin-top: 2px;">{{ $req->email }}</div>
                            </td>
                            <td>
                                <span class="docreq-doc-badge">
                                    <i class="fa-solid fa-file-lines"></i> {{ $docName }}
                                </span>
                            </td>
                            <td class="docreq-purpose-text">{{ $req->purpose }}</td>
                            
                            <td class="rg-text-center">
                                <form action="{{ route('registrar.update_document_status', $req->request_id ?? $req->id) }}" method="POST" class="docreq-inline-form">
                                    @csrf
                                    
                                    <select name="status" class="rg-select-sm" x-model="selectedStatus">
                                        <option value="Pending" {{ $req->status == 'Pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="Processing" {{ $req->status == 'Processing' ? 'selected' : '' }}>Processing</option>
                                        <option value="Ready to Claim" {{ $req->status == 'Ready to Claim' ? 'selected' : '' }}>Ready to Claim</option>
                                        <option value="Completed" {{ $req->status == 'Completed' ? 'selected' : '' }}>Completed</option>
                                    </select>

                                    <button type="submit" class="rg-btn-action rg-btn-action-primary">
                                        <i class="fa-solid fa-check"></i> Save
                                    </button>

                                    <template x-if="selectedStatus === 'Ready to Claim'">
                                        <button type="submit" name="notify_email" value="1" class="rg-btn-action rg-btn-action-info">
                                            <i class="fa-solid fa-paper-plane"></i> Email
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
    .rg-page {
        --rg-blue: #0033A0; --rg-blue-dark: #0A1F5C; --rg-blue-tint: #EAF0FF; --rg-gold: #F2B705;
        --rg-ink: #0B1B3A; --rg-muted: #5C6B8A; --rg-line: #E2E8F3; --rg-surface: #F7F9FD;
        --rg-green: #15803D; --rg-green-tint: #E8F7EE; --rg-red: #C62828;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: var(--rg-ink);
        max-width: 1300px; margin: 0 auto; padding: 24px 20px 40px;
    }

    .rg-header { display: flex; justify-content: space-between; align-items: flex-end; gap: 20px; flex-wrap: wrap; padding-bottom: 18px; margin-bottom: 24px; border-bottom: 1px solid var(--rg-line); }
    .rg-title { margin: 0; font-size: 26px; font-weight: 800; letter-spacing: -0.2px; color: var(--rg-blue-dark); }
    .rg-subtitle { margin: 8px 0 0; font-size: 14px; color: var(--rg-muted); }

    .search-input-wrapper { position: relative; }
    .search-icon { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--rg-muted); font-size: 14px; }
    .rg-input {
        width: 100%; box-sizing: border-box; height: 42px; padding: 0 16px 0 42px;
        font-size: 13.5px; color: var(--rg-ink); background: #fff;
        border: 1px solid var(--rg-line); border-radius: 8px; outline: none;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    .rg-input:focus { border-color: var(--rg-blue); box-shadow: 0 0 0 3px rgba(0, 51, 160, 0.14); }

    .rg-alert { display: flex; gap: 10px; align-items: flex-start; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 13.5px; font-weight: 600; line-height: 1.45; }
    .rg-alert i { margin-top: 2px; }
    .rg-alert-success { background: var(--rg-green-tint); color: #14532D; border: 1px solid #BFE8CE; border-left: 4px solid var(--rg-green); }
    .rg-alert-error { background: #FDECEC; color: #8E1B1B; border: 1px solid #F5C6C6; border-left: 4px solid var(--rg-red); }

    .rg-card { background: #fff; border: 1px solid var(--rg-line); border-radius: 10px; overflow: hidden; box-shadow: 0 1px 2px rgba(10, 31, 92, 0.05); }
    .card-bar { background: var(--rg-surface); padding: 14px 20px; font-weight: 700; font-size: 14px; color: var(--rg-blue-dark); border-bottom: 1px solid var(--rg-line); display: flex; align-items: center; gap: 8px; }
    .card-bar-split { justify-content: space-between; }
    .card-bar-split > span:first-child { display: inline-flex; align-items: center; gap: 8px; }
    
    .rg-tab-count { background: var(--rg-line); color: var(--rg-muted); font-size: 11.5px; font-weight: 700; padding: 3px 10px; border-radius: 10px; }

    .docreq-filter-tabs { display: flex; gap: 8px; padding: 14px 20px 0; background: #fff; }
    .docreq-filter-tab {
        background: transparent; border: none; padding: 9px 16px; font-size: 13px; font-weight: 600;
        color: var(--rg-muted); cursor: pointer; border-radius: 8px 8px 0 0; display: flex; align-items: center; gap: 7px;
        transition: 0.15s; border-bottom: 2px solid transparent;
    }
    .docreq-filter-tab:hover { color: var(--rg-blue-dark); }
    .docreq-filter-tab-active { color: var(--rg-blue-dark); border-bottom: 2px solid var(--rg-blue-dark); }
    .docreq-filter-count { background: var(--rg-surface); color: var(--rg-muted); font-size: 10.5px; font-weight: 700; padding: 2px 8px; border-radius: 20px; border: 1px solid var(--rg-line); }
    .docreq-filter-tab-active .docreq-filter-count { background: var(--rg-blue-tint); color: var(--rg-blue-dark); border-color: #CFDBFB; }

    .rg-table-wrap { overflow-x: auto; }
    .rg-table { width: 100%; border-collapse: collapse; font-size: 14px; }
    .rg-table th { background: var(--rg-surface); padding: 12px 20px; font-size: 12px; font-weight: 700; color: var(--rg-muted); text-transform: uppercase; text-align: left; border-bottom: 1px solid var(--rg-line); white-space: nowrap; }
    .rg-table td { padding: 14px 20px; border-bottom: 1px solid #EEF2F9; color: #26345A; vertical-align: middle; font-weight: 500; }
    .rg-table tbody tr:last-child td { border-bottom: none; }
    .rg-table tbody tr:hover { background: #F5F8FF; }

    .rg-text-center { text-align: center !important; }

    .student-id-tag { font-family: ui-monospace, monospace; font-weight: 700; color: var(--rg-blue); background: var(--rg-blue-tint); padding: 3px 9px; border-radius: 6px; font-size: 12.5px; border: 1px solid #CFDBFB; }

    .docreq-doc-badge { display: inline-flex; align-items: center; gap: 6px; font-weight: 600; color: var(--rg-ink); background: var(--rg-surface); padding: 4px 10px; border-radius: 6px; font-size: 12.5px; border: 1px solid var(--rg-line); }
    .docreq-doc-badge i { color: var(--rg-blue); }

    .docreq-purpose-text { display: block; max-width: 100%; font-size: 13.5px; color: var(--rg-muted); }

    .docreq-inline-form { display: flex; align-items: center; justify-content: center; gap: 8px; flex-wrap: nowrap; min-width: max-content; }
    
    .rg-select-sm {
        border-radius: 20px; font-size: 12.5px; font-weight: 600; padding: 5px 12px; border: 1px solid #CBD5E6;
        outline: none; color: var(--rg-ink); background: #fff; cursor: pointer; transition: 0.2s; min-width: 130px;
    }
    .rg-select-sm:focus { border-color: var(--rg-blue); box-shadow: 0 0 0 3px rgba(0, 51, 160, 0.14); }

    .rg-btn-action {
        border: none; font-size: 11.5px; font-weight: 700; padding: 6px 12px; border-radius: 20px;
        cursor: pointer; transition: 0.15s; display: inline-flex; align-items: center; gap: 5px; white-space: nowrap;
    }
    .rg-btn-action-primary { background: var(--rg-blue-dark); color: #fff; }
    .rg-btn-action-primary:hover { background: #061238; }
    .rg-btn-action-info { background: #0284C7; color: #fff; }
    .rg-btn-action-info:hover { background: #0369A1; }

    .docreq-empty-row { text-align: center; padding: 50px 20px !important; color: var(--rg-muted); }
    .docreq-empty-row i { font-size: 28px; display: block; margin-bottom: 10px; color: #cbd5e1; }
</style>
@endsection