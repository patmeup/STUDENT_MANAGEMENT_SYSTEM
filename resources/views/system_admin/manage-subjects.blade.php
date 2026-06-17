@extends('layouts.app')

@section('content')
<div class="subj-container">

    <div class="subj-header">
        <div class="header-titles">
            <h2>Global Subject Encoder</h2>
            <p>Admin Control Panel — Curriculum Core Masterlist</p>
        </div>
        <div class="header-badge">Admin Mode</div>
    </div>

    @if(session('success'))
        <div class="alert-success-custom">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    <div class="subj-card">
        <div class="card-bar">
            <span><i class="fas fa-book-medical"></i> Subject Specifications Form</span>
        </div>
        
        <form action="{{ route('system_admin.subjects.store') }}" method="POST" autocomplete="off" class="pure-form">
            @csrf
            
            <div class="form-group">
                <label>Subject Code / Acronym</label>
                <input type="text" name="subject_code" placeholder="e.g., COPRO1, GEC-RPH, NET101" class="uppercase-input" required>
                <small class="field-help">This field must be completely unique inside the global database system.</small>
            </div>

            <div class="form-group">
                <label>Subject Descriptive Title</label>
                <input type="text" name="subject_title" placeholder="e.g., Computer Programming 1 (Contextualized)" required>
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label>Lecture Units Credit</label>
                    <input type="number" name="lecture_units" min="0" max="9" value="3" required>
                </div>
                <div class="form-group">
                    <label>Laboratory Units Credit</label>
                    <input type="number" name="lab_units" min="0" max="9" value="0" required>
                </div>
            </div>

            <div class="form-actions-bar">
                <button type="reset" class="btn-clear">Reset Form</button>
                <button type="submit" class="btn-submit-subj">Compile & Save to Global List</button>
            </div>
        </form>
    </div>

    <div class="subject-list-card">
        <div class="card-header-flex">
            <h3 class="card-title-list">Global Subject Catalog</h3>
            <span class="badge-count">{{ count($subjects) }} Total Subjects</span>
        </div>

        <div class="table-responsive">
            <table class="minimal-table">
                <thead>
                    <tr>
                        <th>Subject Code</th>
                        <th>Descriptive Title</th>
                        <th style="text-align: center;">Lect. Units</th>
                        <th style="text-align: center;">Lab. Units</th>
                        <th style="text-align: center;">Total Units</th>
                        <th style="text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($subjects as $subject)
                        <tr>
                            <td><span class="subject-code-tag">{{ $subject->subject_code }}</span></td>
                            <td class="subject-title-text">{{ $subject->subject_title }}</td>
                            <td style="text-align: center;">{{ $subject->lecture_units }}</td>
                            <td style="text-align: center;">{{ $subject->lab_units }}</td>
                            <td style="text-align: center;">
                                <strong class="total-units-text">{{ $subject->total_units }}</strong>
                            </td>
                            <td style="text-align: center;">
                                <button class="btn-action-edit" title="Edit Subject">
                                    Edit
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-table-state">
                                There's no registered subject found in the system records.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<style>
    .subj-container {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: #1e293b;
        max-width: 900px;
        margin: 0 auto;
        padding-bottom: 50px;
    }

    .subj-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        border-bottom: 2px solid #e2e8f0;
        padding-bottom: 15px;
    }
    .header-titles h2 { margin: 0 0 5px 0; font-size: 26px; font-weight: 700; color: #0f172a; }
    .header-titles p { margin: 0; font-size: 14px; color: #64748b; }
    .header-badge { background-color: #dc2626; color: white; padding: 6px 14px; border-radius: 20px; font-weight: 600; font-size: 12px; }

    .subj-card {
        background: white;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        overflow: hidden;
        margin-bottom: 30px;
    }
    .card-bar {
        background: #ffffff;
        padding: 18px 24px;
        font-weight: 700;
        font-size: 15px;
        color: #0f172a;
        border-bottom: 1px solid #f1f5f9;
    }

    .pure-form { padding: 24px; display: flex; flex-direction: column; gap: 20px; }
    .form-group { display: flex; flex-direction: column; gap: 6px; }
    .form-group label { font-size: 13px; font-weight: 600; color: #475569; }
    
    .form-group input {
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 12px;
        font-size: 14px;
        outline: none;
        box-sizing: border-box;
        width: 100%;
        color: #0f172a;
        background: #fff;
    }
    .form-group input:focus {
        border-color: #0033A0;
        box-shadow: 0 0 0 3px rgba(0, 51, 160, 0.1);
    }
    
    .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .uppercase-input { text-transform: uppercase; }
    .field-help { font-size: 11px; color: #94a3b8; margin-top: 2px; }

    .form-actions-bar {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        margin-top: 15px;
        border-top: 1px solid #f1f5f9;
        padding-top: 20px;
    }
    .btn-submit-subj {
        background: #0033A0;
        color: white;
        border: none;
        padding: 12px 24px;
        font-size: 14px;
        font-weight: 700;
        border-radius: 6px;
        cursor: pointer;
    }
    .btn-submit-subj:hover { background: #002270; }
    
    .btn-clear {
        background: transparent;
        color: #64748b;
        border: 1px solid #cbd5e1;
        padding: 12px 20px;
        font-size: 14px;
        font-weight: 600;
        border-radius: 6px;
        cursor: pointer;
    }
    .btn-clear:hover { background: #f8fafc; color: #334155; }

    .alert-success-custom {
        background: #d1e7dd;
        color: #0f5132;
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 25px;
        font-weight: 600;
        border-left: 5px solid #0f5132;
        font-size: 14px;
    }

    .subject-list-card {
        background-color: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.02);
        overflow: hidden;
    }
    .card-header-flex {
        background-color: #f8fafc;
        padding: 18px 24px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .card-title-list {
        font-size: 15px;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
    }
    .badge-count {
        background-color: #e2e8f0;
        color: #334155;
        font-size: 0.75rem;
        font-weight: 700;
        padding: 4px 12px;
        border-radius: 9999px;
    }
    .table-responsive {
        width: 100%;
        overflow-x: auto;
    }
    .minimal-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
        font-size: 0.9rem;
    }
    .minimal-table th {
        background-color: #f8fafc;
        color: #64748b;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.05em;
        padding: 14px 24px;
        border-bottom: 1px solid #e2e8f0;
    }
    .minimal-table td {
        padding: 14px 24px;
        border-bottom: 1px solid #f1f5f9;
        color: #334155;
        vertical-align: middle;
    }
    .minimal-table tbody tr:hover {
        background-color: #f8fafc;
    }
    .subject-code-tag {
        font-family: ui-monospace, SFMono-Regular, Consolas, monospace;
        font-weight: 700;
        color: #2563eb;
        background-color: #eff6ff;
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 0.85rem;
    }
    .subject-title-text {
        font-weight: 500;
        color: #1e293b;
    }
    .total-units-text {
        color: #0f172a;
        font-weight: 700;
    }
    .empty-table-state {
        text-align: center;
        color: #64748b;
        padding: 40px !important;
        font-style: italic;
    }
    .btn-action-edit {
        background-color: transparent;
        border: 1px solid #cbd5e1;
        color: #475569;
        padding: 5px 12px;
        font-size: 0.8rem;
        font-weight: 600;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s;
    }
    .btn-action-edit:hover {
        background-color: #f1f5f9;
        color: #1e293b;
        border-color: #94a3b8;
    }
</style>
@endsection