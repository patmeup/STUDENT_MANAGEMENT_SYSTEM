@extends('layouts.app')

@section('content')
<div class="sa-container">
    <div class="sa-header">
        <div class="header-titles">
            <h2><i class="fas fa-graduation-cap"></i> Manage Student Grades</h2>
            <p>
                Monitor and view student grade submission statuses
                @if(isset($activeAcademicPeriod))
                    for <strong>{{ $activeAcademicPeriod->school_year }} ({{ $activeAcademicPeriod->semester }})</strong>
                @endif
            </p>
        </div>
    </div>

    <div class="filter-bar-card">
        <form method="GET" action="{{ route('registrar.manage-grades') }}" class="filter-form">
            <input type="hidden" name="search" value="{{ request('search') }}">
            <div class="term-select-wrapper">
                <i class="fa-regular fa-calendar-days"></i>
                <select name="term" class="term-select" onchange="this.form.submit()">
                    @foreach($termPeriods->sortBy('start_date') as $tp)
                        <option value="{{ $tp->term }}" {{ $termFilter == $tp->term ? 'selected' : '' }}>
                            {{ ucfirst($tp->term) }} Term ({{ \Carbon\Carbon::parse($tp->start_date)->format('M d') }} - {{ \Carbon\Carbon::parse($tp->end_date)->format('M d, Y') }})
                        </option>
                    @endforeach
                </select>
            </div>
        </form>

        <form method="GET" action="{{ route('registrar.manage-grades') }}" class="search-form">
            <input type="hidden" name="term" value="{{ $termFilter }}">
            <div class="search-input-wrapper">
                <i class="fas fa-search search-icon"></i>
                <input type="text" name="search" class="search-input" placeholder="Search by Student ID, First Name, or Last Name..." value="{{ request('search') }}">
            </div>
            <button type="submit" class="btn-filter-submit">
                <i class="fa-solid fa-filter"></i> Filter
            </button>
        </form>
    </div>

    <div class="sa-card">
        <div class="table-responsive">
            <table class="minimal-table">
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Full Name</th>
                        <th>Course / Year</th>
                        <th class="text-center">Class Standing</th>
                        <th class="text-center">Major Exam</th>
                        <th class="text-center">Equivalent</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $student)
                        <tr>
                            <td><strong class="code-highlight">{{ $student->student_id }}</strong></td>
                            <td><strong>{{ strtoupper($student->last_name) }}</strong>, {{ $student->first_name }}</td>
                            <td>
                                <span class="sem-tag">{{ $student->course }}</span>
                                <span class="year-tag">Yr {{ $student->year_level }}</span>
                            </td>

                            <!-- Class Standing -->
                            <td class="text-center fw-medium">
                                {{ $student->term_grade->class_standing ?? '—' }}
                            </td>

                            <!-- Major Exam -->
                            <td class="text-center fw-medium">
                                {{ $student->term_grade->major_exam ?? '—' }}
                            </td>

                            <!-- Equivalent Grade -->
                            <td class="text-center">
                                @if(isset($student->term_grade->equivalent))
                                    <span class="equivalent-badge">{{ number_format($student->term_grade->equivalent, 2) }}</span>
                                @else
                                    <span class="text-muted-cell">—</span>
                                @endif
                            </td>

                            <td class="text-center">
                                @if(isset($student->term_grade))
                                    <span class="status-pill status-submitted">
                                        <i class="fa-solid fa-circle-check"></i> Submitted
                                    </span>
                                @else
                                    <span class="status-pill status-pending">
                                        <i class="fa-solid fa-hourglass-half"></i> Pending
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 50px; color: #64748b;">
                                <i class="fa-regular fa-folder-open" style="font-size: 30px; display: block; margin-bottom: 10px; color: #cbd5e1;"></i>
                                No students found matching your criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Links -->
        @if($students->hasPages())
            <div class="pagination-footer">
                {{ $students->appends(['search' => request('search'), 'term' => $termFilter])->links() }}
            </div>
        @endif
    </div>
</div>

<style>
    .sa-container { font-family: 'Segoe UI', sans-serif; padding: 20px; max-width: 1300px; margin: 0 auto; }
    .sa-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #e2e8f0; padding-bottom: 12px; }
    .header-titles h2 { margin: 0; font-size: 24px; color: #0f172a; font-weight: 800; display: flex; align-items: center; gap: 10px; }
    .header-titles p { margin: 6px 0 0 0; font-size: 14px; color: #64748b; }
    .header-titles p strong { color: #1e293b; }

    /* Filter Bar */
    .filter-bar-card {
        display: flex; flex-wrap: wrap; gap: 12px; align-items: center;
        background: white; border: 1px solid #e2e8f0; border-radius: 12px;
        padding: 16px 20px; margin-bottom: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
    }
    .term-select-wrapper {
        display: flex; align-items: center; gap: 10px;
        border: 1px solid #cbd5e1; border-radius: 50px; padding: 8px 18px;
        background: #f8fafc; color: #64748b;
    }
    .term-select { border: none; background: transparent; font-weight: 600; color: #334155; font-size: 13.5px; outline: none; cursor: pointer; }

    .search-form { display: flex; gap: 10px; flex: 1; min-width: 280px; }
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

    /* Table Card */
    .sa-card { border-radius: 12px; border: 1px solid #e2e8f0; background: #ffffff; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }

    .minimal-table { width: 100%; border-collapse: collapse; font-size: 14px; }
    .minimal-table th { background: #f8fafc; padding: 14px 20px; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; text-align: left; }
    .minimal-table td { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
    .minimal-table tbody tr:hover { background-color: #f8fafc; }

    .code-highlight { color: #2563eb; font-family: monospace; font-size: 13.5px; }
    .year-tag { font-weight: 700; color: #0369a1; background: #e0f2fe; padding: 4px 8px; border-radius: 6px; font-size: 11px; border: 1px solid #bae6fd; margin-left: 6px; }
    .sem-tag { font-weight: 600; color: #475569; background: #f8fafc; padding: 4px 8px; border-radius: 6px; font-size: 11px; border: 1px solid #e2e8f0; }
    .text-muted-cell { color: #94a3b8; }

    .equivalent-badge {
        font-weight: 800; color: #1e2a5a; background: #eef1fb;
        padding: 5px 14px; border-radius: 20px; font-size: 13px;
        border: 1px solid #c7d2fe;
    }

    .status-pill {
        display: inline-flex; align-items: center; gap: 6px;
        font-size: 11.5px; font-weight: 700; padding: 6px 14px; border-radius: 20px;
    }

    .status-submitted { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
    .status-pending { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }

    .pagination-footer {
        display: flex; justify-content: flex-end; padding: 16px 20px;
        border-top: 1px solid #f1f5f9; background: white;
    }
    .pagination-footer nav ul { display: flex; gap: 4px; list-style: none; padding: 0; margin: 0; }
    .pagination-footer nav ul li a, .pagination-footer nav ul li span {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 32px; height: 32px; padding: 0 8px;
        border: 1px solid #e2e8f0; border-radius: 6px; color: #475569;
        font-size: 13px; text-decoration: none; transition: 0.15s;
    }
    .pagination-footer nav ul li a:hover { background: #f8fafc; border-color: #cbd5e1; }
    .pagination-footer nav ul li.active span { background: #1e2a5a; color: white; border-color: #1e2a5a; }
</style>
@endsection