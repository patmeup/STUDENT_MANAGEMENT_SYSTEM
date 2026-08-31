@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="grade-report-container py-4">

    <div class="report-header-row mb-4">
        <div>
            <h2 class="report-title">My Grade Report</h2>
            <p class="report-subtitle">View your academic performance, term grades, and final equivalents per subject.</p>
        </div>
        <div class="ay-badge">
            <i class="fa-solid fa-calendar-days"></i>
            @if($activeAcademicPeriod)
                {{ $activeAcademicPeriod->school_year }} &middot; {{ $activeAcademicPeriod->semester }}
            @else
                No Active Academic Period
            @endif
        </div>
    </div>

    <div class="report-card">
        <div class="report-card-header">
            <i class="fa-solid fa-table-list"></i> Enrolled Subjects & Grades Summary
        </div>

        <div class="table-responsive">
            <table class="grade-table">
                <thead>
                    <tr>
                        <th rowspan="2" class="col-subject">SUBJECT & DESCRIPTION</th>
                        <th rowspan="2" class="col-units">LEC UNITS</th>
                        <th rowspan="2" class="col-units">LAB UNITS</th>
                        <th rowspan="2" class="col-prof">PROFESSOR</th>
                        <th colspan="2" class="term-header term-header-prelim">PRELIM</th>
                        <th colspan="2" class="term-header term-header-midterm">MIDTERM</th>
                        <th colspan="2" class="term-header term-header-finals">FINALS</th>
                    </tr>
                    <tr>
                        <th class="sub-col">Percentage</th>
                        <th class="sub-col">Equivalent</th>
                        <th class="sub-col">Percentage</th>
                        <th class="sub-col">Equivalent</th>
                        <th class="sub-col">Percentage</th>
                        <th class="sub-col">Equivalent</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($gradesData as $data)
                        <tr class="grade-row">
                            <td class="col-subject">
                                <span class="subject-code">{{ $data['subject_code'] }}</span>
                                <span class="subject-sep">•</span>
                                <span class="subject-title">{{ $data['subject_title'] }}</span>
                            </td>
                            <td class="text-center units-value">{{ number_format($data['lec_units'], 1) }}</td>
                            <td class="text-center units-value">{{ number_format($data['lab_units'], 1) }}</td>
                            <td class="prof-cell">
                                <span class="prof-name">{{ $data['professor'] ?: 'TBA' }}</span>
                            </td>

                            <!-- Prelim -->
                            <td class="text-center grade-value">{{ $data['prelim']['grade'] !== null ? number_format($data['prelim']['grade'], 2) : '—' }}</td>
                            <td class="text-center">
                                @if($data['prelim']['equivalent'] ?? null)
                                    <span class="equiv-chip {{ $data['prelim']['equivalent'] <= 3 ? 'equiv-pass' : 'equiv-fail' }}">{{ $data['prelim']['equivalent'] }}</span>
                                @else
                                    <span class="equiv-chip equiv-empty">—</span>
                                @endif
                            </td>

                            <!-- Midterm -->
                            <td class="text-center grade-value">{{ $data['midterm']['grade'] !== null ? number_format($data['midterm']['grade'], 2) : '—' }}</td>
                            <td class="text-center">
                                @if($data['midterm']['equivalent'] ?? null)
                                    <span class="equiv-chip {{ $data['midterm']['equivalent'] <= 3 ? 'equiv-pass' : 'equiv-fail' }}">{{ $data['midterm']['equivalent'] }}</span>
                                @else
                                    <span class="equiv-chip equiv-empty">—</span>
                                @endif
                            </td>

                            <td class="text-center grade-value">{{ $data['finals']['grade'] !== null ? number_format($data['finals']['grade'], 2) : '—' }}</td>
                            <td class="text-center">
                                @if($data['finals']['equivalent'] ?? null)
                                    <span class="equiv-chip {{ $data['finals']['equivalent'] <= 3 ? 'equiv-pass' : 'equiv-fail' }}">{{ $data['finals']['equivalent'] }}</span>
                                @else
                                    <span class="equiv-chip equiv-empty">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="empty-state-cell">
                                <i class="fa-solid fa-folder-open"></i>
                                <p class="mb-0 fw-medium">No enrolled subjects or published grades found at this time.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .grade-report-container { max-width: 1300px; margin: 0 auto; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }

    .report-header-row { display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; flex-wrap: wrap; margin-bottom: 8px; padding-bottom: 16px; }
    .report-title { font-size: 24px; font-weight: 800; color: #1e2a5a; margin: 0 0 6px; }
    .report-subtitle { font-size: 13.5px; color: #64748b; margin: 0; }

    .ay-badge {
        background: #1e2a5a; color: white; font-size: 12.5px; font-weight: 700;
        padding: 8px 16px; border-radius: 30px; display: inline-flex; align-items: center; gap: 8px;
    }

    .report-card { background: white; border-radius: 14px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.03); margin-top: 8px; }
    .report-card-header {
        padding: 18px 24px; background: #f8fafc; border-bottom: 1px solid #e2e8f0;
        font-weight: 700; color: #1e2a5a; font-size: 14px; display: flex; align-items: center; gap: 10px;
    }

    .grade-table { width: 100%; border-collapse: collapse; }
    .grade-table th {
        font-size: 10.5px; font-weight: 800; letter-spacing: 0.5px; color: #475569;
        background-color: #f8fafc; border-bottom: 2px solid #e2e8f0; padding: 12px 10px; text-align: center;
    }
    .grade-table td { padding: 14px 10px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; font-size: 13.5px; }
    .grade-row:hover { background-color: #fafbfc; }

    .col-subject { text-align: left; padding-left: 18px !important; min-width: 280px; }
    .col-units { min-width: 70px; }
    .col-prof { min-width: 170px; }
    .col-final { min-width: 90px; }

    .subject-code { font-weight: 800; color: #0284c7; font-size: 13.5px; white-space: nowrap; }
    .subject-sep { color: #cbd5e1; margin: 0 6px; font-size: 12px; }
    .subject-title { color: #64748b; font-size: 12.5px; }

    .units-value { font-weight: 600; color: #334155; }

    .prof-cell { text-align: left; }
    .prof-name { font-size: 12.5px; color: #475569; font-weight: 600; }

    .term-header { padding: 9px 10px !important; font-size: 11px !important; }
    .term-header-prelim { background-color: #eef1fb !important; color: #1e2a5a !important; }
    .term-header-midterm { background-color: #fef9e7 !important; color: #92650a !important; }
    .term-header-finals { background-color: #f0fdf4 !important; color: #166534 !important; }

    .sub-col { font-size: 9.5px !important; background-color: #f8fafc !important; color: #94a3b8 !important; padding: 6px 8px !important; font-weight: 700 !important; }

    .grade-value { color: #64748b; font-weight: 500; }

    .equiv-chip {
        display: inline-block; min-width: 30px; padding: 3px 9px; border-radius: 6px;
        font-size: 12px; font-weight: 800;
    }
    .equiv-pass { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
    .equiv-fail { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
    .equiv-empty { color: #cbd5e1; font-weight: 600; }

    .final-equiv-badge {
        display: inline-block; padding: 5px 14px; border-radius: 20px; font-weight: 800; font-size: 13.5px;
    }
    .final-pass { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
    .final-fail { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }

    .empty-state-cell { text-align: center; padding: 60px 20px; color: #94a3b8; }
    .empty-state-cell i { font-size: 32px; color: #cbd5e1; display: block; margin-bottom: 12px; }
</style>
@endsection