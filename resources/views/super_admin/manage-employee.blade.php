@extends('layouts.app')

@section('content')
<div class="sa-container">
    
    <div class="sa-header">
        <div class="header-titles">
            <h2>Employee Accounts</h2>
            <p>Create new staff accounts, assign system roles, and manage their access.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="sa-alert-success">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="sa-alert-error">
            <i class="fas fa-exclamation-triangle"></i> {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="sa-alert-error">
            <i class="fas fa-exclamation-circle" style="font-size: 16px;"></i>
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

    <div id="js-alert-error" class="sa-alert-error" style="display: none;">
        <i class="fas fa-exclamation-triangle"></i>
        <span id="js-alert-message">Please fill out all required fields before submitting.</span>
    </div>

    <div class="sa-workspace">
        
        <div class="sa-card form-panel">
            <div class="card-bar"><i class="fas fa-user-plus"></i> Add New Employee</div>
            
            <form action="{{ route('super_admin.employee.store') }}" method="POST" id="employee-form" autocomplete="off" class="pure-form" novalidate>
                @csrf
                
                <div class="form-group">
                    <label>First Name <span class="required-star">*</span></label>
                    <input type="text" name="first_name" id="first_name" placeholder="e.g., JUAN" value="{{ old('first_name') }}" required>
                </div>

                <div class="form-group">
                    <label>Last Name <span class="required-star">*</span></label>
                    <input type="text" name="last_name" id="last_name" placeholder="e.g., DELA CRUZ" value="{{ old('last_name') }}" required>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Middle Name (Optional)</label>
                        <input type="text" name="middle_name" id="middle_name" placeholder="e.g., REYES" value="{{ old('middle_name') }}">
                    </div>
                    <div class="form-group">
                        <label>Sex <span class="required-star">*</span></label>
                        <div class="custom-select-wrapper">
                            <select name="sex" id="sex" class="custom-select-dropdown" required>
                                <option value="" disabled {{ old('sex') ? '' : 'selected' }} hidden>Select Sex</option>
                                <option value="Male" {{ old('sex') == 'Male' ? 'selected' : '' }}>Male</option>
                                <option value="Female" {{ old('sex') == 'Female' ? 'selected' : '' }}>Female</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Email Address <span class="required-star">*</span></label>
                    <input type="email" name="email" id="email" placeholder="username@arellano.edu.ph" value="{{ old('email') }}" required>
                </div>

                <div class="form-group">
                    <label>Role <span class="required-star">*</span></label>
                    <div class="custom-select-wrapper">
                        <select name="role" id="role-select" class="custom-select-dropdown" required>
                            <option value="" disabled {{ old('role') ? '' : 'selected' }} hidden>Select Role</option>
                            <option value="system_admin" {{ old('role') == 'system_admin' ? 'selected' : '' }}>System Admin</option>
                            <option value="dean" {{ old('role') == 'dean' ? 'selected' : '' }}>Dean</option>
                            <option value="registrar" {{ old('role') == 'registrar' ? 'selected' : '' }}>Registrar</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" id="department-group" style="{{ old('role') == 'dean' ? 'display: flex;' : 'display: none;' }}">
                    <label>Department <span class="required-star">*</span></label>
                    <div class="custom-select-wrapper">
                        <select name="department" id="department-select" class="custom-select-dropdown">
                            <option value="" disabled {{ old('department') ? '' : 'selected' }} hidden>Select Department</option>
                            <option value="College of Computer Studies" {{ old('department') == 'College of Computer Studies' ? 'selected' : '' }}>College of Computer Studies</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn-generate-account">Create Account</button>
            </form>
        </div>

        {{-- TABLE PANEL WITH SEARCH FEATURE --}}
        <div class="sa-card table-panel">
            <div class="card-bar d-flex-jsb table-header-flex">
                <span>Employee List</span>
                <div class="table-controls" style="display: flex; align-items: center; gap: 12px;">
                    <div class="search-wrapper">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="personnel-search" placeholder="Search ID, name, email or role...">
                    </div>
                    <span class="count-tag">Total: {{ $employees->count() }}</span>
                </div>
            </div>
            
            <div class="table-wrap">
                <table class="sa-table" id="personnel-table">
                    <thead>
                        <tr>
                            <th>Employee Details</th>
                            <th>Role</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employees as $emp)
                            <tr class="personnel-row">
                                <td>
                                    <strong class="emp-id-text search-target-id">{{ $emp->employee_id }}</strong>
                                    <br><span class="emp-name-text search-target-name">{{ strtoupper($emp->last_name) }}, {{ strtoupper($emp->first_name) }}</span>
                                    
                                    @if($emp->department)
                                        <br><span class="emp-dept-text"><i class="fas fa-university"></i> {{ $emp->department }}</span>
                                    @endif
                                    
                                    <br><small class="emp-email-text search-target-email">{{ $emp->email }}</small>
                                </td>
                                <td>
                                    <span class="role-badge-sa {{ $emp->role }} search-target-role">
                                        {{ str_replace('_', ' ', strtoupper($emp->role)) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="status-indicator-zone" style="display: flex; align-items: center; justify-content: space-between; width: 100%; gap: 12px;">
                                        
                                        <div class="status-indicator">
                                            <span class="pulse-dot {{ strtolower($emp->status ?? 'Active') }}"></span>
                                            <span class="status-label">{{ ucfirst($emp->status ?? 'Active') }}</span>
                                        </div>

                                        @if(strtolower($emp->role) !== 'super_admin' && $emp->email !== session('user_email'))
                                            <form action="{{ route('super_admin.employees.toggle_status', $emp->id) }}" method="POST" style="margin: 0; display: inline-block;">
                                                @csrf
                                                @method('PATCH')
                                                
                                                @if(ucfirst($emp->status ?? 'Active') === 'Active')
                                                    <button type="submit" class="btn-action-restrict" onclick="return confirm('Are you sure you want to restrict this employee?')" title="Lock Account">
                                                        <i class="fas fa-user-slash"></i> Restrict
                                                    </button>
                                                @else
                                                    <button type="submit" class="btn-action-restore" onclick="return confirm('Are you sure you want to restore this employee?')" title="Unlock Account">
                                                        <i class="fas fa-user-check"></i> Restore
                                                    </button>
                                                @endif
                                            </form>
                                        @else
                                            <span class="badge-root-immunity">Protected</span>
                                        @endif

                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr class="static-empty-row">
                                <td colspan="3" class="empty-message-state">
                                    No employees added yet. Use the form to create an account.
                                </td>
                            </tr>
                        @endforelse
                        <tr id="no-search-results-row" style="display: none;">
                            <td colspan="3" class="empty-message-state" style="text-align: center; color: #94a3b8; padding: 40px 0;">
                                <i class="fas fa-database" style="font-size: 20px; display: block; margin-bottom: 8px;"></i>
                                No matching records found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const employeeForm = document.getElementById('employee-form');
        const roleSelect = document.getElementById('role-select');
        const departmentGroup = document.getElementById('department-group');
        const departmentSelect = document.getElementById('department-select');
        const jsAlertError = document.getElementById('js-alert-error');
        const jsAlertMessage = document.getElementById('js-alert-message');

        roleSelect.addEventListener('change', function () {
            if (this.value === 'dean') {
                departmentGroup.style.display = 'flex';
                departmentSelect.setAttribute('required', 'required');
            } else {
                departmentGroup.style.display = 'none';
                departmentSelect.removeAttribute('required');
                departmentSelect.value = '';
            }
        });

        employeeForm.addEventListener('submit', function (e) {
            const firstName = document.getElementById('first_name').value.trim();
            const lastName = document.getElementById('last_name').value.trim();
            const sex = document.getElementById('sex').value;
            const email = document.getElementById('email').value.trim();
            const role = roleSelect.value;
            const department = departmentSelect.value;

            jsAlertError.style.display = 'none';

            if (!firstName || !lastName || !sex || !email || !role) {
                e.preventDefault();
                showJsError('Please fill out all required fields before submitting.');
                return false;
            }

            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(email)) {
                e.preventDefault();
                showJsError('Please enter a valid email address.');
                return false;
            }

            if (role === 'dean' && !department) {
                e.preventDefault();
                showJsError('Please select a department for the Dean role.');
                return false;
            }
        });

        function showJsError(message) {
            jsAlertMessage.textContent = message;
            jsAlertError.style.display = 'flex';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        const searchInput = document.getElementById('personnel-search');
        const tableRows = document.querySelectorAll('.personnel-row');
        const noResultsRow = document.getElementById('no-search-results-row');

        searchInput.addEventListener('input', function () {
            const query = this.value.toLowerCase().trim();
            let visibleRecordsCount = 0;

            tableRows.forEach(row => {
                const empId = row.querySelector('.search-target-id').textContent.toLowerCase();
                const name = row.querySelector('.search-target-name').textContent.toLowerCase();
                const email = row.querySelector('.search-target-email').textContent.toLowerCase();
                const role = row.querySelector('.search-target-role').textContent.toLowerCase();

                if (empId.includes(query) || name.includes(query) || email.includes(query) || role.includes(query)) {
                    row.style.display = ''; 
                    visibleRecordsCount++;
                } else {
                    row.style.display = 'none'; 
                }
            });

            if (visibleRecordsCount === 0 && tableRows.length > 0) {
                noResultsRow.style.display = '';
            } else {
                noResultsRow.style.display = 'none';
            }
        });
    });
</script>

<style>
    .sa-container { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #0f172a; max-width: 1300px; margin: 0 auto; padding: 20px; }
    
    .sa-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 2px solid #e2e8f0; padding-bottom: 18px; }
    .header-titles h2 { margin: 0 0 4px 0; font-size: 28px; font-weight: 800; color: #0f172a; letter-spacing: -0.5px; }
    .header-titles p { margin: 0; font-size: 14.5px; color: #64748b; }

    .required-star { color: #dc2626; font-weight: 700; }

    .sa-workspace { display: flex; flex-direction: row; gap: 28px; align-items: flex-start; justify-content: space-between; width: 100%; }
    .form-panel { flex: 1; min-width: 380px; background: #ffffff; }
    .table-panel { flex: 1.5; background: #ffffff; }

    .sa-card { border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 10px -2px rgba(0, 0, 0, 0.03); overflow: hidden; }
    .card-bar { background: #f8fafc; padding: 16px 22px; font-weight: 700; font-size: 14.5px; color: #1e293b; border-bottom: 1px solid #e2e8f0; }
    
    .pure-form { padding: 24px; display: flex; flex-direction: column; gap: 16px; }
    .form-group { display: flex; flex-direction: column; gap: 6px; }
    .form-group label { font-size: 12px; font-weight: 600; color: #475569; }
    .form-group input { border: 1px solid #cbd5e1; border-radius: 6px; padding: 10px 14px; font-size: 14px; color: #0f172a; outline: none; background: #ffffff; }
    .form-group input:focus { border-color: #475569; box-shadow: 0 0 0 3px rgba(71, 85, 105, 0.15); }
    .form-grid-2 { display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 16px; }
    
    .btn-generate-account { background: #1e293b; color: #ffffff; border: none; padding: 14px; font-size: 14px; font-weight: 700; border-radius: 6px; cursor: pointer; margin-top: 12px; transition: background 0.2s; text-align: center; }
    .btn-generate-account:hover { background: #0f172a; }

    .table-wrap { width: 100%; overflow-x: auto; }
    .sa-table { width: 100%; border-collapse: collapse; text-align: left; }
    .sa-table th { background: #f8fafc; padding: 14px 20px; font-size: 11.5px; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; }
    .sa-table td { padding: 15px 20px; border-bottom: 1px solid #f1f5f9; font-size: 13.5px; vertical-align: middle; }
    
    .emp-id-text { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; color: #0f172a; font-size: 13px; font-weight: 700; }
    .emp-name-text { font-weight: 700; color: #1e293b; font-size: 13.5px; }
    .emp-dept-text { font-size: 12px; color: #0284c7; font-weight: 600; }
    .emp-email-text { color: #64748b; font-size: 12px; }

    .role-badge-sa { display: inline-block; padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: 700; letter-spacing: 0.3px; text-align: center; }
    .role-badge-sa.system_admin { background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; }
    .role-badge-sa.dean { background: #f0fdfa; color: #0d9488; border: 1px solid #ccfbf1; }
    .role-badge-sa.registrar { background: #eff6ff; color: #1e40af; border: 1px solid #dbeafe; }

    .status-indicator { display: flex; align-items: center; gap: 6px; }
    .pulse-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }
    .status-label { font-size: 13px; color: #334155; font-weight: 500; }

    .sa-alert-success { background: #ecfdf5; color: #065f46; padding: 14px 20px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; border-left: 5px solid #059669; font-size: 14px; display: flex; align-items: center; gap: 10px; }
    .sa-alert-error { background: #fef2f2; color: #991b1b; padding: 14px 20px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; border-left: 5px solid #dc2626; font-size: 14px; display: flex; align-items: flex-start; gap: 10px; }

    .d-flex-jsb { display: flex; justify-content: space-between; align-items: center; }
    .count-tag { background: #e2e8f0; color: #334155; font-size: 11.5px; padding: 4px 10px; border-radius: 5px; font-weight: 700; white-space: nowrap; }
    .empty-message-state { text-align: center; color: #94a3b8; padding: 50px 0; font-style: italic; font-size: 13.5px; }

    .custom-select-wrapper { position: relative; width: 100%; }
    .custom-select-dropdown { width: 100%; padding: 11px 40px 11px 14px; font-size: 14px; color: #0f172a; background-color: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; appearance: none; -webkit-appearance: none; -moz-appearance: none; cursor: pointer; outline: none; transition: all 0.2s ease; }
    .custom-select-dropdown:hover { border-color: #94a3b8; }
    .custom-select-dropdown:focus { border-color: #475569; box-shadow: 0 0 0 3px rgba(71, 85, 105, 0.15); }
    .custom-select-wrapper::after { content: "▼"; font-size: 0.65rem; color: #64748b; position: absolute; right: 14px; top: 50%; transform: translateY(-50%); pointer-events: none; }

    .pulse-dot.active { background: #22c55e; box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); animation: pulseGreen 2s infinite; }
    .pulse-dot.disabled { background: #dc2626; box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.7); animation: pulseRed 2s infinite; }

    .btn-action-restrict { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; padding: 6px 12px; border-radius: 6px; font-size: 11.5px; font-weight: 700; cursor: pointer; transition: all 0.2s ease; display: inline-flex; align-items: center; gap: 5px; }
    .btn-action-restrict:hover { background: #dc2626; color: #ffffff; border-color: #dc2626; }

    .btn-action-restore { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; padding: 6px 12px; border-radius: 6px; font-size: 11.5px; font-weight: 700; cursor: pointer; transition: all 0.2s ease; display: inline-flex; align-items: center; gap: 5px; }
    .btn-action-restore:hover { background: #16a34a; color: #ffffff; border-color: #16a34a; }

    .badge-root-immunity { font-size: 11px; color: #64748b; font-style: italic; font-weight: 700; background: #f1f5f9; padding: 5px 10px; border-radius: 6px; border: 1px solid #cbd5e1; text-transform: uppercase; letter-spacing: 0.3px; }

    .table-header-flex { display: flex; flex-direction: row; justify-content: space-between; align-items: center; gap: 15px; }
    .search-wrapper { position: relative; display: flex; align-items: center; }
    .search-icon { position: absolute; left: 12px; color: #94a3b8; font-size: 13px; pointer-events: none; }
    #personnel-search { border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 12px 6px 32px; font-size: 12.5px; color: #0f172a; outline: none; background: #ffffff; width: 240px; transition: all 0.2s ease; font-weight: 500; }
    #personnel-search:focus { border-color: #475569; width: 280px; box-shadow: 0 0 0 3px rgba(71, 85, 105, 0.1); }

    @keyframes pulseGreen {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(34, 197, 94, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
    }

    @keyframes pulseRed {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(220, 38, 38, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(220, 38, 38, 0); }
    }
</style>
@endsection