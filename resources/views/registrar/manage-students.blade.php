@extends('layouts.app')

@section('content')
<div class="masterlist-container">

    <div class="masterlist-header">
        <div class="header-titles">
            <h2>Student Masterlist Ledger</h2>
            <p>Arellano University — Andres Bonifacio Campus (Active Academic Registry)</p>
        </div>
        <div class="header-count">
            Total Records: <strong>{{ $totalRecords }}</strong>
        </div>
    </div>

    <div class="filter-card" style="background: white; border-radius: 12px; border: 1px solid #e2e8f0; padding: 20px; margin-bottom: 25px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <form id="liveFilterForm" action="{{ route('registrar.manage-students') }}" method="GET" style="display: flex; gap: 16px; align-items: center; flex-wrap: wrap;">
            
            <div style="flex: 1; min-width: 250px;">
                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px; letter-spacing: 0.5px;">Search Directory</label>
                <input type="text" id="searchInput" name="search" value="{{ request('search') }}" placeholder="Search ID, First Name, or Last Name..." 
                       style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; box-sizing: border-box; color: #334155;">
            </div>

            <div style="width: 180px;">
                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px; letter-spacing: 0.5px;">Course</label>
                <select name="course" class="auto-submit-select" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; background-color: white; color: #334155; height: 41px; cursor: pointer;">
                    <option value="">All Courses</option>
                    <option value="BSIT" {{ request('course') == 'BSIT' ? 'selected' : '' }}>BSIT</option>
                    <option value="BSCS" {{ request('course') == 'BSCS' ? 'selected' : '' }}>BSCS</option>
                    <option value="BSIS" {{ request('course') == 'BSIS' ? 'selected' : '' }}>BSIS</option>
                </select>
            </div>

            <div style="width: 180px;">
                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px; letter-spacing: 0.5px;">Entry Type</label>
                <select name="student_type" class="auto-submit-select" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; background-color: white; color: #334155; height: 41px; cursor: pointer;">
                    <option value="">All Types</option>
                    <option value="NEW" {{ request('student_type') == 'NEW' ? 'selected' : '' }}>NEW</option>
                    <option value="TRANSFEREE" {{ request('student_type') == 'TRANSFEREE' ? 'selected' : '' }}>TRANSFEREE</option>
                    <option value="CROSS ENROLEE" {{ request('student_type') == 'CROSS ENROLEE' ? 'selected' : '' }}>CROSS ENROLEE</option>
                </select>
            </div>

            @if(request()->has('search') || request()->has('course') || request()->has('student_type'))
                <div style="margin-top: 20px;">
                    <a href="{{ route('registrar.manage-students') }}" style="background-color: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; padding: 10px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; display: inline-block; transition: all 0.2s;">
                        Clear Filters
                    </a>
                </div>
            @endif
        </form>
    </div>

    <div class="masterlist-card">
        <div class="card-top-bar">
            <span><i class="fas fa-list-ul"></i> Enrolled Student Roster</span>
        </div>

        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th style="width: 13%;">Student Number</th>
                        <th style="width: 28%;">Full Name</th>
                        <th style="width: 12%;">Course</th>
                        <th style="width: 15%;">Year & Sem</th>
                        <th style="width: 12%;">Entry Type</th>
                        <th style="width: 20%; text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $student)
                        <tr>
                            <td>
                                <span class="student-id-badge">{{ $student->student_id }}</span>
                            </td>
                            
                            <td class="student-name">
                                {{ $student->last_name }}, {{ $student->first_name }} {{ $student->middle_name }}
                            </td>
                            
                            <td class="student-course">
                                <span class="course-tag">{{ $student->course }}</span>
                            </td>

                            <td>
                                <span class="year-sem-tag">{{ $student->year_level }}</span>
                                <span class="year-sem-tag" style="margin-top: 4px;">{{ $student->semester }}</span>
                            </td>

                            <td>
                                <span class="type-badge {{ strtolower(str_replace(' ', '-', $student->student_type)) }}">
                                    {{ $student->student_type }}
                                </span>
                            </td>
                            
                            <td style="text-align: center;">
                                <a href="{{ route('registrar.students.profile', ['student_id' => $student->student_id]) }}" class="btn-action-view" title="View/Edit Full Profile">
                                    <i class="fas fa-folder-open"></i> View Profile
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-table-state">
                                <div class="empty-msg">
                                    <p>No student records captured yet inside the database.</p>
                                    <small>Go to "Manage Enrollment" to register your first student folder entry.</small>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .masterlist-container {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: #1e293b;
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px 0 50px 0;
    }

    .masterlist-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        border-bottom: 2px solid #e2e8f0;
        padding-bottom: 15px;
    }
    .header-titles h2 {
        margin: 0 0 5px 0;
        font-size: 26px;
        font-weight: 700;
        color: #0f172a;
    }
    .header-titles p {
        margin: 0;
        font-size: 14px;
        color: #64748b;
    }
    .header-count {
        background-color: rgba(0, 51, 160, 0.05);
        color: #0033A0;
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        border: 1px solid rgba(0, 51, 160, 0.1);
    }

    .masterlist-card {
        background: white;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        overflow: hidden;
    }
    .card-top-bar {
        background-color: #ffffff;
        padding: 20px 24px;
        font-size: 16px;
        font-weight: 700;
        color: #0033A0;
        border-bottom: 1px solid #f1f5f9;
    }

    .table-responsive {
        width: 100%;
        overflow-x: auto;
    }
    .custom-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }
    .custom-table th {
        background-color: #f8fafc;
        color: #475569;
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
        padding: 16px 24px;
        border-bottom: 1px solid #e2e8f0;
        letter-spacing: 0.5px;
    }
    .custom-table td {
        padding: 16px 24px;
        border-bottom: 1px solid #f1f5f9;
        font-size: 14px;
        color: #334155;
        vertical-align: middle;
    }
    .custom-table tbody tr:hover {
        background-color: #f8fafc;
    }

    .student-id-badge {
        font-family: monospace;
        background-color: #0f172a;
        color: #f8fafc;
        padding: 4px 10px;
        border-radius: 5px;
        font-weight: 700;
        font-size: 13px;
    }
    .student-name {
        font-weight: 600;
        color: #0f172a;
        text-transform: uppercase;
    }
    .course-tag {
        font-weight: 700;
        color: #475569;
    }

    .year-sem-tag {
        display: block;
        font-weight: 600;
        color: #475569;
        font-size: 12px;
        line-height: 1.6;
    }

    .type-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
    }
    .type-badge.new {
        background-color: #dcfce7;
        color: #15803d;
    }
    .type-badge.transferee {
        background-color: #fef9c3;
        color: #a16207;
    }
    .type-badge.cross-enrolee {
        background-color: #e0f2fe;
        color: #0369a1;
    }

    .btn-action-view {
        display: inline-block;
        background-color: transparent;
        color: #0033A0;
        border: 1px solid #0033A0;
        padding: 6px 14px;
        border-radius: 6px;
        font-size: 12.5px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.15s ease;
    }
    .btn-action-view:hover {
        background-color: #0033A0;
        color: white;
        box-shadow: 0 2px 6px rgba(0, 51, 160, 0.2);
    }

    .empty-table-state {
        text-align: center !important;
        padding: 50px 0 !important;
        background-color: #fafafa;
    }
    .empty-msg p {
        margin: 0 0 5px 0;
        font-size: 16px;
        font-weight: 600;
        color: #64748b;
    }
    .empty-msg small {
        color: #94a3b8;
        font-size: 13px;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const filterForm = document.getElementById('liveFilterForm');
    const searchInput = document.getElementById('searchInput');
    const selectDropdowns = document.querySelectorAll('.auto-submit-select');
    
    // 1. Instantly trigger submission when selection changes
    selectDropdowns.forEach(dropdown => {
        dropdown.addEventListener('change', () => {
            filterForm.submit();
        });
    });

    // 2. Debounce typing event: triggers 500ms after user pauses typing
    let typingDebounceTimer;
    searchInput.addEventListener('input', () => {
        clearTimeout(typingDebounceTimer);
        typingDebounceTimer = setTimeout(() => {
            filterForm.submit();
        }, 500); 
    });

    // 3. Keep the input focus clean upon asynchronous interface refresh
    if (searchInput.value !== '') {
        const textVal = searchInput.value;
        searchInput.value = '';
        searchInput.focus();
        searchInput.value = textVal;
    }
});
</script>
@endsection