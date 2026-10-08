@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="rg-page">
    <header class="rg-header">
        <div>
            <h2 class="rg-title">Subject Management & Registry</h2>
            <p class="rg-subtitle">Master course and subject directory management.</p>
        </div>
    </header>

    @if(session('success'))
        <div class="rg-alert rg-alert-success" role="status">
            <i class="fas fa-check-circle"></i> <span>{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="rg-alert rg-alert-error" role="alert">
            <i class="fas fa-exclamation-circle"></i>
            <div>
                <strong style="display:block; margin-bottom: 4px;">Unable to process request:</strong>
                <ul style="margin: 0; padding-left: 20px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div id="js-alert-error" class="rg-alert rg-alert-error" role="alert" style="display: none;">
        <i class="fas fa-exclamation-triangle"></i>
        <div>
            <strong style="display:block; margin-bottom: 4px;">Unable to process request:</strong>
            <ul id="js-alert-list" style="margin: 0; padding-left: 20px;"></ul>
        </div>
    </div>

    <div class="admin-workspace">
        <div class="rg-card form-panel">
            <div class="card-bar">
                <span id="form-panel-title"><i class="fas fa-book-medical"></i> Add New Subject</span>
            </div>

            <form id="subjectForm" action="{{ route('system_admin.subjects.store') }}" method="POST" autocomplete="off" class="pure-form" novalidate>
                @csrf
                <input type="hidden" name="_method" id="form_method">
                <input type="hidden" name="subject_id" id="form_subject_id">

                <div class="form-group">
                    <label>Subject Code <span class="required-star">*</span></label>
                    <input type="text" name="subject_code" id="form_subject_code" placeholder="e.g., COPRO1, GEC-RPH, NET101" class="rg-input uppercase-input" required>
                    <small style="color:var(--rg-muted); font-size:11px;">Enter code only (alphanumeric, dashes allowed).</small>
                </div>
                
                <div class="form-group">
                    <label>Subject Descriptive Title <span class="required-star">*</span></label>
                    <input type="text" name="subject_title" id="form_subject_title" placeholder="e.g., Computer Programming 1" class="rg-input" required>
                    <small style="color:var(--rg-muted); font-size:11px;">Enter descriptive title only (letters, spaces, and numbers).</small>
                </div>
                
                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Lecture Units <span class="required-star">*</span></label>
                        <input type="number" name="lecture_units" id="form_lecture_units" min="1" max="9" value="3" class="rg-input" required>
                    </div>
                    <div class="form-group">
                        <label>Laboratory Units <span class="required-star">*</span></label>
                        <input type="number" name="lab_units" id="form_lab_units" min="1" max="9" value="1" class="rg-input" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Assigned Professor <span class="required-star">*</span></label>
                    <select name="professor_id" id="form_professor_id" class="rg-select" required>
                        <option value="" disabled selected hidden>-- Select Professor --</option>
                        @if(isset($professors) && count($professors) > 0)
                            @foreach($professors as $prof)
                                <option value="{{ $prof->id }}">{{ strtoupper($prof->last_name) }}, {{$prof->first_name }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <div class="form-actions-bar">
                    <button type="button" class="rg-btn rg-btn-ghost" onclick="resetFormState()">Reset Form</button>
                    <button type="submit" id="btn-submit-action" class="rg-btn rg-btn-primary" style="flex: 1; justify-content: center;">Save Subject</button>
                </div>
            </form>
        </div>

        <div class="rg-card table-panel">
            <div class="card-bar">
                <span style="font-size: 15px;"><i class="fa-solid fa-folder-open" style="margin-right: 6px;"></i> Subject Directory</span>
            </div>

            <div class="rg-toolbar">
                <div class="rg-search">
                    <i class="fas fa-search"></i>
                    <input type="text" id="adminSubjectSearch" placeholder="Search by Code, Title, or Professor...">
                </div>

                <span class="rg-result-count" id="subjectTotalCount">
                    Total: {{ isset($subjects) ? count($subjects) : 0 }}
                </span>
            </div>

            <div class="rg-table-wrap">
                <table class="rg-table" id="subjectTable">
                    <thead>
                        <tr>
                            <th style="width: 110px;">Subject Code</th>
                            <th style="width: auto;">Subject Title</th>
                            <th style="width: 175px;">Assigned Faculty</th>
                            <th class="rg-text-center" style="width: 50px;">Lec</th>
                            <th class="rg-text-center" style="width: 50px;">Lab</th>
                            <th class="rg-text-center" style="width: 50px;">Total</th>
                            <th class="rg-text-center" style="width: 110px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="adminSubjectTableBody">
                        @if(isset($subjects) && count($subjects) > 0)
                            @foreach($subjects as $subject)
                                <tr class="subject-data-row">
                                    <td><span class="student-id-tag search-code">{{ strtoupper($subject->subject_code) }}</span></td>
                                    <td style="font-weight: 700; color: var(--rg-blue-dark);" class="search-title">{{ $subject->subject_title }}</td>

                                    <td class="search-prof">
                                        @if($subject->professor)
                                            <span class="rg-chip role-chip-professor">
                                                <i class="fas fa-user-tie"></i> {{ strtoupper($subject->professor->last_name) }}, {{$subject->professor->first_name }}
                                            </span>
                                        @elseif(isset($dean) &&$dean)
                                            <span class="rg-chip role-chip-dean">
                                                <i class="fas fa-user-tie"></i> {{ strtoupper($dean->last_name) }}, {{$dean->first_name }}
                                            </span>
                                        @else
                                            <span class="rg-chip role-chip-professor">
                                                <i class="fas fa-user-tie"></i> DE SANTA, MICHAEL
                                            </span>
                                        @endif
                                    </td>

                                    <td class="rg-text-center">{{ $subject->lecture_units }}</td>
                                    <td class="rg-text-center">{{ $subject->lab_units }}</td>
                                    <td class="rg-text-center">
                                        <strong style="color: var(--rg-ink);">{{ $subject->total_units ?? ($subject->lecture_units +$subject->lab_units) }}</strong>
                                    </td>
                                    <td class="rg-text-center">
                                        <div class="action-btn-group">
                                            <button type="button" class="rg-btn-action rg-btn-action-ghost btn-trigger-edit" data-id="{{ $subject->id }}">
                                                Edit
                                            </button>
                                            <button type="button" class="rg-btn-action rg-btn-action-danger" onclick="openDeleteSubject({{ $subject->id }}, '{{ addslashes($subject->subject_code) }}')">
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
                <div id="subjectNoResults" style="display:none; text-align:center; color:var(--rg-muted); padding:40px 0; font-style:italic; font-size:13.5px;">
                    <i class="fas fa-database" style="font-size: 20px; display: block; margin-bottom: 8px;"></i> No subjects match your search query.
                </div>
            </div>
        </div>
    </div>
</div>

<div id="deleteSubjectModal" class="rg-modal-overlay" style="display:none;">
    <div class="rg-modal-card" style="max-width: 420px;">
        <div class="rg-modal-header" style="background: #FEF2F2; color: #DC2626; border-bottom: 1px solid #FECACA;">
            <h3><i class="fas fa-exclamation-triangle"></i> Confirm Deletion</h3>
            <button type="button" class="rg-modal-close" onclick="closeModal('deleteSubjectModal')" style="color: #F87171;">&times;</button>
        </div>
        <div class="rg-modal-body" style="padding: 24px;">
            <p style="margin: 0 0 20px; font-size: 14px; line-height: 1.5; color: var(--rg-ink);">Are you sure you want to remove subject <strong id="deleteSubjectCode"></strong> from the catalog? This cannot be undone.</p>
            <form id="deleteSubjectForm" method="POST">
                @csrf
                @method('DELETE')
                <div style="display: flex; justify-content: flex-end; gap: 10px; padding-top: 16px; border-top: 1px solid var(--rg-line);">
                    <button type="button" class="rg-btn rg-btn-ghost" onclick="closeModal('deleteSubjectModal')">Cancel</button>
                    <button type="submit" class="rg-btn" style="background: #DC2626; color: #fff;">Yes, Remove</button>
                </div>
            </form>
        </div>
    </div>
</div>

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

                        document.getElementById('form-panel-title').innerHTML = '<i class="fas fa-book"></i> Edit Subject Record';
                        submitBtn.innerText = "Update Subject";
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

        document.getElementById('form-panel-title').innerHTML = '<i class="fas fa-book-medical"></i> Add New Subject';
        submitBtn.innerText = "Save Subject";
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

    document.querySelectorAll('.rg-modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function(e) {
            if (e.target === this) closeModal(this.id);
        });
    });
</script>

<style>
    .rg-page {
        --rg-blue: #0033A0; --rg-blue-dark: #0A1F5C; --rg-blue-tint: #EAF0FF; --rg-gold: #F2B705;
        --rg-ink: #0B1B3A; --rg-muted: #5C6B8A; --rg-line: #E2E8F3; --rg-surface: #F7F9FD;
        --rg-green: #15803D; --rg-green-tint: #E8F7EE; --rg-red: #C62828;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: var(--rg-ink);
        max-width: 1400px; margin: 0 auto; padding: 24px 20px 40px;
    }

    .rg-header { display: flex; justify-content: space-between; align-items: flex-end; gap: 20px; flex-wrap: wrap; padding-bottom: 18px; margin-bottom: 24px; border-bottom: 1px solid var(--rg-line); }
    .rg-title { margin: 0; font-size: 26px; font-weight: 800; letter-spacing: -0.2px; color: var(--rg-blue-dark); }
    .rg-subtitle { margin: 8px 0 0; font-size: 14px; color: var(--rg-muted); }

    .rg-alert { display: flex; gap: 10px; align-items: flex-start; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 13.5px; font-weight: 600; line-height: 1.45; }
    .rg-alert i { margin-top: 2px; }
    .rg-alert-success { background: var(--rg-green-tint); color: #14532D; border: 1px solid #BFE8CE; border-left: 4px solid var(--rg-green); }
    .rg-alert-error { background: #FDECEC; color: #8E1B1B; border: 1px solid #F5C6C6; border-left: 4px solid var(--rg-red); }

    .rg-card { background: #fff; border: 1px solid var(--rg-line); border-radius: 10px; overflow: hidden; box-shadow: 0 1px 2px rgba(10, 31, 92, 0.05); }
    .card-bar { background: var(--rg-surface); padding: 16px 20px; font-weight: 700; font-size: 14.5px; color: var(--rg-blue-dark); border-bottom: 1px solid var(--rg-line); display: flex; align-items: center; gap: 8px; }

    .admin-workspace { display: flex; flex-direction: row; gap: 24px; align-items: flex-start; justify-content: space-between; width: 100%; }
    .form-panel { flex: 0 0 380px; width: 380px; }
    .table-panel { flex: 1; min-width: 0; }

    @media (max-width: 1024px) {
        .admin-workspace { flex-direction: column; }
        .form-panel { flex: none; width: 100%; }
    }

    .pure-form { padding: 24px; display: flex; flex-direction: column; gap: 16px; }
    .form-group { display: flex; flex-direction: column; gap: 6px; }
    .form-group label { font-size: 12px; font-weight: 700; color: var(--rg-muted); text-transform: uppercase; letter-spacing: 0.3px; }
    .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    
    .required-star { color: var(--rg-red); }
    
    .rg-input, .rg-select {
        width: 100%; box-sizing: border-box; height: 40px; padding: 0 14px;
        font-size: 14px; font-weight: 500; color: var(--rg-ink); background: #fff;
        border: 1px solid #CBD5E6; border-radius: 6px; outline: none; transition: border-color 0.2s, box-shadow 0.2s;
    }
    .rg-input:focus, .rg-select:focus { border-color: var(--rg-blue); box-shadow: 0 0 0 3px rgba(0, 51, 160, 0.14); }
    .rg-select {
        padding-right: 36px; appearance: none; -webkit-appearance: none; cursor: pointer;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='2' stroke='%235C6B8A'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7' /%3E%3C/svg%3E");
        background-repeat: no-repeat; background-position: right 12px center; background-size: 16px;
    }
    .uppercase-input { text-transform: uppercase; }

    .form-actions-bar { display: flex; justify-content: flex-end; gap: 12px; margin-top: 10px; border-top: 1px solid var(--rg-line); padding-top: 20px; }
    
    .rg-btn { display: inline-flex; align-items: center; gap: 7px; padding: 10px 18px; border-radius: 6px; font-size: 13.5px; font-weight: 700; text-decoration: none; cursor: pointer; border: 1px solid transparent; transition: background .15s, color .15s, border-color .15s; white-space: nowrap; line-height: 1.2; }
    .rg-btn-primary { background: var(--rg-blue); color: #fff; }
    .rg-btn-primary:hover { background: var(--rg-blue-dark); color: #fff; }
    .rg-btn-ghost { background: transparent; color: var(--rg-muted); border-color: #CBD5E6; }
    .rg-btn-ghost:hover { background: var(--rg-surface); color: var(--rg-ink); }

    .rg-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 14px 18px; padding: 14px 20px; border-bottom: 1px solid var(--rg-line); background: #fff; }
    .rg-search { position: relative; flex: 1 1 280px; max-width: 420px; }
    .rg-search i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #8A97B3; font-size: 13px; pointer-events: none; }
    .rg-search input { width: 100%; box-sizing: border-box; padding: 10px 12px 10px 34px; font-size: 13.5px; color: var(--rg-ink); background: #fff; border: 1px solid #CBD5E6; border-radius: 8px; outline: none; }
    .rg-search input:focus { border-color: var(--rg-blue); box-shadow: 0 0 0 3px rgba(0, 51, 160, 0.14); }
    .rg-result-count { margin-left: auto; font-size: 12.5px; font-weight: 600; color: var(--rg-muted); background: var(--rg-line); padding: 4px 10px; border-radius: 12px; }

    .rg-table-wrap { overflow-x: auto; width: 100%; }
    .rg-table { width: 100%; min-width: 650px; border-collapse: collapse; font-size: 14px; }
    .rg-table th { background: var(--rg-surface); padding: 12px 18px; font-size: 11.5px; font-weight: 700; color: var(--rg-muted); text-transform: uppercase; text-align: left; border-bottom: 1px solid var(--rg-line); }
    .rg-table td { padding: 15px 18px; border-bottom: 1px solid #EEF2F9; color: var(--rg-ink); vertical-align: middle; }
    .rg-table tbody tr:last-child td { border-bottom: none; }
    .rg-table tbody tr:hover { background: #F5F8FF; }
    .rg-text-center { text-align: center !important; }

    .student-id-tag { font-family: ui-monospace, monospace; font-weight: 700; color: var(--rg-blue); background: var(--rg-blue-tint); padding: 3px 9px; border-radius: 6px; font-size: 12.5px; border: 1px solid #CFDBFB; display: inline-block; }

    .rg-chip { display: inline-block; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; white-space: nowrap; border: 1px solid transparent; }
    .role-chip-professor { background: #f0fdfa; color: #0d9488; border-color: #ccfbf1; }
    .role-chip-dean { background: #FFF7ED; color: #C2410C; border-color: #FFEDD5; }

    .action-btn-group { display: flex; gap: 6px; justify-content: center; }
    .rg-btn-action { border: 1px solid transparent; padding: 6px 12px; font-size: 12px; font-weight: 600; border-radius: 6px; cursor: pointer; transition: 0.15s; }
    .rg-btn-action-ghost { background: transparent; border-color: #CBD5E6; color: var(--rg-muted); }
    .rg-btn-action-ghost:hover { background: var(--rg-surface); color: var(--rg-ink); }
    .rg-btn-action-danger { background: transparent; border-color: #FECACA; color: var(--rg-red); }
    .rg-btn-action-danger:hover { background: #FEF2F2; color: #991B1B; border-color: #F87171; }

    .empty-table-state { text-align: center; color: var(--rg-muted) !important; padding: 50px 0 !important; font-style: italic; }

    .rg-modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(11, 27, 58, 0.6); z-index: 1000; display: flex; justify-content: center; align-items: center; padding: 20px; box-sizing: border-box; }
    .rg-modal-card { background: #fff; width: 100%; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.15); overflow: hidden; animation: fadeIn 0.2s ease-in-out; }
    .rg-modal-header { display: flex; justify-content: space-between; align-items: center; padding: 18px 24px; }
    .rg-modal-header h3 { margin: 0; font-size: 16px; font-weight: 700; display: flex; align-items: center; gap: 8px; }
    .rg-modal-close { background: transparent; border: none; font-size: 24px; cursor: pointer; transition: 0.15s; padding: 0; line-height: 1; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
</style>
@endsection