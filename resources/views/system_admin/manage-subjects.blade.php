@extends('layouts.app')

@section('content')
<div class="sa-container">

    <div class="sa-header">
        <div class="header-titles">
            <h2>Subject Management & Registry</h2>
            <p>Master course and subject directory management.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="sa-alert-success">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="sa-alert-danger" style="align-items: flex-start;">
            <i class="fas fa-exclamation-circle" style="margin-top: 3px;"></i>
            <div>
                <strong>Unable to process request:</strong>
                <ul style="margin: 4px 0 0 0; padding-left: 20px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div id="js-alert-error" class="sa-alert-danger" style="display: none; align-items: flex-start; gap: 10px;">
        <i class="fas fa-exclamation-triangle" style="margin-top: 3px;"></i>
        <div>
            <strong>Unable to process request:</strong>
            <ul id="js-alert-list" style="margin: 4px 0 0 0; padding-left: 20px;"></ul>
        </div>
    </div>

    <div class="sa-workspace">

        <div class="sa-card form-panel">
            <div class="card-bar">
                <span><i class="fas fa-book-medical"></i> Add New Subject</span>
            </div>

            <form id="subjectForm" action="{{ route('system_admin.subjects.store') }}" method="POST" autocomplete="off" class="pure-form" novalidate>
                @csrf

                <input type="hidden" name="_method" id="form_method">
                <input type="hidden" name="subject_id" id="form_subject_id">

                <div class="form-group">
                    <label>Subject Code <span class="required-star">*</span></label>
                    <input type="text" name="subject_code" id="form_subject_code" placeholder="e.g., COPRO1, GEC-RPH, NET101" class="uppercase-input" required>
                    <small class="field-help">Enter code only (alphanumeric, dashes allowed).</small>
                </div>
                <div class="form-group">
                    <label>Subject Descriptive Title <span class="required-star">*</span></label>
                    <input type="text" name="subject_title" id="form_subject_title" placeholder="e.g., Computer Programming 1" required>
                    <small class="field-help">Enter descriptive title only (letters, spaces, and numbers).</small>
                </div>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Lecture Units <span class="required-star">*</span></label>
                        <input type="number" name="lecture_units" id="form_lecture_units" min="1" max="9" value="3" required>
                    </div>
                    <div class="form-group">
                        <label>Laboratory Units <span class="required-star">*</span></label>
                        <input type="number" name="lab_units" id="form_lab_units" min="1" max="9" value="1" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Assigned Professor <span class="required-star">*</span></label>
                    <div class="custom-select-wrapper">
                        <select name="professor_id" id="form_professor_id" class="select-input" required>
                            <option value="">-- Select Professor --</option>
                            @if(isset($professors) && count($professors) > 0)
                                @foreach($professors as $prof)
                                    <option value="{{ $prof->id }}">{{ strtoupper($prof->last_name) }}, {{$prof->first_name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>

                <div class="form-actions-bar">
                    <button type="button" class="btn-clear" onclick="resetFormState()">Reset Form</button>
                    <button type="submit" id="btn-submit-action" class="btn-submit-subj">Save Subject</button>
                </div>
            </form>
        </div>

        <div class="sa-card table-panel">
            <div class="card-header-flex">

                <div class="card-title-container">
                    <h3 class="card-title-list">Subject Directory</h3>
                </div>

                <div class="header-controls-flex" style="display: flex; align-items: center; gap: 15px;">
                    <div class="table-search-box">
                        <svg class="search-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/>
                            <line x1="16.2" y1="16.2" x2="21" y2="21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <input
                            type="text"
                            id="adminSubjectSearch"
                            placeholder="Search by Code, Title, or Professor...">
                    </div>

                    <span class="badge-count" id="subjectTotalCount">
                        Total: {{ isset($subjects) ? count($subjects) : 0 }}
                    </span>
                </div>

            </div>

            <div class="table-responsive">
                <table class="minimal-table" id="subjectTable">
                    <thead>
                        <tr>
                            <th style="width: 110px;">Subject Code</th>
                            <th style="width: auto;">Subject Title</th>
                            <th style="width: 175px;">Assigned Faculty</th>
                            <th style="text-align: center; width: 50px;">Lec</th>
                            <th style="text-align: center; width: 50px;">Lab</th>
                            <th style="text-align: center; width: 50px;">Total</th>
                            <th style="text-align: center; width: 110px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="adminSubjectTableBody">
                        @if(isset($subjects) && count($subjects) > 0)
                            @foreach($subjects as $subject)
                                <tr class="subject-data-row">
                                    <td><span class="subject-code-tag search-code">{{ strtoupper($subject->subject_code) }}</span></td>
                                    <td class="subject-title-text search-title">{{ $subject->subject_title }}</td>

                                    <td class="search-prof">
                                        @if($subject->professor)
                                            <span class="prof-assigned-tag">
                                                <i class="fas fa-user-tie"></i> {{ strtoupper($subject->professor->last_name) }}, {{$subject->professor->first_name }}
                                            </span>
                                        @elseif(isset($dean) &&$dean)
                                            <span class="prof-assigned-tag">
                                                <i class="fas fa-user-tie"></i> {{ strtoupper($dean->last_name) }}, {{$dean->first_name }}
                                            </span>
                                        @else
                                            <span class="prof-assigned-tag">
                                                <i class="fas fa-user-tie"></i> DE SANTA, MICHAEL
                                            </span>
                                        @endif
                                    </td>

                                    <td style="text-align: center;">{{ $subject->lecture_units }}</td>
                                    <td style="text-align: center;">{{ $subject->lab_units }}</td>
                                    <td style="text-align: center;">
                                        <strong class="total-units-text">{{ $subject->total_units ?? ($subject->lecture_units +$subject->lab_units) }}</strong>
                                    </td>
                                    <td style="text-align: center;">
                                        <div class="action-btn-group">
                                            <button
                                                type="button"
                                                class="btn-action-edit btn-trigger-edit"
                                                data-id="{{ $subject->id }}">
                                                Edit
                                            </button>

                                            <button
                                                type="button"
                                                class="btn-action-delete"
                                                onclick="openDeleteSubject({{ $subject->id }}, '{{ addslashes($subject->subject_code) }}')">
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr id="emptyTableStateRow" class="static-empty-row">
                                <td colspan="7" class="empty-table-state">
                                    There's no registered subject found in the system records.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
                <div id="subjectNoResults" style="display:none; text-align:center; color:#94a3b8; padding:40px 0; font-style:italic; font-size:13.5px;">
                    <i class="fas fa-database" style="font-size: 20px; display: block; margin-bottom: 8px;"></i> No subjects match your search query.
                </div>
            </div>
        </div>

    </div>
</div>

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

<style>
    .sa-container { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #0f172a; max-width: 1350px; margin: 0 auto; padding: 20px 24px; }
    .sa-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 2px solid #e2e8f0; padding-bottom: 18px; }
    .header-titles h2 { margin: 0 0 4px 0; font-size: 26px; font-weight: 800; color: #0f172a; letter-spacing: -0.5px; }
    .header-titles p { margin: 0; font-size: 14px; color: #64748b; }

    .required-star { color: #dc2626; font-weight: 700; }

    .sa-workspace { display: flex; flex-direction: row; gap: 24px; align-items: flex-start; justify-content: space-between; width: 100%; }
    .form-panel { flex: 0 0 380px; background: #ffffff; }
    .table-panel { flex: 1; background: #ffffff; min-width: 0; }

    .sa-card { border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 10px -2px rgba(0,0,0,0.03); overflow: hidden; }
    .card-bar { background: #f8fafc; padding: 16px 20px; font-weight: 700; font-size: 14.5px; color: #1e293b; border-bottom: 1px solid #e2e8f0; }

    .card-header-flex { background-color: #f8fafc; padding: 16px 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; }
    .card-title-list { font-size: 14.5px; font-weight: 700; color: #1e293b; margin: 0; }
    .header-controls-flex { display: flex; align-items: center; gap: 16px; }

    .table-search-box { position: relative; display: flex; align-items: center; }
    .table-search-box input { border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 12px 6px 32px; font-size: 12.5px; color: #0f172a; outline: none; background: #ffffff; width: 220px; transition: all 0.2s ease; font-weight: 500; box-sizing: border-box; }
    .table-search-box input:focus { border-color: #1e2a5a; width: 250px; box-shadow: 0 0 0 3px rgba(30, 42, 90, 0.08); }
    .table-search-box .search-icon { position: absolute; left: 12px; color: #94a3b8; width: 14px; height: 14px; pointer-events: none; }

    .custom-select-wrapper { position: relative; width: 100%; }
    .custom-select-wrapper::after { content: "▼"; font-size: 0.65rem; color: #64748b; position: absolute; right: 14px; top: 50%; transform: translateY(-50%); pointer-events: none; }

    .pure-form { padding: 24px; display: flex; flex-direction: column; gap: 16px; }
    .form-group { display: flex; flex-direction: column; gap: 6px; }
    .form-group label { font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase; letter-spacing: 0.3px; }
    .form-group input, .select-input { border: 1px solid #cbd5e1; border-radius: 6px; padding: 10px 14px; font-size: 14px; color: #0f172a; outline: none; background: #ffffff; box-sizing: border-box; width: 100%; appearance: none; -webkit-appearance: none; }
    .form-group input:focus, .select-input:focus { border-color: #1e2a5a; box-shadow: 0 0 0 3px rgba(30, 42, 90, 0.08); }
    .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .uppercase-input { text-transform: uppercase; }
    .field-help { font-size: 11px; color: #64748b; margin-top: 2px; }

    .form-actions-bar { display: flex; justify-content: flex-end; gap: 12px; margin-top: 12px; border-top: 1px solid #f1f5f9; padding-top: 16px; }
    .btn-submit-subj { 
        background: #1e2a5a; 
        color: white; 
        border: none; 
        padding: 12px 20px; 
        font-size: 14px; 
        font-weight: 700; 
        border-radius: 6px; 
        cursor: pointer; 
        flex: 1; 
        transition: background 0.2s; 
    }
    .btn-submit-subj:hover { 
        background: #152238; 
    }
    .btn-clear { background: transparent; color: #64748b; border: 1px solid #cbd5e1; padding: 12px 16px; font-size: 14px; font-weight: 600; border-radius: 6px; cursor: pointer; }
    .btn-clear:hover { background: #f8fafc; }

    .table-responsive { width: 100%; overflow-x: auto; }
    .minimal-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 13.5px; table-layout: fixed; }
    .minimal-table th { background: #f8fafc; padding: 14px 16px; font-size: 11.5px; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .minimal-table td { padding: 15px 16px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; word-break: break-word; }
    .minimal-table tbody tr:hover { background-color: #f8fafc; }

    .subject-code-tag { font-family: ui-monospace, SFMono-Regular, Consolas, monospace; font-weight: 700; color: #0f172a; background-color: #f1f5f9; border: 1px solid #cbd5e1; padding: 4px 8px; border-radius: 6px; font-size: 12px; display: inline-block; white-space: nowrap; }
    .subject-title-text { font-weight: 700; color: #1e293b; }

    .prof-assigned-tag { font-size: 12px; font-weight: 600; color: #0d9488; background-color: #f0fdfa; border: 1px solid #ccfbf1; padding: 4px 10px; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%; }

    .total-units-text { color: #0f172a; font-weight: 700; }
    .empty-table-state { text-align: center; color: #94a3b8; padding: 50px 0 !important; font-style: italic; }

    .action-btn-group { display: flex; gap: 6px; justify-content: center; }
    .btn-action-edit { background-color: transparent; border: 1px solid #cbd5e1; color: #475569; padding: 5px 12px; font-size: 12px; font-weight: 600; border-radius: 6px; cursor: pointer; transition: all 0.2s; white-space: nowrap; }
    .btn-action-edit:hover { background-color: #f1f5f9; color: #1e293b; border-color: #94a3b8; }
    .btn-action-delete { background-color: transparent; border: 1px solid #fecaca; color: #dc2626; padding: 5px 12px; font-size: 12px; font-weight: 600; border-radius: 6px; cursor: pointer; transition: all 0.2s; white-space: nowrap; }
    .btn-action-delete:hover { background-color: #fef2f2; color: #991b1b; border-color: #f87171; }

    .sa-alert-success { background: #ecfdf5; color: #065f46; padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; font-weight: 600; border-left: 5px solid #059669; font-size: 14px; display: flex; align-items: center; gap: 10px; }
    .sa-alert-danger { background: #fef2f2; color: #991b1b; padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; font-weight: 600; border-left: 5px solid #dc2626; font-size: 14px; display: flex; align-items: flex-start; gap: 10px; }
    .badge-count { background: #e2e8f0; color: #334155; font-size: 11.5px; padding: 4px 12px; border-radius: 20px; font-weight: 700; white-space: nowrap; }

    .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.45); z-index: 9999; display: flex; align-items: center; justify-content: center; }
    .modal-box { background: #ffffff; border-radius: 14px; width: 100%; max-width: 500px; box-shadow: 0 20px 60px rgba(0,0,0,0.2); overflow: hidden; }
    .modal-box-sm { max-width: 420px; }
    .modal-header { display: flex; justify-content: space-between; align-items: center; padding: 18px 24px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
    .modal-header h3 { margin: 0; font-size: 15px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 8px; }
    .modal-header-danger { background: #fef2f2; border-bottom-color: #fecaca; }
    .modal-header-danger h3 { color: #dc2626; }
    .modal-close { background: none; border: none; font-size: 20px; color: #94a3b8; cursor: pointer; padding: 0; line-height: 1; }
    .modal-close:hover { color: #0f172a; }
    .modal-body-text { padding: 24px 24px 0 24px; font-size: 14px; color: #334155; line-height: 1.6; }
    .modal-actions { display: flex; justify-content: flex-end; gap: 10px; padding: 16px 24px; border-top: 1px solid #f1f5f9; }
    .btn-modal-cancel { background: transparent; border: 1px solid #cbd5e1; color: #64748b; padding: 9px 18px; font-size: 13px; font-weight: 600; border-radius: 6px; cursor: pointer; }
    .btn-modal-cancel:hover { background: #f8fafc; }
    .btn-modal-confirm-delete { background: #dc2626; color: #ffffff; border: none; padding: 9px 20px; font-size: 13px; font-weight: 700; border-radius: 6px; cursor: pointer; }
    .btn-modal-confirm-delete:hover { background: #b91c1c; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const existingSubjects = @json(
        isset($subjects) 
            ? $subjects->map(function($s) {
                return [
                    'code' => strtolower($s->subject_code),
                    'title' => strtolower($s->subject_title)
                ];
            }) 
            : []
    );

    const editButtons = document.querySelectorAll('.btn-trigger-edit');
    const formElement = document.getElementById('subjectForm');
    const submitBtn = document.getElementById('btn-submit-action');
    const jsAlertError = document.getElementById('js-alert-error');
    const jsAlertList = document.getElementById('js-alert-list');

    const searchInput = document.getElementById('adminSubjectSearch');
    const tableRows = document.querySelectorAll('.subject-data-row');
    const noResultsDiv = document.getElementById('subjectNoResults');
    const totalCountBadge = document.getElementById('subjectTotalCount');
    const initialTotal = tableRows.length;

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const query = searchInput.value.toLowerCase().trim();
            let visibleCount = 0;

            tableRows.forEach(row => {
                const code = row.querySelector('.search-code').textContent.toLowerCase();
                const title = row.querySelector('.search-title').textContent.toLowerCase();
                const prof = row.querySelector('.search-prof').textContent.toLowerCase();

                if (code.includes(query) || title.includes(query) || prof.includes(query)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (totalCountBadge) {
                totalCountBadge.textContent = query === '' ? `Total: ${initialTotal}` : `Total: ${visibleCount}`;
            }

            if (noResultsDiv) {
                noResultsDiv.style.display = (visibleCount === 0 && initialTotal > 0) ? 'block' : 'none';
            }
        });
    }

    if (formElement) {
        formElement.addEventListener('submit', function (e) {
            const subjectCode = document.getElementById('form_subject_code').value.trim();
            const subjectTitle = document.getElementById('form_subject_title').value.trim();
            const lecUnits = parseInt(document.getElementById('form_lecture_units').value, 10);
            const labUnits = parseInt(document.getElementById('form_lab_units').value, 10);
            const subjectId = document.getElementById('form_subject_id').value;

            let errorMessages = [];

            if (!subjectCode || !subjectTitle || isNaN(lecUnits) || isNaN(labUnits)) {
                errorMessages.push('Please fill out all required fields before submitting.');
            } else {
                const codePattern = /^[a-zA-Z0-9\-\s]+$/;
                if (!codePattern.test(subjectCode)) {
                    errorMessages.push('Subject Code must only contain letters, numbers, and hyphens.');
                }

                const titlePattern = /^[a-zA-Z0-9\s\-\(\)\.,]+$/;
                if (!titlePattern.test(subjectTitle)) {
                    errorMessages.push('Subject Descriptive Title must only contain valid title characters.');
                }

                if (lecUnits <= 0) {
                    errorMessages.push('Lecture Units cannot be 0 or negative. Please enter a valid number of units.');
                }

                if (labUnits <= 0) {
                    errorMessages.push('Laboratory Units cannot be 0 or negative. Please enter a valid number of units.');
                }

                const codeLower = subjectCode.toLowerCase();
                const titleLower = subjectTitle.toLowerCase();

                const isDuplicateCode = existingSubjects.some(s => s.code === codeLower);
                if (isDuplicateCode && !subjectId) {
                    errorMessages.push('This subject code is already registered in the system.');
                }

                const isDuplicateTitle = existingSubjects.some(s => s.title === titleLower);
                if (isDuplicateTitle && !subjectId) {
                    errorMessages.push('This subject descriptive title is already registered.');
                }
            }

            if (errorMessages.length > 0) {
                e.preventDefault();
                showJsErrors(errorMessages);
                return false;
            }
        });
    }

    function showJsErrors(messages) {
        if (jsAlertError && jsAlertList) {
            jsAlertList.innerHTML = '';
            messages.forEach(msg => {
                const li = document.createElement('li');
                li.textContent = msg;
                jsAlertList.appendChild(li);
            });
            jsAlertError.style.display = 'flex';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }

    editButtons.forEach(button => {
        button.addEventListener('click', function () {
            const id = this.getAttribute('data-id');

            let editUrl = "{{ route('admin.subject.edit', ':id') }}";
            editUrl = editUrl.replace(':id', id);

            fetch(editUrl)
                .then(response => {
                    if (!response.ok) throw new Error('Network query error');
                    return response.json();
                })
                .then(data => {
                    document.getElementById('form_subject_id').value = data.id;
                    document.getElementById('form_subject_code').value = data.subject_code;
                    document.getElementById('form_subject_title').value = data.subject_title;
                    document.getElementById('form_lecture_units').value = data.lecture_units;
                    document.getElementById('form_lab_units').value = data.lab_units;
                    document.getElementById('form_professor_id').value = data.professor_id ?? '';

                    let updateUrl = "{{ route('system_admin.subjects.update', ':id') }}";
                    updateUrl = updateUrl.replace(':id', data.id);

                    formElement.action = updateUrl;

                    document.getElementById('form_method').value = 'PATCH';

                    submitBtn.innerText = "Update Subject";
                    submitBtn.style.backgroundColor = "#dc2626"; 
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    alert('The details of the subject could not be retrieved.');
                });
        });
    });
});

function resetFormState() {
    const formElement = document.getElementById('subjectForm');
    const submitBtn = document.getElementById('btn-submit-action');

    formElement.reset();

    document.getElementById('form_subject_id').value = '';
    document.getElementById('form_method').value = '';
    formElement.action = "{{ route('system_admin.subjects.store') }}";

    submitBtn.innerText = "Save Subject";
    submitBtn.style.backgroundColor = "#1e2a5a";
}

function openDeleteSubject(id, code) {
    document.getElementById('deleteSubjectCode').textContent = code;

    let deleteUrl = "{{ route('system_admin.subjects.destroy', ':id') }}";
    deleteUrl = deleteUrl.replace(':id', id);

    document.getElementById('deleteSubjectForm').action = deleteUrl;
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
@endsection