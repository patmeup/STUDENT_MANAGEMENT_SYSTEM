@extends('layouts.app')

@section('content')
<div class="masterlist-container" style="max-width: 1000px; margin: 0 auto; padding-bottom: 50px; font-family: 'Segoe UI', sans-serif;">
    
    <!-- Header -->
    <div class="masterlist-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 2px solid #e2e8f0; padding-bottom: 15px;">
        <div class="header-titles">
            <h2 style="margin: 0 0 5px 0; font-size: 26px; font-weight: 700; color: #0f172a;">Student Profile Information</h2>
            <p style="margin: 0; font-size: 14px; color: #64748b;">View and update student records and document requirements.</p>
        </div>
        <div>
            <a href="{{ route('department_secretary.students') }}" style="display: inline-block; background-color: transparent; color: #0f172a; border: 1px solid #0f172a; padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; text-decoration: none;">
                <i class="fas fa-arrow-left"></i> Back to Student List
            </a>
        </div>
    </div>

    <!-- Student Information (Read-Only) -->
    <div style="background: white; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); overflow: hidden; margin-bottom: 30px;">
        <div style="background-color: #0f172a; padding: 18px 24px; font-size: 16px; font-weight: 700; color: white;">
            <span><i class="fas fa-user-graduate"></i> Student Record — {{ $student->student_id }}</span>
        </div>

        <div style="padding: 24px;">
            <p style="font-size: 14px; color: #64748b; margin-top: 0; margin-bottom: 20px;">
                Review academic profile and contact details below (Read-Only Mode).
            </p>

            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 14px 12px; font-weight: 700; color: #475569; width: 28%; vertical-align: middle; border-bottom: 1px solid #f1f5f9;">Full Name:</td>
                    <td style="padding: 14px 12px; color: #0f172a; font-weight: 600; text-transform: uppercase; border-bottom: 1px solid #f1f5f9;">
                        {{ $student->last_name }}, {{ $student->first_name }} {{ $student->middle_name }}
                    </td>
                </tr>
                <tr>
                    <td style="padding: 14px 12px; font-weight: 700; color: #475569; vertical-align: middle; border-bottom: 1px solid #f1f5f9;">Course / Program:</td>
                    <td style="padding: 14px 12px; color: #0f172a; font-weight: 600; border-bottom: 1px solid #f1f5f9;">
                        {{ $student->course }}
                    </td>
                </tr>
                <tr>
                    <td style="padding: 14px 12px; font-weight: 700; color: #475569; vertical-align: middle; border-bottom: 1px solid #f1f5f9;">Section:</td>
                    <td style="padding: 14px 12px; color: #0f172a; font-weight: 600; border-bottom: 1px solid #f1f5f9;">
                        {{ $student->section ?? 'None' }}
                    </td>
                </tr>
                <tr>
                    <td style="padding: 14px 12px; font-weight: 700; color: #475569; vertical-align: middle; border-bottom: 1px solid #f1f5f9;">Year Level & Semester:</td>
                    <td style="padding: 14px 12px; color: #0f172a; font-weight: 600; border-bottom: 1px solid #f1f5f9;">
                        {{ $student->year_level }} — {{ $student->semester }}
                    </td>
                </tr>
                <tr>
                    <td style="padding: 14px 12px; font-weight: 700; color: #475569; vertical-align: middle; border-bottom: 1px solid #f1f5f9;">Academic Standing Status:</td>
                    <td style="padding: 14px 12px; color: #0f172a; font-weight: 600; border-bottom: 1px solid #f1f5f9;">
                        {{ $student->status ?? 'Enrolled' }}
                    </td>
                </tr>
                <tr>
                    <td style="padding: 14px 12px; font-weight: 700; color: #475569; vertical-align: middle; border-bottom: 1px solid #f1f5f9;">Contact Number:</td>
                    <td style="padding: 14px 12px; color: #0f172a; font-weight: 600; border-bottom: 1px solid #f1f5f9;">
                        {{ $student->contact_no }}
                    </td>
                </tr>
                <tr>
                    <td style="padding: 14px 12px; font-weight: 700; color: #475569; vertical-align: middle; border-bottom: 1px solid #f1f5f9;">Email Address:</td>
                    <td style="padding: 14px 12px; color: #0f172a; font-weight: 600; border-bottom: 1px solid #f1f5f9;">
                        {{ $student->email }}
                    </td>
                </tr>
                <tr>
                    <td style="padding: 14px 12px; font-weight: 700; color: #475569; vertical-align: middle; border-bottom: 1px solid #f1f5f9;">Home Address:</td>
                    <td style="padding: 14px 12px; color: #0f172a; font-weight: 600; border-bottom: 1px solid #f1f5f9;">
                        {{ $student->city_address }}
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Student Documents Section (Read-Only) -->
    <div style="background: white; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); overflow: hidden;">
        <div style="background-color: #334155; padding: 18px 24px; font-size: 16px; font-weight: 700; color: white;">
            <span><i class="fas fa-folder-open"></i> Required Documents for ({{ $student->student_type }})</span>
        </div>

        <div style="padding: 24px;">
            <p style="font-size: 14px; color: #64748b; margin-top: 0; margin-bottom: 20px;">
                View uploaded files and document statuses (Read-Only).
            </p>

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

            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background-color: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                        <th style="padding: 10px; text-align: left; font-size: 13px; color: #475569;">Document Type</th>
                        <th style="padding: 10px; text-align: left; font-size: 13px; color: #475569;">Current Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($docs as $doc)
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 12px; font-size: 14px; font-weight: 600; color: #0f172a; width: 40%;">
                                {{ $doc['label'] }}
                            </td>
                            <td style="padding: 12px; font-size: 13px; width: 60%;">
                                @if($student->{$doc['col']})
                                    <span style="color: #059669; font-weight: 600; display: inline-block; margin-bottom: 4px;">● Uploaded</span><br>
                                    <a href="{{ route('department_secretary.secure-view', ['student_id' => $student->student_id, 'filename' => $student->{$doc['col']}]) }}" target="_blank" style="color: #2563eb; text-decoration: underline; font-size: 12px;">
                                        View File
                                    </a>
                                @else
                                    <span style="color: #dc2626; font-weight: 600;">○ No File</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection