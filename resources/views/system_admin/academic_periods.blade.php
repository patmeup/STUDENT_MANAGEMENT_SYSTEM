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
        <div class="alert-success-custom"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert-danger-custom"><i class="fas fa-exclamation-circle"></i> {{ session('error') }}</div>
    @endif

    <!-- Create New Academic Period -->
    <div class="sa-card mb-4">
        <div class="card-header-flex">
            <h3 class="card-title-list">Create New Academic Period</h3>
        </div>
        <div style="padding: 20px 24px;">
            <form action="{{ route('system_admin.store_academic_period') }}" method="POST" class="create-period-form">
                @csrf
                <div class="form-field">
                    <label class="field-label">School Year</label>
                    <input type="text" name="school_year" class="field-input" placeholder="e.g. 2026-2027" required>
                </div>
                <div class="form-field">
                    <label class="field-label">Semester</label>
                    <select name="semester" class="field-input" required>
                        <option value="1st Semester">1st Semester</option>
                        <option value="2nd Semester">2nd Semester</option>
                        <option value="Summer">Summer</option>
                    </select>
                </div>
                <button type="submit" class="btn-action-view">
                    <i class="fas fa-plus"></i> Create Period
                </button>
            </form>
        </div>
    </div>

    <!-- Existing Periods List -->
    <div class="sa-card">
        <div class="card-header-flex">
            <h3 class="card-title-list">Academic Periods</h3>
            <span class="badge-count">{{ count($periods) }} Total</span>
        </div>

        @if($periods->isEmpty())
            <div style="padding: 50px; text-align: center; color: #64748b;">
                <i class="fas fa-calendar-xmark" style="font-size: 30px; display: block; margin-bottom: 10px; color: #cbd5e1;"></i>
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
                                    <button type="submit" class="btn-action-view" onclick="return confirm('Set this as the active academic period? This will deactivate the current one.')">
                                        <i class="fas fa-check"></i> Set as Active
                                    </button>
                                </form>
                            @endif
                            <form action="{{ route('system_admin.delete_academic_period', $period->id) }}" method="POST" style="display:inline-block;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-action-plot" style="background: #dc2626;" onclick="return confirm('Delete this academic period and all its term dates?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="term-dates-grid">
                        @foreach(['prelim' => 'Prelim', 'midterm' => 'Midterm', 'semifinal' => 'Semi-Final', 'final' => 'Final'] as $key => $label)
                            @php $existingTerm = $terms->firstWhere('term', $key); @endphp
                            <form action="{{ route('system_admin.store_term_period') }}" method="POST" class="term-date-card">
                                @csrf
                                <input type="hidden" name="academic_period_id" value="{{ $period->id }}">
                                <input type="hidden" name="term" value="{{ $key }}">
                                <span class="term-date-label">{{ $label }}</span>
                                <div class="term-date-inputs">
                                    <input type="date" name="start_date" value="{{ $existingTerm->start_date ?? '' }}" class="term-date-input" required>
                                    <span class="term-date-sep">to</span>
                                    <input type="date" name="end_date" value="{{ $existingTerm->end_date ?? '' }}" class="term-date-input" required>
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

<style>
    .sa-container { font-family: 'Segoe UI', sans-serif; padding: 20px; max-width: 1200px; margin: 0 auto; }
    .sa-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #e2e8f0; padding-bottom: 12px; }
    .header-titles h2 { margin: 0; font-size: 24px; color: #0f172a; font-weight: 800; }
    .header-titles p { margin: 4px 0 0 0; font-size: 14px; color: #64748b; }

    .sa-card { border-radius: 12px; border: 1px solid #e2e8f0; background: #ffffff; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    .card-header-flex { background-color: #f8fafc; padding: 16px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
    .card-title-list { font-size: 15px; font-weight: 700; margin: 0; color: #1e293b; }
    .badge-count { background: #0f172a; color: white; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 12px; }

    .create-period-form { display: flex; align-items: flex-end; gap: 16px; flex-wrap: wrap; }
    .form-field { display: flex; flex-direction: column; gap: 6px; }
    .field-label { font-size: 12px; font-weight: 700; color: #475569; }
    .field-input { padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13.5px; color: #334155; min-width: 200px; }
    .field-input:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.08); }

    .btn-action-view { text-decoration: none; background: #2563eb; color: white; padding: 9px 16px; border: none; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
    .btn-action-view:hover { background: #1d4ed8; }
    .btn-action-plot { text-decoration: none; background: #0f172a; color: white; padding: 6px 12px; border: none; border-radius: 6px; font-size: 12.5px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
    .btn-action-plot:hover { opacity: 0.85; }

    .period-block { padding: 20px 24px; border-bottom: 1px solid #f1f5f9; }
    .period-block:last-child { border-bottom: none; }
    .period-block-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
    .period-title-group { display: flex; align-items: center; gap: 10px; }
    .period-title { font-size: 15px; font-weight: 700; color: #1e293b; }
    .status-active-badge { display: inline-flex; align-items: center; gap: 5px; background: #dcfce7; color: #15803d; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 20px; }
    .status-inactive-badge { background: #f1f5f9; color: #94a3b8; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 20px; }
    .period-actions { display: flex; gap: 8px; }

    .term-dates-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 12px; }
    .term-date-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px; display: flex; flex-direction: column; gap: 10px; }
    .term-date-label { font-size: 12.5px; font-weight: 700; color: #1e2a5a; text-transform: uppercase; letter-spacing: 0.4px; }
    .term-date-inputs { display: flex; align-items: center; gap: 6px; }
    .term-date-input { flex: 1; min-width: 0; padding: 6px 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 12px; }
    .term-date-sep { font-size: 11px; color: #94a3b8; }
    .btn-save-term { background: #1e2a5a; color: white; border: none; padding: 7px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; }
    .btn-save-term:hover { background: #141d40; }

    .alert-success-custom { background: #f0fdf4; color: #166534; padding: 12px 18px; border-radius: 8px; border-left: 5px solid #16a34a; margin-bottom: 15px; font-weight: 600; font-size: 13.5px; }
    .alert-danger-custom { background: #fef2f2; color: #991b1b; padding: 12px 18px; border-radius: 8px; border-left: 5px solid #dc2626; margin-bottom: 15px; font-weight: 600; font-size: 13.5px; }
</style>
@endsection