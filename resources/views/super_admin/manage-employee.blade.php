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
                        <select name="sex" required>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Official Institutional Email</label>
                    <input type="email" name="email" placeholder="username@arellano.edu.ph" required>
                </div>

                <div class="form-group">
                    <label>Designated Operational Role</label>
                    <select name="role" required>
                        <option value="">-- Select System Tier Authorization --</option>
                        <option value="system_admin">System Admin (Global Subject & Settings Maintenance)</option>
                        <option value="dean">College Dean / Secretary (Curriculum & Mapping)</option>
                        <option value="registrar">Registrar Officer (Student Records & Ledger Enrollment)</option>
                    </select>
                </div>

                <button type="submit" class="btn-generate-account">Provision Account & ID</button>
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
                                    <br><span class="emp-name-text">{{ $emp->last_name }}, {{ $emp->first_name }}</span>
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
                                        <span class="status-label">{{ $emp->status }}</span>
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

<style>
    .sa-container { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #0f172a; max-width: 1250px; margin: 0 auto; padding-bottom: 60px; }
    
    /* Header Architecture */
    .sa-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 2px solid #e2e8f0; padding-bottom: 18px; }
    .header-titles h2 { margin: 0 0 4px 0; font-size: 28px; font-weight: 800; color: #0f172a; letter-spacing: -0.5px; }
    .header-titles p { margin: 0; font-size: 14.5px; color: #64748b; }
    .root-level { background-color: #475569; color: #ffffff; padding: 6px 14px; border-radius: 20px; font-weight: 700; font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.5px; }

    /* Layout Grids */
    .sa-workspace { display: flex; gap: 28px; align-items: flex-start; }
    .form-panel { flex: 1; min-width: 390px; }
    .table-panel { flex: 1.6; }

    /* Component Cards */
    .sa-card { background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 10px -2px rgba(0, 0, 0, 0.05); overflow: hidden; }
    .card-bar { background: #f8fafc; padding: 16px 22px; font-weight: 700; font-size: 14.5px; color: #1e293b; border-bottom: 1px solid #e2e8f0; }
    
    /* Pure Form Mechanics */
    .pure-form { padding: 24px; display: flex; flex-direction: column; gap: 18px; }
    .form-group { display: flex; flex-direction: column; gap: 6px; }
    .form-group label { font-size: 13px; font-weight: 600; color: #475569; }
    .form-group input, .form-group select { border: 1px solid #cbd5e1; border-radius: 6px; padding: 11px 14px; font-size: 14px; color: #0f172a; outline: none; background: #ffffff; }
    .form-group input:focus, .form-group select:focus { border-color: #475569; box-shadow: 0 0 0 3px rgba(71, 85, 105, 0.15); }
    .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    
    /* Action Buttons */
    .btn-generate-account { background: #1e293b; color: #ffffff; border: none; padding: 14px; font-size: 14px; font-weight: 700; border-radius: 6px; cursor: pointer; margin-top: 8px; transition: background 0.2s; }
    .btn-generate-account:hover { background: #0f172a; }

    /* Database Table Layouts */
    .table-wrap { width: 100%; overflow-x: auto; }
    .sa-table { width: 100%; border-collapse: collapse; text-align: left; }
    .sa-table th { background: #f8fafc; padding: 14px 20px; font-size: 11.5px; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; border-top: 1px solid #e2e8f0; }
    .sa-table td { padding: 15px 20px; border-bottom: 1px solid #f1f5f9; font-size: 14px; vertical-align: middle; }
    
    .emp-id-text { font-family: 'Courier New', Courier, monospace; color: #1e293b; font-size: 14.5px; font-weight: 700; }
    .emp-name-text { font-weight: 600; color: #0f172a; }
    .emp-email-text { color: #64748b; font-size: 12.5px; }

    /* Badge Matrices based on Custom Roles */
    .role-badge-sa { display: inline-block; padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: 700; letter-spacing: 0.3px; }
    .role-badge-sa.system_admin { background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; }
    .role-badge-sa.dean { background: #f0fdfa; color: #0d9488; border: 1px solid #ccfbf1; }
    .role-badge-sa.registrar { background: #eff6ff; color: #1e40af; border: 1px solid #dbeafe; }

    /* Status Pulse Components */
    .status-indicator { display: flex; align-items: center; gap: 6px; }
    .pulse-dot { width: 8px; height: 8px; background: #22c55e; border-radius: 50%; }
    .status-label { font-size: 13px; color: #334155; font-weight: 500; }

    /* Alerts and Helpers */
    .sa-alert-success { background: #ecfdf5; color: #065f46; padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; font-weight: 600; border-left: 5px solid #059669; font-size: 14px; }
    .d-flex-jsb { display: flex; justify-content: space-between; align-items: center; }
    .count-tag { background: #e2e8f0; color: #334155; font-size: 11.5px; padding: 3px 10px; border-radius: 5px; font-weight: 700; }
    .empty-message-state { text-align: center; color: #94a3b8; padding: 50px 0; font-style: italic; font-size: 13.5px; }
</style>
@endsection