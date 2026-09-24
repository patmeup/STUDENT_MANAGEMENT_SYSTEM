<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 24px 28px; }
        body { font-family: 'DejaVu Sans', sans-serif; color: #1e293b; font-size: 11.5px; }

        .print-letterhead-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .print-letterhead-table td { border: none; padding: 0; vertical-align: middle; }
        .print-letterhead-crest {
            width: 48px; height: 48px; border-radius: 50%;
            background: #1e2a5a; color: #facc15; font-weight: 800; font-size: 16px;
            text-align: center; vertical-align: middle;
        }
        .print-letterhead-bottom { border-bottom: 3px solid #1e2a5a; padding-bottom: 12px; }
        .school-name { margin: 0; font-size: 19px; font-weight: 800; color: #1e2a5a; letter-spacing: 0.3px; }
        .report-subtitle { margin: 2px 0 0 0; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.6px; }

        .meta-info-table { width: 100%; border-collapse: collapse; margin: 14px 0; font-size: 11.5px; background: #f8fafc; border: 1px solid #cbd5e1; }
        .meta-info-table td { padding: 8px 12px; border: 1px solid #e2e8f0; color: #1e293b; width: 50%; }
        .meta-label { display: block; font-size: 9px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px; }
        .print-date-highlight { background: #eef1fb; }
        .print-date-highlight .date-value { font-weight: 800; color: #1e2a5a; font-size: 13px; }

        .print-summary-bar { margin-bottom: 14px; }
        .print-summary-chip { font-size: 11px; font-weight: 700; padding: 5px 12px; border-radius: 20px; border: 1px solid; display: inline-block; margin-right: 8px; }
        .chip-present { background: #dcfce7; color: #15803d; border-color: #bbf7d0; }
        .chip-late { background: #fef3c7; color: #b45309; border-color: #fde68a; }
        .chip-absent { background: #fee2e2; color: #b91c1c; border-color: #fca5a5; }

        .print-data-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .print-data-table th { background-color: #1e2a5a; color: #ffffff; border: 1px solid #1e2a5a; padding: 8px 10px; font-size: 10.5px; text-transform: uppercase; letter-spacing: 0.3px; text-align: center; }
        .print-data-table td { border: 1px solid #cbd5e1; padding: 7px 10px; font-size: 11.5px; color: #0f172a; }
        .print-data-table tbody tr:nth-child(even) td { background-color: #f8fafc; }
        .text-center { text-align: center; }
        .text-uppercase { text-transform: uppercase; }
        .fw-bold { font-weight: 700; }

        .print-footer-table { width: 100%; border-collapse: collapse; margin-top: 26px; }
        .print-footer-table td { border: none; padding: 0; vertical-align: bottom; }
        .print-footer-count { font-size: 11px; font-weight: 700; color: #334155; text-align: left; }
        .print-signature-block { text-align: center; }
        .print-signature-line { border-top: 1px solid #334155; padding-top: 4px; font-size: 10px; color: #64748b; }
        .print-footer-timestamp { font-size: 9.5px; color: #94a3b8; font-style: italic; text-align: right; }
    </style>
</head>
<body>
    @php
        $presentCount = $existingAttendance->where('status', 'Present')->count();
        $lateCount = $existingAttendance->where('status', 'Late')->count();
        $absentCount = $existingAttendance->where('status', 'Absent')->count();
    @endphp

    <table class="print-letterhead-table">
        <tr class="print-letterhead-bottom">
            <td class="print-letterhead-crest">AU</td>
            <td style="width: 12px;"></td>
            <td>
                <h2 class="school-name">Arellano University</h2>
                <p class="report-subtitle">Official Class Attendance Report</p>
            </td>
        </tr>
    </table>

    <table class="meta-info-table">
        <tr>
            <td><span class="meta-label">Subject</span>{{ strtoupper($classDetails->subject_code) }} &mdash; {{ $classDetails->subject_title }}</td>
            <td><span class="meta-label">Section</span>{{ strtoupper($classDetails->section_name) }}</td>
        </tr>
        <tr>
            <td><span class="meta-label">Professor</span>{{ strtoupper(($classDetails->prof_last ?? '') . ', ' . ($classDetails->prof_first ?? '')) }}</td>
            <td><span class="meta-label">Room</span>{{ strtoupper($classDetails->room_assignment ?? '') }}</td>
        </tr>
        <tr>
            <td class="print-date-highlight">
                <span class="meta-label">Attendance Record Date</span>
                <span class="date-value">{{ $attendanceDate }}</span>
            </td>
            <td><span class="meta-label">Schedule</span>{{ $classDetails->class_days }}, {{ date('h:i A', strtotime($classDetails->start_time)) }} - {{ date('h:i A', strtotime($classDetails->end_time)) }}</td>
        </tr>
    </table>

    <div class="print-summary-bar">
        <span class="print-summary-chip chip-present">Present: {{ $presentCount }}</span>
        <span class="print-summary-chip chip-late">Late: {{ $lateCount }}</span>
        <span class="print-summary-chip chip-absent">Absent: {{ $absentCount }}</span>
    </div>

    <table class="print-data-table">
        <thead>
            <tr>
                <th style="width: 6%;">#</th>
                <th style="width: 18%;">Student ID</th>
                <th style="width: 38%;">Full Name</th>
                <th style="width: 18%;">Status</th>
                <th style="width: 20%;">Internal Remarks</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $index => $student)
                @php
                    $record = $existingAttendance->get($student->student_id);
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $student->student_id }}</td>
                    <td class="text-uppercase fw-bold">{{ $student->last_name }}, {{ $student->first_name }}</td>
                    <td class="text-center">{{ $record->status ?? 'N/A' }}</td>
                    <td class="text-center">{{ ($record->remarks ?? null) ? $record->remarks : '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center">No enrolled students for this schedule section.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="print-footer-table">
        <tr>
            <td class="print-footer-count">Total Students: {{ count($students) }}</td>
            <td class="print-signature-block"><div class="print-signature-line">Prepared by</div></td>
            <td class="print-footer-timestamp">Generated on {{ now()->format('F d, Y \a\t h:i A') }}</td>
        </tr>
    </table>
</body>
</html>