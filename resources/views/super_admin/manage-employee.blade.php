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

        <div class="sa-card table-panel">
            <div class="card-bar d-flex-jsb">
                <span>Active Infrastructure Personnel</span>
                <span class="count-tag">Total Provisioned: {{ $employees->count() }}</span>
            </div>
            
            <div class="table-wrap">
                <table class="sa-table">
                    <thead>
                        <tr>
                            <th>Identified Personnel</th>
                            <th>Role / Clearance</th>
                            <th>Network Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employees as $emp)
                            <tr>
                                <td>
                                    <strong class="emp-id-text">{{ $emp->employee_id }}</strong>
                                    <br><span class="emp-name-text">{{ strtoupper($emp->last_name) }}, {{ strtoupper($emp->first_name) }}</span>
                                    
                                    @if($emp->department)
                                        <br><span class="emp-dept-text"><i class="fas fa-university"></i> {{ $emp->department }}</span>
                                    @endif
                                    
                                    <br><small class="emp-email-text">{{ $emp->email }}</small>
                                </td>
                                <td>
                                    <span class="role-badge-sa {{ $emp->role }}">
                                        {{ str_replace('_', ' ', strtoupper($emp->role)) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="status-indicator">
                                        <span class="pulse-dot"></span>
                                        <span class="status-label">{{ ucfirst($emp->status ?? 'Active') }}</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="empty-message-state">
                                    No custom system roles provisioned yet. Use the control form to deploy accounts.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

{{-- 👇 DYNAMIC JAVASCRIPT MECHANICS PARA SA DEPARTMENT SLIDE SHOW --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const roleSelect = document.getElementById('role-select');
        const departmentGroup = document.getElementById('department-group');
        const departmentSelect = document.getElementById('department-select');

        roleSelect.addEventListener('change', function () {
            if (this.value === 'dean') {
                // Ipakita ang Department field at gawing mandatory (required) ang pagpili
                departmentGroup.style.display = 'flex';
                departmentSelect.setAttribute('required', 'required');
            } else {
                // Itago ang field at alisin ang required para pwedeng mag-null sa submission
                departmentGroup.style.display = 'none';
                departmentSelect.removeAttribute('required');
                departmentSelect.value = ''; // I-reset ang piniling option
            }
        });
    });
</script>

<style>
    .sa-container { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #0f172a; max-width: 1300px; margin: 0 auto; padding: 20px; }
    
    .sa-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 2px solid #e2e8f0; padding-bottom: 18px; }
    .header-titles h2 { margin: 0 0 4px 0; font-size: 28px; font-weight: 800; color: #0f172a; letter-spacing: -0.5px; }
    .header-titles p { margin: 0; font-size: 14.5px; color: #64748b; }
    .root-level { background-color: #475569; color: #ffffff; padding: 6px 14px; border-radius: 20px; font-weight: 700; font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.5px; }

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
    .pulse-dot { width: 8px; height: 8px; background: #22c55e; border-radius: 50%; }
    .status-label { font-size: 13px; color: #334155; font-weight: 500; }

    .sa-alert-success { background: #ecfdf5; color: #065f46; padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; font-weight: 600; border-left: 5px solid #059669; font-size: 14px; }
    .d-flex-jsb { display: flex; justify-content: space-between; align-items: center; }
    .count-tag { background: #e2e8f0; color: #334155; font-size: 11.5px; padding: 4px 10px; border-radius: 5px; font-weight: 700; }
    .empty-message-state { text-align: center; color: #94a3b8; padding: 50px 0; font-style: italic; font-size: 13.5px; }

    .custom-select-wrapper { position: relative; width: 100%; }
    .custom-select-dropdown { width: 100%; padding: 10px 40px 10px 14px; font-size: 14px; color: #0f172a; background-color: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; appearance: none; -webkit-appearance: none; -moz-appearance: none; cursor: pointer; outline: none; transition: all 0.2s ease; }
    .custom-select-dropdown:hover { border-color: #94a3b8; }
    .custom-select-dropdown:focus { border-color: #475569; box-shadow: 0 0 0 3px rgba(71, 85, 105, 0.15); }
    .custom-select-wrapper::after { content: "▼"; font-size: 0.65rem; color: #64748b; position: absolute; right: 14px; top: 50%; transform: translateY(-50%); pointer-events: none; }
    .field-help { font-size: 11px; margin-top: 2px; }
</style>
@endsection