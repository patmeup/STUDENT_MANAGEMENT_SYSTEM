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
            <span class="badge-count" id="totalPeriodsCount">{{ count($periods) }} Total</span>
        </div>

        @if($periods->isEmpty())
            <div style="padding: 50px; text-align: center; color: #94a3b8; font-style: italic;">
                <i class="fas fa-calendar-xmark" style="font-size: 24px; display: block; margin-bottom: 8px;"></i>
                No academic periods created yet.
            </div>
        @else
            @foreach($periods as $period)
                @php $terms = $termPeriods[$period->id] ?? collect(); @endphp
                <div class="period-block">
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
                            @if(!$period->is_active)
                                <form action="{{ route('system_admin.activate_academic_period', $period->id) }}" method="POST" style="display:inline-block;">
                                    @csrf
                                    <button type="submit" class="btn-action-edit" onclick="return confirm('Set this as the active academic period? This will deactivate the current one.')">
                                        <i class="fas fa-check"></i> Set as Active
                                    </button>
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
                                <span class="term-date-label">{{ $label }}</span>
                                
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
                    if (y2 !== y1 + 1) {
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

    .btn-submit-subj { background: #000000; color: #ffffff; border: none; padding: 0 24px; font-size: 14px; font-weight: 700; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 6px; height: 42px; transition: background 0.2s; white-space: nowrap; }
    .btn-submit-subj:hover { background: #1e293b; }

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
    .term-date-sep { font-size: 12px; color: #64748b; font-weight: 500; }
    
    .term-inputs-group { display: flex; gap: 8px; }
    .term-field-single { flex: 1; display: flex; flex-direction: column; gap: 4px; min-width: 0; }
    .term-sub-label { font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; }

    .btn-save-term { background: #000000; color: white; border: none; padding: 9px; border-radius: 6px; font-size: 12.5px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; transition: background 0.2s; }
    .btn-save-term:hover { background: #1e293b; }

    .btn-action-edit { background-color: transparent; border: 1px solid #cbd5e1; color: #475569; padding: 7px 14px; font-size: 12.5px; font-weight: 600; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s; }
    .btn-action-edit:hover { background-color: #f1f5f9; color: #1e293b; border-color: #94a3b8; }

    .btn-action-delete { background-color: transparent; border: 1px solid #fecaca; color: #dc2626; padding: 7px 14px; font-size: 12.5px; font-weight: 600; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s; }
    .btn-action-delete:hover { background-color: #fef2f2; color: #991b1b; border-color: #f87171; }

    .sa-alert-success { background: #ecfdf5; color: #065f46; padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; font-weight: 600; border-left: 5px solid #059669; font-size: 14px; display: flex; align-items: center; gap: 10px; }
    .sa-alert-danger { background: #fef2f2; color: #991b1b; padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; font-weight: 600; border-left: 5px solid #dc2626; font-size: 14px; display: flex; align-items: flex-start; gap: 10px; }
</style>
@endsection