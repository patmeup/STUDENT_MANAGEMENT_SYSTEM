@extends('layouts.app')

@section('content')
<div class="sa-container">
    
    <div class="sa-header">
        <div class="header-titles">
            <h2>Class Schedules</h2>
            <p>View and monitor department class schedules and faculty assignments.</p>
        </div>
    </div>

    <div class="sa-workspace">

        <div class="sa-card table-panel">
            <div class="card-header-flex" style="display: flex; flex-direction: column; gap: 14px; align-items: stretch;">
                <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                    <h3 class="card-title-list">Class Schedule List</h3>
                    <span class="badge-count" id="visibleClassesCount">{{ isset($schedules) ? $schedules->count() : 0 }} Active Classes</span>
                </div>

                <div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center; width: 100%;">
                    <div style="flex: 2; min-width: 280px;">
                        <input type="text" id="scheduleSearchInput" placeholder="Search by Subject Code, Title, Section, Room, or Professor..." 
                            class="custom-search-input">
                    </div>

                    <div style="display: flex; gap: 6px; flex-wrap: wrap; align-items: center;">
                        <span style="font-size: 11.5px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-right: 2px;">Year:</span>
                        <button type="button" class="filter-pill active" data-filter-type="year" data-filter-value="">All</button>
                        <button type="button" class="filter-pill" data-filter-type="year" data-filter-value="1st Year">1st</button>
                        <button type="button" class="filter-pill" data-filter-type="year" data-filter-value="2nd Year">2nd</button>
                        <button type="button" class="filter-pill" data-filter-type="year" data-filter-value="3rd Year">3rd</button>
                        <button type="button" class="filter-pill" data-filter-type="year" data-filter-value="4th Year">4th</button>
                    </div>

                    <div style="display: flex; gap: 6px; flex-wrap: wrap; align-items: center;">
                        <span style="font-size: 11.5px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-right: 2px;">Sem:</span>
                        <button type="button" class="filter-pill active" data-filter-type="sem" data-filter-value="">All</button>
                        <button type="button" class="filter-pill" data-filter-type="sem" data-filter-value="1st Semester">1st</button>
                        <button type="button" class="filter-pill" data-filter-type="sem" data-filter-value="2nd Semester">2nd</button>
                        <button type="button" class="filter-pill" id="summerPillButton" data-filter-type="sem" data-filter-value="Summer" style="display: none;">Summer</button>
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
                            <tr id="noMatchRow" style="display: none;">
                                <td colspan="5" class="empty-table-state">
                                    No class schedules match your search or filter criteria.
                                </td>
                            </tr>
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
        const searchInput = document.getElementById('scheduleSearchInput');
        const filterPills = document.querySelectorAll('.filter-pill');
        const summerPillButton = document.getElementById('summerPillButton');
        const tableRows = document.querySelectorAll('.schedule-row-item');
        const noMatchRow = document.getElementById('noMatchRow');
        const visibleClassesCount = document.getElementById('visibleClassesCount');
        const initialTotal = tableRows.length;

        let activeYear = '';
        let activeSem = '';

        function updateSummerButtonVisibility() {
            if (!summerPillButton) return;
            if (activeYear === '3rd Year') {
                summerPillButton.style.display = '';
            } else {
                summerPillButton.style.display = 'none';
                if (activeSem === 'Summer') {
                    activeSem = '';
                    document.querySelectorAll('[data-filter-type="sem"]').forEach(p => {
                        p.classList.remove('active');
                        if (p.getAttribute('data-filter-value') === '') p.classList.add('active');
                    });
                }
            }
        }

        function filterTable() {
            const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
            let visibleCount = 0;

            tableRows.forEach(row => {
                const rowText = row.innerText.toLowerCase();
                const rowYear = row.getAttribute('data-year') || '';
                const rowSem = row.getAttribute('data-sem') || '';

                const matchesSearch = rowText.includes(searchTerm);
                const matchesYear = activeYear === '' || rowYear === activeYear;
                const matchesSem = activeSem === '' || rowSem === activeSem;

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

            if (noMatchRow) {
                if (visibleCount === 0 && initialTotal > 0) {
                    noMatchRow.style.display = '';
                } else {
                    noMatchRow.style.display = 'none';
                }
            }
        }

        filterPills.forEach(pill => {
            pill.addEventListener('click', function () {
                const type = this.getAttribute('data-filter-type');
                const value = this.getAttribute('data-filter-value');

                document.querySelectorAll(`.filter-pill[data-filter-type="${type}"]`).forEach(p => {
                    p.classList.remove('active');
                });
                this.classList.add('active');

                if (type === 'year') {
                    activeYear = value;
                    updateSummerButtonVisibility();
                } else if (type === 'sem') {
                    activeSem = value;
                }

                filterTable();
            });
        });

        if (searchInput) {
            searchInput.addEventListener('input', filterTable);
        }

        updateSummerButtonVisibility();
        filterTable();
    });
</script>

<style>
    .sa-container { font-family: 'Segoe UI', sans-serif; padding: 20px; max-width: 1300px; margin: 0 auto; }
    .sa-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #e2e8f0; padding-bottom: 18px; }
    .header-titles h2 { margin: 0 0 4px 0; font-size: 26px; font-weight: 800; color: #0f172a; letter-spacing: -0.5px; }
    .header-titles p { margin: 0; font-size: 14px; color: #64748b; }
    .sa-workspace { display: flex; flex-direction: row; width: 100%; }
    .table-panel { flex: 1; width: 100%; background: #ffffff; }
    .sa-card { border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); overflow: hidden; }
    .card-header-flex { background-color: #f8fafc; padding: 16px 24px; border-bottom: 1px solid #e2e8f0; }
    .card-title-list { font-size: 14.5px; font-weight: 700; color: #1e293b; margin: 0; }
    
    .custom-search-input { width: 100%; padding: 9px 12px; font-size: 13.5px; color: #334155; border: 1px solid #cbd5e1; border-radius: 6px; outline: none; box-sizing: border-box; background: #fff; }
    .custom-search-input:focus { border-color: #cbd5e1 !important; box-shadow: none !important; outline: none !important; }

    .filter-pill { background: #f1f5f9; border: 1px solid #cbd5e1; color: #475569; padding: 5px 10px; font-size: 11.5px; font-weight: 600; border-radius: 20px; cursor: pointer; transition: all 0.15s ease; }
    .filter-pill:hover { background: #e2e8f0; color: #0f172a; }
    .filter-pill.active { background: #0f172a; color: #ffffff; border-color: #0f172a; box-shadow: 0 2px 4px rgba(15, 23, 42, 0.2); }

    .table-responsive { width: 100%; overflow-x: auto; }
    .minimal-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 13.5px; }
    .minimal-table th { background: #f8fafc; padding: 12px 24px; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; }
    .minimal-table td { padding: 14px 24px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
    .minimal-table tbody tr:hover { background-color: #f8fafc; }
    .subject-code-tag { font-family: ui-monospace, SFMono-Regular, Consolas, monospace; font-weight: 700; color: #2563eb; background-color: #eff6ff; padding: 4px 8px; border-radius: 6px; font-size: 12px; display: inline-block; border: 1px solid #dbeafe; }
    .sched-info-tag { font-size: 11.5px; font-weight: 600; color: #1e293b; background-color: #f1f5f9; padding: 3px 8px; border-radius: 4px; display: inline-block; margin-right: 4px; border: 1px solid #e2e8f0; }
    .sched-info-tag i { color: #475569; margin-right: 3px; }
    .empty-table-state { text-align: center; color: #94a3b8; padding: 50px 0 !important; font-style: italic; font-size: 13.5px; }
    .badge-count { background: #0f172a; color: white; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 12px; }
</style>
@endsection