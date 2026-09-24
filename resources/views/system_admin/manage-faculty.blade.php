@extends('layouts.app')

@section('content')
<div class="admin-container">
    <div class="admin-header">
        <div class="header-titles">
            <h2>Faculty Management</h2>
            <p>Manage faculty accounts, assignments, and roster information.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="admin-alert-success">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    @if(isset($errors) && count($errors) > 0)
        <div class="admin-alert-danger" style="align-items: flex-start;">
            <i class="fas fa-exclamation-circle" style="margin-top: 3px;"></i>
            <div>
                <strong>Unable to process request:</strong>
                <ul style="margin: 4px 0 0 0; padding-left: 20px;">
                    @php
                        $allErrors = $errors->all();
                    @endphp
                    @for($i = 0; $i < count($allErrors); $i++)
                        <li>{{ $allErrors[$i] }}</li>
                    @endfor
                </ul>
            </div>
        </div>
    @endif

    <div id="js-alert-error" class="admin-alert-danger" style="display: none; align-items: flex-start; gap: 10px;">
        <i class="fas fa-exclamation-triangle" style="margin-top: 3px;"></i>
        <div>
            <strong>Unable to process request:</strong>
            <ul id="js-alert-list" style="margin: 4px 0 0 0; padding-left: 20px;"></ul>
        </div>
    </div>

    <div class="admin-workspace">
        <div class="admin-card form-panel">
            <div class="card-bar">
                <span id="form-panel-title"><i class="fas fa-user-plus"></i> Add Faculty Member</span>
            </div>

            <form action="{{ route('system_admin.manage-faculty.store') }}" method="POST" id="faculty-form" autocomplete="off" class="pure-form" novalidate>
                @csrf
                <input type="hidden" name="_method" id="form_method">
                <input type="hidden" name="faculty_id" id="form_faculty_id">

                <input type="hidden" name="first_name" id="hidden_first_name">
                <input type="hidden" name="last_name" id="hidden_last_name">
                <input type="hidden" name="middle_name" id="hidden_middle_name">
                <input type="hidden" name="sex" id="hidden_sex">
                <input type="hidden" name="email" id="hidden_email">

                <div class="form-group">
                    <label>First Name <span id="star_first_name" class="required-star">*</span></label>
                    <input type="text" name="first_name" id="first_name" placeholder="Please put the first name" value="{{ old('first_name') }}" required>
                </div>
                <div class="form-group">
                    <label>Last Name <span id="star_last_name" class="required-star">*</span></label>
                    <input type="text" name="last_name" id="last_name" placeholder="Please put the last name" value="{{ old('last_name') }}" required>
                </div>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Middle Name (Optional)</label>
                        <input type="text" name="middle_name" id="middle_name" placeholder="Middle name" value="{{ old('middle_name') }}">
                    </div>
                    <div class="form-group">
                        <label>Sex <span id="star_sex" class="required-star">*</span></label>
                        <div class="custom-select-wrapper" id="wrapper_sex">
                            <select name="sex" id="sex" class="custom-select-dropdown" required>
                                <option value="" disabled {{ old('sex') ? '' : 'selected' }} hidden>Select Sex</option>
                                <option value="Male" {{ old('sex') == 'Male' ? 'selected' : '' }}>Male</option>
                                <option value="Female" {{ old('sex') == 'Female' ? 'selected' : '' }}>Female</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Assigned Academic Department <span class="required-star">*</span></label>
                    <div class="custom-select-wrapper">
                        <select name="department" id="department" class="custom-select-dropdown" required>
                            <option value="College of Computer Studies" {{ old('department') == 'College of Computer Studies' ? 'selected' : '' }}>College of Computer Studies</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Faculty Role / Academic Position <span class="required-star">*</span></label>
                    <div class="custom-select-wrapper">
                        <select name="role" id="role" class="custom-select-dropdown" required>
                            <option value="" disabled {{ old('role') ? '' : 'selected' }} hidden>Select Position</option>
                            <option value="dean" {{ old('role') == 'dean' ? 'selected' : '' }}>Dean</option>
                            <option value="dept_sec" {{ old('role') == 'dept_sec' ? 'selected' : '' }}>Department Secretary</option>
                            <option value="professor" {{ old('role') == 'professor' ? 'selected' : '' }}>Professor</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Personal Email <span id="star_email" class="required-star">*</span></label>
                    <input type="email" name="email" id="email" placeholder="Please put the personal email" value="{{ old('email') }}" required>
                    <small class="field-help" id="email-help-text">A temporary default credential (Welcome@AU2026) will be generated automatically.</small>
                </div>
                
                <div class="form-actions-bar">
                    <button type="button" class="btn-clear" id="btn-reset-form" onclick="resetFacultyFormState()">Reset Form</button>
                    <button type="submit" id="btn-submit-action" class="btn-deploy-faculty">Save Faculty Member</button>
                </div>
            </form>
        </div>

        <div class="admin-card table-panel">
            <div class="card-header-flex">
                <div class="card-title-container">
                    <h3 class="card-title-list">Faculty Members Directory</h3>
                </div>
                <div class="header-controls-flex">
                    <div class="custom-select-wrapper" style="width: 140px;">
                        <select id="roleFilterSelect" class="form-select-custom" style="padding: 6px 30px 6px 12px; font-size: 12.5px;">
                            <option value="">All Roles</option>
                            <option value="dean">Dean</option>
                            <option value="professor">Professor</option>
                            <option value="department secretary">Department Secretary</option>
                        </select>
                    </div>

                    <div class="table-search-box">
                        <svg class="search-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/>
                            <line x1="16.2" y1="16.2" x2="21" y2="21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <input type="text" id="adminFacultySearch" placeholder="Search by Name, ID, or Email...">
                    </div>

                    <span class="badge-count" id="facultyTotalCount">
                        Total: {{ count($faculties) }}
                    </span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="minimal-table" id="facultyTable">
                    <thead>
                        <tr>
                            <th>Faculty Identity Details</th>
                            <th style="text-align: center;">Position / Role</th>
                            <th style="text-align: center;">Status</th>
                            <th style="text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(count($faculties) > 0)
                            @for($fIdx = 0; $fIdx < count($faculties);$fIdx++)
                                @php
                                    $faculty = $faculties[$fIdx];
                                @endphp
                                <tr class="faculty-row-item">
                                    <td>
                                        <strong class="faculty-id-text search-target-id">{{ $faculty->employee_id }}</strong>
                                        <br><span class="faculty-name-text search-target-name">{{ strtoupper($faculty->last_name) }}, {{ strtoupper($faculty->first_name) }}</span>
                                        <br><small class="faculty-email-text search-target-email">{{ $faculty->email }}</small>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="rank-badge {{ $faculty->role }} search-target-role">
                                            {{ $faculty->role === 'dept_sec' ? 'Department Secretary' : ($faculty->role === 'dean' ? 'Dean' : 'Professor') }}
                                        </span>
                                    </td>
                                    <td style="text-align: center;">
                                        <div class="status-indicator-center">
                                            <span class="pulse-dot-green"></span>
                                            <span class="status-label-text">Active</span>
                                        </div>
                                    </td>
                                    <td style="text-align: center;">
                                        <div class="action-btn-group">
                                            <button type="button" class="btn-action-edit btn-trigger-edit"
                                                data-id="{{ $faculty->id }}"
                                                data-firstname="{{ $faculty->first_name }}"
                                                data-lastname="{{ $faculty->last_name }}"
                                                data-middlename="{{ $faculty->middle_name }}"
                                                data-sex="{{ $faculty->sex }}"
                                                data-email="{{ $faculty->email }}"
                                                data-role="{{ $faculty->role }}"
                                                data-department="{{ $faculty->department }}">
                                                Edit
                                            </button>
                                            <button type="button" class="btn-action-delete"
                                                onclick="openDeleteFaculty({{ $faculty->id }}, '{{ strtoupper($faculty->last_name) }}, {{ strtoupper($faculty->first_name) }}')">
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endfor
                        @else
                            <tr class="static-empty-row">
                                <td colspan="4" class="empty-table-state">
                                    No faculty members recorded under the institutional scope yet.
                                </td>
                            </tr>
                        @endif
                        <tr id="facultyNoResults" style="display:none;">
                            <td colspan="4" class="empty-table-state" style="text-align: center; color: #94a3b8; padding: 40px 0;">
                                <i class="fas fa-database" style="font-size: 20px; display: block; margin-bottom: 8px;"></i>
                                No faculty members match your search/filter query.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div id="deleteFacultyModal" class="modal-overlay" style="display:none;">
    <div class="modal-box modal-box-sm">
        <div class="modal-header modal-header-danger">
            <h3><i class="fas fa-exclamation-triangle"></i> Confirm Deletion</h3>
            <button class="modal-close" type="button" onclick="closeModal('deleteFacultyModal')">&times;</button>
        </div>
        <div class="modal-body-text">
            <p>Are you sure you want to remove <strong id="deleteFacultyName"></strong> from the system? This action cannot be undone.</p>
        </div>
        <form id="deleteFacultyForm" method="POST">
            @csrf
            @method('DELETE')
            <div class="modal-actions">
                <button type="button" class="btn-modal-cancel" onclick="closeModal('deleteFacultyModal')">Cancel</button>
                <button type="submit" class="btn-modal-confirm-delete">Yes, Remove</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const existingFacultyList = @js($faculties->map(fn($f) => [
        'first_name' => strtolower($f->first_name),
        'last_name'  => strtolower($f->last_name),
        'email'      => strtolower($f->email),
        'role'       => strtolower($f->role)
    ]));

    const facultyForm = document.getElementById('faculty-form');
    const submitBtn = document.getElementById('btn-submit-action');
    const jsAlertError = document.getElementById('js-alert-error');
    const jsAlertList = document.getElementById('js-alert-list');

    if (facultyForm) {
        facultyForm.addEventListener('submit', function (e) {
            const facultyId = document.getElementById('form_faculty_id').value;
            let errorMessages = [];

            if (!facultyId) {
                const firstName = document.getElementById('first_name').value.trim();
                const lastName = document.getElementById('last_name').value.trim();
                const sex = document.getElementById('sex').value;
                const email = document.getElementById('email').value.trim();
                const department = document.getElementById('department').value;
                const role = document.getElementById('role').value;

                if (!firstName || !lastName || !sex || !department || !role || !email) {
                    errorMessages.push('Please fill out all required fields before submitting.');
                } else {
                    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailPattern.test(email)) {
                        errorMessages.push('Please enter a valid email address.');
                    }
                    const emailLower = email.toLowerCase();
                    if (existingFacultyList.some(f => f.email === emailLower)) {
                        errorMessages.push('This email address is already registered in the system.');
                    }
                }
            } else {
                const department = document.getElementById('department').value;
                const role = document.getElementById('role').value;
                if (!department || !role) {
                    errorMessages.push('Please select a department and role.');
                }
            }

            if (jsAlertError) jsAlertError.style.display = 'none';

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

    // Trigger Edit from Table row to Form Panel
    const editButtons = document.querySelectorAll('.btn-trigger-edit');
    editButtons.forEach(button => {
        button.addEventListener('click', function () {
            const id = this.getAttribute('data-id');
            const firstName = this.getAttribute('data-firstname');
            const lastName = this.getAttribute('data-lastname');
            const middleName = this.getAttribute('data-middlename');
            const sex = this.getAttribute('data-sex');
            const email = this.getAttribute('data-email');
            const role = this.getAttribute('data-role');
            const department = this.getAttribute('data-department');

            document.getElementById('form_faculty_id').value = id;
            
            // Fill values
            document.getElementById('first_name').value = firstName;
            document.getElementById('last_name').value = lastName;
            document.getElementById('middle_name').value = middleName && middleName !== 'null' ? middleName : '';
            document.getElementById('sex').value = sex;
            document.getElementById('email').value = email;
            document.getElementById('department').value = department;
            document.getElementById('role').value = role;

            // Fill hidden inputs
            document.getElementById('hidden_first_name').value = firstName;
            document.getElementById('hidden_last_name').value = lastName;
            document.getElementById('hidden_middle_name').value = middleName && middleName !== 'null' ? middleName : '';
            document.getElementById('hidden_sex').value = sex;
            document.getElementById('hidden_email').value = email;

            // Make fields Readonly/Disabled
            document.getElementById('first_name').setAttribute('readonly', true);
            document.getElementById('last_name').setAttribute('readonly', true);
            document.getElementById('middle_name').setAttribute('readonly', true);
            document.getElementById('sex').setAttribute('disabled', true);
            document.getElementById('email').setAttribute('readonly', true);

            // Add visual readonly styling class
            document.getElementById('first_name').classList.add('readonly-input');
            document.getElementById('last_name').classList.add('readonly-input');
            document.getElementById('middle_name').classList.add('readonly-input');
            document.getElementById('sex').classList.add('readonly-input');
            document.getElementById('wrapper_sex').classList.add('readonly-wrapper');
            document.getElementById('email').classList.add('readonly-input');

            // Hide required stars for readonly fields
            document.getElementById('star_first_name').style.display = 'none';
            document.getElementById('star_last_name').style.display = 'none';
            document.getElementById('star_sex').style.display = 'none';
            document.getElementById('star_email').style.display = 'none';

            // Update Form Action Route
            facultyForm.action = `/system_admin/manage-faculty/${id}`;
            document.getElementById('form_method').value = 'PATCH';

            // Change UI headers and button text
            document.getElementById('form-panel-title').innerHTML = '<i class="fas fa-user-edit"></i> Edit Faculty Member';
            document.getElementById('email-help-text').textContent = 'Identity details are locked and cannot be modified.';
            submitBtn.innerText = "Update Faculty Member";
            submitBtn.style.backgroundColor = "#dc2626";

            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });

    // Search & Role Filtering logic
    const searchInput = document.getElementById('adminFacultySearch');
    const roleFilterSelect = document.getElementById('roleFilterSelect');
    const tableRows = document.querySelectorAll('.faculty-row-item');
    const noResults = document.getElementById('facultyNoResults');
    const totalCountBadge = document.getElementById('facultyTotalCount');
    const initialTotal = tableRows.length;

    function filterFacultyTable() {
        const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
        const selectedRole = roleFilterSelect ? roleFilterSelect.value.toLowerCase().trim() : '';
        let visibleCount = 0;

        tableRows.forEach(row => {
            const empId = row.querySelector('.search-target-id').textContent.toLowerCase();
            const name = row.querySelector('.search-target-name').textContent.toLowerCase();
            const email = row.querySelector('.search-target-email').textContent.toLowerCase();
            const role = row.querySelector('.search-target-role').textContent.toLowerCase().trim();

            const matchesRole = (selectedRole === '' || role === selectedRole);
            const matchesSearch = (empId.includes(query) || name.includes(query) || email.includes(query) || role.includes(query));

            if (matchesRole && matchesSearch) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        if (totalCountBadge) {
            totalCountBadge.textContent = (query === '' && selectedRole === '') ? `Total: ${initialTotal}` : `Total: ${visibleCount}`;
        }

        if (noResults) {
            noResults.style.display = (visibleCount === 0 && initialTotal > 0) ? 'table-row' : 'none';
        }
    }

    if (searchInput) searchInput.addEventListener('input', filterFacultyTable);
    if (roleFilterSelect) roleFilterSelect.addEventListener('change', filterFacultyTable);
});

function resetFacultyFormState() {
    const facultyForm = document.getElementById('faculty-form');
    const submitBtn = document.getElementById('btn-submit-action');

    facultyForm.reset();

    document.getElementById('form_faculty_id').value = '';
    document.getElementById('form_method').value = '';
    facultyForm.action = "{{ route('system_admin.manage-faculty.store') }}";

    // Remove Readonly attributes
    document.getElementById('first_name').removeAttribute('readonly');
    document.getElementById('last_name').removeAttribute('readonly');
    document.getElementById('middle_name').removeAttribute('readonly');
    document.getElementById('sex').removeAttribute('disabled');
    document.getElementById('email').removeAttribute('readonly');

    // Remove visual readonly styling
    document.getElementById('first_name').classList.remove('readonly-input');
    document.getElementById('last_name').classList.remove('readonly-input');
    document.getElementById('middle_name').classList.remove('readonly-input');
    document.getElementById('sex').classList.remove('readonly-input');
    document.getElementById('wrapper_sex').classList.remove('readonly-wrapper');
    document.getElementById('email').classList.remove('readonly-input');

    // Restore required stars
    document.getElementById('star_first_name').style.display = '';
    document.getElementById('star_last_name').style.display = '';
    document.getElementById('star_sex').style.display = '';
    document.getElementById('star_email').style.display = '';

    document.getElementById('form-panel-title').innerHTML = '<i class="fas fa-user-plus"></i> Add Faculty Member';
    document.getElementById('email-help-text').textContent = 'A temporary default credential (Welcome@AU2026) will be generated automatically.';
    submitBtn.innerText = "Save Faculty Member";
    submitBtn.style.backgroundColor = "#1e2a5a";
}

function openDeleteFaculty(id, name) {
    document.getElementById('deleteFacultyName').textContent = name;
    
    let deleteUrl = "{{ route('system_admin.manage-faculty.destroy', ':id') }}";
    deleteUrl = deleteUrl.replace(':id', id);

    document.getElementById('deleteFacultyForm').action = deleteUrl;
    document.getElementById('deleteFacultyModal').style.display = 'flex';
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
    .admin-container { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #0f172a; max-width: 1350px; margin: 0 auto; padding: 20px 24px; }
    .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 2px solid #e2e8f0; padding-bottom: 18px; }
    .header-titles h2 { margin: 0 0 4px 0; font-size: 26px; font-weight: 800; color: #0f172a; letter-spacing: -0.5px; }
    .header-titles p { margin: 0; font-size: 14px; color: #64748b; }

    .required-star { color: #dc2626; font-weight: 700; }

    .admin-workspace { display: flex; flex-direction: row; gap: 24px; align-items: flex-start; justify-content: space-between; width: 100%; }
    .form-panel { flex: 0 0 380px; background: #ffffff; }
    .table-panel { flex: 1; background: #ffffff; min-width: 0; }

    .admin-card { border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 10px -2px rgba(0,0,0,0.03); overflow: hidden; transition: .2s ease; }
    .card-bar { background: #f8fafc; padding: 16px 20px; font-weight: 700; font-size: 14.5px; color: #1e293b; border-bottom: 1px solid #e2e8f0; }
    .card-header-flex { background-color: #f8fafc; padding: 16px 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; }
    .card-title-container { display: flex; align-items: center; gap: 12px; }
    .card-title-list { font-size: 14.5px; font-weight: 700; color: #1e293b; margin: 0; }
    .header-controls-flex { display: flex; align-items: center; gap: 12px; }

    /* EXACT SEARCH BOX & ICON STYLING FROM CURRICULUM MANAGEMENT */
    .table-search-box { position: relative; display: flex; align-items: center; }
    .table-search-box input { border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 12px 6px 32px; font-size: 12.5px; color: #0f172a; outline: none; background: #ffffff; width: 220px; transition: all 0.2s ease; font-weight: 500; box-sizing: border-box; }
    .table-search-box input:focus { border-color: #1e2a5a; width: 250px; box-shadow: 0 0 0 3px rgba(30, 42, 90, 0.08); }
    .table-search-box .search-icon { position: absolute; left: 12px; color: #94a3b8; width: 14px; height: 14px; pointer-events: none; }

    .pure-form { padding: 24px; display: flex; flex-direction: column; gap: 16px; }
    .form-group { display: flex; flex-direction: column; gap: 6px; }
    .form-group label { font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase; letter-spacing: 0.3px; }
    .form-group input { border: 1px solid #cbd5e1; border-radius: 6px; padding: 10px 14px; font-size: 14px; color: #0f172a; outline: none; background: #ffffff; box-sizing: border-box; width: 100%; }
    .form-group input:focus { border-color: #1e2a5a; box-shadow: 0 0 0 3px rgba(30, 42, 90, 0.08); }
    .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .field-help { font-size: 11px; color: #64748b; margin-top: 2px; }

    .form-actions-bar { display: flex; justify-content: flex-end; gap: 12px; margin-top: 12px; border-top: 1px solid #f1f5f9; padding-top: 16px; }
    .btn-deploy-faculty { 
        background: #1e2a5a; 
        color: #ffffff; 
        border: none; 
        padding: 12px 20px; 
        font-size: 14px; 
        font-weight: 700; 
        border-radius: 6px; 
        cursor: pointer; 
        flex: 1; 
        transition: background 0.2s; 
    }
    .btn-deploy-faculty:hover { background: #152238; }
    .btn-clear { background: transparent; color: #64748b; border: 1px solid #cbd5e1; padding: 12px 16px; font-size: 14px; font-weight: 600; border-radius: 6px; cursor: pointer; }
    .btn-clear:hover { background: #f8fafc; }

    .table-responsive { width: 100%; overflow-x: auto; }
    .minimal-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 13.5px; }
    .minimal-table th { background: #f8fafc; padding: 14px 16px; font-size: 11.5px; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; }
    .minimal-table td { padding: 15px 16px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
    .minimal-table tbody tr:hover { background-color: #f8fafc; }

    .faculty-id-text { font-family: ui-monospace, SFMono-Regular, Consolas, monospace; font-weight: 700; color: #0f172a; font-size: 13px; }
    .faculty-name-text { font-weight: 700; color: #0f172a; font-size: 14px; }
    .faculty-email-text { color: #64748b; font-size: 12px; }

    .rank-badge { display: inline-block; padding: 4px 10px; font-size: 11px; font-weight: 700; border-radius: 4px; text-transform: uppercase; border: 1px solid transparent; }
    .rank-badge.professor { background: #f1f5f9; color: #334155; border-color: #cbd5e1; }
    .rank-badge.dept_sec { background: #fdf2f8; color: #be185d; border-color: #fbcfe8; }
    .rank-badge.dean { background: #fff7ed; color: #c2410c; border-color: #ffedd5; }

    .empty-table-state { text-align: center; color: #94a3b8; padding: 50px 0 !important; font-style: italic; }

    .status-indicator-center { display: flex; align-items: center; justify-content: center; gap: 6px; }
    .pulse-dot-green { width: 8px; height: 8px; background: #22c55e; border-radius: 50%; }
    .status-label-text { font-size: 13px; color: #334155; font-weight: 500; }

    .action-btn-group { display: flex; gap: 6px; justify-content: center; }
    .btn-action-edit { background-color: transparent; border: 1px solid #cbd5e1; color: #475569; padding: 5px 12px; font-size: 12px; font-weight: 600; border-radius: 6px; cursor: pointer; transition: all 0.2s; }
    .btn-action-edit:hover { background-color: #f1f5f9; color: #1e293b; border-color: #94a3b8; }
    .btn-action-delete { background-color: transparent; border: 1px solid #fecaca; color: #dc2626; padding: 5px 12px; font-size: 12px; font-weight: 600; border-radius: 6px; cursor: pointer; transition: all 0.2s; }
    .btn-action-delete:hover { background-color: #fef2f2; color: #991b1b; border-color: #f87171; }

    .admin-alert-success { background: #ecfdf5; color: #065f46; padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; font-weight: 600; border-left: 5px solid #059669; font-size: 14px; display: flex; align-items: center; gap: 10px; }
    .admin-alert-danger { background: #fef2f2; color: #991b1b; padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; font-weight: 600; border-left: 5px solid #dc2626; font-size: 14px; display: flex; align-items: flex-start; gap: 10px; }
    .badge-count { background: #e2e8f0; color: #334155; font-size: 11.5px; padding: 4px 12px; border-radius: 20px; font-weight: 700; white-space: nowrap; }

    .custom-select-wrapper { position: relative; width: 100%; }
    .custom-select-dropdown, .form-select-custom { width: 100%; padding: 10px 40px 10px 14px; font-size: 14px; color: #0f172a; background-color: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; appearance: none; -webkit-appearance: none; cursor: pointer; outline: none; transition: all 0.2s ease; }
    .form-select-custom { padding: 6px 30px 6px 12px; font-size: 12.5px; font-weight: 500; }
    .custom-select-dropdown:focus, .form-select-custom:focus { border-color: #1e2a5a; box-shadow: 0 0 0 3px rgba(30, 42, 90, 0.08); }
    .custom-select-wrapper::after { content: "▼"; font-size: 0.65rem; color: #64748b; position: absolute; right: 14px; top: 50%; transform: translateY(-50%); pointer-events: none; }

    .readonly-input { background-color: #f1f5f9 !important; color: #64748b !important; border-color: #cbd5e1 !important; cursor: not-allowed !important; user-select: none; }
    .readonly-wrapper::after { color: #cbd5e1 !important; }

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
@endsection