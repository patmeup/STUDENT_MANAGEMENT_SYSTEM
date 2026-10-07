@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

@php
    $activePeriod = $activePeriod ?? \Illuminate\Support\Facades\DB::table('academic_periods')->where('is_active', true)->first();

    $promoteConfirm = "Promote blocks for the new school year?\n\n"
        . "Passed students move up one year (BSIT1 to BSIT2, BSIT2 to BSIT3, BSIT3 to BSIT4). Passed 4th years become Graduated.\n"
        . "Students with a failed or missing final grade stay in their current block.\n\n"
        . "Run this before assigning new 1st years and before bulk enrolling the new school year.";
@endphp

<div class="rg-page">

    <header class="rg-header">
        <div>
            <h1 class="rg-title">Department Student Registry</h1>
            <p class="rg-subtitle">View, search, and manage student records and block sections.</p>
        </div>

        <div class="rg-period" title="Bulk enrollment applies to this period">
            <span class="rg-period-label">Active period</span>
            @if($activePeriod)
                <span class="rg-period-value">{{ $activePeriod->school_year }}</span>
                <span class="rg-period-sem">{{ $activePeriod->semester }}</span>
            @else
                <span class="rg-period-value rg-period-none">None set</span>
            @endif
        </div>
    </header>

    @if(session('success'))
        <div class="rg-alert rg-alert-success" role="status">
            <i class="fas fa-check-circle"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

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

    <div class="rg-tabs" role="tablist">
        <button type="button" class="rg-tab active" role="tab" aria-selected="true"
                onclick="switchView('individualTab', this)">
            <i class="fas fa-user"></i> Individual list
            <span class="rg-tab-count">{{ count($students) }}</span>
        </button>
        <button type="button" class="rg-tab" role="tab" aria-selected="false"
                onclick="switchView('sectionTab', this)">
            <i class="fas fa-layer-group"></i> Block sections
            <span class="rg-tab-count">{{ $sections->count() }}</span>
        </button>
    </div>

    <section id="individualTab" class="rg-panel active-panel">
        <div class="rg-card">
            <div class="rg-toolbar">
                <div class="rg-search">
                    <i class="fas fa-search"></i>
                    <input type="text" id="studentSearchInput"
                           placeholder="Search by student ID, name, or course"
                           aria-label="Search students">
                </div>

                <div class="rg-filter" role="group" aria-label="Filter by year level">
                    <span class="rg-filter-label">Year level</span>
                    <button type="button" class="filter-pill active" data-filter-value="">All</button>
                    <button type="button" class="filter-pill" data-filter-value="1st Year">1st</button>
                    <button type="button" class="filter-pill" data-filter-value="2nd Year">2nd</button>
                    <button type="button" class="filter-pill" data-filter-value="3rd Year">3rd</button>
                    <button type="button" class="filter-pill" data-filter-value="4th Year">4th</button>
                </div>

                <span class="rg-result-count" id="studentTotalCount">{{ count($students) }} students</span>
            </div>

            <div class="rg-table-wrap">
                <table class="rg-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Course</th>
                            <th>Section</th>
                            <th>Year level</th>
                            <th class="rg-th-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="studentTableBody">
                        @forelse($students as $student)
                            @php
                                $initials = strtoupper(mb_substr($student->first_name, 0, 1) . mb_substr($student->last_name, 0, 1));
                                $searchText = strtolower($student->student_id . ' ' . $student->last_name . ' ' . $student->first_name . ' ' . $student->course . ' ' . $student->section);
                            @endphp
                            <tr class="student-row" data-year="{{ $student->year_level }}" data-search="{{ $searchText }}">
                                <td>
                                    <div class="rg-person">
                                        <div>
                                            <div class="rg-person-name"><strong>{{ strtoupper($student->last_name) }}</strong>, {{ $student->first_name }}</div>
                                            <div class="rg-person-id">{{ $student->student_id }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="rg-chip rg-chip-plain">{{ $student->course }}</span></td>
                                <td>
                                    @if($student->section)
                                        <span class="rg-chip rg-chip-blue">{{ $student->section }}</span>
                                    @else
                                        <span class="rg-unassigned">Unassigned</span>
                                    @endif
                                </td>
                                <td><span class="rg-chip rg-chip-plain">{{ $student->year_level }}</span></td>
                                <td>
                                    <div class="rg-actions">
                                        <a href="{{ route('department_secretary.students.profile', $student->student_id) }}"
                                           class="rg-btn rg-btn-ghost" title="View student profile">
                                            <i class="fas fa-folder-open"></i> Profile
                                        </a>

                                        <a href="{{ route('department_secretary.plot_subjects', $student->student_id) }}"
                                           class="rg-btn rg-btn-primary" title="Plot subjects for this student">
                                            <i class="fas fa-th-list"></i> Plot subjects
                                        </a>

                                        <form action="{{ route('department_secretary.delete_student', $student->student_id) }}" method="POST"
                                              data-confirm="Delete student {{ $student->student_id }}? This permanently removes their record from the database.">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rg-btn-icon rg-btn-danger"
                                                    title="Delete student" aria-label="Delete student {{ $student->student_id }}">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr id="studentEmptyRow">
                                <td colspan="5">
                                    <div class="rg-empty">
                                        <i class="fas fa-user-graduate"></i>
                                        <strong>No student records yet</strong>
                                        <span>Students registered under your department will show up here.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                        <tr id="studentNoMatchRow" style="display: none;">
                            <td colspan="5">
                                <div class="rg-empty">
                                    <i class="fas fa-search"></i>
                                    <strong>No students match your search</strong>
                                    <span>Try a different name, ID, or year level.</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section id="sectionTab" class="rg-panel">
        <div class="rg-card">
            <div class="rg-toolbar">
                <div class="rg-search">
                    <i class="fas fa-search"></i>
                    <input type="text" id="blockSearchInput" placeholder="Search by block section name" aria-label="Search block sections">
                </div>

                <form action="{{ route('department_secretary.promote_blocks') }}" method="POST"
                      class="rg-toolbar-end" data-confirm="{{ $promoteConfirm }}">
                    @csrf
                    <button type="submit" class="rg-btn rg-btn-outline-navy">
                        <i class="fas fa-level-up-alt"></i> Promote blocks
                    </button>
                </form>
            </div>

            <div class="rg-table-wrap">
                <table class="rg-table">
                    <thead>
                        <tr>
                            <th>Block section</th>
                            <th>Students enrolled</th>
                            <th>Active subjects</th>
                            <th class="rg-th-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="blockTableBody">
                        @forelse($sections as $block)
                            @php
                                $sectionName = $block->section_name;
                                $enrolled    = $block->enrolled_count ?? 0;
                                $pending     = $block->pending_count ?? 0;
                                $subjects    = $block->total_subjects ?? 0;
                                $hasPending  = $block->has_pending_grades ?? false;
                                
                                $enrollConfirm = "Bulk enroll every student of " . strtoupper($sectionName)
                                    . " into its schedules for "
                                    . ($activePeriod ? $activePeriod->school_year . ', ' . $activePeriod->semester : 'the active period') . '?';
                                    
                                $btnDisabled = ($pending == 0) || $hasPending;
                                $btnTitle = $hasPending 
                                    ? 'Cannot enroll: May pending unreleased grades mula sa nakaraang semester.' 
                                    : ($pending > 0 ? 'Enroll this block in the active period' : 'No students are waiting to be enrolled');
                            @endphp
                            <tr class="block-row" data-search="{{ strtolower($sectionName) }}">
                                <td><span class="rg-block-name">{{ strtoupper($sectionName) }}</span></td>

                                <td>
                                    <span class="rg-count {{ $enrolled > 0 ? 'rg-count-on' : 'rg-count-off' }}">
                                        <i class="fas fa-users"></i>
                                        {{ $enrolled }} {{ Str::plural('student', $enrolled) }}
                                    </span>
                                </td>

                                <td><strong>{{ $subjects }}</strong> {{ Str::plural('subject', $subjects) }}</td>

                                <td>
                                    <div class="rg-actions">
                                        <a href="{{ route('department_secretary.view_section', $sectionName) }}" class="rg-btn rg-btn-ghost">
                                            <i class="fas fa-users"></i> View students
                                        </a>

                                        <form action="{{ route('department_secretary.bulk_enroll') }}" method="POST" data-confirm="{{ $enrollConfirm }}">
                                            @csrf
                                            <input type="hidden" name="current_section" value="{{ $sectionName }}">
                                            <button type="submit" class="rg-btn rg-btn-primary"
                                                    {{ $btnDisabled ? 'disabled' : '' }}
                                                    title="{{ $btnTitle }}">
                                                <i class="fas fa-user-plus"></i> Bulk enroll
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr id="blockEmptyRow">
                                <td colspan="4">
                                    <div class="rg-empty">
                                        <i class="fas fa-layer-group"></i>
                                        <strong>No block sections for the active period</strong>
                                        <span>Deploy class schedules in Manage Schedules to create blocks.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                        <tr id="blockNoMatchRow" style="display: none;">
                            <td colspan="4">
                                <div class="rg-empty">
                                    <i class="fas fa-search"></i>
                                    <strong>No block matches your search</strong>
                                    <span>Check the section name and try again.</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

<script>
    function switchView(tabId, buttonElement) {
        document.querySelectorAll('.rg-panel').forEach(panel => panel.classList.remove('active-panel'));
        document.querySelectorAll('.rg-tab').forEach(btn => {
            btn.classList.remove('active');
            btn.setAttribute('aria-selected', 'false');
        });

        document.getElementById(tabId).classList.add('active-panel');
        buttonElement.classList.add('active');
        buttonElement.setAttribute('aria-selected', 'true');
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('form[data-confirm]').forEach(form => {
            form.addEventListener('submit', function (e) {
                if (!confirm(form.dataset.confirm)) {
                    e.preventDefault();
                }
            });
        });

        const studentSearch  = document.getElementById('studentSearchInput');
        const filterPills    = document.querySelectorAll('.filter-pill');
        const studentRows    = document.querySelectorAll('.student-row');
        const noMatchRow     = document.getElementById('studentNoMatchRow');
        const totalCountText = document.getElementById('studentTotalCount');
        const initialTotal   = studentRows.length;

        let activeYear = '';

        function filterStudents() {
            const query = studentSearch ? studentSearch.value.toLowerCase().trim() : '';
            let visibleCount = 0;

            studentRows.forEach(row => {
                const matchesSearch = (row.dataset.search || '').includes(query);
                const matchesYear   = activeYear === '' || row.dataset.year === activeYear;
                const show = matchesSearch && matchesYear;

                row.style.display = show ? '' : 'none';
                if (show) visibleCount++;
            });

            if (totalCountText) {
                totalCountText.textContent = visibleCount + (visibleCount === 1 ? ' student' : ' students');
            }
            if (noMatchRow) {
                noMatchRow.style.display = (visibleCount === 0 && initialTotal > 0) ? '' : 'none';
            }
        }

        filterPills.forEach(pill => {
            pill.addEventListener('click', function () {
                filterPills.forEach(p => p.classList.remove('active'));
                this.classList.add('active');
                activeYear = this.getAttribute('data-filter-value');
                filterStudents();
            });
        });

        if (studentSearch) studentSearch.addEventListener('input', filterStudents);

        const blockSearch  = document.getElementById('blockSearchInput');
        const blockRows    = document.querySelectorAll('.block-row');
        const blockNoMatch = document.getElementById('blockNoMatchRow');

        if (blockSearch) {
            blockSearch.addEventListener('input', function () {
                const query = blockSearch.value.toLowerCase().trim();
                let visible = 0;

                blockRows.forEach(row => {
                    const show = (row.dataset.search || '').includes(query);
                    row.style.display = show ? '' : 'none';
                    if (show) visible++;
                });

                if (blockNoMatch) {
                    blockNoMatch.style.display = (visible === 0 && blockRows.length > 0) ? '' : 'none';
                }
            });
        }
    });
</script>

<style>
    .rg-page {
        --rg-blue: #0033A0;
        --rg-blue-dark: #0A1F5C;
        --rg-blue-tint: #EAF0FF;
        --rg-gold: #F2B705;
        --rg-ink: #0B1B3A;
        --rg-muted: #5C6B8A;
        --rg-line: #E2E8F3;
        --rg-surface: #F7F9FD;
        --rg-green: #15803D;
        --rg-green-tint: #E8F7EE;
        --rg-red: #C62828;

        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: var(--rg-ink);
        max-width: 1300px;
        margin: 0 auto;
        padding: 24px 20px 40px;
    }

    .rg-header { display: flex; justify-content: space-between; align-items: flex-end; gap: 20px; flex-wrap: wrap; padding-bottom: 18px; margin-bottom: 20px; border-bottom: 1px solid var(--rg-line); }
    .rg-title { margin: 0; font-size: 26px; font-weight: 800; letter-spacing: -0.2px; color: var(--rg-blue-dark); }
    .rg-subtitle { margin: 5px 0 0; font-size: 14px; color: var(--rg-muted); }

    .rg-period { display: grid; grid-template-columns: auto auto; column-gap: 10px; align-items: baseline; background: #fff; border: 1px solid var(--rg-line); border-left: 4px solid var(--rg-gold); border-radius: 8px; padding: 9px 16px 9px 14px; }
    .rg-period-label { grid-column: 1 / -1; font-size: 11.5px; font-weight: 600; color: var(--rg-muted); margin-bottom: 2px; }
    .rg-period-value { font-size: 15px; font-weight: 800; color: var(--rg-blue-dark); }
    .rg-period-sem { font-size: 13px; font-weight: 600; color: var(--rg-blue); }
    .rg-period-none { color: var(--rg-red); }

    .rg-alert { display: flex; gap: 10px; align-items: flex-start; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 13.5px; font-weight: 600; line-height: 1.45; }
    .rg-alert i { margin-top: 2px; }
    .rg-alert ul { margin: 0; padding-left: 18px; }
    .rg-alert-success { background: var(--rg-green-tint); color: #14532D; border: 1px solid #BFE8CE; border-left: 4px solid var(--rg-green); }
    .rg-alert-error { background: #FDECEC; color: #8E1B1B; border: 1px solid #F5C6C6; border-left: 4px solid var(--rg-red); }

    .rg-tabs { display: flex; gap: 4px; margin-bottom: 18px; border-bottom: 1px solid var(--rg-line); }
    .rg-tab { display: inline-flex; align-items: center; gap: 8px; background: none; border: none; border-bottom: 3px solid transparent; margin-bottom: -1px; padding: 10px 16px; font-size: 14px; font-weight: 600; color: var(--rg-muted); cursor: pointer; transition: color .15s, border-color .15s; }
    .rg-tab:hover { color: var(--rg-blue-dark); }
    .rg-tab.active { color: var(--rg-blue-dark); border-bottom-color: var(--rg-gold); }
    .rg-tab-count { background: var(--rg-line); color: var(--rg-muted); font-size: 11.5px; font-weight: 700; padding: 1px 8px; border-radius: 10px; }
    .rg-tab.active .rg-tab-count { background: var(--rg-blue); color: #fff; }

    .rg-panel { display: none; }
    .rg-panel.active-panel { display: block; }

    .rg-card { background: #fff; border: 1px solid var(--rg-line); border-radius: 10px; overflow: hidden; box-shadow: 0 1px 2px rgba(10, 31, 92, 0.05); }
    .rg-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 14px 18px; padding: 14px 20px; border-bottom: 1px solid var(--rg-line); }
    .rg-toolbar-end { margin: 0 0 0 auto; }

    .rg-search { position: relative; flex: 1 1 320px; max-width: 460px; }
    .rg-search i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #8A97B3; font-size: 13px; pointer-events: none; }
    .rg-search input { width: 100%; box-sizing: border-box; padding: 10px 12px 10px 34px; font-size: 13.5px; color: var(--rg-ink); background: #fff; border: 1px solid #CBD5E6; border-radius: 8px; }
    .rg-search input::placeholder { color: #8A97B3; }
    .rg-search input:focus { outline: none; border-color: var(--rg-blue); box-shadow: 0 0 0 3px rgba(0, 51, 160, 0.14); }

    .rg-filter { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
    .rg-filter-label { font-size: 12.5px; font-weight: 600; color: var(--rg-muted); margin-right: 2px; }
    .filter-pill { background: #fff; border: 1px solid #CBD5E6; color: var(--rg-muted); padding: 5px 12px; font-size: 12.5px; font-weight: 600; border-radius: 16px; cursor: pointer; transition: background .15s, color .15s, border-color .15s; }
    .filter-pill:hover { border-color: var(--rg-blue); color: var(--rg-blue); }
    .filter-pill.active { background: var(--rg-blue); border-color: var(--rg-blue); color: #fff; }

    .rg-result-count { margin-left: auto; font-size: 12.5px; font-weight: 600; color: var(--rg-muted); }

    .rg-table-wrap { overflow-x: auto; }
    .rg-table { width: 100%; border-collapse: collapse; font-size: 14px; }
    .rg-table th { background: var(--rg-surface); padding: 11px 20px; font-size: 12px; font-weight: 700; color: var(--rg-muted); text-align: left; border-bottom: 1px solid var(--rg-line); white-space: nowrap; }
    .rg-table td { padding: 13px 20px; border-bottom: 1px solid #EEF2F9; color: #26345A; vertical-align: middle; }
    .rg-table tbody tr:last-child td { border-bottom: none; }
    .rg-table tbody tr:hover { background: #F5F8FF; }
    .rg-th-actions { text-align: right !important; }

    .rg-person { display: flex; align-items: center; gap: 12px; }
    .rg-avatar { flex: 0 0 auto; width: 36px; height: 36px; border-radius: 50%; background: var(--rg-blue-tint); color: var(--rg-blue); display: inline-flex; align-items: center; justify-content: center; font-size: 12.5px; font-weight: 800; }
    .rg-person-name { font-size: 14px; color: var(--rg-ink); }
    .rg-person-id { margin-top: 1px; font-size: 12px; color: var(--rg-muted); font-variant-numeric: tabular-nums; }

    .rg-chip { display: inline-block; padding: 3px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; white-space: nowrap; }
    .rg-chip-plain { background: var(--rg-surface); color: #3B4A6B; border: 1px solid var(--rg-line); }
    .rg-chip-blue { background: var(--rg-blue-tint); color: var(--rg-blue); border: 1px solid #CFDBFB; }
    .rg-unassigned { font-size: 12.5px; color: #8A97B3; font-style: italic; }

    .rg-block-name { display: inline-block; background: var(--rg-blue); color: #fff; font-size: 14px; font-weight: 800; letter-spacing: 0.3px; padding: 6px 14px; border-radius: 6px; }
    .rg-count { display: inline-flex; align-items: center; gap: 7px; padding: 5px 12px; border-radius: 16px; font-size: 12.5px; font-weight: 700; }
    .rg-count-on { background: var(--rg-green-tint); color: var(--rg-green); border: 1px solid #BFE8CE; }
    .rg-count-off { background: var(--rg-surface); color: var(--rg-muted); border: 1px solid var(--rg-line); }

    .rg-actions { display: flex; justify-content: flex-end; align-items: center; gap: 8px; }
    .rg-actions form { margin: 0; }

    .rg-btn { display: inline-flex; align-items: center; gap: 7px; padding: 7px 14px; border-radius: 7px; font-size: 12.5px; font-weight: 700; text-decoration: none; cursor: pointer; border: 1px solid transparent; transition: background .15s, color .15s, border-color .15s; white-space: nowrap; line-height: 1.2; }
    .rg-btn-primary { background: var(--rg-blue); color: #fff; }
    .rg-btn-primary:hover { background: var(--rg-blue-dark); color: #fff; }
    .rg-btn-primary:disabled { background: #D5DCEB; color: #7D8AA8; cursor: not-allowed; }
    .rg-btn-ghost { background: #fff; color: var(--rg-blue); border-color: #B9C8F0; }
    .rg-btn-ghost:hover { background: var(--rg-blue-tint); color: var(--rg-blue-dark); }
    .rg-btn-outline-navy { background: #fff; color: var(--rg-blue-dark); border-color: var(--rg-blue-dark); }
    .rg-btn-outline-navy:hover { background: var(--rg-blue-dark); color: #fff; }

    .rg-btn-icon { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 7px; background: #fff; border: 1px solid var(--rg-line); color: #8A97B3; cursor: pointer; transition: background .15s, color .15s, border-color .15s; }
    .rg-btn-danger:hover { background: #FDECEC; border-color: #F0B4B4; color: var(--rg-red); }

    .rg-btn:focus-visible, .rg-btn-icon:focus-visible, .rg-tab:focus-visible, .filter-pill:focus-visible { outline: 2px solid var(--rg-blue); outline-offset: 2px; }

    .rg-empty { display: flex; flex-direction: column; align-items: center; gap: 6px; padding: 44px 16px; text-align: center; color: var(--rg-muted); font-size: 13.5px; }
    .rg-empty i { font-size: 28px; color: #B9C4DD; margin-bottom: 4px; }
    .rg-empty strong { color: var(--rg-ink); font-size: 15px; }

    @media (max-width: 720px) {
        .rg-header { align-items: flex-start; }
        .rg-result-count, .rg-toolbar-end { margin-left: 0; }
        .rg-table th, .rg-table td { padding-left: 14px; padding-right: 14px; }
    }

    @media (prefers-reduced-motion: reduce) {
        .rg-page * { transition: none !important; }
    }
</style>
@endsection