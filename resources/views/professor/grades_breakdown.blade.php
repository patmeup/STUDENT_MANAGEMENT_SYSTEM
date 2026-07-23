@extends('layouts.app')

@section('content')
<div class="main-container">

    <div class="back-container">
        <a href="{{ route('professor.class_details', ['schedule_id' => $classDetails->schedule_id, 'tab' => request('term', 'prelim'), 'subtab' => 'encoding']) }}" class="back-link">
            <svg class="back-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Back to Grade Encoding
        </a>
    </div>

    <div class="content-card">
        <div class="student-header-info">
            <h1 class="student-name">{{ $student->last_name }}, {{ $student->first_name }} {{ $student->middle_name }}</h1>
            <p class="student-id-label">Student ID: <span class="student-id-val">{{ $student->student_id }}</span></p>
        </div>
        <div class="info-grid">
            <div class="info-box">
                <span class="info-title">Subject</span>
                <span class="info-desc">{{ $classDetails->subject_code }} - {{ $classDetails->subject_title }}</span>
            </div>
            <div class="info-box">
                <span class="info-title">Section</span>
                <span class="info-desc">{{ $classDetails->section_name }}</span>
            </div>
        </div>
    </div>

    <div class="content-card">
        @php 
            $activeTerm = $term ?? null;
            $termLabelMap = ['prelim' => 'Prelim', 'midterm' => 'Midterm', 'semifinal' => 'Semi-Final', 'final' => 'Final'];
        @endphp

        <div class="section-heading">
            <div class="heading-flex">
                <div class="icon-box">
                    <svg class="section-svg" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                </div>
                <h2>
                    @if($activeTerm)
                        {{ $termLabelMap[$activeTerm] ?? ucfirst($activeTerm) }} Term Grades Breakdown
                    @else
                        Grades Breakdown
                    @endif
                </h2>
            </div>
        </div>

        <div class="table-container">
            <table class="portal-table">
                <thead>
                    <tr>
                        <th>Term</th>
                        <th>Activity Name</th>
                        <th>Type</th>
                        <th class="text-center">Max Score</th>
                        <th class="text-center">Score Obtained</th>
                        <th>Feedback</th>
                    </tr>
                </thead>
                <tbody>
                    @php 
                        $hasTasks = false; 
                        $termsToShow = $activeTerm ? [$activeTerm] : ['prelim', 'midterm', 'semifinal', 'final'];
                    @endphp

                    @foreach($termsToShow as $t)
                        @if(isset($tasksWithGrades[$t]) && count($tasksWithGrades[$t]) > 0)
                            @php $hasTasks = true; @endphp
                            @foreach($tasksWithGrades[$t] as $task)
                                <tr>
                                    <td>
                                        <span class="term-tag term-{{ $t }}">{{ ucfirst($t) }}</span>
                                    </td>
                                    <td class="font-semibold text-gray-900">{{ $task->activity_name }}</td>
                                    <td>
                                        <span class="type-pill">{{ $task->activity_type }}</span>
                                    </td>
                                    <td class="text-center">{{ $task->max_score ?? 'N/A' }}</td>
                                    <td class="text-center">
                                        @if(is_null($task->grade))
                                            <span class="badge-ungraded">Ungraded</span>
                                        @else
                                            <span class="badge-graded">{{ $task->grade }}</span>
                                        @endif
                                    </td>
                                    <td class="text-gray-600">{{ $task->feedback ?? 'No feedback' }}</td>
                                </tr>
                            @endforeach
                        @endif
                    @endforeach

                    @if(!$hasTasks)
                        <tr>
                            <td colspan="6" class="empty-row">No tasks or activities recorded for this term yet.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <!-- Attendance History Section -->
    <div class="content-card">
        <div class="section-heading">
            <div class="heading-flex">
                <div class="icon-box">
                    <svg class="section-svg" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </div>
                <h2>Attendance History</h2>
            </div>
        </div>

        <div class="table-container">
            <table class="portal-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th class="text-center">Status</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attendances as $att)
                        <tr>
                            <td class="font-semibold text-gray-900">{{ \Carbon\Carbon::parse($att->attendance_date)->format('F d, Y') }}</td>
                            <td class="text-center">
                                <span class="status-pill 
                                    @if($att->status == 'Present') status-present 
                                    @elseif($att->status == 'Absent') status-absent 
                                    @else status-late @endif">
                                    {{ $att->status }}
                                </span>
                            </td>
                            <td class="text-gray-600">{{ $att->remarks ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="empty-row">No attendance records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .main-container {
        max-width: 80rem;
        margin: 0 auto;
        padding: 1.5rem 1rem;
    }

    .back-container {
        margin-bottom: 1.5rem;
    }

    .back-link {
        display: inline-flex;
        align-items: center;
        font-size: 0.875rem;
        font-weight: 600;
        color: #1d4ed8;
        text-decoration: none;
    }
    .back-link:hover {
        text-decoration: underline;
    }

    .back-icon {
        width: 1rem;
        height: 1rem;
        margin-right: 0.35rem;
    }

    .content-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
        padding: 1.5rem;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
        margin-bottom: 1.5rem;
    }

    .student-header-info {
        padding-bottom: 1rem;
        border-bottom: 1px solid #f3f4f6;
        margin-bottom: 1rem;
    }

    .student-name {
        font-size: 1.25rem;
        font-weight: 700;
        color: #111827;
    }

    .student-id-label {
        font-size: 0.875rem;
        color: #6b7280;
        margin-top: 0.25rem;
    }

    .student-id-val {
        background: #f3f4f6;
        padding: 0.1rem 0.4rem;
        border-radius: 0.25rem;
        color: #374151;
        font-weight: 500;
    }

    .info-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    @media (min-width: 768px) {
        .info-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    .info-box {
        background: #f9fafb;
        border: 1px solid #f3f4f6;
        padding: 0.875rem 1rem;
        border-radius: 0.5rem;
    }

    .info-title {
        display: block;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        color: #9ca3af;
        letter-spacing: 0.05em;
    }

    .info-desc {
        display: block;
        font-size: 0.95rem;
        font-weight: 600;
        color: #1f2937;
        margin-top: 0.2rem;
    }

    .section-heading {
        margin-bottom: 1.25rem;
    }

    .heading-flex {
        display: flex;
        align-items: center;
    }

    .icon-box {
        width: 2.5rem;
        height: 2.5rem;
        background: #eff6ff;
        border-radius: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 0.75rem;
    }

    .section-svg {
        width: 1.25rem;
        height: 1.25rem;
        color: #2563eb;
    }

    .section-heading h2 {
        font-size: 1.125rem;
        font-weight: 700;
        color: #111827;
        margin: 0;
    }

    .table-container {
        overflow-x: auto;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
    }

    .portal-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
        font-size: 0.875rem;
    }

    .portal-table th {
        background: #f9fafb;
        color: #6b7280;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.05em;
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #e5e7eb;
    }

    .portal-table td {
        padding: 0.875rem 1rem;
        border-bottom: 1px solid #e5e7eb;
        color: #374151;
        background: #ffffff;
    }

    .portal-table tbody tr:last-child td {
        border-bottom: none;
    }

    .portal-table tbody tr:hover td {
        background: #f9fafb;
    }

    .text-center { text-align: center; }

    .term-tag {
        font-weight: 700;
        font-size: 0.75rem;
        padding: 0.25rem 0.6rem;
        border-radius: 0.25rem;
        text-transform: uppercase;
        display: inline-block;
    }
    .term-prelim { background: #dbeafe; color: #1e40af; }
    .term-midterm { background: #fef3c7; color: #92400e; }
    .term-semifinal { background: #ede9fe; color: #5b21b6; }
    .term-final { background: #dcfce7; color: #166534; }

    .type-pill {
        background: #f3f4f6;
        color: #4b5563;
        padding: 0.2rem 0.5rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 500;
        text-transform: capitalize;
    }

    .badge-ungraded {
        background: #f3f4f6;
        color: #6b7280;
        padding: 0.2rem 0.5rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .badge-graded {
        background: #dcfce7;
        color: #166534;
        padding: 0.2rem 0.6rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 700;
    }

    .status-pill {
        padding: 0.2rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
        display: inline-block;
    }

    .status-present { background: #dcfce7; color: #166534; }
    .status-absent { background: #fee2e2; color: #991b1b; }
    .status-late { background: #fef3c7; color: #92400e; }

    .empty-row {
        text-align: center;
        color: #6b7280;
        padding: 2rem !important;
        background: #f9fafb !important;
    }
</style>
@endsection