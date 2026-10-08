@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="rg-page" style="max-width: 1300px;">

    <header class="rg-header">
        <div class="rg-header-left">
            <div style="margin-bottom: 12px;">
                <a href="{{ route('department_secretary.students') }}" class="rg-btn rg-btn-ghost">
                    <i class="fas fa-arrow-left"></i> Back to Student List
                </a>
            </div>
            <h1 class="rg-title">Student Profile Information</h1>
            <p class="rg-subtitle">View and update student records and document requirements.</p>
        </div>
    </header>

    <div class="rg-profile-grid">
        <div class="rg-card">
            <div class="rg-card-header-dark">
                <i class="fas fa-user-graduate"></i> Student Record — {{ $student->student_id }}
            </div>

            <div class="rg-card-body">
                <div class="rg-table-wrap">
                    <table class="rg-table rg-table-profile">
                        <tbody>
                            <tr>
                                <th>Full Name:</th>
                                <td><strong style="text-transform: uppercase;">{{ $student->last_name }}, {{ $student->first_name }} {{ $student->middle_name }}</strong></td>
                            </tr>
                            <tr>
                                <th>Course / Program:</th>
                                <td><span class="rg-chip rg-chip-plain">{{ $student->course }}</span></td>
                            </tr>
                            <tr>
                                <th>Section:</th>
                                <td>
                                    @if($student->section)
                                        <span class="rg-chip rg-chip-blue">{{ $student->section }}</span>
                                    @else
                                        <span class="rg-unassigned">None</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Year Level & Semester:</th>
                                <td>{{ $student->year_level }} — {{ $student->semester }}</td>
                            </tr>
                            <tr>
                                <th>Academic Standing Status:</th>
                                <td>{{ $student->status ?? 'Enrolled' }}</td>
                            </tr>
                            <tr>
                                <th>Contact Number:</th>
                                <td>{{ $student->contact_no }}</td>
                            </tr>
                            <tr>
                                <th>Email Address:</th>
                                <td>{{ $student->email }}</td>
                            </tr>
                            <tr>
                                <th>Home Address:</th>
                                <td>{{ $student->city_address }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="rg-card">
            <div class="rg-card-header-gray">
                <i class="fas fa-folder-open"></i> Required Documents for ({{ $student->student_type }})
            </div> 

            <div class="rg-card-body">
                @php
                    $docs = [];
                    if ($student->student_type === 'New') {
                        $docs = [
                            ['label' => 'PSA Birth Certificate', 'col' => 'path_psa'],
                            ['label' => '2x2 ID Picture', 'col' => 'path_2x2'],
                            ['label' => 'Form 138 (Report Card)', 'col' => 'path_f138'],
                            ['label' => 'Good Moral Certificate', 'col' => 'path_good_moral_new'],
                            ['label' => 'High School Diploma', 'col' => 'path_diploma'],
                        ];
                    } elseif ($student->student_type === 'Transferee') {
                        $docs = [
                            ['label' => 'PSA Birth Certificate', 'col' => 'path_psa'],
                            ['label' => '2x2 ID Picture', 'col' => 'path_2x2'],
                            ['label' => 'Transcript of Records (TOR)', 'col' => 'path_tor'],
                            ['label' => 'Honorable Dismissal', 'col' => 'path_dismissal'],
                            ['label' => 'Good Moral Certificate', 'col' => 'path_good_moral_trans'],
                        ];
                    } elseif ($student->student_type === 'Cross-Enrollee') {
                        $docs = [
                            ['label' => 'PSA Birth Certificate', 'col' => 'path_psa'],
                            ['label' => '2x2 ID Picture', 'col' => 'path_2x2'],
                            ['label' => 'Cross-Enrollment Permit', 'col' => 'path_permit'],
                        ];
                    }
                @endphp

                <div class="rg-table-wrap">
                    <table class="rg-table">
                        <thead>
                            <tr>
                                <th style="width: 50%;">Document Type</th>
                                <th style="width: 50%;">Current Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($docs as $doc)
                                <tr>
                                    <td><strong>{{ $doc['label'] }}</strong></td>
                                    <td>
                                        @if($student->{$doc['col']})
                                            <div class="rg-doc-status">
                                                <span class="rg-status-uploaded"><i class="fas fa-check-circle"></i> Uploaded</span>
                                                <a href="{{ route('department_secretary.secure-view', ['student_id' => $student->student_id, 'filename' => $student->{$doc['col']}]) }}" target="_blank" class="rg-link">View File</a>
                                            </div>
                                        @else
                                            <span class="rg-status-missing"><i class="fas fa-times-circle"></i> No File</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
    .rg-page {
        --rg-blue: #0033A0; --rg-blue-dark: #0A1F5C; --rg-blue-tint: #EAF0FF; --rg-gold: #F2B705;
        --rg-ink: #0B1B3A; --rg-muted: #5C6B8A; --rg-line: #E2E8F3; --rg-surface: #F7F9FD;
        --rg-green: #15803D; --rg-green-tint: #E8F7EE; --rg-red: #C62828;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: var(--rg-ink);
        margin: 0 auto; padding: 24px 20px 40px;
    }

    .rg-header { display: flex; justify-content: space-between; align-items: flex-end; gap: 20px; flex-wrap: wrap; padding-bottom: 18px; margin-bottom: 24px; border-bottom: 1px solid var(--rg-line); }
    .rg-title { margin: 0; font-size: 26px; font-weight: 800; letter-spacing: -0.2px; color: var(--rg-blue-dark); }
    .rg-subtitle { margin: 8px 0 0; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; font-size: 14px; color: var(--rg-muted); }

    .rg-btn { display: inline-flex; align-items: center; gap: 7px; padding: 7px 14px; border-radius: 7px; font-size: 12.5px; font-weight: 700; text-decoration: none; cursor: pointer; border: 1px solid transparent; transition: background .15s, color .15s, border-color .15s; white-space: nowrap; line-height: 1.2; }
    .rg-btn-ghost { background: #fff; color: var(--rg-blue); border-color: #B9C8F0; }
    .rg-btn-ghost:hover { background: var(--rg-blue-tint); color: var(--rg-blue-dark); }
    .rg-btn:focus-visible { outline: 2px solid var(--rg-blue); outline-offset: 2px; }

    .rg-profile-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 24px;
        align-items: start;
    }

    .rg-card { background: #fff; border: 1px solid var(--rg-line); border-radius: 10px; overflow: hidden; box-shadow: 0 1px 2px rgba(10, 31, 92, 0.05); }
    .rg-card-header-dark { background: var(--rg-blue-dark); color: #fff; padding: 16px 20px; font-size: 15px; font-weight: 700; display: flex; align-items: center; gap: 10px; }
    .rg-card-header-gray { background: #F2B705; color: #fff; padding: 16px 20px; font-size: 15px; font-weight: 700; display: flex; align-items: center; gap: 10px; }
    .rg-card-body { padding: 24px; }

    .rg-table-wrap { overflow-x: auto; border: 1px solid var(--rg-line); border-radius: 8px; }
    .rg-table { width: 100%; border-collapse: collapse; font-size: 14px; }
    .rg-table th { background: var(--rg-surface); padding: 12px 20px; font-size: 13px; font-weight: 700; color: var(--rg-muted); text-align: left; border-bottom: 1px solid var(--rg-line); white-space: nowrap; }
    .rg-table td { padding: 14px 20px; border-bottom: 1px solid #EEF2F9; color: #26345A; vertical-align: middle; font-weight: 500; }
    .rg-table tbody tr:last-child td { border-bottom: none; }
    .rg-table tbody tr:hover { background: #F5F8FF; }

    .rg-table-profile th { background: transparent; width: 35%; }
    .rg-table-profile td { font-weight: 600; }
    .rg-table-profile tbody tr:hover { background: transparent; }

    .rg-chip { display: inline-block; padding: 3px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; white-space: nowrap; }
    .rg-chip-plain { background: var(--rg-surface); color: #3B4A6B; border: 1px solid var(--rg-line); }
    .rg-chip-blue { background: var(--rg-blue-tint); color: var(--rg-blue); border: 1px solid #CFDBFB; }
    .rg-unassigned { font-size: 12.5px; color: #8A97B3; font-style: italic; }

    .rg-doc-status { display: flex; flex-direction: column; align-items: flex-start; gap: 4px; }
    .rg-status-uploaded { color: var(--rg-green); font-weight: 700; font-size: 13px; display: inline-flex; align-items: center; gap: 5px; }
    .rg-status-missing { color: var(--rg-red); font-weight: 700; font-size: 13px; display: inline-flex; align-items: center; gap: 5px; }
    .rg-link { color: var(--rg-blue); text-decoration: underline; font-size: 12.5px; font-weight: 600; transition: color .15s; }
    .rg-link:hover { color: var(--rg-blue-dark); }

    @media (max-width: 992px) {
        .rg-profile-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endsection