@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="rg-page">

    <header class="rg-header">
        <div class="rg-header-left">
            <div style="margin-bottom: 12px;">
                <a href="{{ route('department_secretary.students') }}" class="rg-btn rg-btn-ghost">
                    <i class="fas fa-arrow-left"></i> Back to masterlist
                </a>
            </div>
            <h1 class="rg-title">Block roster</h1>
            <p class="rg-subtitle">
                <span class="rg-block-name">{{ strtoupper($section_name) }}</span>
                <span class="rg-subtitle-note">Students enrolled in this block for the active period.</span>
            </p>
        </div>

        <div class="rg-header-end">
            @if(!empty($activePeriod))
                <div class="rg-period" title="Roster is for this period">
                    <span class="rg-period-label">Active period</span>
                    <span class="rg-period-value">{{ $activePeriod->school_year }}</span>
                    <span class="rg-period-sem">{{ $activePeriod->semester }}</span>
                </div>
            @endif
        </div>
    </header>

    <div class="rg-card">
        <div class="rg-toolbar">
            <strong class="rg-card-title">Class list</strong>
            <span class="rg-result-count">
                {{ count($students) }} {{ Str::plural('student', count($students)) }}
                @if(($totalBlockSubjects ?? 0) > 0)
                    · {{ $totalBlockSubjects }} {{ Str::plural('subject', $totalBlockSubjects) }} in this block
                @endif
            </span>
        </div>

        <div class="rg-table-wrap">
            <table class="rg-table">
                <thead>
                    <tr>
                        <th style="width: 56px; text-align: center;">#</th>
                        <th>Student</th>
                        <th>Course</th>
                        <th>Year level</th>
                        <th>Subjects in this block</th>
                        <th class="rg-th-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $index => $student)
                        @php
                            $initials = strtoupper(mb_substr($student->first_name, 0, 1) . mb_substr($student->last_name, 0, 1));
                            $taking   = $student->block_subjects ?? 0;
                            $total    = $totalBlockSubjects ?? 0;
                            $partial  = $total > 0 && $taking < $total;
                        @endphp
                        <tr>
                            <td style="text-align: center; color: #8A97B3; font-weight: 600;">{{ $index + 1 }}</td>
                            <td>
                                <div class="rg-person">
                                    <div>
                                        <div class="rg-person-name"><strong>{{ strtoupper($student->last_name) }}</strong>, {{ $student->first_name }}</div>
                                        <div class="rg-person-id">{{ $student->student_id }}</div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="rg-chip rg-chip-plain">{{ $student->course }}</span></td>
                            <td><span class="rg-chip rg-chip-plain">{{ $student->year_level }}</span></td>
                            <td>
                                @if($total > 0)
                                    <span class="rg-count {{ $partial ? 'rg-count-partial' : 'rg-count-on' }}"
                                          title="{{ $partial ? 'Takes only part of this block. Possibly an irregular student.' : 'Takes every subject of this block.' }}">
                                        {{ $taking }} of {{ $total }}
                                    </span>
                                @else
                                    <span class="rg-unassigned">No schedules</span>
                                @endif
                            </td>
                            <td>
                                <div class="rg-actions">
                                    <a href="{{ route('department_secretary.plot_subjects', $student->student_id) }}" class="rg-btn rg-btn-primary">
                                        <i class="fas fa-th-list"></i> Edit schedule
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="rg-empty">
                                    <i class="fas fa-folder-open"></i>
                                    <strong>Nobody is enrolled in this block yet</strong>
                                    <span>Use Bulk enroll on the masterlist, or plot subjects for a student.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .rg-page {
        --rg-blue: #0033A0; --rg-blue-dark: #0A1F5C; --rg-blue-tint: #EAF0FF; --rg-gold: #F2B705;
        --rg-ink: #0B1B3A; --rg-muted: #5C6B8A; --rg-line: #E2E8F3; --rg-surface: #F7F9FD;
        --rg-green: #15803D; --rg-green-tint: #E8F7EE; --rg-red: #C62828;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: var(--rg-ink);
        max-width: 1200px; margin: 0 auto; padding: 24px 20px 40px;
    }

    .rg-header { display: flex; justify-content: space-between; align-items: flex-end; gap: 20px; flex-wrap: wrap; padding-bottom: 18px; margin-bottom: 20px; border-bottom: 1px solid var(--rg-line); }
    .rg-header-end { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
    .rg-title { margin: 0; font-size: 26px; font-weight: 800; letter-spacing: -0.2px; color: var(--rg-blue-dark); }
    .rg-subtitle { margin: 8px 0 0; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; font-size: 14px; color: var(--rg-muted); }

    .rg-block-name { display: inline-block; background: var(--rg-blue); color: #fff; font-size: 15px; font-weight: 800; letter-spacing: 0.3px; padding: 6px 14px; border-radius: 6px; }

    .rg-period { display: grid; grid-template-columns: auto auto; column-gap: 10px; align-items: baseline; background: #fff; border: 1px solid var(--rg-line); border-left: 4px solid var(--rg-gold); border-radius: 8px; padding: 9px 16px 9px 14px; }
    .rg-period-label { grid-column: 1 / -1; font-size: 11.5px; font-weight: 600; color: var(--rg-muted); margin-bottom: 2px; }
    .rg-period-value { font-size: 15px; font-weight: 800; color: var(--rg-blue-dark); }
    .rg-period-sem { font-size: 13px; font-weight: 600; color: var(--rg-blue); }

    .rg-card { background: #fff; border: 1px solid var(--rg-line); border-radius: 10px; overflow: hidden; box-shadow: 0 1px 2px rgba(10, 31, 92, 0.05); }
    .rg-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 14px 18px; padding: 14px 20px; border-bottom: 1px solid var(--rg-line); }
    .rg-card-title { font-size: 15px; font-weight: 700; color: var(--rg-blue-dark); }
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
    .rg-unassigned { font-size: 12.5px; color: #8A97B3; font-style: italic; }

    .rg-count { display: inline-flex; align-items: center; padding: 4px 12px; border-radius: 16px; font-size: 12.5px; font-weight: 700; }
    .rg-count-on { background: var(--rg-green-tint); color: var(--rg-green); border: 1px solid #BFE8CE; }
    .rg-count-partial { background: #FEF3C7; color: #92400E; border: 1px solid #F6D98A; }

    .rg-actions { display: flex; justify-content: flex-end; align-items: center; gap: 8px; }
    .rg-btn { display: inline-flex; align-items: center; gap: 7px; padding: 7px 14px; border-radius: 7px; font-size: 12.5px; font-weight: 700; text-decoration: none; cursor: pointer; border: 1px solid transparent; transition: background .15s, color .15s, border-color .15s; white-space: nowrap; line-height: 1.2; }
    .rg-btn-primary { background: var(--rg-blue); color: #fff; }
    .rg-btn-primary:hover { background: var(--rg-blue-dark); color: #fff; }
    .rg-btn-ghost { background: #fff; color: var(--rg-blue); border-color: #B9C8F0; }
    .rg-btn-ghost:hover { background: var(--rg-blue-tint); color: var(--rg-blue-dark); }
    .rg-btn:focus-visible { outline: 2px solid var(--rg-blue); outline-offset: 2px; }

    .rg-empty { display: flex; flex-direction: column; align-items: center; gap: 6px; padding: 44px 16px; text-align: center; color: var(--rg-muted); font-size: 13.5px; }
    .rg-empty i { font-size: 28px; color: #B9C4DD; margin-bottom: 4px; }
    .rg-empty strong { color: var(--rg-ink); font-size: 15px; }

    @media (max-width: 720px) {
        .rg-header { align-items: flex-start; }
        .rg-result-count { margin-left: 0; }
        .rg-table th, .rg-table td { padding-left: 14px; padding-right: 14px; }
    }
    @media (prefers-reduced-motion: reduce) { .rg-page * { transition: none !important; } }
</style>
@endsection