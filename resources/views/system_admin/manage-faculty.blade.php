@extends('layouts.app')

@section('content')
<div class="admin-container">
    <div class="admin-header">
        <div class="header-titles">
            <h2>Personnel Infrastructure Registry</h2>
            <p>System Admin Control Panel — Faculty Recruitment & Roster Infrastructure</p>
        </div>
        <div class="header-badge faculty-level">System Admin</div>
    </div>

    @if(session('success'))
        <div class="admin-alert-success">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    <div class="admin-workspace">     
        <div class="admin-card form-panel">
            <div class="card-bar">
                <span><i class="fas fa-user-plus"></i> Onboard New Faculty Member</span>
            </div>

            <form action="{{ route('system_admin.manage-faculty.store') }}" method="POST" autocomplete="off" class="pure-form">
                @csrf
                
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="first_name" placeholder="Please put the first name" required>
                </div>

                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" placeholder="Please put the last name" required>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Middle Name (Optional)</label>
                        <input type="text" name="middle_name" placeholder="Please put the middle name">
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
                    <label>Assigned Academic Department</label>
                    <div class="custom-select-wrapper">
                        <select name="department" class="custom-select-dropdown" required>
                            <option value="College of Computer Studies" selected>College of Computer Studies</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Faculty Designation / Job Title</label>
                    <div class="custom-select-wrapper">
                        <select name="role" class="custom-select-dropdown" required>
                            <option value="dean">Dean</option>
                            <option value="dept_sec">Department Secretary</option>
                            <option value="professor">Professor</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Personal Email</label>
                    <input type="email" name="email" placeholder="Please put the personal email" required>
                    <small class="field-help">A temporary default security credential (Welcome@AU2026) will be generated automatically.</small>
                </div>

                <button type="submit" class="btn-deploy-faculty">Authorize & Deploy Faculty</button>
            </form>
        </div>

        <div class="admin-card table-panel">
            <div class="card-header-flex">
                <h3 class="card-title-list">Active Institutional Faculty Roster</h3>
                <span class="badge-count">{{ $faculties->count() }} Personnel Stationed</span>
            </div>

            <div class="table-responsive">
                <table class="minimal-table">
                    <thead>
                        <tr>
                            <th>Faculty Identity & Credentials</th>
                            <th style="text-align: center;">Designation / Title</th>
                            <th style="text-align: center;">Network Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($faculties->count() > 0)
                            @foreach($faculties as $faculty)
                                <tr>
                                    <td>
                                        <strong class="faculty-id-text">{{ $faculty->employee_id }}</strong>
                                        <br><span class="faculty-name-text">{{ strtoupper($faculty->last_name) }}, {{ strtoupper($faculty->first_name) }}</span>
                                        <br><small class="faculty-email-text">{{ $faculty->email }}</small>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="rank-badge {{ $faculty->role }}">
                                            @if($faculty->role === 'dean')
                                                Dean
                                            @elseif($faculty->role === 'dept_sec')
                                                Department Secretary
                                            @else
                                                Professor
                                            @endif
                                        </span>
                                    </td>
                                    <td style="text-align: center;">
                                        <div class="status-indicator-center">
                                            <span class="pulse-dot-green"></span>
                                            <span class="status-label-text">Active</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="3" class="empty-table-state">
                                    No faculty members recorded under the institutional scope yet.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    .admin-container { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #0f172a; max-width: 1300px; margin: 0 auto; padding: 20px; }
    
    /* Header Setup - Neutral Black theme for Admin */
    .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 2px solid #e2e8f0; padding-bottom: 18px; }
    .header-titles h2 { margin: 0 0 4px 0; font-size: 26px; font-weight: 800; color: #0f172a; letter-spacing: -0.5px; }
    .header-titles p { margin: 0; font-size: 14px; color: #64748b; }
    .faculty-level { background-color: #000000; color: #ffffff; padding: 6px 14px; border-radius: 20px; font-weight: 700; font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.5px; }

    /* Layout structural spaces */
    .admin-workspace { display: flex; flex-direction: row; gap: 28px; align-items: flex-start; justify-content: space-between; width: 100%; }
    .form-panel { flex: 1; min-width: 360px; background: #ffffff; }
    .table-panel { flex: 1.5; background: #ffffff; }

    /* Cards UI */
    .admin-card { border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 10px -2px rgba(0, 0, 0, 0.03); overflow: hidden; }
    .card-bar { background: #f8fafc; padding: 16px 22px; font-weight: 700; font-size: 14.5px; color: #1e293b; border-bottom: 1px solid #e2e8f0; }
    .card-header-flex { background-color: #f8fafc; padding: 16px 22px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
    .card-title-list { font-size: 14.5px; font-weight: 700; color: #1e293b; margin: 0; }
    
    /* Input structures */
    .pure-form { padding: 24px; display: flex; flex-direction: column; gap: 16px; }
    .form-group { display: flex; flex-direction: column; gap: 6px; }
    .form-group label { font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase; letter-spacing: 0.3px; }
    .form-group input { border: 1px solid #cbd5e1; border-radius: 6px; padding: 10px 14px; font-size: 14px; color: #0f172a; outline: none; background: #ffffff; box-sizing: border-box; width: 100%; }
    .form-group input:focus { border-color: #000000; box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.05); }
    .form-grid-2 { display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 16px; }
    .uppercase-input { text-transform: uppercase; }
    .field-help { font-size: 11px; color: #64748b; margin-top: 2px; }
    
    /* Control Action Button */
    .btn-deploy-faculty { background: #000000; color: #ffffff; border: none; padding: 12px; font-size: 14px; font-weight: 700; border-radius: 6px; cursor: pointer; margin-top: 8px; text-align: center; }
    .btn-deploy-faculty:hover { background: #1e293b; }

    /* Tables UI engine */
    .table-responsive { width: 100%; overflow-x: auto; }
    .minimal-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 13.5px; }
    .minimal-table th { background: #f8fafc; padding: 14px 20px; font-size: 11.5px; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; }
    .minimal-table td { padding: 15px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
    .minimal-table tbody tr:hover { background-color: #f8fafc; }
    
    .faculty-id-text { font-family: ui-monospace, SFMono-Regular, Consolas, monospace; font-weight: 700; color: #0f172a; font-size: 13px; }
    .faculty-name-text { font-weight: 700; color: #0f172a; font-size: 14px; }
    .faculty-email-text { color: #64748b; font-size: 12px; }

    /* Dynamic Colorful Badges based on Functional Title Class Slugs */
    .rank-badge { display: inline-block; padding: 4px 10px; font-size: 11px; font-weight: 700; border-radius: 4px; text-transform: uppercase; border: 1px solid transparent; }
    .rank-badge.professor { background: #f1f5f9; color: #334155; border-color: #cbd5e1; }
    .rank-badge.dept_sec { background: #fdf2f8; color: #be185d; border-color: #fbcfe8; }

    /* RESERVED CODE FOR OTHER ROLES
    .rank-badge.laboratory-coordinator { background: #f0fdf4; color: #166534; border-color: #bbf7d0; }
    .rank-badge.program-chair { background: #f0f9ff; color: #075985; border-color: #bae6fd; } */

    .empty-table-state { text-align: center; color: #94a3b8; padding: 50px 0 !important; font-style: italic; }

    /* Activity markers */
    .status-indicator-center { display: flex; align-items: center; justify-content: center; gap: 6px; }
    .pulse-dot-green { width: 8px; height: 8px; background: #22c55e; border-radius: 50%; }
    .status-label-text { font-size: 13px; color: #334155; font-weight: 500; }

    /* Feedbacks space */
    .admin-alert-success { background: #ecfdf5; color: #065f46; padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; font-weight: 600; border-left: 5px solid #059669; font-size: 14px; }
    .badge-count { background: #e2e8f0; color: #334155; font-size: 11.5px; padding: 4px 12px; border-radius: 20px; font-weight: 700; }

    /* Custom Selection Styling */
    .custom-select-wrapper { position: relative; width: 100%; }
    .custom-select-dropdown { width: 100%; padding: 10px 40px 10px 14px; font-size: 14px; color: #0f172a; background-color: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; appearance: none; -webkit-appearance: none; cursor: pointer; outline: none; }
    .custom-select-dropdown:focus { border-color: #000000; box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.05); }
    .custom-select-wrapper::after { content: "▼"; font-size: 0.65rem; color: #64748b; position: absolute; right: 14px; top: 50%; transform: translateY(-50%); pointer-events: none; }
</style>
@endsection