@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="rg-page">
    <header class="rg-header">
        <div>
            <h2 class="rg-title">Academic Period Management</h2>
            <p class="rg-subtitle">Set the current school year, semester, and term date ranges used system-wide.</p>
        </div>
    </header>

    @if(session('success'))
        <div class="rg-alert rg-alert-success" role="status">
            <i class="fas fa-check-circle"></i> <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="rg-alert rg-alert-error" role="alert">
            <i class="fas fa-exclamation-circle"></i> <span>{{ session('error') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="rg-alert rg-alert-error" role="alert">
            <i class="fas fa-exclamation-circle"></i>
            <div>
                <strong style="display:block; margin-bottom: 4px;">Unable to process request:</strong>
                <ul style="margin: 0; padding-left: 20px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div id="js-alert-error" class="rg-alert rg-alert-error" role="alert" style="display: none;">
        <i class="fas fa-exclamation-triangle"></i>
        <div>
            <strong style="display:block; margin-bottom: 4px;">Unable to process request:</strong>
            <ul id="js-alert-list" style="margin: 0; padding-left: 20px;"></ul>
        </div>
    </div>

    <div class="rg-card" style="margin-bottom: 24px;">
        <div class="card-bar">
            <span><i class="fas fa-calendar-plus"></i> Create New Academic Period</span>
        </div>
        <div style="padding: 24px;">
            <form id="academicPeriodForm" action="{{ route('system_admin.store_academic_period') }}" method="POST" class="create-period-grid" autocomplete="off" novalidate>
                @csrf
                <div class="form-group">
                    <label>School Year <span class="required-star">*</span></label>
                    <input type="text" name="school_year" id="form_school_year" class="rg-input" placeholder="e.g. 2026-2027" required>
                </div>
                <div class="form-group">
                    <label>Semester <span class="required-star">*</span></label>
                    <select name="semester" id="form_semester" class="rg-select" required>
                        <option value="" disabled selected hidden>Choose Semester</option>
                        <option value="1st Semester">1st Semester</option>
                        <option value="2nd Semester">2nd Semester</option>
                        <option value="Summer">Summer</option>
                    </select>
                </div>
                <div style="display: flex; align-items: flex-end;">
                    <button type="submit" class="rg-btn rg-btn-primary" style="height: 40px; width: 100%; justify-content: center;">
                        <i class="fas fa-plus"></i> Create Period
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="rg-card">
        <div class="rg-toolbar">
            <div class="rg-search">
                <i class="fas fa-search"></i>
                <input type="text" id="academicPeriodSearch" placeholder="Search by School Year...">
            </div>
            <span class="rg-result-count" id="totalPeriodsCount">{{ count($periods) }} Total</span>
        </div>

        @if($periods->isEmpty())
            <div class="empty-table-state" style="padding: 50px;">
                <i class="fas fa-calendar-xmark" style="font-size: 24px; display: block; margin-bottom: 8px;"></i>
                No academic periods created yet.
            </div>
        @else
            <div id="noPeriodResults" class="empty-table-state" style="display: none; padding: 50px;">
                <i class="fas fa-magnifying-glass" style="font-size: 24px; display: block; margin-bottom: 8px;"></i>
                No academic periods match your search.
            </div>
            @foreach($periods as $period)
                @php
                    $terms = $termPeriods[$period->id] ?? collect();

                    $requiredTerms = ['prelim', 'midterm', 'semifinal', 'final'];
                    $missingTerms = collect($requiredTerms)->filter(function ($key) use ($terms) {
                        $t = $terms->firstWhere('term', $key);
                        return !$t || empty($t->start_date) || empty($t->end_date);
                    });
                    $canActivate = $missingTerms->isEmpty();

                    $baseStartYear = 2023;
                    $yearLevels = [1 => '1st Year', 2 => '2nd Year', 3 => '3rd Year', 4 => '4th Year'];
                    $startYear = (int) substr(trim($period->school_year), 0, 4);
                    $levelNumber = $startYear - $baseStartYear + 1;
                    $mappedYearLevel = $yearLevels[$levelNumber] ?? null;

                    $subjectsForPeriod = collect($curriculumSubjects[trim($period->semester)] ?? [])
                        ->filter(fn($s) => $mappedYearLevel && trim($s->year_level) === $mappedYearLevel)
                        ->values();
                @endphp
                <div class="period-block" data-school-year="{{ strtolower(trim($period->school_year)) }}">
                    <div class="period-block-header">
                        <div class="period-title-group">
                            <span class="period-title">{{ $period->school_year }} — {{$period->semester }}</span>
                            @if($period->is_active)
                                <span class="status-active-badge"><i class="fas fa-circle" style="font-size: 6px;"></i> Active</span>
                            @else
                                <span class="status-inactive-badge">Inactive</span>
                            @endif
                        </div>
                        <div class="period-actions">
                            <button type="button" class="rg-btn-action rg-btn-action-ghost"
                                onclick="openPeriodSubjectsModal(@js($period->school_year . ' — ' . $period->semester), @js($mappedYearLevel ?? 'No matching year level'), @js($subjectsForPeriod))">
                                <i class="fas fa-book"></i> View Subjects
                            </button>
                            @if(!$period->is_active)
                                <form action="{{ route('system_admin.activate_academic_period', $period->id) }}" method="POST" style="display:inline-block;">
                                    @csrf
                                    @if($canActivate)
                                        <button type="submit" class="rg-btn-action rg-btn-action-ghost" onclick="return confirm('Set this as the active academic period? This will deactivate the current one.')">
                                            <i class="fas fa-check"></i> Set as Active
                                        </button>
                                    @else
                                        <button type="button" class="rg-btn-action rg-btn-action-ghost" disabled style="opacity: 0.5; cursor: not-allowed;"
                                            title="Save the dates for all terms first (missing: {{ $missingTerms->map(fn($k) => ucfirst($k))->implode(', ') }})">
                                            <i class="fas fa-lock"></i> Set as Active
                                        </button>
                                    @endif
                                </form>
                            @endif
                            <form action="{{ route('system_admin.delete_academic_period', $period->id) }}" method="POST" class="delete-period-form" style="display:inline-block;">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" class="is-active-input" value="{{ $period->is_active ? 1 : 0 }}">
                                <button type="submit" class="rg-btn-action rg-btn-action-danger btn-delete-trigger">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="term-dates-grid">
                        @foreach(['prelim' => 'Prelim', 'midterm' => 'Midterm', 'semifinal' => 'Semi-Final', 'final' => 'Final'] as $key =>$label)
                            @php $existingTerm = $terms->firstWhere('term',$key); @endphp
                            <form action="{{ route('system_admin.store_term_period') }}" method="POST" class="term-date-card term-form" novalidate>
                                @csrf
                                <input type="hidden" name="academic_period_id" value="{{ $period->id }}">
                                <input type="hidden" name="term" value="{{ $key }}">
                                
                                <div>
                                    <span class="term-date-label">{{ $label }}</span>
                                </div>
                                
                                <div class="term-inputs-group">
                                    <div class="term-field-single">
                                        <label class="term-sub-label">Start</label>
                                        <input type="date" name="start_date" value="{{ $existingTerm->start_date ?? '' }}" class="rg-input start-date-field" style="height: 36px; font-size: 12.5px;" required>
                                    </div>
                                    <div class="term-field-single">
                                        <label class="term-sub-label">End</label>
                                        <input type="date" name="end_date" value="{{ $existingTerm->end_date ?? '' }}" class="rg-input end-date-field" style="height: 36px; font-size: 12.5px;" required>
                                    </div>
                                </div>

                                <button type="submit" class="rg-btn rg-btn-primary" style="justify-content: center; font-size: 12px; padding: 8px;">
                                    <i class="fas fa-save"></i> {{ $existingTerm ? 'Update' : 'Save' }}
                                </button>
                            </form>
                        @endforeach
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</div>

<div id="termSubjectsModal" class="rg-modal-overlay" style="display: none;">
    <div class="rg-modal-card" style="max-width: 650px;">
        <div class="rg-modal-header" style="background: var(--rg-surface); border-bottom: 1px solid var(--rg-line);">
            <div>
                <h3 id="modal-term-title" style="margin: 0; font-size: 16px; font-weight: 700; color: var(--rg-blue-dark);"><i class="fas fa-book-open"></i> Period Subjects</h3>
                <span id="modal-term-subtitle" style="font-size: 12px; color: var(--rg-muted); font-weight: 600;"></span>
            </div>
            <button type="button" class="rg-modal-close" onclick="closeTermSubjectsModal()">&times;</button>
        </div>
        
        <div style="padding: 20px; max-height: 420px; overflow-y: auto;">
            <table class="rg-table" style="width: 100%;">
                <thead>
                    <tr>
                        <th>Course</th>
                        <th>Code</th>
                        <th>Subject Title</th>
                    </tr>
                </thead>
                <tbody id="modal-subjects-list">
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const existingPeriods = [
            @if(isset($periods))
                @foreach($periods as $p)
                    {
                        school_year: @js(trim($p->school_year)),
                        semester: @js(trim($p->semester))
                    },
                @endforeach
            @endif
        ];

        const periodForm = document.getElementById('academicPeriodForm');
        const jsAlertError = document.getElementById('js-alert-error');
        const jsAlertList = document.getElementById('js-alert-list');

        if (periodForm) {
            periodForm.addEventListener('submit', function (e) {
                const schoolYear = document.getElementById('form_school_year').value.trim();
                const semester = document.getElementById('form_semester').value;

                if (jsAlertError) jsAlertError.style.display = 'none';
                let errorMessages = [];

                if (!schoolYear || !semester) {
                    errorMessages.push('Please fill out all required fields before submitting.');
                } else {
                    const syPattern = /^\d{4}-\d{4}$/;
                    if (!syPattern.test(schoolYear)) {
                        errorMessages.push('School Year format must be YYYY-YYYY (e.g., 2026-2027).');
                    } else {
                        const years = schoolYear.split('-');
                        const y1 = parseInt(years[0], 10);
                        const y2 = parseInt(years[1], 10);
                        if (y1 === y2) {
                            errorMessages.push(`Invalid School Year: "${schoolYear}" repeats the same year twice. It must span two consecutive years (e.g., 2026-2027).`);
                        } else if (y2 !== y1 + 1) {
                            errorMessages.push('The second year must be exactly one year after the first year (e.g., 2026-2027).');
                        }
                    }

                    const isDuplicate = existingPeriods.some(p => p.school_year === schoolYear && p.semester === semester);
                    if (isDuplicate) {
                        errorMessages.push('This academic period (School Year and Semester combination) already exists.');
                    }
                }

                if (errorMessages.length > 0) {
                    e.preventDefault();
                    showJsErrors(errorMessages);
                    return false;
                }
            });
        }

        const periodSearchInput = document.getElementById('academicPeriodSearch');
        const periodBlocks = document.querySelectorAll('.period-block');
        const noPeriodResults = document.getElementById('noPeriodResults');
        const totalPeriodsBadge = document.getElementById('totalPeriodsCount');
        const totalPeriodsAll = periodBlocks.length;

        if (periodSearchInput) {
            periodSearchInput.addEventListener('input', function () {
                const query = periodSearchInput.value.toLowerCase().trim();
                let visibleCount = 0;

                periodBlocks.forEach(block => {
                    const schoolYear = block.getAttribute('data-school-year') || '';
                    if (schoolYear.includes(query)) {
                        block.style.display = '';
                        visibleCount++;
                    } else {
                        block.style.display = 'none';
                    }
                });

                if (totalPeriodsBadge) {
                    totalPeriodsBadge.textContent = query === ''
                        ? `${totalPeriodsAll} Total`
                        : `${visibleCount} of ${totalPeriodsAll}`;
                }

                if (noPeriodResults) {
                    noPeriodResults.style.display = (visibleCount === 0 && totalPeriodsAll > 0) ? 'block' : 'none';
                }
            });
        }

        const deleteForms = document.querySelectorAll('.delete-period-form');
        const totalPeriodsCount = existingPeriods.length;

        deleteForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                const isActive = form.querySelector('.is-active-input').value === '1';

                if (totalPeriodsCount <= 1) {
                    e.preventDefault();
                    alert('You cannot delete this academic period because there must be at least one period in the system.');
                    return false;
                }

                if (isActive) {
                    const confirmActiveDelete = confirm('Warning: You are about to delete the currently ACTIVE academic period. Are you completely sure?');
                    if (!confirmActiveDelete) {
                        e.preventDefault();
                        return false;
                    }
                } else {
                    const confirmDelete = confirm('Delete this academic period and all its term dates?');
                    if (!confirmDelete) {
                        e.preventDefault();
                        return false;
                    }
                }
            });
        });

        const termForms = document.querySelectorAll('.term-form');
        termForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                const startDate = form.querySelector('.start-date-field').value;
                const endDate = form.querySelector('.end-date-field').value;

                if (!startDate || !endDate) {
                    e.preventDefault();
                    alert('Please provide both Start and End dates for the term.');
                    return false;
                }

                if (startDate === endDate) {
                    e.preventDefault();
                    alert('The Start date and End date cannot be the same day.');
                    return false;
                }

                if (new Date(startDate) > new Date(endDate)) {
                    e.preventDefault();
                    alert('The Start date cannot be later than the End date.');
                    return false;
                }
            });
        });

        function showJsErrors(messages) {
            if (jsAlertError && jsAlertList) {
                jsAlertList.innerHTML = '';
                messages.forEach(msg => {
                    const li = document.createElement('li');
                    li.textContent = msg;
                    jsAlertList.appendChild(li);
                });
                jsAlertError.style.display = 'flex';
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        }
    });

    function openPeriodSubjectsModal(periodLabel, yearLevel, subjects) {
        const modal = document.getElementById('termSubjectsModal');
        const title = document.getElementById('modal-term-title');
        const subtitle = document.getElementById('modal-term-subtitle');
        const listContainer = document.getElementById('modal-subjects-list');

        title.innerHTML = `<i class="fas fa-book-open"></i> ${periodLabel}`;
        subtitle.textContent = `Year Level: ${yearLevel} • ${subjects.length} subject(s)`;

        listContainer.innerHTML = '';

        if (!subjects || subjects.length === 0) {
            listContainer.innerHTML = `
                <tr>
                    <td colspan="3" class="empty-table-state" style="padding: 25px !important;">
                        No subjects registered for this period in the curriculum masterlist.
                    </td>
                </tr>`;
        } else {
            subjects.forEach(sub => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><span class="student-id-tag">${sub.course_code}</span></td>
                    <td><span class="student-id-tag" style="background:#e8f7ee; color:#15803D; border-color:#bfe8ce;">${sub.subject_code}</span></td>
                    <td style="font-weight: 700; color: var(--rg-blue-dark);">${sub.subject_title}</td>
                `;
                listContainer.appendChild(tr);
            });
        }

        modal.style.display = 'flex';
    }

    function closeTermSubjectsModal() {
        document.getElementById('termSubjectsModal').style.display = 'none';
    }
</script>

<style>
    .rg-page {
        --rg-blue: #0033A0; --rg-blue-dark: #0A1F5C; --rg-blue-tint: #EAF0FF; --rg-gold: #F2B705;
        --rg-ink: #0B1B3A; --rg-muted: #5C6B8A; --rg-line: #E2E8F3; --rg-surface: #F7F9FD;
        --rg-green: #15803D; --rg-green-tint: #E8F7EE; --rg-red: #C62828;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: var(--rg-ink);
        max-width: 1500px; margin: 0 auto; padding: 24px 20px 40px;
    }

    .rg-header { display: flex; justify-content: space-between; align-items: flex-end; gap: 20px; flex-wrap: wrap; padding-bottom: 18px; margin-bottom: 24px; border-bottom: 1px solid var(--rg-line); }
    .rg-title { margin: 0; font-size: 26px; font-weight: 800; letter-spacing: -0.2px; color: var(--rg-blue-dark); }
    .rg-subtitle { margin: 8px 0 0; font-size: 14px; color: var(--rg-muted); }

    .rg-alert { display: flex; gap: 10px; align-items: flex-start; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 13.5px; font-weight: 600; line-height: 1.45; }
    .rg-alert i { margin-top: 2px; }
    .rg-alert-success { background: var(--rg-green-tint); color: #14532D; border: 1px solid #BFE8CE; border-left: 4px solid var(--rg-green); }
    .rg-alert-error { background: #FDECEC; color: #8E1B1B; border: 1px solid #F5C6C6; border-left: 4px solid var(--rg-red); }

    .rg-card { background: #fff; border: 1px solid var(--rg-line); border-radius: 10px; overflow: hidden; box-shadow: 0 1px 2px rgba(10, 31, 92, 0.05); }
    .card-bar { background: var(--rg-surface); padding: 16px 20px; font-weight: 700; font-size: 14.5px; color: var(--rg-blue-dark); border-bottom: 1px solid var(--rg-line); display: flex; align-items: center; gap: 8px; }

    .create-period-grid { display: grid; grid-template-columns: 1fr 1fr auto; gap: 16px; align-items: end; }
    @media (max-width: 900px) {
        .create-period-grid { grid-template-columns: 1fr; }
    }

    .form-group { display: flex; flex-direction: column; gap: 6px; }
    .form-group label { font-size: 12px; font-weight: 700; color: var(--rg-muted); text-transform: uppercase; letter-spacing: 0.3px; }
    .required-star { color: var(--rg-red); }

    .rg-input, .rg-select {
        width: 100%; box-sizing: border-box; height: 40px; padding: 0 14px;
        font-size: 14px; font-weight: 500; color: var(--rg-ink); background: #fff;
        border: 1px solid #CBD5E6; border-radius: 6px; outline: none; transition: border-color 0.2s, box-shadow 0.2s;
    }
    .rg-input:focus, .rg-select:focus { border-color: var(--rg-blue); box-shadow: 0 0 0 3px rgba(0, 51, 160, 0.14); }
    .rg-select {
        padding-right: 36px; appearance: none; -webkit-appearance: none; cursor: pointer;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='2' stroke='%235C6B8A'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7' /%3E%3C/svg%3E");
        background-repeat: no-repeat; background-position: right 12px center; background-size: 16px;
    }

    .rg-btn { display: inline-flex; align-items: center; gap: 7px; padding: 10px 18px; border-radius: 6px; font-size: 13.5px; font-weight: 700; text-decoration: none; cursor: pointer; border: 1px solid transparent; transition: background .15s, color .15s, border-color .15s; white-space: nowrap; line-height: 1.2; }
    .rg-btn-primary { background: var(--rg-blue); color: #fff; }
    .rg-btn-primary:hover { background: var(--rg-blue-dark); color: #fff; }
    .rg-btn-ghost { background: transparent; color: var(--rg-muted); border-color: #CBD5E6; }
    .rg-btn-ghost:hover { background: var(--rg-surface); color: var(--rg-ink); }

    .rg-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 14px 18px; padding: 14px 20px; border-bottom: 1px solid var(--rg-line); background: #fff; }
    .rg-search { position: relative; flex: 1 1 260px; max-width: 380px; }
    .rg-search i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #8A97B3; font-size: 13px; pointer-events: none; }
    .rg-search input { width: 100%; box-sizing: border-box; padding: 10px 12px 10px 34px; font-size: 13.5px; color: var(--rg-ink); background: #fff; border: 1px solid #CBD5E6; border-radius: 8px; outline: none; }
    .rg-search input:focus { border-color: var(--rg-blue); box-shadow: 0 0 0 3px rgba(0, 51, 160, 0.14); }
    .rg-result-count { margin-left: auto; font-size: 12.5px; font-weight: 600; color: var(--rg-muted); background: var(--rg-line); padding: 4px 10px; border-radius: 12px; }

    .period-block { padding: 20px 24px; border-bottom: 1px solid var(--rg-line); }
    .period-block:last-child { border-bottom: none; }
    .period-block-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px; }
    .period-title-group { display: flex; align-items: center; gap: 12px; }
    .period-title { font-size: 15px; font-weight: 700; color: var(--rg-blue-dark); }
    
    .status-active-badge { display: inline-flex; align-items: center; gap: 5px; background: var(--rg-green-tint); color: #14532D; font-size: 11.5px; font-weight: 700; padding: 4px 12px; border-radius: 20px; border: 1px solid #BFE8CE; }
    .status-inactive-badge { background: var(--rg-surface); color: var(--rg-muted); font-size: 11.5px; font-weight: 700; padding: 4px 12px; border-radius: 20px; border: 1px solid var(--rg-line); }
    
    .period-actions { display: flex; gap: 8px; flex-wrap: wrap; }

    .term-dates-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; }
    .term-date-card { background: var(--rg-surface); border: 1px solid var(--rg-line); border-radius: 10px; padding: 16px; display: flex; flex-direction: column; gap: 12px; }
    .term-date-label { font-size: 12px; font-weight: 700; color: var(--rg-muted); text-transform: uppercase; letter-spacing: 0.3px; }
    
    .term-inputs-group { display: flex; gap: 8px; }
    .term-field-single { flex: 1; display: flex; flex-direction: column; gap: 4px; min-width: 0; }
    .term-sub-label { font-size: 11px; font-weight: 600; color: var(--rg-muted); text-transform: uppercase; }

    .rg-btn-action { border: 1px solid transparent; padding: 6px 12px; font-size: 12px; font-weight: 600; border-radius: 6px; cursor: pointer; transition: 0.15s; display: inline-flex; align-items: center; gap: 5px; }
    .rg-btn-action-ghost { background: transparent; border-color: #CBD5E6; color: var(--rg-muted); }
    .rg-btn-action-ghost:hover { background: var(--rg-surface); color: var(--rg-ink); }
    .rg-btn-action-danger { background: transparent; border-color: #FECACA; color: var(--rg-red); }
    .rg-btn-action-danger:hover { background: #FEF2F2; color: #991B1B; border-color: #F87171; }

    .empty-table-state { text-align: center; color: var(--rg-muted) !important; padding: 50px 0 !important; font-style: italic; }

    .rg-table-wrap { overflow-x: auto; width: 100%; }
    .rg-table { width: 100%; min-width: 500px; border-collapse: collapse; font-size: 14px; }
    .rg-table th { background: var(--rg-surface); padding: 12px 18px; font-size: 11.5px; font-weight: 700; color: var(--rg-muted); text-transform: uppercase; text-align: left; border-bottom: 1px solid var(--rg-line); }
    .rg-table td { padding: 12px 18px; border-bottom: 1px solid #EEF2F9; color: var(--rg-ink); vertical-align: middle; }
    .rg-table tbody tr:last-child td { border-bottom: none; }
    .rg-table tbody tr:hover { background: #F5F8FF; }

    .student-id-tag { font-family: ui-monospace, monospace; font-weight: 700; color: var(--rg-blue); background: var(--rg-blue-tint); padding: 3px 9px; border-radius: 6px; font-size: 12.5px; border: 1px solid #CFDBFB; display: inline-block; }

    .rg-modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(11, 27, 58, 0.6); z-index: 1000; display: flex; justify-content: center; align-items: center; padding: 20px; box-sizing: border-box; }
    .rg-modal-card { background: #fff; width: 100%; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.15); overflow: hidden; animation: fadeIn 0.2s ease-in-out; }
    .rg-modal-header { display: flex; justify-content: space-between; align-items: center; padding: 18px 24px; }
    .rg-modal-header h3 { margin: 0; font-size: 16px; font-weight: 700; display: flex; align-items: center; gap: 8px; }
    .rg-modal-close { background: transparent; border: none; font-size: 24px; cursor: pointer; transition: 0.15s; padding: 0; line-height: 1; color: var(--rg-muted); }
    .rg-modal-close:hover { color: var(--rg-ink); }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
</style>
@endsection