@extends('layouts.app')

@section('content')
<div class="sa-container">
    
    <div class="sa-header">
        <div class="header-titles">
            <h2>Class Schedule Matrix</h2>
            <p>College Dean Panel — Academic Oversight & Monitoring Center</p>
        </div>
        <div class="header-badge" style="background-color: #6366f1; color: #ffffff; padding: 6px 14px; border-radius: 20px; font-weight: 700; font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.5px;">Dean Mode</div>
    </div>

    <div class="sa-workspace">

        <div class="sa-card table-panel">
            <div class="card-header-flex" style="display: flex; flex-direction: column; gap: 12px; align-items: stretch;">
                <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                    <h3 class="card-title-list"><i class="fas fa-list-alt" style="margin-right: 6px; color: #6366f1;"></i> Master Deployment Matrix</h3>
                    <span class="badge-count" id="visibleClassesCount">{{ isset($schedules) ? $schedules->count() : 0 }} Active Classes</span>
                </div>
                
                <div style="display: flex; gap: 12px; flex-wrap: wrap; width: 100%;">
                    <div style="position: relative; flex: 2; min-width: 250px;">
                        <i class="fas fa-search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 14px;"></i>
                        <input type="text" id="scheduleSearchInput" placeholder="Search by Subject Code, Title, Section, Room, or Professor..." 
                            style="width: 100%; padding: 10px 10px 10px 40px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; outline: none; box-sizing: border-box;">
                    </div>

                    <div style="flex: 1; min-width: 130px;">
                        <select id="filterYearLevel" class="select-input" style="padding: 9px 12px; font-size: 13px;">
                            <option value="">-- All Years --</option>
                            <option value="1st Year">1st Year</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option>
                            <option value="4th Year">4th Year</option>
                        </select>
                    </div>

                    <div style="flex: 1; min-width: 140px;">
                        <select id="filterSemester" class="select-input" style="padding: 9px 12px; font-size: 13px;">
                            <option value="">-- All Semesters --</option>
                            <option value="1st Semester">1st Semester</option>
                            <option value="2nd Semester">2nd Semester</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="minimal-table" id="scheduleTable">
                    <thead>
                        <tr>
                            <th>Subject Info</th>
                            <th>Section / Block</th>
                            <th>Schedule & Room Details</th>
                            <th>Assigned Faculty</th>
                            <th style="text-align: center; width: 110px;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($schedules) && $schedules->count() > 0)
                            @foreach($schedules as $sched)
                                <tr class="schedule-row-item"
                                    data-year="{{ $sched->year_level ?? '' }}"
                                    data-sem="{{ $sched->semester ?? '' }}">

                                    <td>
                                        <span class="subject-code-tag">{{ strtoupper($sched->subject_code ?? '') }}</span>
                                        <div style="font-size: 12px; color: #64748b; margin-top: 4px; font-weight: 500;">
                                            {{ $sched->subject_title ?? '' }}
                                        </div>
                                    </td>

                                    <td><strong style="color: #0f172a;">{{ strtoupper($sched->section_name) }}</strong></td>

                                    <td>
                                        <span class="sched-info-tag">
                                            <i class="fas fa-calendar-day"></i> {{ $sched->class_days }}
                                        </span>
                                        <span class="sched-info-tag">
                                            <i class="fas fa-clock"></i> {{ date('g:i A', strtotime($sched->start_time)) }} - {{ date('g:i A', strtotime($sched->end_time)) }}
                                        </span>
                                        <div style="font-size: 11.5px; color: #475569; margin-top: 4px; font-weight: 600;">
                                            <i class="fas fa-door-open" style="color: #94a3b8;"></i> Room: {{ strtoupper($sched->room_assignment) }}
                                        </div>
                                    </td>

                                    <td>
                                        @if($sched->prof_last && $sched->prof_first)
                                            <strong>{{ $sched->prof_last }}, {{ $sched->prof_first }}</strong>
                                        @else
                                            <span class="badge bg-secondary">TBA</span>
                                        @endif
                                    </td>

                                    <td style="text-align: center;">
                                        <span style="font-size: 11.5px; padding: 4px 10px; border-radius: 12px; font-weight: 700; background-color: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0;">
                                            Deployed
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="5" class="empty-table-state">
                                    No active class scheduling or section deployments documented yet.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        
        // --- Multi-Criteria Search & Filter Dropdown Engine ---
        const searchInput = document.getElementById('scheduleSearchInput');
        const filterYearLevel = document.getElementById('filterYearLevel');
        const filterSemester = document.getElementById('filterSemester');
        const tableRows = document.querySelectorAll('#scheduleTable tbody tr');
        const visibleClassesCount = document.getElementById('visibleClassesCount');

        function filterScheduleMatrixTable() {
            const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
            const selectedYear = filterYearLevel ? filterYearLevel.value : '';
            const selectedSem = filterSemester ? filterSemester.value : '';
            let visibleCount = 0;

            tableRows.forEach(row => {
                if (row.querySelector('.empty-table-state')) return;

                const rowText = row.innerText.toLowerCase();
                const rowYear = row.getAttribute('data-year') || '';
                const rowSem = row.getAttribute('data-sem') || '';

                const matchesSearch = rowText.includes(searchTerm);
                const matchesYear = selectedYear === '' || rowYear === selectedYear;
                const matchesSem = selectedSem === '' || rowSem === selectedSem;

                if (matchesSearch && matchesYear && matchesSem) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (visibleClassesCount) {
                visibleClassesCount.textContent = `${visibleCount} Active Classes`;
            }
        }

        if (searchInput) searchInput.addEventListener('input', filterScheduleMatrixTable);
        if (filterYearLevel) filterYearLevel.addEventListener('change', filterScheduleMatrixTable);
        if (filterSemester) filterSemester.addEventListener('change', filterScheduleMatrixTable);

        filterScheduleMatrixTable();
    });
</script>

<style>
    .sa-container { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #0f172a; max-width: 1400px; margin: 0 auto; padding: 20px; }
    .sa-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 2px solid #e2e8f0; padding-bottom: 18px; }
    .header-titles h2 { margin: 0 0 4px 0; font-size: 28px; font-weight: 800; color: #0f172a; letter-spacing: -0.5px; }
    .header-titles p { margin: 0; font-size: 14.5px; color: #64748b; }
    .sa-workspace { display: flex; flex-direction: row; width: 100%; }
    .table-panel { flex: 1; width: 100%; background: #ffffff; }
    .sa-card { border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 10px -2px rgba(0, 0, 0, 0.03); overflow: hidden; }
    .card-header-flex { background-color: #f8fafc; padding: 16px 22px; border-bottom: 1px solid #e2e8f0; }
    .card-title-list { font-size: 14.5px; font-weight: 700; color: #1e293b; margin: 0; }
    .select-input { border: 1px solid #cbd5e1; border-radius: 6px; padding: 10px 14px; font-size: 14px; color: #0f172a; outline: none; background: #ffffff; box-sizing: border-box; width: 100%; }
    .select-input:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15); }
    .select-input { background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e"); background-position: right 12px center; background-repeat: no-repeat; background-size: 20px; -webkit-appearance: none; -moz-appearance: none; appearance: none; padding-right: 40px; cursor: pointer; }
    .table-responsive { width: 100%; overflow-x: auto; }
    .minimal-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 13.5px; }
    .minimal-table th { background: #f8fafc; padding: 14px 20px; font-size: 11.5px; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; }
    .minimal-table td { padding: 15px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
    .minimal-table tbody tr:hover { background-color: #f8fafc; }
    .subject-code-tag { font-family: ui-monospace, SFMono-Regular, Consolas, monospace; font-weight: 700; color: #4f46e5; background-color: #eef2ff; padding: 4px 8px; border-radius: 6px; font-size: 12px; display: inline-block; }
    .sched-info-tag { font-size: 11.5px; font-weight: 600; color: #1e293b; background-color: #f1f5f9; padding: 3px 8px; border-radius: 4px; display: inline-block; margin-right: 4px; border: 1px solid #e2e8f0; }
    .sched-info-tag i { color: #475569; margin-right: 3px; }
    .prof-assigned-tag { font-size: 12.5px; font-weight: 600; color: #047857; background-color: #ecfdf5; border: 1px solid #a7f3d0; padding: 4px 10px; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px; }
    .prof-unassigned-tag { font-size: 12px; font-weight: 500; color: #64748b; background-color: #f1f5f9; padding: 4px 10px; border-radius: 6px; display: inline-block; font-style: italic; }
    .empty-table-state { text-align: center; color: #94a3b8; padding: 50px 0 !important; font-style: italic; font-size: 13.5px; }
    .badge-count { background: #e2e8f0; color: #334155; font-size: 11.5px; padding: 4px 12px; border-radius: 20px; font-weight: 700; }
</style>
@endsection