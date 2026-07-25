@extends('layouts.app')

@section('content')
<div class="sa-container">
    <div class="sa-header">
        <div class="header-titles">
            <h2><i class="fas fa-user-clock"></i> Faculty Grade Submission Status</h2>
            <p>
                Track which classes have complete, partial, or missing grade submissions
                @if($activeAcademicPeriod)
                    for <strong>{{ $activeAcademicPeriod->school_year }} ({{ $activeAcademicPeriod->semester }})</strong>
                @endif
            </p>
        </div>
    </div>

    <div class="filter-bar-card">
        <form method="GET" action="{{ route('registrar.faculty-grade-status') }}" class="term-form">
            <input type="hidden" name="search" value="{{ $search }}">
            <div class="term-select-wrapper">
                <i class="fa-regular fa-calendar-days"></i>
                <select name="term" class="term-select" onchange="this.form.submit()">
                    @foreach($termPeriods as $tp)
                        <option value="{{ $tp->term }}" {{ $termFilter == $tp->term ? 'selected' : '' }}>
                            {{ ucfirst($tp->term) }} Term ({{ \Carbon\Carbon::parse($tp->start_date)->format('M d') }} - {{ \Carbon\Carbon::parse($tp->end_date)->format('M d, Y') }})
                        </option>
                    @endforeach
                </select>
            </div>
        </form>

        <form method="GET" action="{{ route('registrar.faculty-grade-status') }}" class="search-form">
            <input type="hidden" name="term" value="{{ $termFilter }}">
            <div class="search-input-wrapper">
                <i class="fas fa-search search-icon"></i>
                <input type="text" name="search" class="search-input" placeholder="Search by professor, subject, or section..." value="{{ $search }}">
            </div>
            <button type="submit" class="btn-filter-submit">
                <i class="fa-solid fa-filter"></i> Filter
            </button>
        </form>
    </div>

    <div class="status-legend">
        <span class="legend-chip legend-complete"><i class="fa-solid fa-circle-check"></i> Complete</span>
        <span class="legend-chip legend-partial"><i class="fa-solid fa-triangle-exclamation"></i> Partial</span>
        <span class="legend-chip legend-not-started"><i class="fa-solid fa-circle-xmark"></i> Not Started</span>
    </div>

    <!-- Table Card -->
    <div class="sa-card">
        <div class="table-responsive">
            <table class="minimal-table">
                <thead>
                    <tr>
                        <th>Professor</th>
                        <th>Subject / Section</th>
                        <th class="text-center">Enrolled</th>
                        <th class="text-center">Submitted</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($report as $class)
                        <tr>
                            <td>
                                @if($class->prof_first_name && $class->prof_last_name)
                                    <strong>{{ strtoupper($class->prof_last_name) }}</strong>, {{ $class->prof_first_name }}
                                @else
                                    <span class="text-muted-cell">TBA</span>
                                @endif
                            </td>
                            <td>
                                <span class="code-highlight">{{ strtoupper($class->subject_code) }}</span>
                                — {{ $class->subject_title }}
                                <span class="year-tag">{{ strtoupper($class->section_name) }}</span>
                            </td>
                            <td class="text-center fw-medium">{{ $class->enrolled_count }}</td>
                            <td class="text-center fw-medium">{{ $class->submitted_count }}</td>
                            <td class="text-center">
                                @if($class->status === 'complete')
                                    <span class="status-pill status-submitted"><i class="fa-solid fa-circle-check"></i> Complete</span>
                                @elseif($class->status === 'partial')
                                    <span class="status-pill status-partial"><i class="fa-solid fa-triangle-exclamation"></i> Partial ({{ $class->submitted_count }}/{{ $class->enrolled_count }})</span>
                                @elseif($class->status === 'not_started')
                                    <span class="status-pill status-pending"><i class="fa-solid fa-circle-xmark"></i> Not Started</span>
                                @else
                                    <span class="status-pill status-none"><i class="fa-solid fa-ban"></i> No Students</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 50px; color: #64748b;">
                                <i class="fa-regular fa-folder-open" style="font-size: 30px; display: block; margin-bottom: 10px; color: #cbd5e1;"></i>
                                No class schedules found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .sa-container { font-family: 'Segoe UI', sans-serif; padding: 20px; max-width: 1300px; margin: 0 auto; }
    .sa-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #e2e8f0; padding-bottom: 12px; }
    .header-titles h2 { margin: 0; font-size: 24px; color: #0f172a; font-weight: 800; display: flex; align-items: center; gap: 10px; }
    .header-titles p { margin: 6px 0 0 0; font-size: 14px; color: #64748b; }
    .header-titles p strong { color: #1e293b; }

    .filter-bar-card {
        display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 12px;
        background: white; border: 1px solid #e2e8f0; border-radius: 12px;
        padding: 16px 20px; margin-bottom: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
    }
    .term-select-wrapper {
        display: flex; align-items: center; gap: 10px;
        border: 1px solid #cbd5e1; border-radius: 50px; padding: 8px 18px;
        background: #f8fafc; color: #64748b;
    }
    .term-select { border: none; background: transparent; font-weight: 600; color: #334155; font-size: 13.5px; outline: none; cursor: pointer; }

    .search-form { display: flex; gap: 10px; flex: 1; min-width: 280px; margin-top: 12px; }
    .search-input-wrapper { position: relative; flex: 1; }
    .search-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 13px; }
    .search-input {
        width: 100%; box-sizing: border-box; padding: 9px 12px 9px 36px;
        border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; color: #334155; outline: none;
    }
    .search-input:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.08); }
    .btn-filter-submit {
        background: #1e2a5a; color: white; border: none; padding: 9px 18px;
        border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer;
        display: inline-flex; align-items: center; gap: 6px;
    }
    .btn-filter-submit:hover { background: #141d40; }

    .status-legend { display: flex; gap: 14px; margin-bottom: 16px; padding: 0 4px; }

    .status-legend { display: flex; gap: 14px; }
    .legend-chip { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 600; }
    .legend-complete { color: #15803d; }
    .legend-partial { color: #b45309; }
    .legend-not-started { color: #dc2626; }

    .sa-card { border-radius: 12px; border: 1px solid #e2e8f0; background: #ffffff; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }

    .minimal-table { width: 100%; border-collapse: collapse; font-size: 14px; }
    .minimal-table th { background: #f8fafc; padding: 14px 20px; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; text-align: left; }
    .minimal-table td { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
    .minimal-table tbody tr:hover { background-color: #f8fafc; }

    .code-highlight { color: #2563eb; font-family: monospace; font-size: 13.5px; font-weight: 700; }
    .year-tag { font-weight: 700; color: #0369a1; background: #e0f2fe; padding: 4px 8px; border-radius: 6px; font-size: 11px; border: 1px solid #bae6fd; margin-left: 6px; }
    .text-muted-cell { color: #94a3b8; }

    .status-pill { display: inline-flex; align-items: center; gap: 6px; font-size: 11.5px; font-weight: 700; padding: 6px 14px; border-radius: 20px; white-space: nowrap; }
    .status-submitted { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
    .status-partial { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
    .status-pending { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
    .status-none { background: #f1f5f9; color: #94a3b8; border: 1px solid #e2e8f0; }
</style>
@endsection