@extends('layouts.app')

@section('content')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="docreq-container py-4" x-data="{ openRequest: null, search: '', statusFilter: 'all' }">

    <div class="docreq-header">
        <div>
            <h2 class="docreq-title">Student Document Requests</h2>
            <p class="docreq-subtitle">Pamahalaan at i-apruba ang mga kahilingan ng dokumento mula sa mga estudyante.</p>
        </div>
        <div class="docreq-search">
            <i class="fa-solid fa-search"></i>
            <input type="text" x-model="search" placeholder="Maghanap ng estudyante o ID...">
        </div>
    </div>

    @if(session('Success'))
        <div class="docreq-alert docreq-alert-success">
            <i class="fa-solid fa-circle-check"></i> {{ session('Success') }}
        </div>
    @endif
    @if(session('Error'))
        <div class="docreq-alert docreq-alert-danger">
            <i class="fa-solid fa-triangle-exclamation"></i> {{ session('Error') }}
        </div>
    @endif

    <div class="docreq-card">
        <div class="docreq-card-header">
            <i class="fa-solid fa-folder-open"></i> Listahan ng mga Nag-request ng Dokumento
            <span class="docreq-count-badge">{{ count($requests) }}</span>
        </div>

        <div class="docreq-filter-tabs">
            <button type="button" class="docreq-filter-tab" :class="statusFilter === 'all' ? 'docreq-filter-tab-active' : ''" @click="statusFilter = 'all'">
                All <span class="docreq-filter-count">{{ count($requests) }}</span>
            </button>
            <button type="button" class="docreq-filter-tab" :class="statusFilter === 'pending' ? 'docreq-filter-tab-active' : ''" @click="statusFilter = 'pending'">
                Pending <span class="docreq-filter-count">{{ $requests->where('status', '!=', 'Completed')->count() }}</span>
            </button>
            <button type="button" class="docreq-filter-tab" :class="statusFilter === 'completed' ? 'docreq-filter-tab-active' : ''" @click="statusFilter = 'completed'">
                Completed <span class="docreq-filter-count">{{ $requests->where('status', 'Completed')->count() }}</span>
            </button>
        </div>

        <div class="table-responsive">
            <table class="docreq-table">
                <thead>
                    <tr>
                        <th style="width: 16%;">Date Requested</th>
                        <th style="width: 12%;">Student ID</th>
                        <th style="width: 24%;">Student Name</th>
                        <th style="width: 22%;">Purpose</th>
                        <th style="width: 12%;" class="text-center">Status</th>
                        <th style="width: 14%;" class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $req)
                        @php
                            $searchBlob = strtolower($req->last_name.' '.$req->first_name.' '.$req->student_id);
                            $statusKey = $req->status == 'Completed' ? 'completed' : 'pending';
                        @endphp
                        <tr x-show="(search === '' || '{{ $searchBlob }}'.includes(search.toLowerCase())) && (statusFilter === 'all' || statusFilter === '{{ $statusKey }}')">
                            <td class="text-muted docreq-cell-sm">{{ date('M d, Y h:i A', strtotime($req->created_at)) }}</td>
                            <td class="fw-bold">{{ $req->student_id }}</td>
                            <td>
                                <div class="docreq-student-name">{{ $req->last_name }}, {{ $req->first_name }}</div>
                                <div class="docreq-student-email">{{ $req->email }}</div>
                            </td>
                            <td class="docreq-purpose-text">{{ $req->purpose }}</td>
                            <td class="text-center">
                                <span class="docreq-badge {{ $req->status == 'Completed' ? 'docreq-badge-success' : 'docreq-badge-pending' }}">
                                    {{ $req->status }}
                                </span>
                            </td>
                            <td class="text-center">
                                <button type="button" class="docreq-btn-outline" @click="openRequest = {{ $req->request_id }}">
                                    <i class="fa-solid fa-eye"></i> View &amp; Send
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="docreq-empty-row">
                                <i class="fa-regular fa-folder-open"></i>
                                <span>Walang nakitang document requests sa kasalukuyan.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- MODALS --}}
    @foreach($requests as $req)
        @php
            $decodedDocs = json_decode($req->document_name, true);
        @endphp
        <div class="docreq-modal-backdrop"
             x-show="openRequest === {{ $req->request_id }}"
             x-transition.opacity
             @click.self="openRequest = null"
             style="display: none;">
            <div class="docreq-modal" x-show="openRequest === {{ $req->request_id }}" x-transition>
                <div class="docreq-modal-header">
                    <h5><i class="fa-solid fa-file-invoice"></i> Request Details</h5>
                    <button type="button" class="docreq-modal-close" @click="openRequest = null">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="docreq-modal-body">
                    <div class="docreq-info-box">
                        <div class="docreq-info-row">
                            <span class="docreq-info-label">Student Name</span>
                            <span class="docreq-info-value">{{ $req->last_name }}, {{ $req->first_name }}</span>
                        </div>
                        <div class="docreq-info-row">
                            <span class="docreq-info-label">Student ID</span>
                            <span class="docreq-info-value">{{ $req->student_id }}</span>
                        </div>
                        <div class="docreq-info-row">
                            <span class="docreq-info-label">Email Address</span>
                            <span class="docreq-info-value docreq-info-email">{{ $req->email }}</span>
                        </div>
                        <div class="docreq-info-row docreq-info-row-last">
                            <span class="docreq-info-label">Purpose</span>
                            <span class="docreq-info-value fst-italic">&ldquo;{{ $req->purpose }}&rdquo;</span>
                        </div>
                    </div>

                    <form action="{{ route('registrar.update-documents', $req->student_pk) }}" method="POST" enctype="multipart/form-data" id="docForm{{ $req->request_id }}">
                        @csrf
                        @method('PUT')

                        <label class="docreq-doclist-label">Mga Nire-request na Dokumento</label>
                        <div class="docreq-doclist-table">
                            <div class="docreq-doclist-head">
                                <span>Document</span>
                                <span>Status</span>
                                <span>Replace File</span>
                            </div>

                            @php
                                $docNames = is_array($decodedDocs) ? $decodedDocs : [$req->document_name];
                            @endphp

                            @foreach($docNames as $dName)
                                @php
                                    $mapInfo = $docMapping[$dName] ?? null;
                                    $currentFile = $mapInfo ? ($req->{$mapInfo['column']} ?? null) : null;
                                @endphp
                                <div class="docreq-doclist-row">
                                    <div class="docreq-doc-name">
                                        <i class="fa-solid fa-file-lines"></i> {{ $dName }}
                                    </div>
                                    <div>
                                        @if($currentFile)
                                            <span class="docreq-file-status docreq-file-uploaded"><i class="fa-solid fa-circle-check"></i> Uploaded</span>
                                            <a href="{{ route('registrar.secure-view', [$req->student_id, $currentFile]) }}" target="_blank" class="docreq-view-link">View File</a>
                                        @else
                                            <span class="docreq-file-status docreq-file-missing"><i class="fa-solid fa-circle-exclamation"></i> Missing</span>
                                        @endif
                                    </div>
                                    <div>
                                        @if($mapInfo)
                                            <input type="file" name="{{ $mapInfo['input'] }}" class="docreq-file-input">
                                        @else
                                            <span class="docreq-file-status">&mdash;</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <button type="submit" class="docreq-btn-save">
                            <i class="fa-solid fa-floppy-disk"></i> Save Document Changes
                        </button>
                    </form>
                </div>

                <div class="docreq-modal-footer">
                    <button type="button" class="docreq-btn-ghost" @click="openRequest = null">Close</button>
                    <form action="{{ route('registrar.send.document', $req->request_id) }}" method="POST">
                        @csrf
                        <button type="submit" class="docreq-btn-primary" {{ $req->status == 'Completed' ? 'disabled' : '' }}>
                            <i class="fa-solid fa-paper-plane"></i>
                            {{ $req->status == 'Completed' ? 'Already Sent' : 'Approve & Send via Email' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @endforeach

</div>

<style>
    .docreq-container { max-width: 1200px; margin: 0 auto; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }

    .docreq-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; margin-bottom: 24px; flex-wrap: wrap; }
    .docreq-title { font-size: 24px; font-weight: 800; color: #1e2a5a; margin: 0 0 4px; }
    .docreq-subtitle { font-size: 13.5px; color: #64748b; margin: 0; }

    .docreq-search { position: relative; width: 280px; max-width: 100%; }
    .docreq-search i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 13px; }
    .docreq-search input {
        width: 100%; padding: 9px 14px 9px 36px; border-radius: 8px; border: 1px solid #cbd5e1;
        font-size: 13.5px; outline: none; transition: 0.2s;
    }
    .docreq-search input:focus { border-color: #1e2a5a; box-shadow: 0 0 0 3px rgba(30,42,90,0.08); }

    .docreq-alert { padding: 13px 18px; border-radius: 8px; margin-bottom: 18px; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 10px; }
    .docreq-alert-success { background: #f0fdf4; color: #166534; border-left: 4px solid #22c55e; }
    .docreq-alert-danger { background: #fef2f2; color: #991b1b; border-left: 4px solid #ef4444; }

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

    .docreq-table { width: 100%; border-collapse: collapse; }
    .docreq-table thead th {
        background: #f8fafc; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px;
        color: #64748b; padding: 13px 16px; border-bottom: 2px solid #e2e8f0; text-align: left;
    }
    .docreq-table tbody td { padding: 14px 16px; font-size: 13.5px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
    .docreq-table tbody tr:hover { background: #fafbfc; }
    .docreq-table tbody tr:last-child td { border-bottom: none; }
    .docreq-cell-sm { font-size: 12.5px; }

    .docreq-student-name { font-weight: 700; color: #1e293b; }
    .docreq-student-email { font-size: 11.5px; color: #94a3b8; }
    .docreq-purpose-text { display: block; max-width: 220px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #475569; }

    .docreq-badge { font-size: 11px; font-weight: 700; padding: 5px 12px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.3px; }
    .docreq-badge-pending { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
    .docreq-badge-success { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }

    .docreq-btn-outline {
        background: white; border: 1px solid #cbd5e1; color: #1e2a5a; font-size: 12.5px; font-weight: 600;
        padding: 7px 14px; border-radius: 20px; cursor: pointer; transition: 0.15s; display: inline-flex;
        align-items: center; gap: 6px;
    }
    .docreq-btn-outline:hover { background: #1e2a5a; border-color: #1e2a5a; color: white; }

    .docreq-empty-row { text-align: center; padding: 50px 20px; color: #94a3b8; }
    .docreq-empty-row i { font-size: 28px; display: block; margin-bottom: 10px; }

    /* Modal */
    .docreq-modal-backdrop {
        position: fixed; inset: 0; background: rgba(15, 23, 42, 0.55); z-index: 1050;
        display: flex; align-items: center; justify-content: center; padding: 20px;
    }
    .docreq-modal { background: white; border-radius: 16px; width: 100%; max-width: 460px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.35); overflow: hidden; }

    .docreq-modal-header { display: flex; justify-content: space-between; align-items: center; padding: 18px 22px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
    .docreq-modal-header h5 { margin: 0; font-size: 16px; font-weight: 800; color: #1e2a5a; display: flex; align-items: center; gap: 10px; }
    .docreq-modal-close { background: none; border: none; color: #94a3b8; font-size: 16px; cursor: pointer; padding: 4px; line-height: 1; }
    .docreq-modal-close:hover { color: #1e2a5a; }

    .docreq-modal-body { padding: 22px; }

    .docreq-info-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px 16px; margin-bottom: 20px; }
    .docreq-info-row { display: flex; justify-content: space-between; gap: 12px; padding: 7px 0; border-bottom: 1px dashed #e2e8f0; font-size: 13px; }
    .docreq-info-row-last { border-bottom: none; }
    .docreq-info-label { color: #94a3b8; flex-shrink: 0; }
    .docreq-info-value { color: #1e293b; font-weight: 600; text-align: right; }
    .docreq-info-email { color: #0284c7; }

    .docreq-doclist-label { font-size: 12px; font-weight: 700; color: #1e2a5a; text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 10px; display: block; }
    .docreq-doclist { display: flex; flex-direction: column; gap: 8px; }
    .docreq-doclist-item {
        display: flex; align-items: center; gap: 10px; font-size: 13.5px; color: #334155;
        background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 12px; font-weight: 500;
    }
    .docreq-doclist-item i { color: #1e2a5a; }

    .docreq-doclist-table { border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; margin-bottom: 16px; }
    .docreq-doclist-head {
        display: grid; grid-template-columns: 1.4fr 1fr 1.2fr; gap: 10px;
        background: #f8fafc; padding: 10px 14px; font-size: 10.5px; font-weight: 700;
        text-transform: uppercase; letter-spacing: 0.3px; color: #94a3b8; border-bottom: 1px solid #e2e8f0;
    }
    .docreq-doclist-row {
        display: grid; grid-template-columns: 1.4fr 1fr 1.2fr; gap: 10px; align-items: center;
        padding: 12px 14px; border-bottom: 1px solid #f1f5f9; font-size: 13px;
    }
    .docreq-doclist-row:last-child { border-bottom: none; }
    .docreq-doc-name { display: flex; align-items: center; gap: 8px; color: #334155; font-weight: 600; }
    .docreq-doc-name i { color: #1e2a5a; font-size: 12px; }

    .docreq-file-status { display: flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 700; }
    .docreq-file-uploaded { color: #15803d; }
    .docreq-file-missing { color: #b45309; }
    .docreq-view-link { display: block; font-size: 11.5px; color: #0284c7; text-decoration: underline; margin-top: 3px; }

    .docreq-file-input { font-size: 11.5px; width: 100%; max-width: 170px; }

    .docreq-btn-save {
        background: #1e2a5a; color: white; border: none; font-size: 12.5px; font-weight: 700;
        padding: 9px 16px; border-radius: 20px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;
        transition: 0.15s;
    }
    .docreq-btn-save:hover { background: #16204a; }

    .docreq-modal-footer { display: flex; justify-content: flex-end; gap: 10px; padding: 16px 22px; background: #f8fafc; border-top: 1px solid #e2e8f0; }

    .docreq-btn-ghost { background: white; border: 1px solid #cbd5e1; color: #475569; font-size: 13px; font-weight: 600; padding: 9px 18px; border-radius: 20px; cursor: pointer; transition: 0.15s; }
    .docreq-btn-ghost:hover { border-color: #94a3b8; }

    .docreq-btn-primary {
        background: #16a34a; border: none; color: white; font-size: 13px; font-weight: 700; padding: 9px 18px;
        border-radius: 20px; cursor: pointer; transition: 0.15s; display: inline-flex; align-items: center; gap: 8px;
    }
    .docreq-btn-primary:hover { background: #15803d; }
    .docreq-btn-primary:disabled { background: #94a3b8; cursor: not-allowed; }
</style>
@endsection