@extends('layouts.app')

@section('content')
<div class="sa-container">
    <div class="sa-header">
        <div class="header-titles">
            <h2>Root Personnel Infrastructure</h2>
            <p>Super Admin Console — System Identity & Access Management (SIAM)</p>
        </div>
        <div class="header-badge root-level">Root Authorization</div>
    </div>

    @if(session('success'))
        <div class="sa-alert-success">
            <i class="fas fa-shield-check"></i> {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="sa-alert-error" style="background: #fef2f2; color: #991b1b; padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; font-weight: 600; border-left: 5px solid #dc2626; font-size: 14px;">
            <i class="fas fa-exclamation-triangle"></i> {{ session('error') }}
        </div>
    @endif

    <div class="sa-workspace">
        {{-- FORM PANEL --}}
        <div class="sa-card form-panel">
            <div class="card-bar"><i class="fas fa-user-plus"></i> Grant System Access Clearance</div>
            <form action="{{ route('super_admin.employee.store') }}" method="POST" autocomplete="off" class="pure-form">
                @csrf
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="first_name" placeholder="e.g., JUAN" required>
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" placeholder="e.g., DELA CRUZ" required>
                </div>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Middle Name (Optional)</label>
                        <input type="text" name="middle_name" placeholder="e.g., REYES">
                    </div>
                    <div class="form-group">
                        <label>Sex</label>
                        <div class="custom-select-wrapper">
                            <select name="sex" class="custom-select-dropdown" required>
                                <option value="" disabled selected hidden>Select Sex</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Official Institutional Email</label>
                    <input type="email" name="email" placeholder="username@arellano.edu.ph" required>
                </div>
                <div class="form-group">
                    <label>Designated Operational Role</label>
                    <div class="custom-select-wrapper">
                        <select name="role" id="role-select" class="custom-select-dropdown" required>
                            <option value="" disabled selected hidden>Select System Tier Authorization</option>
                            <option value="system_admin">System Admin</option>
                            <option value="dean">Dean</option>
                            <option value="registrar">Registrar</option>
                        </select>
                    </div>
                </div>
                <div class="form-group" id="department-group" style="display: none;">
                    <label>Assigned Academic Department</label>
                    <div class="custom-select-wrapper">
                        <select name="department" id="department-select" class="custom-select-dropdown">
                            <option value="" disabled selected hidden>Select College Department</option>
                            <option value="College of Computer Studies">College of Computer Studies</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn-generate-account">Deploy Personnel Account</button>
            </form>
        </div>

        {{-- TABLE PANEL WITH SEARCH FEATURE --}}
        <div class="sa-card table-panel">
            <div class="card-bar d-flex-jsb table-header-flex">
                <span>Active Infrastructure Personnel</span>
                <div class="table-controls" style="display: flex; align-items: center; gap: 12px;">
                    {{-- Embedded Live Search Bar --}}
                    <div class="search-wrapper">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="personnel-search" placeholder="Query ID, name, email or role...">
                    </div>
                    <span class="count-tag">Total Provisioned: {{ $employees->count() }}</span>
                </div>
            </div>
            <div class="table-wrap">
                <table class="sa-table" id="personnel-table">
                    <thead>
                        <tr>
                            <th>Identified Personnel</th>
                            <th>Role / Clearance</th>
                            <th>Network Status</th>
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
                                            <form action="{{ route('super_admin.employees.toggle_status', $emp->id) }}" method="POST" style="margin: 0; display: inline-block;" id="action-form-{{ $emp->id }}">
                                                @csrf
                                                @method('PATCH')
                                                @if(ucfirst($emp->status ?? 'Active') === 'Active')
                                                    <button type="button" class="btn-action-restrict trigger-restrict-modal" 
                                                        data-action-url="{{ route('super_admin.employees.toggle_status', $emp->id) }}"
                                                        data-emp-name="{{ strtoupper($emp->last_name) }}, {{ strtoupper($emp->first_name) }}"
                                                        title="Lock Account">
                                                        <i class="fas fa-user-slash"></i> Restrict
                                                    </button>
                                                @else
                                                    {{-- BINAGO ANG RESTORE BUTTON PARA GAMITIN ANG CUSTOM MODAL --}}
                                                    <button type="button" class="btn-action-restore trigger-restore-modal" 
                                                        data-action-url="{{ route('super_admin.employees.toggle_status', $emp->id) }}"
                                                        data-emp-name="{{ strtoupper($emp->last_name) }}, {{ strtoupper($emp->first_name) }}"
                                                        title="Unlock Account">
                                                        <i class="fas fa-user-check"></i> Restore
                                                    </button>
                                                @endif
                                            </form>
                                        @else
                                            <span class="badge-root-immunity">Root Immunity</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr class="static-empty-row">
                                <td colspan="3" class="empty-message-state">
                                    No custom system roles provisioned yet. Use the control form to deploy accounts.
                                </td>
                            </tr>
                        @endforelse
                        <tr id="no-search-results-row" style="display: none;">
                            <td colspan="3" class="empty-message-state" style="text-align: center; color: #94a3b8; padding: 40px 0;">
                                <i class="fas fa-database" style="font-size: 20px; display: block; margin-bottom: 8px;"></i>
                                No matching clearance profiles found for your search query.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- CUSTOM RESTRICTION MODAL (Warning Theme) --}}
<div id="restrict-modal" class="custom-modal-overlay">
    <div class="custom-modal-box">
        <div class="modal-header-warning">
            <i class="fas fa-exclamation-triangle"></i> Warning: Access Restriction
        </div>
        <div class="modal-body">
            <p>You are about to restrict the system access of:</p>
            <div class="target-identity" id="modal-emp-name">EMPLOYEE NAME</div>
            <p class="modal-subtext">This will immediately disable their login credentials and active sessions. This action can be reversed via the "Restore" button.</p>
        </div>
        <div class="modal-footer">
            <button class="btn-modal-cancel" id="cancel-restrict">Cancel</button>
            <form id="confirm-restrict-form" method="POST">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn-modal-confirm">Confirm Restriction</button>
            </form>
        </div>
    </div>
</div>

{{-- CUSTOM RESTORE MODAL (Success Theme) --}}
<div id="restore-modal" class="custom-modal-overlay">
    <div class="custom-modal-box">
        <div class="modal-header-success">
            <i class="fas fa-user-check"></i> Confirm: Access Restoration
        </div>
        <div class="modal-body">
            <p>You are about to restore the system access of:</p>
            <div class="target-identity" id="restore-modal-emp-name">EMPLOYEE NAME</div>
            <p class="modal-subtext">This will immediately re-enable their login credentials and grant them operational access based on their assigned role.</p>
        </div>
        <div class="modal-footer">
            <button class="btn-modal-cancel" id="cancel-restore">Cancel</button>
            <form id="confirm-restore-form" method="POST">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn-modal-confirm-success">Confirm Restoration</button>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // --- DEPLOYMENT ROLE LOGIC ---
        const roleSelect = document.getElementById('role-select');
        const departmentGroup = document.getElementById('department-group');
        const departmentSelect = document.getElementById('department-select');

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

        // --- REAL-TIME DATA ARCHITECTURE SEARCH ENGINE ---
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

        // --- CUSTOM MODAL LOGIC FOR RESTRICTION ---
        const restrictModal = document.getElementById('restrict-modal');
        const restrictNameDisplay = document.getElementById('modal-emp-name');
        const restrictConfirmForm = document.getElementById('confirm-restrict-form');
        const restrictCancelBtn = document.getElementById('cancel-restrict');
        const restrictTriggerButtons = document.querySelectorAll('.trigger-restrict-modal');

        restrictTriggerButtons.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                restrictNameDisplay.textContent = this.getAttribute('data-emp-name');
                restrictConfirmForm.action = this.getAttribute('data-action-url');
                restrictModal.classList.add('active');
            });
        });

        restrictCancelBtn.addEventListener('click', () => restrictModal.classList.remove('active'));
        restrictModal.addEventListener('click', function(e) {
            if (e.target === restrictModal) restrictModal.classList.remove('active');
        });

        // --- CUSTOM MODAL LOGIC FOR RESTORATION ---
        const restoreModal = document.getElementById('restore-modal');
        const restoreNameDisplay = document.getElementById('restore-modal-emp-name');
        const restoreConfirmForm = document.getElementById('confirm-restore-form');
        const restoreCancelBtn = document.getElementById('cancel-restore');
        const restoreTriggerButtons = document.querySelectorAll('.trigger-restore-modal');

        restoreTriggerButtons.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                restoreNameDisplay.textContent = this.getAttribute('data-emp-name');
                restoreConfirmForm.action = this.getAttribute('data-action-url');
                restoreModal.classList.add('active');
            });
        });

        restoreCancelBtn.addEventListener('click', () => restoreModal.classList.remove('active'));
        restoreModal.addEventListener('click', function(e) {
            if (e.target === restoreModal) restoreModal.classList.remove('active');
        });
    });
</script>

<style>
    .sa-container {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: #0f172a;
        max-width: 1300px;
        margin: 0 auto;
        padding: 20px;
    }
    .sa-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        border-bottom: 2px solid #e2e8f0;
        padding-bottom: 18px;
    }
    .header-titles h2 {
        margin: 0 0 4px 0;
        font-size: 28px;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -0.5px;
    }
    .header-titles p {
        margin: 0;
        font-size: 14.5px;
        color: #64748b;
    }
    .root-level {
        background-color: #475569;
        color: #ffffff;
        padding: 6px 14px;
        border-radius: 20px;
        font-weight: 700;
        font-size: 11.5px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .sa-workspace {
        display: flex;
        flex-direction: row;
        gap: 28px;
        align-items: flex-start;
        justify-content: space-between;
        width: 100%;
    }
    .form-panel {
        flex: 1;
        min-width: 380px;
        background: #ffffff;
    }
    .table-panel {
        flex: 1.5;
        background: #ffffff;
    }
    .sa-card {
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 10px -2px rgba(0, 0, 0, 0.03);
        overflow: hidden;
    }
    .card-bar {
        background: #f8fafc;
        padding: 16px 22px;
        font-weight: 700;
        font-size: 14.5px;
        color: #1e293b;
        border-bottom: 1px solid #e2e8f0;
    }
    .pure-form {
        padding: 24px;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }
    .form-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .form-group label {
        font-size: 12px;
        font-weight: 600;
        color: #475569;
    }
    .form-group input {
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 10px 14px;
        font-size: 14px;
        color: #0f172a;
        outline: none;
        background: #ffffff;
    }
    .form-group input:focus {
        border-color: #475569;
        box-shadow: 0 0 0 3px rgba(71, 85, 105, 0.15);
    }
    .form-grid-2 {
        display: grid;
        grid-template-columns: 1.2fr 0.8fr;
        gap: 16px;
    }
    .btn-generate-account {
        background: #1e293b;
        color: #ffffff;
        border: none;
        padding: 14px;
        font-size: 14px;
        font-weight: 700;
        border-radius: 6px;
        cursor: pointer;
        margin-top: 12px;
        transition: background 0.2s;
        text-align: center;
    }
    .btn-generate-account:hover {
        background: #0f172a;
    }
    .table-wrap {
        width: 100%;
        overflow-x: auto;
    }
    .sa-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }
    .sa-table th {
        background: #f8fafc;
        padding: 14px 20px;
        font-size: 11.5px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        border-bottom: 1px solid #e2e8f0;
    }
    .sa-table td {
        padding: 15px 20px;
        border-bottom: 1px solid #f1f5f9;
        font-size: 13.5px;
        vertical-align: middle;
    }
    .emp-id-text {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        color: #0f172a;
        font-size: 13px;
        font-weight: 700;
    }
    .emp-name-text {
        font-weight: 700;
        color: #1e293b;
        font-size: 13.5px;
    }
    .emp-dept-text {
        font-size: 12px;
        color: #0284c7;
        font-weight: 600;
    }
    .emp-email-text {
        color: #64748b;
        font-size: 12px;
    }
    .role-badge-sa {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.3px;
        text-align: center;
    }
    .role-badge-sa.system_admin {
        background: #f1f5f9;
        color: #334155;
        border: 1px solid #cbd5e1;
    }
    .role-badge-sa.dean {
        background: #f0fdfa;
        color: #0d9488;
        border: 1px solid #ccfbf1;
    }
    .role-badge-sa.registrar {
        background: #eff6ff;
        color: #1e40af;
        border: 1px solid #dbeafe;
    }
    .status-indicator {
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .pulse-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
    }
    .status-label {
        font-size: 13px;
        color: #334155;
        font-weight: 500;
    }
    .sa-alert-success {
        background: #ecfdf5;
        color: #065f46;
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 25px;
        font-weight: 600;
        border-left: 5px solid #059669;
        font-size: 14px;
    }
    .d-flex-jsb {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .count-tag {
        background: #e2e8f0;
        color: #334155;
        font-size: 11.5px;
        padding: 4px 10px;
        border-radius: 5px;
        font-weight: 700;
        white-space: nowrap;
    }
    .empty-message-state {
        text-align: center;
        color: #94a3b8;
        padding: 50px 0;
        font-style: italic;
        font-size: 13.5px;
    }
    .custom-select-wrapper {
        position: relative;
        width: 100%;
    }
    .custom-select-dropdown {
        width: 100%;
        padding: 11px 40px 11px 14px;
        font-size: 14px;
        color: #0f172a;
        background-color: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        box-sizing: border-box;
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        cursor: pointer;
        outline: none;
        transition: all 0.2s ease;
    }
    .custom-select-dropdown:hover {
        border-color: #94a3b8;
    }
    .custom-select-dropdown:focus {
        border-color: #475569;
        box-shadow: 0 0 0 3px rgba(71, 85, 105, 0.15);
    }
    .custom-select-wrapper::after {
        content: "▼";
        font-size: 0.65rem;
        color: #64748b;
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        pointer-events: none;
    }
    .pulse-dot.active {
        background: #22c55e;
        box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7);
        animation: pulseGreen 2s infinite;
    }
    .pulse-dot.disabled {
        background: #dc2626;
        box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.7);
        animation: pulseRed 2s infinite;
    }
    .btn-action-restrict {
        background: #fef2f2;
        color: #dc2626;
        border: 1px solid #fecaca;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 11.5px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .btn-action-restrict:hover {
        background: #dc2626;
        color: #ffffff;
        border-color: #dc2626;
    }
    .btn-action-restore {
        background: #f0fdf4;
        color: #16a34a;
        border: 1px solid #bbf7d0;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 11.5px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .btn-action-restore:hover {
        background: #16a34a;
        color: #ffffff;
        border-color: #16a34a;
    }
    .badge-root-immunity {
        font-size: 11px;
        color: #64748b;
        font-style: italic;
        font-weight: 700;
        background: #f1f5f9;
        padding: 5px 10px;
        border-radius: 6px;
        border: 1px solid #cbd5e1;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .table-header-flex {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
    }
    .search-wrapper {
        position: relative;
        display: flex;
        align-items: center;
    }
    .search-icon {
        position: absolute;
        left: 12px;
        color: #94a3b8;
        font-size: 13px;
        pointer-events: none;
    }
    #personnel-search {
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 6px 12px 6px 32px;
        font-size: 12.5px;
        color: #0f172a;
        outline: none;
        background: #ffffff;
        width: 240px;
        transition: all 0.2s ease;
        font-weight: 500;
    }
    #personnel-search:focus {
        border-color: #475569;
        width: 280px;
        box-shadow: 0 0 0 3px rgba(71, 85, 105, 0.1);
    }
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

    /* --- CUSTOM MODAL STYLES --- */
    .custom-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(15, 23, 42, 0.7);
        backdrop-filter: blur(4px);
        z-index: 9999;
        display: none;
        justify-content: center;
        align-items: center;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .custom-modal-overlay.active {
        display: flex;
        opacity: 1;
    }

    .custom-modal-box {
        background: #ffffff;
        width: 420px;
        border-radius: 12px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        overflow: hidden;
        transform: scale(0.95);
        transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        border: 1px solid #e2e8f0;
    }

    .custom-modal-overlay.active .custom-modal-box {
        transform: scale(1);
    }

    /* Warning Theme (Restrict) */
    .modal-header-warning {
        background: #fef2f2;
        color: #991b1b;
        padding: 16px 20px;
        font-weight: 700;
        font-size: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
        border-bottom: 1px solid #fee2e2;
    }

    /* Success Theme (Restore) */
    .modal-header-success {
        background: #f0fdf4;
        color: #166534;
        padding: 16px 20px;
        font-weight: 700;
        font-size: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
        border-bottom: 1px solid #bbf7d0;
    }

    .modal-body {
        padding: 24px 20px;
        font-size: 14px;
        color: #334155;
        line-height: 1.6;
    }

    .target-identity {
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
        padding: 10px;
        border-radius: 6px;
        font-weight: 700;
        color: #0f172a;
        text-align: center;
        margin: 12px 0;
        font-family: ui-monospace, monospace;
    }

    .modal-subtext {
        font-size: 12.5px;
        color: #64748b;
        margin-top: 10px;
    }

    .modal-footer {
        padding: 16px 20px;
        background: #f8fafc;
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        border-top: 1px solid #e2e8f0;
    }

    .btn-modal-cancel {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        color: #475569;
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }

    .btn-modal-cancel:hover {
        background: #f1f5f9;
        color: #0f172a;
    }

    .btn-modal-confirm {
        background: #dc2626;
        color: #ffffff;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        box-shadow: 0 2px 4px rgba(220, 38, 38, 0.2);
    }

    .btn-modal-confirm:hover {
        background: #b91c1c;
        transform: translateY(-1px);
    }

    .btn-modal-confirm-success {
        background: #16a34a;
        color: #ffffff;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        box-shadow: 0 2px 4px rgba(22, 163, 74, 0.2);
    }

    .btn-modal-confirm-success:hover {
        background: #15803d;
        transform: translateY(-1px);
    }
</style>
@endsection