@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="rg-page">
    <header class="rg-header">
        <div>
            <h2 class="rg-title">Faculty Management</h2>
            <p class="rg-subtitle">Manage faculty accounts, assignments, and roster information.</p>
        </div>
    </header>

    @if(session('success'))
        <div class="rg-alert rg-alert-success" role="status">
            <i class="fas fa-check-circle"></i> <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(isset($errors) && count($errors) > 0)
        <div class="rg-alert rg-alert-error" role="alert">
            <i class="fas fa-exclamation-circle"></i>
            <div>
                <strong style="display:block; margin-bottom: 4px;">Unable to process request:</strong>
                <ul style="margin: 0; padding-left: 20px;">
                    @php
                        $allErrors =$errors->all();
                    @endphp
                    @for($i = 0; $i < count($allErrors);$i++)
                        <li>{{ $allErrors[$i] }}</li>
                    @endfor
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
                    <input type="text" name="first_name" id="first_name" class="rg-input" placeholder="Please put the first name" value="{{ old('first_name') }}" required>
                </div>
                
                <div class="form-group">
                    <label>Last Name <span id="star_last_name" class="required-star">*</span></label>
                    <input type="text" name="last_name" id="last_name" class="rg-input" placeholder="Please put the last name" value="{{ old('last_name') }}" required>
                </div>
                
                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Middle Name <small style="color:var(--rg-muted); text-transform:none; font-weight:normal;">(Optional)</small></label>
                        <input type="text" name="middle_name" id="middle_name" class="rg-input" placeholder="Middle name" value="{{ old('middle_name') }}">
                    </div>
                    <div class="form-group">
                        <label>Sex <span id="star_sex" class="required-star">*</span></label>
                        <select name="sex" id="sex" class="rg-select" required>
                            <option value="" disabled {{ old('sex') ? '' : 'selected' }} hidden>Select Sex</option>
                            <option value="Male" {{ old('sex') == 'Male' ? 'selected' : '' }}>Male</option>
                            <option value="Female" {{ old('sex') == 'Female' ? 'selected' : '' }}>Female</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Assigned Academic Department <span class="required-star">*</span></label>
                    <select name="department" id="department" class="rg-select" required>
                        <option value="College of Computer Studies" {{ old('department') == 'College of Computer Studies' ? 'selected' : '' }}>College of Computer Studies</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Faculty Role / Position <span class="required-star">*</span></label>
                    <select name="role" id="role" class="rg-select" required>
                        <option value="" disabled {{ old('role') ? '' : 'selected' }} hidden>Select Position</option>
                        <option value="dean" {{ old('role') == 'dean' ? 'selected' : '' }}>Dean</option>
                        <option value="dept_sec" {{ old('role') == 'dept_sec' ? 'selected' : '' }}>Department Secretary</option>
                        <option value="professor" {{ old('role') == 'professor' ? 'selected' : '' }}>Professor</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Personal Email <span id="star_email" class="required-star">*</span></label>
                    <input type="email" name="email" id="email" class="rg-input" placeholder="Please put the personal email" value="{{ old('email') }}" required>
                </div>
                
                <div class="form-actions-bar">
                    <button type="button" class="rg-btn rg-btn-ghost" id="btn-reset-form" onclick="resetFacultyFormState()">Reset</button>
                    <button type="submit" id="btn-submit-action" class="rg-btn rg-btn-primary" style="flex: 1; justify-content: center;">Save Faculty Member</button>
                </div>
            </form>
        </div>

        <div class="rg-card table-panel">
            <div class="card-bar">
                <span style="font-size: 15px;"><i class="fa-solid fa-folder-open" style="margin-right: 6px;"></i> Faculty Members Directory</span>
            </div>

            <div class="rg-toolbar">
                <div class="rg-search">
                    <i class="fas fa-search"></i>
                    <input type="text" id="adminFacultySearch" placeholder="Search by Name, ID, or Email...">
                </div>

                <div class="rg-filter" role="group" aria-label="Filter by role">
                    <span class="rg-filter-label">Role</span>
                    <button type="button" class="filter-pill active" data-role="">All</button>
                    <button type="button" class="filter-pill" data-role="dean">Dean</button>
                    <button type="button" class="filter-pill" data-role="professor">Professor</button>
                    <button type="button" class="filter-pill" data-role="department secretary">Dept. Sec</button>
                </div>

                <span class="rg-result-count" id="facultyTotalCount">{{ count($faculties) }} faculties</span>
            </div>

            <div class="rg-table-wrap">
                <table class="rg-table" id="facultyTable">
                    <thead>
                        <tr>
                            <th style="width: 38%;">Faculty Identity Details</th>
                            <th class="rg-text-center" style="width: 25%;">Position / Role</th>
                            <th class="rg-text-center" style="width: 15%;">Status</th>
                            <th class="rg-text-center" style="width: 22%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(count($faculties) > 0)
                            @foreach($faculties as $faculty)
                                <tr class="faculty-row-item">
                                    <td>
                                        <div class="faculty-id-tag search-target-id">{{ $faculty->employee_id }}</div>
                                        <strong style="color: var(--rg-blue-dark); text-transform: uppercase; display: block; margin-top: 6px;" class="search-target-name">{{ $faculty->last_name }}, {{$faculty->first_name }}</strong>
                                        <div style="font-size: 12.5px; color: var(--rg-muted); margin-top: 2px;" class="search-target-email">{{ $faculty->email }}</div>
                                    </td>
                                    <td class="rg-text-center">
                                        <span class="rg-chip role-chip-{{ $faculty->role }} search-target-role">
                                            {{ $faculty->role === 'dept_sec' ? 'Department Secretary' : ($faculty->role === 'dean' ? 'Dean' : 'Professor') }}
                                        </span>
                                    </td>
                                    <td class="rg-text-center">
                                        <div class="status-indicator-center">
                                            <span class="pulse-dot-green"></span>
                                            <span class="status-label-text">Active</span>
                                        </div>
                                    </td>
                                    <td class="rg-text-center">
                                        <div class="action-btn-group">
                                            <button type="button" class="rg-btn-action rg-btn-action-ghost btn-trigger-edit"
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
                                            <button type="button" class="rg-btn-action rg-btn-action-danger"
                                                onclick="openDeleteFaculty({{ $faculty->id }}, '{{ strtoupper($faculty->last_name) }}, {{ strtoupper($faculty->first_name) }}')">
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr class="static-empty-row">
                                <td colspan="4" class="empty-table-state">
                                    No faculty members recorded under the institutional scope yet.
                                </td>
                            </tr>
                        @endif
                        <tr id="facultyNoResults" style="display:none;">
                            <td colspan="4" class="empty-table-state" style="text-align: center; color: var(--rg-muted); padding: 40px 0;">
                                <i class="fas fa-database" style="font-size: 24px; display: block; margin-bottom: 8px; color: #cbd5e1;"></i>
                                No faculty members match your search/filter query.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div id="deleteFacultyModal" class="rg-modal-overlay" style="display:none;">
    <div class="rg-modal-card" style="max-width: 420px;">
        <div class="rg-modal-header" style="background: #FEF2F2; color: #DC2626; border-bottom: 1px solid #FECACA;">
            <h3><i class="fas fa-exclamation-triangle"></i> Confirm Deletion</h3>
            <button type="button" class="rg-modal-close" onclick="closeModal('deleteFacultyModal')" style="color: #F87171;">&times;</button>
        </div>
        <div class="rg-modal-body" style="padding: 24px;">
            <p style="margin: 0 0 20px; font-size: 14px; line-height: 1.5; color: var(--rg-ink);">Are you sure you want to remove <strong id="deleteFacultyName"></strong> from the system? This action cannot be undone.</p>
            <form id="deleteFacultyForm" method="POST">
                @csrf
                @method('DELETE')
                <div style="display: flex; justify-content: flex-end; gap: 10px; padding-top: 16px; border-top: 1px solid var(--rg-line);">
                    <button type="button" class="rg-btn rg-btn-ghost" onclick="closeModal('deleteFacultyModal')">Cancel</button>
                    <button type="submit" class="rg-btn" style="background: #DC2626; color: #fff;">Yes, Remove</button>
                </div>
            </form>
        </div>
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
                
                document.getElementById('first_name').value = firstName;
                document.getElementById('last_name').value = lastName;
                document.getElementById('middle_name').value = middleName && middleName !== 'null' ? middleName : '';
                document.getElementById('sex').value = sex;
                document.getElementById('email').value = email;
                document.getElementById('department').value = department;
                document.getElementById('role').value = role;

                document.getElementById('hidden_first_name').value = firstName;
                document.getElementById('hidden_last_name').value = lastName;
                document.getElementById('hidden_middle_name').value = middleName && middleName !== 'null' ? middleName : '';
                document.getElementById('hidden_sex').value = sex;
                document.getElementById('hidden_email').value = email;

                document.getElementById('first_name').setAttribute('readonly', true);
                document.getElementById('last_name').setAttribute('readonly', true);
                document.getElementById('middle_name').setAttribute('readonly', true);
                document.getElementById('sex').setAttribute('disabled', true);
                document.getElementById('email').setAttribute('readonly', true);

                document.getElementById('first_name').classList.add('readonly-input');
                document.getElementById('last_name').classList.add('readonly-input');
                document.getElementById('middle_name').classList.add('readonly-input');
                document.getElementById('sex').classList.add('readonly-input');
                document.getElementById('email').classList.add('readonly-input');

                document.getElementById('star_first_name').style.display = 'none';
                document.getElementById('star_last_name').style.display = 'none';
                document.getElementById('star_sex').style.display = 'none';
                document.getElementById('star_email').style.display = 'none';

                facultyForm.action = `/system_admin/manage-faculty/${id}`;
                document.getElementById('form_method').value = 'PATCH';

                document.getElementById('form-panel-title').innerHTML = '<i class="fas fa-user-edit"></i> Edit Faculty Member';
                submitBtn.innerText = "Update Faculty Member";

                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        });

        const searchInput = document.getElementById('adminFacultySearch');
        const filterPills = document.querySelectorAll('.filter-pill');
        const tableRows = document.querySelectorAll('.faculty-row-item');
        const noResults = document.getElementById('facultyNoResults');
        const totalCountBadge = document.getElementById('facultyTotalCount');
        const initialTotal = tableRows.length;
        let currentRoleFilter = '';

        filterPills.forEach(pill => {
            pill.addEventListener('click', function() {
                filterPills.forEach(p => p.classList.remove('active'));
                this.classList.add('active');
                currentRoleFilter = this.getAttribute('data-role');
                filterFacultyTable();
            });
        });

        function filterFacultyTable() {
            const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
            const selectedRole = currentRoleFilter.toLowerCase().trim();
            let visibleCount = 0;

            tableRows.forEach(row => {
                const empId = row.querySelector('.search-target-id').textContent.toLowerCase();
                const name = row.querySelector('.search-target-name').textContent.toLowerCase();
                const email = row.querySelector('.search-target-email').textContent.toLowerCase();
                const role = row.querySelector('.search-target-role').textContent.toLowerCase().trim();

                const matchesRole = (selectedRole === '' || role === selectedRole || (selectedRole === 'department secretary' && role === 'department secretary'));
                const matchesSearch = (empId.includes(query) || name.includes(query) || email.includes(query) || role.includes(query));

                if (matchesRole && matchesSearch) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (totalCountBadge) {
                totalCountBadge.textContent = visibleCount + (visibleCount <= 1 ? ' faculty' : ' faculties');
            }

            if (noResults) {
                noResults.style.display = (visibleCount === 0 && initialTotal > 0) ? 'table-row' : 'none';
            }
        }

        if (searchInput) searchInput.addEventListener('input', filterFacultyTable);
    });

    function resetFacultyFormState() {
        const facultyForm = document.getElementById('faculty-form');
        const submitBtn = document.getElementById('btn-submit-action');

        facultyForm.reset();

        document.getElementById('form_faculty_id').value = '';
        document.getElementById('form_method').value = '';
        facultyForm.action = "{{ route('system_admin.manage-faculty.store') }}";

        document.getElementById('first_name').removeAttribute('readonly');
        document.getElementById('last_name').removeAttribute('readonly');
        document.getElementById('middle_name').removeAttribute('readonly');
        document.getElementById('sex').removeAttribute('disabled');
        document.getElementById('email').removeAttribute('readonly');

        document.getElementById('first_name').classList.remove('readonly-input');
        document.getElementById('last_name').classList.remove('readonly-input');
        document.getElementById('middle_name').classList.remove('readonly-input');
        document.getElementById('sex').classList.remove('readonly-input');
        document.getElementById('email').classList.remove('readonly-input');

        document.getElementById('star_first_name').style.display = '';
        document.getElementById('star_last_name').style.display = '';
        document.getElementById('star_sex').style.display = '';
        document.getElementById('star_email').style.display = '';

        document.getElementById('form-panel-title').innerHTML = '<i class="fas fa-user-plus"></i> Add Faculty Member';
        submitBtn.innerText = "Save Faculty Member";
        submitBtn.style.backgroundColor = "var(--rg-blue)";
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
    .readonly-input { background-color: var(--rg-surface) !important; color: var(--rg-muted) !important; cursor: not-allowed !important; user-select: none; }

    .form-actions-bar { display: flex; justify-content: flex-end; gap: 12px; margin-top: 10px; border-top: 1px solid var(--rg-line); padding-top: 20px; }
    
    .rg-btn { display: inline-flex; align-items: center; gap: 7px; padding: 10px 18px; border-radius: 6px; font-size: 13.5px; font-weight: 700; text-decoration: none; cursor: pointer; border: 1px solid transparent; transition: background .15s, color .15s, border-color .15s; white-space: nowrap; line-height: 1.2; }
    .rg-btn-primary { 
        background: var(--rg-blue); 
        color: #fff; 
    }
    .rg-btn-primary:hover { 
        background: var(--rg-blue-dark); 
        color: #fff; 
    }
    .rg-btn-ghost { background: transparent; color: var(--rg-muted); border-color: #CBD5E6; }
    .rg-btn-ghost:hover { background: var(--rg-surface); color: var(--rg-ink); }

    .rg-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 14px 18px; padding: 14px 20px; border-bottom: 1px solid var(--rg-line); background: #fff; }
    
    .rg-search { position: relative; flex: 1 1 280px; max-width: 420px; }
    .rg-search i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #8A97B3; font-size: 13px; pointer-events: none; }
    .rg-search input { width: 100%; box-sizing: border-box; padding: 10px 12px 10px 34px; font-size: 13.5px; color: var(--rg-ink); background: #fff; border: 1px solid #CBD5E6; border-radius: 8px; }
    .rg-search input::placeholder { color: #8A97B3; }
    .rg-search input:focus { outline: none; border-color: var(--rg-blue); box-shadow: 0 0 0 3px rgba(0, 51, 160, 0.14); }

    .rg-filter { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
    .rg-filter-label { font-size: 12.5px; font-weight: 600; color: var(--rg-muted); margin-right: 4px; }
    .filter-pill { background: #fff; border: 1px solid #CBD5E6; color: var(--rg-muted); padding: 5px 12px; font-size: 12.5px; font-weight: 600; border-radius: 16px; cursor: pointer; transition: background .15s, color .15s, border-color .15s; }
    .filter-pill:hover { border-color: var(--rg-blue); color: var(--rg-blue); }
    .filter-pill.active { background: var(--rg-blue); border-color: var(--rg-blue); color: #fff; }

    .rg-result-count { margin-left: auto; font-size: 12.5px; font-weight: 600; color: var(--rg-muted); }

    .rg-table-wrap { overflow-x: auto; width: 100%; }
    .rg-table { width: 100%; min-width: 650px; border-collapse: collapse; font-size: 14px; }
    .rg-table th { background: var(--rg-surface); padding: 12px 18px; font-size: 11.5px; font-weight: 700; color: var(--rg-muted); text-transform: uppercase; text-align: left; border-bottom: 1px solid var(--rg-line); }
    .rg-table td { padding: 15px 18px; border-bottom: 1px solid #EEF2F9; color: var(--rg-ink); vertical-align: middle; }
    .rg-table tbody tr:last-child td { border-bottom: none; }
    .rg-table tbody tr:hover { background: #F5F8FF; }
    .rg-text-center { text-align: center !important; }

    .student-id-tag { font-family: ui-monospace, monospace; font-weight: 700; color: var(--rg-blue); background: var(--rg-blue-tint); padding: 3px 9px; border-radius: 6px; font-size: 12.5px; border: 1px solid #CFDBFB; display: inline-block; }

    .rg-chip { display: inline-block; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; white-space: nowrap; border: 1px solid transparent; }
    .role-chip-professor { background: var(--rg-surface); color: var(--rg-muted); border-color: var(--rg-line); }
    .role-chip-dept_sec { background: #FDF2F8; color: #BE185D; border-color: #FBCFE8; }
    .role-chip-dean { background: #FFF7ED; color: #C2410C; border-color: #FFEDD5; }

    .status-indicator-center { display: flex; align-items: center; justify-content: center; gap: 6px; }
    .pulse-dot-green { width: 8px; height: 8px; background: var(--rg-green); border-radius: 50%; }
    .status-label-text { font-size: 13px; color: var(--rg-ink); font-weight: 600; }

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