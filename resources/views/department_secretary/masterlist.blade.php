@extends('layouts.app')

@section('content')
<div class="sa-container">
    
    <div class="sa-header">
        <div class="header-titles">
            <h2>Student Masterlist</h2>
            <p>{{ $dept }} — Departmental Registry Overview</p>
        </div>
        <div class="header-badge sec-level">Department Secretary</div>
    </div>

    @if(session('success'))
        <div class="alert alert-success" style="background: #d1fae5; color: #065f46; padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; border: 1px solid #a7f3d0;">
            {{ session('success') }}
        </div>
    @endif

    <div class="filter-action-bar">
        <div class="search-box-wrapper">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="studentSearchInput" placeholder="Search student number or name...">
        </div>
    </div>

    <div class="sa-workspace-full">
        <div class="sa-card table-panel-full">
            <div class="card-header-flex">
                <h3 class="card-title-list">Enrolled Department Roster</h3>
                <span class="badge-count" id="studentCountBadge">{{ count($students) }} Total Students</span>
            </div>

            <div class="table-responsive">
                <table class="minimal-table" id="studentMasterTable">
                    <thead>
                        <tr>
                            <th>Student Number</th>
                            <th>Full Name</th>
                            <th>Course</th>
                            <th>Year Level</th>
                            <th style="text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($students as $student)
                            <tr class="student-row">
                                <td><strong class="student-id-badge">{{ $student->student_id }}</strong></td>
                                <td><span class="student-name">{{ strtoupper($student->last_name) }}, {{ $student->first_name }}</span></td>
                                <td><span class="course-tag">{{ $student->course }}</span></td>
                                <td>{{ $student->year_level ?? '1st Year' }}</td>
                                <td style="text-align: center;">
                                    <a href="{{ route('department_secretary.plot_subjects', ['student_id' => $student->student_id]) }}" class="btn-action-plot">
                                        <i data-lucide="notebook-pen" style="width: 14px; height: 14px; display: inline-block; vertical-align: middle; margin-right: 4px;"></i> Plot Subjects
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr class="no-data-row">
                                <td colspan="5" class="empty-table-state">
                                    No students registered under your department yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('studentSearchInput');
        const tableRows = document.querySelectorAll('#studentMasterTable tbody .student-row');
        const countBadge = document.getElementById('studentCountBadge');

        if (searchInput) {
            searchInput.addEventListener('keyup', function () {
                const searchTerm = searchInput.value.toLowerCase().trim();
                let visibleCount = 0;

                tableRows.forEach(row => {
                    const idText = row.querySelector('.student-id-badge')?.textContent.toLowerCase() || '';
                    const nameText = row.querySelector('.student-name')?.textContent.toLowerCase() || '';

                    if (idText.includes(searchTerm) || nameText.includes(searchTerm)) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                if (countBadge) {
                    countBadge.textContent = `${visibleCount} Total Students`;
                }
            });
        }
    });
</script>

<style>
    .sa-container { font-family: 'Segoe UI', sans-serif; padding: 20px; max-width: 1400px; margin: 0 auto; }
    .sa-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 2px solid #e2e8f0; padding-bottom: 15px; }
    .header-titles h2 { margin: 0; font-size: 24px; color: #0f172a; font-weight: 800; }
    .header-titles p { margin: 4px 0 0 0; font-size: 14px; color: #64748b; }
    .sec-level { background-color: #1e3a8a; color: white; padding: 6px 14px; border-radius: 20px; font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }

    .filter-action-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .search-box-wrapper { position: relative; flex: 1; max-width: 400px; }
    .search-box-wrapper input { width: 100%; padding: 10px 16px 10px 40px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 14px; outline: none; transition: border 0.2s; }
    .search-box-wrapper input:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
    .search-icon { position: absolute; left: 14px; top: 13px; color: #94a3b8; font-size: 14px; }

    .sa-workspace-full { width: 100%; }
    .sa-card { border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); overflow: hidden; background: #ffffff; }
    .card-header-flex { background-color: #f8fafc; padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
    .card-title-list { font-size: 15px; font-weight: 700; margin: 0; color: #1e293b; }
    
    .minimal-table { width: 100%; border-collapse: collapse; font-size: 14px; }
    .minimal-table th { background: #f8fafc; padding: 14px 24px; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; text-align: left; letter-spacing: 0.5px; }
    .minimal-table td { padding: 16px 24px; border-bottom: 1px solid #f1f5f9; color: #334155; }
    
    .student-id-badge { background: #0f172a; color: white; padding: 4px 8px; border-radius: 6px; font-family: monospace; font-size: 13px; }
    .student-name { font-weight: 600; color: #1e293b; }
    .course-tag { font-weight: 700; color: #2563eb; }
    
    .btn-action-plot { text-decoration: none; display: inline-block; background-color: #ffffff; color: #2563eb; border: 1px solid #bfdbfe; padding: 6px 14px; border-radius: 6px; font-weight: 600; font-size: 13px; transition: all 0.2s; }
    .btn-action-plot:hover { background-color: #2563eb; color: white; border-color: #2563eb; box-shadow: 0 2px 4px rgba(37, 99, 235, 0.15); }
    
    .badge-count { background: #0f172a; color: white; font-size: 12px; padding: 4px 12px; border-radius: 20px; font-weight: 700; }
    .empty-table-state { text-align: center; color: #94a3b8; padding: 80px 0 !important; font-style: italic; }
</style>
@endsection