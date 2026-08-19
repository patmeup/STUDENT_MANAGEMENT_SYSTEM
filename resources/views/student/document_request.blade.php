@extends('layouts.app')

@section('content')
<div class="doc-page-wrapper">

    @if(session('success'))
        <div class="alert-banner alert-success mb-4">
            <i data-lucide="check-circle" class="alert-icon"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <div class="doc-header-bar">
        <div>
            <h1 class="doc-page-title">Document Requests</h1>
            <p class="doc-page-subtitle">Request official academic records and track physical document fulfillment.</p>
        </div>
        <button type="button" class="btn-primary-action" onclick="openRequestModal()">
            <i data-lucide="plus" class="btn-icon"></i>
            <span>New Request</span>
        </button>
    </div>

    <div class="notice-banner mb-4">
        <div class="notice-icon-box">
            <i data-lucide="info" class="notice-icon"></i>
        </div>
        <div class="notice-content">
            <strong class="notice-title">Important Pick-up Policy</strong>
            <p class="notice-text">
                All requested documents are issued as to the Registrar Office, Once status changes to
                <span class="status-inline-tag">Ready to Claim</span>, you can claim your document.
            </p>
        </div>
    </div>

    <div class="table-card">
        <div class="table-card-header">
            <div class="header-left">
                <i data-lucide="history" class="header-icon"></i>
                <h2 class="table-card-title">Request History & Status</h2>
            </div>
            <span class="history-count-badge">{{ count($requests) }} {{ Str::plural('request', count($requests)) }}</span>
        </div>

        <div class="table-responsive">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th style="width: 20%;">Date Requested</th>
                        <th style="width: 25%;">Document Name</th>
                        <th style="width: 25%;">Purpose</th>
                        <th style="width: 15%;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $req)
                        <tr>
                            <td class="td-date">
                                <i data-lucide="calendar" class="td-icon"></i>
                                {{ \Carbon\Carbon::parse($req->created_at)->format('M d, Y') }}
                                <span class="td-time">{{ \Carbon\Carbon::parse($req->created_at)->format('h:i A') }}</span>
                            </td>
                            <td class="td-doc-name">
                                <span class="doc-title-text">{{ str_replace(['[', ']', '"'], '', $req->document_name) }}</span>
                            </td>
                            <td class="td-purpose">
                                {{ $req->purpose }}
                            </td>
                            <td>
                                @if($req->status === 'Pending')
                                    <span class="status-badge status-pending">
                                        <i data-lucide="clock" class="badge-icon"></i> Pending
                                    </span>
                                @elseif($req->status === 'Processing')
                                    <span class="status-badge status-processing">
                                        <i data-lucide="loader-2" class="badge-icon spin"></i> Processing
                                    </span>
                                @elseif($req->status === 'Ready to Claim')
                                    <span class="status-badge status-ready">
                                        <i data-lucide="check-circle-2" class="badge-icon"></i> Ready to Claim
                                    </span>
                                @elseif($req->status === 'Claimed' || $req->status === 'Completed')
                                    <span class="status-badge status-completed">
                                        <i data-lucide="archive" class="badge-icon"></i> {{ $req->status }}
                                    </span>
                                @else
                                    <span class="status-badge status-pending">{{ $req->status }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="empty-state-cell">
                                <div class="empty-state-wrapper">
                                    <div class="empty-icon-circle">
                                        <i data-lucide="folder-open" class="empty-icon"></i>
                                    </div>
                                    <h3 class="empty-title">No document requests submitted yet</h3>
                                    <p class="empty-subtitle">Click <strong>"New Request"</strong> above to request TOR, Diploma, or Certificates.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<div class="modal-backdrop" id="requestModal">
    <div class="modal-dialog">
        <div class="modal-content-card">
            
            <div class="modal-top-bar">
                <div class="modal-title-group">
                    <div class="modal-icon-badge">
                        <i data-lucide="file-plus" class="modal-header-icon"></i>
                    </div>
                    <div>
                        <h3 class="modal-heading">Request Official Document</h3>
                        <p class="modal-subheading">Fill in details for Registrar verification</p>
                    </div>
                </div>
                <button type="button" class="modal-close-icon" onclick="closeRequestModal()">&times;</button>
            </div>

            <form action="{{ route('student.submit_document_request') }}" method="POST">
                @csrf
                <div class="modal-inner-body">
                    
                    <div class="form-field-group">
                        <label class="field-label">Select Document <span class="required-asterisk">*</span></label>
                        <select name="document_name" id="docTypeSelect" required class="custom-select-input" onchange="toggleCertificateType(this.value)">
                            <option value="" disabled selected>-- Choose Document --</option>
                            <option value="Transcript of Records (TOR)">Transcript of Records (TOR)</option>
                            <option value="Diploma">Diploma</option>
                            <option value="Certificate">Certificate</option>
                        </select>
                    </div>

                    <div class="form-field-group">
                        <label class="field-label">Purpose of Request <span class="required-asterisk">*</span></label>
                        <textarea 
                            name="purpose" 
                            required 
                            class="custom-textarea-input" 
                            rows="3" 
                            placeholder="State reason (e.g. For Employment, Board Exam, Scholarship, Transfer, etc.)"
                        ></textarea>
                    </div>

                </div>

                <div class="modal-bottom-bar">
                    <button type="button" class="btn-secondary-action" onclick="closeRequestModal()">Cancel</button>
                    <button type="submit" class="btn-primary-action">
                        <i data-lucide="send" class="btn-icon"></i>
                        <span>Submit Request</span>
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

<style>
    .doc-page-wrapper {
        width: 100%;
        max-width: 1280px;
        margin: 0 auto;
        padding: 24px 20px 40px 20px;
        box-sizing: border-box;
        font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }

    .doc-header-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        gap: 16px;
        flex-wrap: wrap;
    }

    .doc-page-title {
        font-size: 26px;
        font-weight: 800;
        color: #0f172a;
        margin: 0 0 4px 0;
        letter-spacing: -0.02em;
    }

    .doc-page-subtitle {
        font-size: 14px;
        color: #64748b;
        margin: 0;
    }

    .btn-primary-action {
        background-color: #1e3a8a;
        color: #ffffff;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s ease;
        box-shadow: 0 2px 4px rgba(30, 58, 138, 0.15);
    }

    .btn-primary-action:hover {
        background-color: #1d4ed8;
        transform: translateY(-1px);
        box-shadow: 0 4px 6px rgba(30, 58, 138, 0.2);
    }

    .btn-secondary-action {
        background-color: #ffffff;
        color: #475569;
        border: 1px solid #cbd5e1;
        padding: 10px 18px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-secondary-action:hover {
        background-color: #f8fafc;
        color: #0f172a;
        border-color: #94a3b8;
    }

    .btn-icon {
        width: 16px;
        height: 16px;
    }

    .notice-banner {
        background-color: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 12px;
        padding: 16px 20px;
        display: flex;
        align-items: flex-start;
        gap: 16px;
    }

    .notice-icon-box {
        background-color: #dbeafe;
        color: #1d4ed8;
        padding: 8px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .notice-icon {
        width: 20px;
        height: 20px;
    }

    .notice-title {
        color: #1e40af;
        font-size: 14px;
        display: block;
        margin-bottom: 2px;
    }

    .notice-text {
        color: #1e3a8a;
        font-size: 13px;
        margin: 0;
        line-height: 1.5;
    }

    .status-inline-tag {
        background-color: #dcfce7;
        color: #15803d;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 12px;
    }

    .table-card {
        background-color: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        overflow: hidden;
    }

    .table-card-header {
        background-color: #f8fafc;
        padding: 16px 24px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .header-left {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .header-icon {
        width: 18px;
        height: 18px;
        color: #475569;
    }

    .table-card-title {
        font-size: 15px;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
    }

    .history-count-badge {
        background-color: #e2e8f0;
        color: #475569;
        font-size: 12px;
        font-weight: 600;
        padding: 3px 10px;
        border-radius: 12px;
    }

    .table-responsive {
        width: 100%;
        overflow-x: auto;
    }

    .modern-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }

    .modern-table th {
        background-color: #f1f5f9;
        color: #475569;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        padding: 14px 24px;
        border-bottom: 1px solid #e2e8f0;
    }

    .modern-table td {
        padding: 16px 24px;
        border-bottom: 1px solid #f1f5f9;
        font-size: 14px;
        color: #334155;
        vertical-align: middle;
    }

    .modern-table tbody tr:hover {
        background-color: #f8fafc;
    }

    .td-date {
        color: #0f172a;
        font-weight: 600;
    }

    .td-icon {
        width: 14px;
        height: 14px;
        color: #94a3b8;
        display: inline-block;
        vertical-align: middle;
        margin-right: 4px;
    }

    .td-time {
        display: block;
        font-size: 12px;
        color: #94a3b8;
        font-weight: normal;
        margin-top: 2px;
    }

    .doc-title-text {
        font-weight: 700;
        color: #0f172a;
    }

    .td-purpose {
        color: #64748b;
        font-size: 13px;
        max-width: 220px;
        white-space: normal;
        word-break: break-word;
    }

    .td-remarks {
        color: #64748b;
        font-size: 13px;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
    }

    .badge-icon {
        width: 14px;
        height: 14px;
    }

    .status-pending { background-color: #fef3c7; color: #b45309; }
    .status-processing { background-color: #e0f2fe; color: #0369a1; }
    .status-ready { background-color: #dcfce7; color: #15803d; }
    .status-completed { background-color: #f1f5f9; color: #475569; }

    .empty-state-cell {
        padding: 60px 20px !important;
        text-align: center;
    }

    .empty-state-wrapper {
        max-width: 360px;
        margin: 0 auto;
    }

    .empty-icon-circle {
        width: 56px;
        height: 56px;
        background-color: #f1f5f9;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 16px auto;
        color: #94a3b8;
    }

    .empty-icon {
        width: 28px;
        height: 28px;
    }

    .empty-title {
        font-size: 16px;
        font-weight: 700;
        color: #1e293b;
        margin: 0 0 6px 0;
    }

    .empty-subtitle {
        font-size: 13px;
        color: #64748b;
        margin: 0;
        line-height: 1.4;
    }

    .modal-backdrop {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background-color: rgba(15, 23, 42, 0.65);
        backdrop-filter: blur(4px);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        padding: 20px;
        box-sizing: border-box;
    }

    .modal-backdrop.active {
        display: flex;
    }

    .modal-dialog {
        width: 100%;
        max-width: 520px;
        animation: modalSlideUp 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    }

    @keyframes modalSlideUp {
        from { opacity: 0; transform: translateY(16px) scale(0.98); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }

    .modal-content-card {
        background-color: #ffffff;
        border-radius: 16px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        overflow: hidden;
    }

    .modal-top-bar {
        padding: 20px 24px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        background-color: #ffffff;
    }

    .modal-title-group {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .modal-icon-badge {
        width: 42px;
        height: 42px;
        background-color: #eff6ff;
        color: #1d4ed8;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .modal-header-icon {
        width: 22px;
        height: 22px;
    }

    .modal-heading {
        font-size: 18px;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 2px 0;
    }

    .modal-subheading {
        font-size: 13px;
        color: #64748b;
        margin: 0;
    }

    .modal-close-icon {
        background: none;
        border: none;
        font-size: 24px;
        color: #94a3b8;
        cursor: pointer;
        line-height: 1;
        padding: 0;
        transition: color 0.15s;
    }

    .modal-close-icon:hover {
        color: #0f172a;
    }

    .modal-inner-body {
        padding: 24px;
    }

    .form-field-group {
        margin-bottom: 20px;
    }

    .form-field-group:last-child {
        margin-bottom: 0;
    }

    .field-label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: #334155;
        margin-bottom: 8px;
    }

    .required-asterisk {
        color: #dc2626;
    }

    .custom-select-input, .custom-textarea-input {
        width: 100%;
        padding: 11px 14px;
        font-size: 14px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background-color: #ffffff;
        color: #0f172a;
        box-sizing: border-box;
        outline: none;
        transition: all 0.2s;
        font-family: inherit;
    }

    .custom-select-input:focus, .custom-textarea-input:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
    }

    .custom-textarea-input {
        resize: none;
        min-height: 90px;
    }

    .modal-bottom-bar {
        padding: 16px 24px;
        background-color: #f8fafc;
        border-top: 1px solid #e2e8f0;
        display: flex;
        justify-content: flex-end;
        gap: 12px;
    }

    .spin {
        animation: spin 1.5s linear infinite;
    }
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>

<script>
    function openRequestModal() {
        document.getElementById('requestModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeRequestModal() {
        document.getElementById('requestModal').classList.remove('active');
        document.body.style.overflow = '';
    }

    window.onclick = function(event) {
        const modal = document.getElementById('requestModal');
        if (event.target === modal) {
            closeRequestModal();
        }
    }

    document.addEventListener("DOMContentLoaded", function() {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
</script>
@endsection