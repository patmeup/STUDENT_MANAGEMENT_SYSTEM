@extends('layouts.app')

@section('content')
<div class="sa-container">
    
    <div class="sa-header">
        <div class="header-titles">
            <h2>Global Subject Encoder</h2>
            <p>Admin Control Panel — Curriculum Core Masterlist</p>
        </div>
       <div class="header-badge system-level">Admin Mode</div>
    </div>

    @if(session('success'))
        <div class="sa-alert-success">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="sa-alert-danger">
            <i class="fas fa-exclamation-circle"></i> {{ $errors->first() }}
        </div>
    @endif

    <div class="sa-workspace">

        <div class="sa-card form-panel">
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
                    <button type="submit" class="btn-submit-subj">Compile & Save</button>
                </div>
            </form>
        </div>

        <div class="sa-card table-panel">
            <div class="card-header-flex">
                <div class="card-title-container">
                    <h3 class="card-title-list">Global Subject Catalog</h3>
                    <span class="badge-count">{{ isset($subjects) ? $subjects->count() : 0 }} Total Subjects</span>
                </div>
                <div class="table-search-box">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="subjectSearchInput" placeholder="Search code or title...">
                </div>
            </div>

            <div class="table-responsive">
                <table class="minimal-table" id="subjectTable">
                    <thead>
                        <tr>
                            <th>Subject Code</th>
                            <th>Descriptive Title</th>
                            <th style="text-align: center;">Lect</th>
                            <th style="text-align: center;">Lab</th>
                            <th style="text-align: center;">Total</th>
                            <th style="text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($subjects) && $subjects->count() > 0)
                            @foreach($subjects as $subject)
                                <tr>
                                    <td><span class="subject-code-tag">{{ strtoupper($subject->subject_code) }}</span></td>
                                    <td class="subject-title-text">{{ $subject->subject_title }}</td>
                                    <td style="text-align: center;">{{ $subject->lecture_units }}</td>
                                    <td style="text-align: center;">{{ $subject->lab_units }}</td>
                                    <td style="text-align: center;">
                                        <strong class="total-units-text">{{ $subject->total_units ?? ($subject->lecture_units + $subject->lab_units) }}</strong>
                                    </td>
                                    <td style="text-align: center;">
                                        <div class="action-btn-group">
                                            <button class="btn-action-edit"
                                                onclick="openEditSubject(
                                                    {{ $subject->id }},
                                                    '{{ addslashes($subject->subject_code) }}',
                                                    '{{ addslashes($subject->subject_title) }}',
                                                    {{ $subject->lecture_units }},
                                                    {{ $subject->lab_units }}
                                                )">Edit</button>
                                            <button class="btn-action-delete"
                                                onclick="openDeleteSubject({{ $subject->id }}, '{{ addslashes($subject->subject_code) }}')">Delete</button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="6" class="empty-table-state">
                                    There's no registered subject found in the system records.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
                <div id="subjectNoResults" style="display:none; text-align:center; color:#94a3b8; padding:40px; font-style:italic; font-size:13.5px;">
                    No subjects match your search.
                </div>
            </div>
        </div>

    </div>
</div>

{{-- ===================== EDIT MODAL ===================== --}}
<div id="editSubjectModal" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fas fa-book-open"></i> Edit Subject Record</h3>
            <button class="modal-close" onclick="closeModal('editSubjectModal')">&times;</button>
        </div>
        <form id="editSubjectForm" method="POST" class="modal-form">
            @csrf
            @method('PATCH')
            <div class="form-group">
                <label>Subject Code / Acronym</label>
                <input type="text" name="subject_code" id="edit_subject_code" class="uppercase-input" required>
            </div>
            <div class="form-group">
                <label>Subject Descriptive Title</label>
                <input type="text" name="subject_title" id="edit_subject_title" required>
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label>Lecture Units</label>
                    <input type="number" name="lecture_units" id="edit_lecture_units" min="0" max="9" required>
                </div>
                <div class="form-group">
                    <label>Laboratory Units</label>
                    <input type="number" name="lab_units" id="edit_lab_units" min="0" max="9" required>
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-modal-cancel" onclick="closeModal('editSubjectModal')">Cancel</button>
                <button type="submit" class="btn-modal-confirm-edit">Save Changes</button>
            </div>
        </form>
    </div>
</div>

{{-- ===================== DELETE MODAL ===================== --}}
<div id="deleteSubjectModal" class="modal-overlay" style="display:none;">
    <div class="modal-box modal-box-sm">
        <div class="modal-header modal-header-danger">
            <h3><i class="fas fa-exclamation-triangle"></i> Confirm Deletion</h3>
            <button class="modal-close" onclick="closeModal('deleteSubjectModal')">&times;</button>
        </div>
        <div class="modal-body-text">
            <p>Are you sure you want to remove subject <strong id="deleteSubjectCode"></strong> from the catalog? This cannot be undone.</p>
        </div>
        <form id="deleteSubjectForm" method="POST">
            @csrf
            @method('DELETE')
            <div class="modal-actions">
                <button type="button" class="btn-modal-cancel" onclick="closeModal('deleteSubjectModal')">Cancel</button>
                <button type="submit" class="btn-modal-confirm-delete">Yes, Remove</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('subjectSearchInput');
    const tableRows   = document.querySelectorAll('#subjectTable tbody tr');
    const noResults   = document.getElementById('subjectNoResults');

    if (searchInput) {
        searchInput.addEventListener('keyup', function () {
            const term = searchInput.value.toLowerCase().trim();
            let visibleCount = 0;
            tableRows.forEach(row => {
                if (row.querySelector('.empty-table-state')) return;
                const match = row.textContent.toLowerCase().includes(term);
                row.style.display = match ? '' : 'none';
                if (match) visibleCount++;
            });
            noResults.style.display = visibleCount === 0 && term !== '' ? 'block' : 'none';
        });
    }
});

function openEditSubject(id, code, title, lectureUnits, labUnits) {
    document.getElementById('edit_subject_code').value    = code;
    document.getElementById('edit_subject_title').value   = title;
    document.getElementById('edit_lecture_units').value   = lectureUnits;
    document.getElementById('edit_lab_units').value       = labUnits;
    document.getElementById('editSubjectForm').action     = `/system_admin/manage-subjects/${id}`;
    document.getElementById('editSubjectModal').style.display = 'flex';
}

function openDeleteSubject(id, code) {
    document.getElementById('deleteSubjectCode').textContent = code;
    document.getElementById('deleteSubjectForm').action = `/system_admin/manage-subjects/${id}`;
    document.getElementById('deleteSubjectModal').style.display = 'flex';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) closeModal(this.id);
    });
});
</script>

<style>
    .sa-container { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #0f172a; max-width: 1300px; margin: 0 auto; padding: 20px; }
    .sa-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 2px solid #e2e8f0; padding-bottom: 18px; }
    .header-titles h2 { margin: 0 0 4px 0; font-size: 28px; font-weight: 800; color: #0f172a; letter-spacing: -0.5px; }
    .header-titles p { margin: 0; font-size: 14.5px; color: #64748b; }
    .system-level { background-color: #dc2626; color: #ffffff; padding: 6px 14px; border-radius: 20px; font-weight: 700; font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.5px; }

    .sa-workspace { display: flex; flex-direction: row; gap: 28px; align-items: flex-start; width: 100%; }
    .form-panel { flex: 1; min-width: 380px; background: #ffffff; }
    .table-panel { flex: 1.5; background: #ffffff; }

    .sa-card { border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 10px -2px rgba(0,0,0,0.03); overflow: hidden; }
    .card-bar { background: #f8fafc; padding: 16px 22px; font-weight: 700; font-size: 14.5px; color: #1e293b; border-bottom: 1px solid #e2e8f0; }
    .card-header-flex { background-color: #f8fafc; padding: 16px 22px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; }
    .card-title-container { display: flex; align-items: center; gap: 12px; }
    .card-title-list { font-size: 14.5px; font-weight: 700; color: #1e293b; margin: 0; }

    .table-search-box { position: relative; max-width: 260px; width: 100%; }
    .table-search-box input { width: 100%; padding: 8px 12px 8px 36px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 6px; outline: none; color: #0f172a; box-sizing: border-box; transition: all 0.2s ease; }
    .table-search-box input:focus { border-color: #0033A0; box-shadow: 0 0 0 3px rgba(0,51,160,0.1); }
    .table-search-box .search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 13px; pointer-events: none; }

    .pure-form { padding: 24px; display: flex; flex-direction: column; gap: 16px; }
    .form-group { display: flex; flex-direction: column; gap: 6px; }
    .form-group label { font-size: 12px; font-weight: 600; color: #475569; }
    .form-group input { border: 1px solid #cbd5e1; border-radius: 6px; padding: 10px 14px; font-size: 14px; color: #0f172a; outline: none; background: #ffffff; box-sizing: border-box; width: 100%; }
    .form-group input:focus { border-color: #0033A0; box-shadow: 0 0 0 3px rgba(0,51,160,0.15); }
    .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .uppercase-input { text-transform: uppercase; }
    .field-help { font-size: 11px; color: #94a3b8; margin-top: 2px; }

    .form-actions-bar { display: flex; justify-content: flex-end; gap: 12px; margin-top: 12px; border-top: 1px solid #f1f5f9; padding-top: 16px; }
    .btn-submit-subj { background: #0033A0; color: #ffffff; border: none; padding: 10px 20px; font-size: 14px; font-weight: 700; border-radius: 6px; cursor: pointer; }
    .btn-submit-subj:hover { background: #002270; }
    .btn-clear { background: transparent; color: #64748b; border: 1px solid #cbd5e1; padding: 10px 16px; font-size: 14px; font-weight: 600; border-radius: 6px; cursor: pointer; }
    .btn-clear:hover { background: #f8fafc; }

    .table-responsive { width: 100%; overflow-x: auto; }
    .minimal-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 13.5px; }
    .minimal-table th { background: #f8fafc; padding: 14px 20px; font-size: 11.5px; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; }
    .minimal-table td { padding: 15px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
    .minimal-table tbody tr:hover { background-color: #f8fafc; }

    .subject-code-tag { font-family: ui-monospace, SFMono-Regular, Consolas, monospace; font-weight: 700; color: #2563eb; background-color: #eff6ff; padding: 4px 8px; border-radius: 6px; font-size: 12px; display: inline-block; }
    .subject-title-text { font-weight: 600; color: #1e293b; }
    .total-units-text { color: #0f172a; font-weight: 700; }
    .empty-table-state { text-align: center; color: #94a3b8; padding: 50px 0 !important; font-style: italic; font-size: 13.5px; }

    .action-btn-group { display: flex; gap: 6px; justify-content: center; }
    .btn-action-edit { background-color: transparent; border: 1px solid #cbd5e1; color: #475569; padding: 5px 12px; font-size: 12px; font-weight: 600; border-radius: 6px; cursor: pointer; transition: all 0.2s; }
    .btn-action-edit:hover { background-color: #f1f5f9; color: #1e293b; border-color: #94a3b8; }
    .btn-action-delete { background-color: transparent; border: 1px solid #fecaca; color: #dc2626; padding: 5px 12px; font-size: 12px; font-weight: 600; border-radius: 6px; cursor: pointer; transition: all 0.2s; }
    .btn-action-delete:hover { background-color: #fef2f2; color: #991b1b; border-color: #f87171; }

    .sa-alert-success { background: #ecfdf5; color: #065f46; padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; font-weight: 600; border-left: 5px solid #059669; font-size: 14px; }
    .sa-alert-danger { background: #fef2f2; color: #991b1b; padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; font-weight: 600; border-left: 5px solid #dc2626; font-size: 14px; }
    .badge-count { background: #e2e8f0; color: #334155; font-size: 11.5px; padding: 4px 12px; border-radius: 20px; font-weight: 700; }

    /* ===== MODAL STYLES ===== */
    .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.45); z-index: 9999; display: flex; align-items: center; justify-content: center; }
    .modal-box { background: #ffffff; border-radius: 14px; width: 100%; max-width: 500px; box-shadow: 0 20px 60px rgba(0,0,0,0.2); overflow: hidden; }
    .modal-box-sm { max-width: 420px; }
    .modal-header { display: flex; justify-content: space-between; align-items: center; padding: 18px 24px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
    .modal-header h3 { margin: 0; font-size: 15px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 8px; }
    .modal-header-danger { background: #fef2f2; border-bottom-color: #fecaca; }
    .modal-header-danger h3 { color: #dc2626; }
    .modal-close { background: none; border: none; font-size: 20px; color: #94a3b8; cursor: pointer; padding: 0; line-height: 1; }
    .modal-close:hover { color: #0f172a; }
    .modal-form { padding: 24px; display: flex; flex-direction: column; gap: 14px; }
    .modal-body-text { padding: 24px 24px 0 24px; font-size: 14px; color: #334155; line-height: 1.6; }
    .modal-actions { display: flex; justify-content: flex-end; gap: 10px; padding: 16px 24px; border-top: 1px solid #f1f5f9; }
    .btn-modal-cancel { background: transparent; border: 1px solid #cbd5e1; color: #64748b; padding: 9px 18px; font-size: 13px; font-weight: 600; border-radius: 6px; cursor: pointer; }
    .btn-modal-cancel:hover { background: #f8fafc; }
    .btn-modal-confirm-edit { background: #0033A0; color: #ffffff; border: none; padding: 9px 20px; font-size: 13px; font-weight: 700; border-radius: 6px; cursor: pointer; }
    .btn-modal-confirm-edit:hover { background: #002270; }
    .btn-modal-confirm-delete { background: #dc2626; color: #ffffff; border: none; padding: 9px 20px; font-size: 13px; font-weight: 700; border-radius: 6px; cursor: pointer; }
    .btn-modal-confirm-delete:hover { background: #b91c1c; }
</style>
@endsection
