@extends('layouts.app')

@section('content')
<div class="admin-container">
    <div class="admin-header">
        <div class="header-titles">
            <h2>Faculty Directory & Management</h2>
            <p>System Admin Control Panel — Manage Faculty Accounts & Roster</p>
        </div>
        <div class="header-badge faculty-level">System Admin</div>
    </div>

    @if(session('success'))
        <div class="admin-alert-success">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="admin-alert-danger">
            <i class="fas fa-exclamation-circle"></i> {{ $errors->first() }}
        </div>
    @endif

    <div class="admin-workspace">
        <div class="admin-card form-panel">
            <div class="card-bar">
                <span><i class="fas fa-user-plus"></i> Manage Faculty Accounts & Roster</span>
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
                        <input type="text" name="middle_name" placeholder="Middle name">
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
                    <label>Faculty Role / Academic Position</label>
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
                    <small class="field-help">A temporary default credential (Welcome@AU2026) will be generated automatically.</small>
                </div>
                <button type="submit" class="btn-deploy-faculty">Save Faculty Member</button>
            </form>
        </div>

        <div class="admin-card table-panel">
            <div class="card-header-flex">
                <div class="card-title-container">
                    <h3 class="card-title-list">Faculty Members Directory</h3>
                    <span class="badge-count"> {{ $faculties->count() }} Faculty Members </span>
                </div>
                <div class="header-controls-flex">
                    <div class="search-bar-container">
                        <i class="fas fa-search search-icon"></i>
                        <input
                        type="text"
                        id="adminFacultySearch"
                        placeholder="Search by Name, ID, or Email..."
                        class="custom-search-input">

                    </div>
                    <span class="badge-count">
                        {{ $faculties->count() }} Personnel Stationed
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
                        @forelse($faculties as $faculty)
                            <tr>
                                <td>
                                    <strong class="faculty-id-text">{{ $faculty->employee_id }}</strong>
                                    <br><span class="faculty-name-text">{{ strtoupper($faculty->last_name) }}, {{ strtoupper($faculty->first_name) }}</span>
                                    <br><small class="faculty-email-text">{{ $faculty->email }}</small>
                                </td>
                                <td style="text-align: center;">
                                    <span class="rank-badge {{ $faculty->role }}">
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
                                        <button class="btn-action-edit"
                                            onclick="openEditFaculty(
                                                {{ $faculty->id }},
                                                @js($faculty->first_name),
                                                @js($faculty->last_name),
                                                @js($faculty->middle_name),
                                                @js($faculty->sex),
                                                @js($faculty->email),
                                                @js($faculty->role),
                                                @js($faculty->department)
                                            )">Edit</button>
                                        <button class="btn-action-delete"
                                            onclick="openDeleteFaculty({{ $faculty->id }}, '{{ strtoupper($faculty->last_name) }}, {{ strtoupper($faculty->first_name) }}')">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="empty-table-state">
                                    No faculty members recorded under the institutional scope yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <div id="facultyNoResults" style="display:none; text-align:center; color:#94a3b8; padding:40px; font-style:italic; font-size:13.5px;">
                    No faculty members match your search.
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ===================== EDIT MODAL ===================== --}}
<div id="editFacultyModal" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fas fa-user-edit"></i> Edit Faculty Record</h3>
            <button class="modal-close" onclick="closeModal('editFacultyModal')">&times;</button>
        </div>
        <form id="editFacultyForm" method="POST" class="modal-form">
            @csrf
            @method('PATCH')
            <div class="form-group">
                <label>First Name</label>
                <input type="text" name="first_name" id="edit_first_name" required>
            </div>
            <div class="form-group">
                <label>Last Name</label>
                <input type="text" name="last_name" id="edit_last_name" required>
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label>Middle Name (Optional)</label>
                    <input type="text" name="middle_name" id="edit_middle_name">
                </div>
                <div class="form-group">
                    <label>Sex</label>
                    <div class="custom-select-wrapper">
                        <select name="sex" id="edit_sex" class="custom-select-dropdown" required>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" id="edit_email" required>
            </div>
            <div class="form-group">
                <label>Department</label>
                <div class="custom-select-wrapper">
                    <select name="department" id="edit_department" class="custom-select-dropdown" required>
                        <option value="College of Computer Studies">College of Computer Studies</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Role / Designation</label>
                <div class="custom-select-wrapper">
                    <select name="role" id="edit_role" class="custom-select-dropdown" required>
                        <option value="dean">Dean</option>
                        <option value="professor">Professor</option>
                        <option value="dept_sec">Department Secretary</option>
                    </select>
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-modal-cancel" onclick="closeModal('editFacultyModal')">Cancel</button>
                <button type="submit" class="btn-modal-confirm-edit">Save Changes</button>
            </div>
        </form>
    </div>
</div>

{{-- ===================== DELETE MODAL ===================== --}}
<div id="deleteFacultyModal" class="modal-overlay" style="display:none;">
    <div class="modal-box modal-box-sm">
        <div class="modal-header modal-header-danger">
            <h3><i class="fas fa-exclamation-triangle"></i> Confirm Deletion</h3>
            <button class="modal-close" onclick="closeModal('deleteFacultyModal')">&times;</button>
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
    // Live Search
    const searchInput = document.getElementById('adminFacultySearch');
    const tableRows   = document.querySelectorAll('#facultyTable tbody tr');
    const noResults   = document.getElementById('facultyNoResults');

    if (searchInput) {
        searchInput.addEventListener('input', function () {
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

function openEditFaculty(id, firstName, lastName, middleName, sex, email, role, department) {
    document.getElementById('edit_first_name').value  = firstName;
    document.getElementById('edit_last_name').value   = lastName;
    document.getElementById('edit_middle_name').value = middleName && middleName !== 'null' ? middleName : '';
    document.getElementById('edit_sex').value         = sex;
    document.getElementById('edit_email').value       = email;
    document.getElementById('edit_role').value        = role;
    document.getElementById('edit_department').value  = department;
    let editUrl = "{{ route('system_admin.manage-faculty.update', ':id') }}";
    editUrl = editUrl.replace(':id', id);

    document.getElementById('editFacultyForm').action = editUrl;
    document.getElementById('editFacultyModal').style.display = 'flex';
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

// Close modal on backdrop click
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) closeModal(this.id);
    });
});
</script>

<style>
    .admin-container { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #0f172a; max-width: 1300px; margin: 0 auto; padding: 20px; }
    .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 2px solid #e2e8f0; padding-bottom: 18px; }
    .header-titles h2 { margin: 0 0 4px 0; font-size: 26px; font-weight: 800; color: #0f172a; letter-spacing: -0.5px; }
    .header-titles p { margin: 0; font-size: 14px; color: #64748b; }
    .faculty-level { background-color: #000000; color: #ffffff; padding: 6px 14px; border-radius: 20px; font-weight: 700; font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.5px; }

    .admin-workspace { display: flex; flex-direction: row; gap: 28px; align-items: flex-start; width: 100%; }
    .form-panel { flex: 1; min-width: 360px; background: #ffffff; }
    .table-panel { flex: 1.5; background: #ffffff; }

    .admin-card { border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 10px -2px rgba(0,0,0,0.03); overflow: hidden; transition: .2s ease; }
    .card-bar { background: #f8fafc; padding: 16px 22px; font-weight: 700; font-size: 14.5px; color: #1e293b; border-bottom: 1px solid #e2e8f0; }
    .card-header-flex { background-color: #f8fafc; padding: 16px 22px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; }
    .card-title-container { display: flex; align-items: center; gap: 12px; }
    .card-title-list { font-size: 14.5px; font-weight: 700; color: #1e293b; margin: 0; }

    .table-search-box { position: relative; max-width: 260px; width: 100%; }
    .table-search-box input { width: 100%; padding: 8px 12px 8px 36px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 6px; outline: none; color: #0f172a; box-sizing: border-box; transition: all 0.2s ease; }
    .table-search-box input:focus { border-color: #000000; box-shadow: 0 0 0 3px rgba(0,0,0,0.06); }
    .table-search-box .search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 13px; pointer-events: none; }

    .pure-form { padding: 24px; display: flex; flex-direction: column; gap: 16px; }
    .form-group { display: flex; flex-direction: column; gap: 6px; }
    .form-group label { font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase; letter-spacing: 0.3px; }
    .form-group input { border: 1px solid #cbd5e1; border-radius: 6px; padding: 10px 14px; font-size: 14px; color: #0f172a; outline: none; background: #ffffff; box-sizing: border-box; width: 100%; }
    .form-group input:focus { border-color: #000000; box-shadow: 0 0 0 3px rgba(0,0,0,0.05); }
    .form-grid-2 { display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 16px; }
    .field-help { font-size: 11px; color: #64748b; margin-top: 2px; }

    .btn-deploy-faculty { background: #000000; color: #ffffff; border: none; padding: 12px; font-size: 14px; font-weight: 700; border-radius: 6px; cursor: pointer; margin-top: 8px; width: 100%; }
    .btn-deploy-faculty:hover { background: #1e293b; }

    .table-responsive { width: 100%; overflow-x: auto; }
    .minimal-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 13.5px; }
    .minimal-table th { background: #f8fafc; padding: 14px 20px; font-size: 11.5px; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; }
    .minimal-table td { padding: 15px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
    .minimal-table tbody tr:hover { background-color: #f8fafc; }

    .faculty-id-text { font-family: ui-monospace, SFMono-Regular, Consolas, monospace; font-weight: 700; color: #0f172a; font-size: 13px; }
    .faculty-name-text { font-weight: 700; color: #0f172a; font-size: 14px; }
    .faculty-email-text { color: #64748b; font-size: 12px; }

    .rank-badge { display: inline-block; padding: 4px 10px; font-size: 11px; font-weight: 700; border-radius: 4px; text-transform: uppercase; border: 1px solid transparent; }
    .rank-badge.professor { background: #f1f5f9; color: #334155; border-color: #cbd5e1; }
    .rank-badge.dept_sec { background: #fdf2f8; color: #be185d; border-color: #fbcfe8; }
    .rank-badge.dean { background: #fff7ed; color: #c2410c; border-color: #ffedd5; }

    .empty-table-state { text-align: center; color: #94a3b8; padding: 50px 0 !important; font-style: italic; }

    /* Activity markers */
    .status-indicator-center { display: flex; align-items: center; justify-content: center; gap: 6px; }
    .pulse-dot-green { width: 8px; height: 8px; background: #22c55e; border-radius: 50%; }
    .status-label-text { font-size: 13px; color: #334155; font-weight: 500; }

    .action-btn-group { display: flex; gap: 6px; justify-content: center; }
    .btn-action-edit { background-color: transparent; border: 1px solid #cbd5e1; color: #475569; padding: 5px 12px; font-size: 12px; font-weight: 600; border-radius: 6px; cursor: pointer; transition: all 0.2s; }
    .btn-action-edit:hover { background-color: #f1f5f9; color: #1e293b; border-color: #94a3b8; }
    .btn-action-delete { background-color: transparent; border: 1px solid #fecaca; color: #dc2626; padding: 5px 12px; font-size: 12px; font-weight: 600; border-radius: 6px; cursor: pointer; transition: all 0.2s; }
    .btn-action-delete:hover { background-color: #fef2f2; color: #991b1b; border-color: #f87171; }

    .admin-alert-success { background: #ecfdf5; color: #065f46; padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; font-weight: 600; border-left: 5px solid #059669; font-size: 14px; }
    .admin-alert-danger { background: #fef2f2; color: #991b1b; padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; font-weight: 600; border-left: 5px solid #dc2626; font-size: 14px; }
    .badge-count { background: #e2e8f0; color: #334155; font-size: 11.5px; padding: 4px 12px; border-radius: 20px; font-weight: 700; }

    .custom-select-wrapper { position: relative; width: 100%; }
    .custom-select-dropdown { width: 100%; padding: 10px 40px 10px 14px; font-size: 14px; color: #0f172a; background-color: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; appearance: none; -webkit-appearance: none; cursor: pointer; outline: none; }
    .custom-select-dropdown:focus { border-color: #000000; box-shadow: 0 0 0 3px rgba(0,0,0,0.05); }
    .custom-select-wrapper::after { content: "▼"; font-size: 0.65rem; color: #64748b; position: absolute; right: 14px; top: 50%; transform: translateY(-50%); pointer-events: none; }

    /* ===== MODAL STYLES ===== */
    .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.45); z-index: 9999; display: flex; align-items: center; justify-content: center; }
    .modal-box { background: #ffffff; border-radius: 14px; width: 100%; max-width: 520px; box-shadow: 0 20px 60px rgba(0,0,0,0.2); overflow: hidden; }
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
    .btn-modal-confirm-edit { background: #000000; color: #ffffff; border: none; padding: 9px 20px; font-size: 13px; font-weight: 700; border-radius: 6px; cursor: pointer; }
    .btn-modal-confirm-edit:hover { background: #1e293b; }
    .btn-modal-confirm-delete { background: #dc2626; color: #ffffff; border: none; padding: 9px 20px; font-size: 13px; font-weight: 700; border-radius: 6px; cursor: pointer; }
    .btn-modal-confirm-delete:hover { background: #b91c1c; }
</style>
@endsection
