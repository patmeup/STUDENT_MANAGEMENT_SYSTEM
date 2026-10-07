@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

@php
    $initials = strtoupper(mb_substr($student->first_name, 0, 1) . mb_substr($student->last_name, 0, 1));
    $yearsPresent = $curriculumSubjects->pluck('year_level')->unique()->values();
    $defaultYear  = $yearsPresent->contains($student->year_level) ? $student->year_level : '';
@endphp

<div class="rg-page">

    <header class="rg-header">
        <div class="rg-person rg-person-lg">
            <span class="rg-avatar rg-avatar-lg" aria-hidden="true">{{ $initials }}</span>
            <div>
                <h1 class="rg-title">Plot Subjects</h1>
                <p class="rg-subtitle">
                    <strong>{{ strtoupper($student->last_name) }}</strong>, {{ $student->first_name }}
                    <span class="rg-id">{{ $student->student_id }}</span>
                </p>
                <div class="rg-meta">
                    <span class="rg-chip rg-chip-plain">{{ $student->course }}</span>
                    <span class="rg-chip rg-chip-plain">{{ $student->year_level ?: 'No year level' }}</span>
                    @if($student->section)
                        <span class="rg-chip rg-chip-blue">{{ $student->section }}</span>
                    @else
                        <span class="rg-unassigned">No block yet</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="rg-header-end">
            <div class="rg-period" title="Subjects are enrolled for this period">
                <span class="rg-period-label">Active Period</span>
                <span class="rg-period-value">{{ $activePeriod->school_year }}</span>
                <span class="rg-period-sem">{{ $activePeriod->semester }}</span>
            </div>
            <a href="{{ route('department_secretary.students') }}" class="rg-btn rg-btn-ghost">
                <i class="fas fa-arrow-left"></i> Back to list
            </a>
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

    <section class="rg-helper" aria-labelledby="helperTitle">
        <div class="rg-helper-text">
            <h2 id="helperTitle"><i class="fas fa-magic" style="color: var(--rg-blue); margin-right: 6px;"></i> Quick Block Assignment</h2>
            <p>Pick a block section to automatically fill in matching schedules. For irregular students, you can uncheck "Replace current selections" to add subjects while keeping existing choices[cite: 20].</p>
        </div>

        <div class="rg-helper-controls">
            <select id="helperSection" class="rg-select" aria-label="Block section">
                <option value="">Choose a block section</option>
                @foreach($availableSections as $sec)
                    <option value="{{ $sec }}">{{ strtoupper($sec) }}</option>
                @endforeach
            </select>

            <label class="rg-check">
                <input type="checkbox" id="replaceCurrent" checked>
                <span>Replace current selections</span>
            </label>

            <button type="button" class="rg-btn rg-btn-primary" id="autoBlockBtn">
                <i class="fas fa-bolt"></i> Apply block
            </button>
            <button type="button" class="rg-btn rg-btn-ghost" id="clearAllBtn">Clear all</button>
        </div>

        <div id="helperMsg" class="rg-helper-msg" role="status" aria-live="polite"></div>
    </section>

    <form action="{{ route('department_secretary.save_subjects') }}" method="POST">
        @csrf
        <input type="hidden" name="student_id" value="{{ $student->student_id }}">

        <div class="rg-card">
            <div class="rg-toolbar">
                <div class="rg-filter" role="group" aria-label="Filter by year level">
                    <span class="rg-filter-label">Filter:</span>
                    <button type="button" class="filter-pill {{ $defaultYear === '' ? 'active' : '' }}" data-filter-value="">All years</button>
                    @foreach($yearsPresent as $yr)
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
                            @endphp
                            <tr class="plot-row {{ $offered ? '' : 'is-unavailable' }}"
                                data-year="{{ $curricula->year_level }}"
                                data-sem="{{ $curricula->semester }}"
                                data-units="{{ $totalUnits }}">
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
        const msg         = document.getElementById('helperMsg');
        const yearNames   = { 1: '1st Year', 2: '2nd Year', 3: '3rd Year', 4: '4th Year' };

        let activeYear = (document.querySelector('.filter-pill.active') || {}).dataset?.filterValue ?? '';

        function say(text, kind) {
            msg.textContent = text;
            msg.className = 'rg-helper-msg ' + (kind ? 'is-' + kind : '');
        }

        function refresh() {
            let visible = 0, selected = 0, selectedHidden = 0, totalUnits = 0;

            rows.forEach(row => {
                const sel  = row.querySelector('.sub-selector');
                const show = activeYear === '' || row.dataset.year === activeYear;
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
            if (noMatchRow) noMatchRow.style.display = (visible === 0 && rows.length > 0) ? '' : 'none';
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

        document.getElementById('autoBlockBtn').addEventListener('click', function () {
            const section = document.getElementById('helperSection').value.trim();
            const replace = document.getElementById('replaceCurrent').checked;

            if (!section) { say('Choose a block section first.', 'warn'); return; }

            const matches = [];
            selects.forEach(sel => {
                if (sel.disabled) return;
                for (let i = 0; i < sel.options.length; i++) {
                    if (sel.options[i].value && sel.options[i].value.toLowerCase() === section.toLowerCase()) {
                        matches.push({ sel: sel, value: sel.options[i].value });
                        break;
                    }
                }
            });

            if (matches.length === 0) {
                say(section.toUpperCase() + ' has no schedules for these subjects. Nothing was changed.', 'warn');
                return;
            }

            if (replace) selects.forEach(sel => { if (!sel.disabled) sel.value = ''; });
            matches.forEach(m => { m.sel.value = m.value; });

            const digit = (section.match(/\d/) || [])[0];
            const targetYear = digit ? yearNames[digit] : null;
            const targetPill = targetYear ? document.querySelector('.filter-pill[data-filter-value="' + targetYear + '"]') : null;
            if (targetPill) targetPill.click(); else refresh();

            say(matches.length + (matches.length === 1 ? ' subject' : ' subjects') + ' set to ' + section.toUpperCase()
                + (replace ? '. Earlier selections were replaced.' : '. Other selections were kept.'), 'ok');
        });

        document.getElementById('clearAllBtn').addEventListener('click', function () {
            selects.forEach(sel => { if (!sel.disabled) sel.value = ''; });
            refresh();
            say('All selections cleared. Save only when you have picked at least one schedule.', 'warn');
        });

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

    .rg-header { display: flex; justify-content: space-between; align-items: flex-end; gap: 20px; flex-wrap: wrap; padding-bottom: 18px; margin-bottom: 20px; border-bottom: 1px solid var(--rg-line); }
    .rg-header-end { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
    .rg-title { margin: 0; font-size: 26px; font-weight: 800; letter-spacing: -0.2px; color: var(--rg-blue-dark); }
    .rg-subtitle { margin: 4px 0 8px; font-size: 14px; color: var(--rg-muted); }
    .rg-subtitle strong { color: var(--rg-ink); }
    .rg-id { margin-left: 8px; font-size: 12.5px; font-variant-numeric: tabular-nums; }
    .rg-meta { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }

    .rg-person { display: flex; align-items: center; gap: 14px; }
    .rg-avatar { flex: 0 0 auto; width: 36px; height: 36px; border-radius: 50%; background: var(--rg-blue-tint); color: var(--rg-blue); display: inline-flex; align-items: center; justify-content: center; font-size: 12.5px; font-weight: 800; }
    .rg-avatar-lg { width: 56px; height: 56px; font-size: 18px; }

    .rg-period { display: grid; grid-template-columns: auto auto; column-gap: 10px; align-items: baseline; background: #fff; border: 1px solid var(--rg-line); border-left: 4px solid var(--rg-gold); border-radius: 8px; padding: 9px 16px 9px 14px; }
    .rg-period-label { grid-column: 1 / -1; font-size: 11.5px; font-weight: 600; color: var(--rg-muted); margin-bottom: 2px; }
    .rg-period-value { font-size: 15px; font-weight: 800; color: var(--rg-blue-dark); }
    .rg-period-sem { font-size: 13px; font-weight: 600; color: var(--rg-blue); }

    .rg-alert { display: flex; gap: 10px; align-items: flex-start; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 13.5px; font-weight: 600; line-height: 1.45; }
    .rg-alert i { margin-top: 2px; }
    .rg-alert ul { margin: 0; padding-left: 18px; }
    .rg-alert-error { background: #FDECEC; color: #8E1B1B; border: 1px solid #F5C6C6; border-left: 4px solid var(--rg-red); }

    .rg-helper { background: #ffffff; border: 1px solid #cbd5e1; border-top: 4px solid var(--rg-blue); border-radius: 10px; padding: 18px 20px; margin-bottom: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    .rg-helper h2 { margin: 0 0 4px; font-size: 15px; font-weight: 800; color: var(--rg-blue-dark); display: flex; align-items: center; }
    .rg-helper p { margin: 0 0 14px; font-size: 13px; line-height: 1.5; color: #475569; max-width: 760px; }
    .rg-helper-controls { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
    .rg-helper-msg { margin-top: 10px; font-size: 13px; font-weight: 600; min-height: 18px; }
    .rg-helper-msg.is-ok { color: var(--rg-green); }
    .rg-helper-msg.is-warn { color: #B45309; }

    .rg-select { padding: 9px 12px; font-size: 13.5px; color: var(--rg-ink); background: #fff; border: 1px solid #CBD5E6; border-radius: 8px; min-width: 220px; cursor: pointer; }
    .rg-select:focus { outline: none; border-color: var(--rg-blue); box-shadow: 0 0 0 3px rgba(0, 51, 160, 0.14); }
    .rg-select:disabled { background: var(--rg-surface); color: #8A97B3; cursor: not-allowed; }
    .rg-select-block { width: 100%; min-width: 320px; max-width: 520px; font-size: 12.5px; }

    .rg-check { display: inline-flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: #26345A; cursor: pointer; }
    .rg-check input { width: 16px; height: 16px; accent-color: var(--rg-blue); }

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