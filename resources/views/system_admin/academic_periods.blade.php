@extends('layouts.app')

@section('content')
<div class="sa-container">
    <div class="sa-header">
        <div class="header-titles">
            <h2>Academic Period Management</h2>
            <p>Set the current school year, semester, and term date ranges used system-wide.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="sa-alert-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="sa-alert-danger"><i class="fas fa-exclamation-circle"></i> {{ session('error') }}</div>
    @endif

    @if($errors->any())
        <div class="sa-alert-danger" style="align-items: flex-start;">
            <i class="fas fa-exclamation-circle" style="margin-top: 3px;"></i>
            <div>
                <strong>Unable to process request:</strong>
                <ul style="margin: 4px 0 0 0; padding-left: 20px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div id="js-alert-error" class="sa-alert-danger" style="display: none; align-items: flex-start; gap: 10px;">
        <i class="fas fa-exclamation-triangle" style="margin-top: 3px;"></i>
        <div>
            <strong>Unable to process request:</strong>
            <ul id="js-alert-list" style="margin: 4px 0 0 0; padding-left: 20px;"></ul>
        </div>
    </div>

    <div class="sa-card mb-4" style="margin-bottom: 24px;">
        <div class="card-bar">
            <span><i class="fas fa-calendar-plus"></i> Create New Academic Period</span>
        </div>
        <div style="padding: 24px;">
            <form id="academicPeriodForm" action="{{ route('system_admin.store_academic_period') }}" method="POST" class="create-period-grid" autocomplete="off" novalidate>
                @csrf
                <div class="form-field">
                    <label class="field-label">School Year <span class="required-star">*</span></label>
                    <input type="text" name="school_year" id="form_school_year" class="field-input" placeholder="e.g. 2026-2027" required>
                </div>
                <div class="form-field">
                    <label class="field-label">Semester <span class="required-star">*</span></label>
                    <div class="custom-select-wrapper">
                        <select name="semester" id="form_semester" class="form-select-custom" required>
                            <option value="" disabled selected hidden>Choose Semester</option>
                            <option value="1st Semester">1st Semester</option>
                            <option value="2nd Semester">2nd Semester</option>
                            <option value="Summer">Summer</option>
                        </select>
                    </div>
                </div>
                <div class="form-field-btn">
                    <button type="submit" class="btn-submit-subj">
                        <i class="fas fa-plus"></i> Create Period
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="sa-card">
        <div class="card-header-flex">
            <div class="card-title-container">
                <h3 class="card-title-list">Academic Periods</h3>
            </div>
            <div class="header-controls-flex" style="display: flex; align-items: center; gap: 15px;">
                <div class="table-search-box">
                    <svg class="search-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/>
                        <line x1="16.2" y1="16.2" x2="21" y2="21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    <input
                        type="text"
                        id="academicPeriodSearch"
                        placeholder="Search by School Year...">
                </div>
                <span class="badge-count" id="totalPeriodsCount">{{ count($periods) }} Total</span>
            </div>
        </div>

        @if($periods->isEmpty())
            <div style="padding: 50px; text-align: center; color: #94a3b8; font-style: italic;">
                <i class="fas fa-calendar-xmark" style="font-size: 24px; display: block; margin-bottom: 8px;"></i>
                No academic periods created yet.
            </div>
        @else
            <div id="noPeriodResults" style="display: none; padding: 50px; text-align: center; color: #94a3b8; font-style: italic;">
                <i class="fas fa-magnifying-glass" style="font-size: 24px; display: block; margin-bottom: 8px;"></i>
                No academic periods match your search.
            </div>
            @foreach($periods as $period)
                @php
                    $terms = $termPeriods[$period->id] ?? collect();

                    // All 4 terms must have both start and end dates saved before activation
                    $requiredTerms = ['prelim', 'midterm', 'semifinal', 'final'];
                    $missingTerms = collect($requiredTerms)->filter(function ($key) use ($terms) {
                        $t = $terms->firstWhere('term', $key);
                        return !$t || empty($t->start_date) || empty($t->end_date);
                    });
                    $canActivate = $missingTerms->isEmpty();

                    // School year -> year level mapping.
                    // Base year 2023-2024 = 1st Year, 2024-2025 = 2nd Year, etc.
                    $baseStartYear = 2023;
                    $yearLevels = [1 => '1st Year', 2 => '2nd Year', 3 => '3rd Year', 4 => '4th Year'];
                    $startYear = (int) substr(trim($period->school_year), 0, 4);
                    $levelNumber = $startYear - $baseStartYear + 1;
                    $mappedYearLevel = $yearLevels[$levelNumber] ?? null;

                    // Subjects for this period's semester AND mapped year level
                    $subjectsForPeriod = collect($curriculumSubjects[trim($period->semester)] ?? [])
                        ->filter(fn($s) => $mappedYearLevel && trim($s->year_level) === $mappedYearLevel)
                        ->values();
                @endphp
                <div class="period-block" data-school-year="{{ strtolower(trim($period->school_year)) }}">
                    <div class="period-block-header">
                        <div class="period-title-group">
                            <span class="period-title">{{ $period->school_year }} — {{ $period->semester }}</span>
                            @if($period->is_active)
                                <span class="status-active-badge"><i class="fas fa-circle" style="font-size: 6px;"></i> Active</span>
                            @else
                                <span class="status-inactive-badge">Inactive</span>
                            @endif
                        </div>
                        <div class="period-actions">
                            <button type="button" class="btn-action-edit"
                                onclick="openPeriodSubjectsModal(@js($period->school_year . ' — ' . $period->semester), @js($mappedYearLevel ?? 'No matching year level'), @js($subjectsForPeriod))">
                                <i class="fas fa-book"></i> View Subjects
                            </button>
                            @if(!$period->is_active)
                                <form action="{{ route('system_admin.activate_academic_period', $period->id) }}" method="POST" style="display:inline-block;">
                                    @csrf
                                    @if($canActivate)
                                        <button type="submit" class="btn-action-edit" onclick="return confirm('Set this as the active academic period? This will deactivate the current one.')">
                                            <i class="fas fa-check"></i> Set as Active
                                        </button>
                                    @else
                                        <button type="button" class="btn-action-edit btn-disabled" disabled
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
                                <button type="submit" class="btn-action-delete btn-delete-trigger">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="term-dates-grid">
                        @foreach(['prelim' => 'Prelim', 'midterm' => 'Midterm', 'semifinal' => 'Semi-Final', 'final' => 'Final'] as $key => $label)
                            @php $existingTerm = $terms->firstWhere('term', $key); @endphp
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
                                        <input type="date" name="start_date" value="{{ $existingTerm->start_date ?? '' }}" class="term-date-input start-date-field" required>
                                    </div>
                                    <div class="term-field-single">
                                        <label class="term-sub-label">End</label>
                                        <input type="date" name="end_date" value="{{ $existingTerm->end_date ?? '' }}" class="term-date-input end-date-field" required>
                                    </div>
                                </div>

                                <button type="submit" class="btn-save-term">
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

<!-- MODAL FOR VIEWING SUBJECTS PER ACADEMIC PERIOD -->
<div id="termSubjectsModal" class="custom-modal-overlay" style="display: none;">
    <div class="custom-modal-content" style="max-width: 650px;">
        <div class="modal-header-bar">
            <div>
                <h3 id="modal-term-title" style="margin: 0; font-size: 16px; font-weight: 700;"><i class="fas fa-book-open"></i> Period Subjects</h3>
                <span id="modal-term-subtitle" style="font-size: 12px; color: #64748b; font-weight: 600;"></span>
            </div>
            <button type="button" class="close-modal-btn" onclick="closeTermSubjectsModal()">&times;</button>
        </div>
        
        <div style="padding: 20px; max-height: 420px; overflow-y: auto;">
            <table class="minimal-table" style="width: 100%;">
                <thead>
                    <tr>
                        <th>Course</th>
                        <th>Code</th>
                        <th>Subject Title</th>
                    </tr>
                </thead>
                <tbody id="modal-subjects-list">
                    <!-- Populated via JavaScript -->
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

    // SEARCH BAR: filter periods by school year
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
                    <td colspan="3" style="text-align: center; color: #94a3b8; padding: 25px !important; font-style: italic;">
                        No subjects registered for this period in the curriculum masterlist.
                    </td>
                </tr>`;
        } else {
            subjects.forEach(sub => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><span class="subject-code-tag" style="background:#f1f5f9; color:#475569;">${sub.course_code}</span></td>
                    <td><span class="subject-code-tag" style="background:#ecfdf5; color:#059669;">${sub.subject_code}</span></td>
                    <td style="font-size: 13px; font-weight: 600; color:#1e293b;">${sub.subject_title}</td>
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
    .sa-container { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #0f172a; max-width: 1650px; margin: 0 auto; padding: 20px 24px; }
    .sa-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 2px solid #e2e8f0; padding-bottom: 18px; }
    .header-titles h2 { margin: 0 0 4px 0; font-size: 26px; font-weight: 800; color: #0f172a; letter-spacing: -0.5px; }
    .header-titles p { margin: 0; font-size: 14px; color: #64748b; }

    .required-star { color: #dc2626; font-weight: 700; }

    .sa-card { border-radius: 12px; border: 1px solid #e2e8f0; background: #ffffff; overflow: hidden; box-shadow: 0 4px 10px -2px rgba(0,0,0,0.03); }
    .card-bar { background: #f8fafc; padding: 16px 20px; font-weight: 700; font-size: 14.5px; color: #1e293b; border-bottom: 1px solid #e2e8f0; }

    .card-header-flex { background-color: #f8fafc; padding: 16px 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
    .card-title-container { display: flex; align-items: center; gap: 12px; }
    .card-title-list { font-size: 14.5px; font-weight: 700; margin: 0; color: #1e293b; }
    .badge-count { background: #e2e8f0; color: #334155; font-size: 11.5px; padding: 4px 12px; border-radius: 20px; font-weight: 700; white-space: nowrap; }

    .table-search-box { position: relative; display: flex; align-items: center; }
    .table-search-box .search-icon { position: absolute; left: 10px; width: 15px; height: 15px; color: #94a3b8; pointer-events: none; }
    .table-search-box input { height: 36px; padding: 0 12px 0 32px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; color: #0f172a; background: #ffffff; outline: none; width: 220px; transition: border-color 0.2s, box-shadow 0.2s; }
    .table-search-box input:focus { border-color: #0033A0; box-shadow: 0 0 0 3px rgba(0,51,160,0.1); }

    .create-period-grid { display: grid; grid-template-columns: 1fr 1fr auto; gap: 16px; align-items: end; }
    .form-field { display: flex; flex-direction: column; gap: 6px; width: 100%; }
    .form-field-btn { display: flex; align-items: end; }
    .field-label { font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase; letter-spacing: 0.3px; }
    .field-input { width: 100%; height: 42px; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; color: #0f172a; background: #ffffff; box-sizing: border-box; outline: none; }
    .field-input:focus { border-color: #000000; box-shadow: 0 0 0 3px rgba(0,0,0,0.05); }

    .custom-select-wrapper { position: relative; width: 100%; }
    .form-select-custom { width: 100%; height: 42px; padding: 10px 40px 10px 14px; font-size: 14px; color: #0f172a; background-color: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; appearance: none; -webkit-appearance: none; cursor: pointer; outline: none; transition: all 0.2s ease; }
    .form-select-custom:focus { border-color: #000000; box-shadow: 0 0 0 3px rgba(0,0,0,0.05); }
    .custom-select-wrapper::after { content: "▼"; font-size: 0.65rem; color: #64748b; position: absolute; right: 14px; top: 50%; transform: translateY(-50%); pointer-events: none; }

    .btn-submit-subj { background: #0033A0; color: #ffffff; border: none; padding: 0 24px; font-size: 14px; font-weight: 700; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 6px; height: 42px; transition: background 0.2s; white-space: nowrap; }
    .btn-submit-subj:hover { background: #002270; }

    .period-block { padding: 20px 24px; border-bottom: 1px solid #f1f5f9; }
    .period-block:last-child { border-bottom: none; }
    .period-block-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px; }
    .period-title-group { display: flex; align-items: center; gap: 12px; }
    .period-title { font-size: 15px; font-weight: 700; color: #1e293b; }
    .status-active-badge { display: inline-flex; align-items: center; gap: 5px; background: #ecfdf5; color: #065f46; font-size: 11.5px; font-weight: 700; padding: 4px 12px; border-radius: 20px; border: 1px solid #a7f3d0; }
    .status-inactive-badge { background: #f1f5f9; color: #64748b; font-size: 11.5px; font-weight: 700; padding: 4px 12px; border-radius: 20px; border: 1px solid #e2e8f0; }
    .period-actions { display: flex; gap: 8px; }

    .term-dates-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; }
    .term-date-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; display: flex; flex-direction: column; gap: 12px; }
    .term-date-label { font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.3px; }
    .term-date-inputs { display: flex; align-items: center; gap: 6px; }
    .term-date-input { flex: 1; min-width: 0; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 12.5px; color: #0f172a; outline: none; background: #ffffff; }
    .term-date-input:focus { border-color: #000000; box-shadow: 0 0 0 2px rgba(0,0,0,0.05); }

    .term-inputs-group { display: flex; gap: 8px; }
    .term-field-single { flex: 1; display: flex; flex-direction: column; gap: 4px; min-width: 0; }
    .term-sub-label { font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; }

    .btn-save-term { background: #0033A0; color: white; border: none; padding: 9px; border-radius: 6px; font-size: 12.5px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; transition: background 0.2s; }
    .btn-save-term:hover { background: #002270; }

    .btn-action-edit { background-color: transparent; border: 1px solid #cbd5e1; color: #475569; padding: 7px 14px; font-size: 12.5px; font-weight: 600; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s; }
    .btn-action-edit:hover { background-color: #f1f5f9; color: #1e293b; border-color: #94a3b8; }

    .btn-action-edit.btn-disabled, .btn-action-edit:disabled { opacity: 0.5; cursor: not-allowed; background-color: #f8fafc; color: #94a3b8; border-color: #e2e8f0; }

    .btn-action-delete { background-color: transparent; border: 1px solid #fecaca; color: #dc2626; padding: 7px 14px; font-size: 12.5px; font-weight: 600; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s; }
    .btn-action-delete:hover { background-color: #fef2f2; color: #991b1b; border-color: #f87171; }

    .btn-view-subjects { background: transparent; border: none; color: #2563eb; font-size: 11.5px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; padding: 0; }
    .btn-view-subjects:hover { text-decoration: underline; color: #1d4ed8; }

    .sa-alert-success { background: #ecfdf5; color: #065f46; padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; font-weight: 600; border-left: 5px solid #059669; font-size: 14px; display: flex; align-items: center; gap: 10px; }
    .sa-alert-danger { background: #fef2f2; color: #991b1b; padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; font-weight: 600; border-left: 5px solid #dc2626; font-size: 14px; display: flex; align-items: flex-start; gap: 10px; }

    .minimal-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
    .minimal-table th { background-color: #f8fafc; color: #64748b; font-weight: 600; text-transform: uppercase; font-size: 0.72rem; padding: 10px 14px; border-bottom: 1px solid #e2e8f0; text-align: left; }
    .minimal-table td { padding: 10px 14px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
    .subject-code-tag { font-family: ui-monospace, SFMono-Regular, Consolas, monospace; font-weight: 700; padding: 3px 6px; border-radius: 4px; font-size: 0.8rem; }

    .custom-modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); display: flex; justify-content: center; align-items: center; z-index: 9999; }
    .custom-modal-content { background: #ffffff; border-radius: 12px; width: 100%; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); overflow: hidden; }
    .modal-header-bar { background: #f8fafc; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; }
    .close-modal-btn { background: transparent; border: none; font-size: 24px; color: #94a3b8; cursor: pointer; line-height: 1; padding: 0; }
    .close-modal-btn:hover { color: #475569; }
</style>
@endsection