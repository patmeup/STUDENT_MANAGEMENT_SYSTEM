@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

@php
    $initials = strtoupper(mb_substr($student->first_name, 0, 1) . mb_substr($student->last_name, 0, 1));
    $allYears = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
    $defaultYear  = in_array($student->year_level, $allYears) ? $student->year_level : '';
@endphp

<div class="rg-page">

    <header class="rg-header" style="display: block;">
        <div style="margin-bottom: 14px;">
            <a href="{{ route('department_secretary.students') }}" class="rg-btn rg-btn-ghost">
                <i class="fas fa-arrow-left"></i> Back to list
            </a>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: flex-end; gap: 20px; flex-wrap: wrap;">
            <div>
                <h1 class="rg-title">Plot Subjects</h1>
                <p class="rg-subtitle">Choose and assign class schedules for the student this semester. Use the search and filters to easily find the subjects.</p>
            
                <div class="rg-meta" style="margin-top: 14px; padding-top: 14px; border-top: 1px dashed var(--rg-line);">
                    <span style="font-weight: 700; color: var(--rg-blue-dark); font-size: 15px; margin-right: 8px;">
                        <i class="fas fa-user-graduate" style="color: var(--rg-muted); margin-right: 4px;"></i>
                        {{ strtoupper($student->last_name) }}, {{ $student->first_name }} 
                        <span style="color: var(--rg-muted); font-weight: 600; font-size: 13px;">({{ $student->student_id }})</span>
                    </span>
                    <span class="rg-chip rg-chip-plain">{{ $student->course }}</span>
                    
                    <span class="rg-chip rg-chip-plain">
                        {{ $student->year_level ?: 'No year level' }}
                        @if(!empty($student->semester))
                            • {{ $student->semester }}
                        @endif
                    </span>

                    @if($student->section)
                        <span class="rg-chip rg-chip-blue">{{ $student->section }}</span>
                    @else
                        <span class="rg-unassigned">No block yet</span>
                    @endif
                </div>
            </div>

            <div class="rg-header-end">
                <div class="rg-period" title="Subjects are enrolled for this period">
                    <span class="rg-period-label">Active Period</span>
                    <span class="rg-period-value">{{ $activePeriod->school_year }}</span>
                    <span class="rg-period-sem">{{ $activePeriod->semester }}</span>
                </div>
            </div>
        </div>
    </header>

    @if(session('error'))
        <div class="rg-alert rg-alert-error" role="alert">
            <i class="fas fa-exclamation-circle"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="rg-alert rg-alert-error" role="alert">
            <i class="fas fa-exclamation-circle"></i>
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('department_secretary.save_subjects') }}" method="POST">
        @csrf
        <input type="hidden" name="student_id" value="{{ $student->student_id }}">

        <div class="rg-card">
            <div class="rg-toolbar">
                <div class="rg-search" style="margin-right: auto; max-width: 320px;">
                    <i class="fas fa-search"></i>
                    <input type="text" id="subjectSearchInput" placeholder="Search subject code or title..." aria-label="Search subjects">
                </div>

                <div class="rg-filter" role="group" aria-label="Filter by year level">
                    <span class="rg-filter-label">Filter:</span>
                    <button type="button" class="filter-pill {{ $defaultYear === '' ? 'active' : '' }}" data-filter-value="">All years</button>
                    @foreach($allYears as $yr)
                        <button type="button" class="filter-pill {{ $defaultYear === $yr ? 'active' : '' }}" data-filter-value="{{ $yr }}">{{ $yr }}</button>
                    @endforeach
                </div>
                <span class="rg-result-count" id="visibleCount"></span>
            </div>

            <div class="rg-table-wrap">
                <table class="rg-table" id="subjectPlotTable">
                    <thead>
                        <tr>
                            <th style="width: 32%;">Subject</th>
                            <th style="width: 18%;">Year & Semester</th>
                            <th class="text-center" style="width: 10%;">Units</th>
                            <th style="width: 40%;">Schedule Selection</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($curriculumSubjects as $curricula)
                            @php
                                $subjectSchedules = $classSchedules->where('subject_id', $curricula->subject_id)->groupBy('section_name');
                                $currentSection   = $enrolledSections[$curricula->subject_id] ?? null;
                                $offered          = $subjectSchedules->count() > 0;
                                $totalUnits       = $curricula->lecture_units + $curricula->lab_units;
                                $searchText       = strtolower($curricula->subject_code . ' ' . $curricula->subject_title);
                            @endphp
                            <tr class="plot-row {{ $offered ? '' : 'is-unavailable' }}"
                                data-year="{{ $curricula->year_level }}"
                                data-sem="{{ $curricula->semester }}"
                                data-units="{{ $totalUnits }}"
                                data-search="{{ $searchText }}">
                                <td>
                                    <div class="rg-subject-code">{{ $curricula->subject_code }}</div>
                                    <div class="rg-subject-title">{{ $curricula->subject_title }}</div>
                                </td>
                                <td>
                                    <span class="rg-chip rg-chip-plain">{{ $curricula->year_level }}</span>
                                    <span class="rg-chip rg-chip-plain">{{ $curricula->semester }}</span>
                                </td>
                                <td class="text-center"><strong>{{ $totalUnits }}</strong></td>
                                <td>
                                    <select name="schedules[{{ $curricula->subject_id }}]" class="rg-select rg-select-block sub-selector" {{ $offered ? '' : 'disabled' }}>
                                        @if($offered)
                                            <option value="">Not enrolled</option>
                                            @foreach($subjectSchedules as $secName => $schedGroup)
                                                @php
                                                    $details = [];
                                                    foreach($schedGroup as $s) {
                                                        $timeStr = date('g:i A', strtotime($s->start_time)) . '-' . date('g:i A', strtotime($s->end_time));
                                                        $details[] = "{$s->schedule_type}: {$s->class_days} ({$timeStr}) [{$s->room_assignment}]";
                                                    }
                                                    $label = strtoupper($secName) . '  |  ' . implode('  |  ', $details);
                                                @endphp
                                                <option value="{{ $secName }}" {{ $currentSection == $secName ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        @else
                                            <option value="">Not offered this period</option>
                                        @endif
                                    </select>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">
                                    <div class="rg-empty">
                                        <i class="fas fa-book-open"></i>
                                        <strong>No subjects found for this period</strong>
                                        <span>Add the course curriculum in Subject Catalog, then deploy schedules in Manage Schedules.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                        <tr id="plotNoMatchRow" style="display:none;">
                            <td colspan="4">
                                <div class="rg-empty">
                                    <i class="fas fa-filter"></i>
                                    <strong>No subjects for this year level</strong>
                                    <span>Choose another year level or "All years".</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rg-savebar">
            <div class="rg-savebar-info">
                <div>
                    <strong id="selectedCount">0</strong> <span id="selectedLabel">subjects selected</span>
                    <span id="hiddenSelected" class="rg-savebar-note"></span>
                </div>
                <div style="font-size: 13px; color: var(--rg-muted); margin-top: 2px;">
                    Total Units: <strong id="totalUnitsCount" style="color: var(--rg-blue-dark);">0</strong> units
                </div>
            </div>
            <button type="submit" class="rg-btn rg-btn-primary rg-btn-lg">
                <i class="fas fa-save"></i> Save enrolled subjects
            </button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const rows        = Array.from(document.querySelectorAll('.plot-row'));
        const selects     = Array.from(document.querySelectorAll('.sub-selector'));
        const pills       = document.querySelectorAll('.filter-pill');
        const noMatchRow  = document.getElementById('plotNoMatchRow');
        const visibleText = document.getElementById('visibleCount');
        const selCount    = document.getElementById('selectedCount');
        const selLabel    = document.getElementById('selectedLabel');
        const hiddenNote  = document.getElementById('hiddenSelected');
        const totalUnitsElem = document.getElementById('totalUnitsCount');
        const searchInput = document.getElementById('subjectSearchInput');

        let activeYear = (document.querySelector('.filter-pill.active') || {}).dataset?.filterValue ?? '';

        function refresh() {
            let visible = 0, selected = 0, selectedHidden = 0, totalUnits = 0;
            const query = searchInput ? searchInput.value.toLowerCase().trim() : '';

            rows.forEach(row => {
                const sel  = row.querySelector('.sub-selector');
                
                const matchesYear = activeYear === '' || row.dataset.year === activeYear;
                const matchesSearch = query === '' || (row.dataset.search || '').includes(query);
                
                const show = matchesYear && matchesSearch;
                const has  = !!(sel && sel.value);
                const units = parseFloat(row.dataset.units) || 0;

                row.style.display = show ? '' : 'none';
                row.classList.toggle('is-selected', has);

                if (show) visible++;
                if (has) { 
                    selected++; 
                    totalUnits += units;
                    if (!show) selectedHidden++; 
                }
            });

            visibleText.textContent = visible + (visible === 1 ? ' subject shown' : ' subjects shown');
            selCount.textContent = selected;
            selLabel.textContent = selected === 1 ? 'subject selected' : 'subjects selected';
            hiddenNote.textContent = selectedHidden > 0 ? '(' + selectedHidden + ' in other year levels)' : '';
            totalUnitsElem.textContent = totalUnits;
            
            if (noMatchRow) {
                const emptyTitle = noMatchRow.querySelector('strong');
                const emptySub = noMatchRow.querySelector('span');
                if (query !== '') {
                    emptyTitle.textContent = 'No subjects match your search';
                    emptySub.textContent = 'Try checking your spelling or clear the search box.';
                } else {
                    emptyTitle.textContent = 'No subjects for this year level';
                    emptySub.textContent = 'Choose another year level or "All years".';
                }
                noMatchRow.style.display = (visible === 0 && rows.length > 0) ? '' : 'none';
            }
        }

        pills.forEach(pill => {
            pill.addEventListener('click', function () {
                pills.forEach(p => p.classList.remove('active'));
                this.classList.add('active');
                activeYear = this.dataset.filterValue;
                refresh();
            });
        });

        selects.forEach(sel => sel.addEventListener('change', refresh));
        
        if (searchInput) {
            searchInput.addEventListener('input', refresh);
        }

        refresh();
    });
</script>

<style>
    .rg-page {
        --rg-blue: #0033A0; --rg-blue-dark: #0A1F5C; --rg-blue-tint: #EAF0FF; --rg-gold: #F2B705;
        --rg-ink: #0B1B3A; --rg-muted: #5C6B8A; --rg-line: #E2E8F3; --rg-surface: #F7F9FD;
        --rg-green: #15803D; --rg-green-tint: #E8F7EE; --rg-red: #C62828;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: var(--rg-ink);
        max-width: 1300px; margin: 0 auto; padding: 24px 20px 40px;
    }

    .rg-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; flex-wrap: wrap; padding-bottom: 18px; margin-bottom: 20px; border-bottom: 1px solid var(--rg-line); }
    .rg-header-end { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
    .rg-title { margin: 0; font-size: 26px; font-weight: 800; letter-spacing: -0.2px; color: var(--rg-blue-dark); }
    .rg-subtitle { margin: 4px 0 0; font-size: 14px; color: var(--rg-muted); max-width: 600px; line-height: 1.5; }
    
    .rg-meta { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }

    .rg-period { display: grid; grid-template-columns: auto auto; column-gap: 10px; align-items: baseline; background: #fff; border: 1px solid var(--rg-line); border-left: 4px solid var(--rg-gold); border-radius: 8px; padding: 9px 16px 9px 14px; }
    .rg-period-label { grid-column: 1 / -1; font-size: 11.5px; font-weight: 600; color: var(--rg-muted); margin-bottom: 2px; }
    .rg-period-value { font-size: 15px; font-weight: 800; color: var(--rg-blue-dark); }
    .rg-period-sem { font-size: 13px; font-weight: 600; color: var(--rg-blue); }

    .rg-search { position: relative; flex: 1 1 200px; }
    .rg-search i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #8A97B3; font-size: 13px; pointer-events: none; }
    .rg-search input { width: 100%; box-sizing: border-box; padding: 9px 12px 9px 34px; font-size: 13.5px; color: var(--rg-ink); background: #fff; border: 1px solid #CBD5E6; border-radius: 8px; }
    .rg-search input::placeholder { color: #8A97B3; }
    .rg-search input:focus { outline: none; border-color: var(--rg-blue); box-shadow: 0 0 0 3px rgba(0, 51, 160, 0.14); }

    .rg-alert { display: flex; gap: 10px; align-items: flex-start; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 13.5px; font-weight: 600; line-height: 1.45; }
    .rg-alert i { margin-top: 2px; }
    .rg-alert ul { margin: 0; padding-left: 18px; }
    .rg-alert-error { background: #FDECEC; color: #8E1B1B; border: 1px solid #F5C6C6; border-left: 4px solid var(--rg-red); }

    .rg-select { padding: 9px 12px; font-size: 13.5px; color: var(--rg-ink); background: #fff; border: 1px solid #CBD5E6; border-radius: 8px; min-width: 220px; cursor: pointer; }
    .rg-select:focus { outline: none; border-color: var(--rg-blue); box-shadow: 0 0 0 3px rgba(0, 51, 160, 0.14); }
    .rg-select:disabled { background: var(--rg-surface); color: #8A97B3; cursor: not-allowed; }
    .rg-select-block { width: 100%; min-width: 320px; max-width: 520px; font-size: 12.5px; }

    .rg-card { background: #fff; border: 1px solid var(--rg-line); border-radius: 10px; overflow: hidden; box-shadow: 0 1px 2px rgba(10, 31, 92, 0.05); }
    .rg-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 14px 18px; padding: 14px 20px; border-bottom: 1px solid var(--rg-line); background: #f8fafc; }
    .rg-filter { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
    .rg-filter-label { font-size: 12.5px; font-weight: 700; color: var(--rg-muted); margin-right: 2px; }
    .filter-pill { background: #fff; border: 1px solid #CBD5E6; color: var(--rg-muted); padding: 5px 14px; font-size: 12.5px; font-weight: 600; border-radius: 16px; cursor: pointer; transition: background .15s, color .15s, border-color .15s; }
    .filter-pill:hover { border-color: var(--rg-blue); color: var(--rg-blue); }
    .filter-pill.active { background: var(--rg-blue); border-color: var(--rg-blue); color: #fff; }
    .rg-result-count { margin-left: auto; font-size: 12.5px; font-weight: 600; color: var(--rg-muted); }

    .rg-table-wrap { overflow-x: auto; }
    .rg-table { width: 100%; border-collapse: collapse; font-size: 14px; }
    .rg-table th { background: var(--rg-surface); padding: 12px 20px; font-size: 12px; font-weight: 700; color: var(--rg-muted); text-align: left; border-bottom: 1px solid var(--rg-line); white-space: nowrap; }
    .rg-table td { padding: 14px 20px; border-bottom: 1px solid #EEF2F9; color: #26345A; vertical-align: middle; }
    .rg-table tbody tr:last-child td { border-bottom: none; }
    .plot-row { box-shadow: inset 3px 0 0 transparent; transition: background .15s, box-shadow .15s; }
    .plot-row:hover { background: #F5F8FF; }
    .plot-row.is-selected { background: #F3F7FF; box-shadow: inset 3px 0 0 var(--rg-blue); }
    .plot-row.is-unavailable td { opacity: 0.6; }
    .rg-subject-code { font-weight: 800; color: var(--rg-blue); font-size: 13.5px; }
    .rg-subject-title { margin-top: 1px; font-size: 13px; color: #3B4A6B; }

    .rg-chip { display: inline-block; padding: 3px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; white-space: nowrap; }
    .rg-chip-plain { background: var(--rg-surface); color: #3B4A6B; border: 1px solid var(--rg-line); }
    .rg-chip-blue { background: var(--rg-blue-tint); color: var(--rg-blue); border: 1px solid #CFDBFB; }
    .rg-unassigned { font-size: 12.5px; color: #8A97B3; font-style: italic; }

    .rg-btn { display: inline-flex; align-items: center; gap: 7px; padding: 8px 15px; border-radius: 7px; font-size: 12.5px; font-weight: 700; text-decoration: none; cursor: pointer; border: 1px solid transparent; transition: background .15s, color .15s, border-color .15s; white-space: nowrap; line-height: 1.2; }
    .rg-btn-lg { padding: 11px 22px; font-size: 14px; }
    .rg-btn-primary { background: var(--rg-blue); color: #fff; }
    .rg-btn-primary:hover { background: var(--rg-blue-dark); color: #fff; }
    .rg-btn-ghost { background: #fff; color: var(--rg-blue); border-color: #B9C8F0; }
    .rg-btn-ghost:hover { background: var(--rg-blue-tint); color: var(--rg-blue-dark); }

    .rg-savebar { position: sticky; bottom: 0; z-index: 5; display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; margin-top: 16px; padding: 14px 20px; background: #fff; border: 1px solid var(--rg-line); border-top: 3px solid var(--rg-gold); border-radius: 10px; box-shadow: 0 -4px 14px rgba(10, 31, 92, 0.08); }
    .rg-savebar-info { font-size: 14px; color: #26345A; display: flex; flex-direction: column; gap: 2px; }
    .rg-savebar-info strong { font-size: 16px; color: var(--rg-blue-dark); }
    .rg-savebar-note { margin-left: 6px; font-size: 12.5px; color: var(--rg-muted); }

    .rg-empty { display: flex; flex-direction: column; align-items: center; gap: 6px; padding: 44px 16px; text-align: center; color: var(--rg-muted); font-size: 13.5px; }
    .rg-empty i { font-size: 28px; color: #B9C4DD; margin-bottom: 4px; }
    .rg-empty strong { color: var(--rg-ink); font-size: 15px; }
</style>
@endsection