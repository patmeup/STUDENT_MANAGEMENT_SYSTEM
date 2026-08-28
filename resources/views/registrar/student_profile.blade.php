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
            <a href="{{ route('registrar.manage-students') }}" style="display: inline-block; background-color: transparent; color: #0033A0; border: 1px solid #0033A0; padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; text-decoration: none;">
                <i class="fas fa-arrow-left"></i> Back to Student List
            </a>
        </div>
    </div>

    @if(session('Success'))
        <div style="background-color: #d1fae5; color: #065f46; padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; font-weight: 600;">
            <i class="fas fa-check-circle"></i> {{ session('Success') }}
        </div>
    @endif

    @if ($errors->any())
        <div style="background-color: #fee2e2; color: #991b1b; padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; font-size: 14px;">
            <ul style="margin: 0; padding-left: 20px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Student Information Form -->
    <div style="background: white; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); overflow: hidden; margin-bottom: 30px;">
        <div style="background-color: #0033A0; padding: 18px 24px; font-size: 16px; font-weight: 700; color: white;">
            <span><i class="fas fa-user-graduate"></i> Student Record — {{ $student->student_id }}</span>
        </div>

        <form action="{{ route('registrar.update-info', $student->id) }}" method="POST" style="padding: 24px;">
            @csrf
            @method('PUT')

            <p style="font-size: 14px; color: #64748b; margin-top: 0; margin-bottom: 20px;">
                Review and update academic profile and contact details below.
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
                    <td style="padding: 14px 12px; border-bottom: 1px solid #f1f5f9;">
                        <select name="course" style="width: 100%; max-width: 400px; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; background-color: #fff; color: #0f172a;">
                            <option value="BSIT" {{ $student->course == 'BSIT' ? 'selected' : '' }}>BSIT</option>
                            <option value="BSCS" {{ $student->course == 'BSCS' ? 'selected' : '' }}>BSCS</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 14px 12px; font-weight: 700; color: #475569; vertical-align: middle; border-bottom: 1px solid #f1f5f9;">Section:</td>
                    <td style="padding: 14px 12px; border-bottom: 1px solid #f1f5f9;">
                        <input type="text" name="section" value="{{ $student->section }}" placeholder="e.g. BSIT-1A" style="width: 100%; max-width: 400px; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; box-sizing: border-box;">
                    </td>
                </tr>
                <tr>
                    <td style="padding: 14px 12px; font-weight: 700; color: #475569; vertical-align: middle; border-bottom: 1px solid #f1f5f9;">Year Level & Semester:</td>
                    <td style="padding: 14px 12px; border-bottom: 1px solid #f1f5f9;">
                        <div style="display: flex; gap: 12px; max-width: 400px;">
                            <select name="year_level" style="flex: 1; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; background-color: #fff;">
                                <option value="1st Year" {{ $student->year_level == '1st Year' ? 'selected' : '' }}>1st Year</option>
                                <option value="2nd Year" {{ $student->year_level == '2nd Year' ? 'selected' : '' }}>2nd Year</option>
                                <option value="3rd Year" {{ $student->year_level == '3rd Year' ? 'selected' : '' }}>3rd Year</option>
                                <option value="4th Year" {{ $student->year_level == '4th Year' ? 'selected' : '' }}>4th Year</option>
                            </select>
                            <select name="semester" style="flex: 1; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; background-color: #fff;">
                                <option value="1st Semester" {{ $student->semester == '1st Semester' ? 'selected' : '' }}>1st Semester</option>
                                <option value="2nd Semester" {{ $student->semester == '2nd Semester' ? 'selected' : '' }}>2nd Semester</option>
                            </select>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 14px 12px; font-weight: 700; color: #475569; vertical-align: middle; border-bottom: 1px solid #f1f5f9;">Academic Standing Status:</td>
                    <td style="padding: 14px 12px; border-bottom: 1px solid #f1f5f9;">
                        <select name="status" style="width: 100%; max-width: 400px; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; font-weight: 600; background-color: #fff; color: #0f172a;">
                            <option value="Enrolled" {{ ($student->status ?? 'Enrolled') == 'Enrolled' ? 'selected' : '' }}>Enrolled</option>
                            <option value="On-Hold" {{ ($student->status ?? '') == 'On-Hold' ? 'selected' : '' }}>On-Hold</option>
                            <option value="Purged" {{ ($student->status ?? '') == 'Purged' ? 'selected' : '' }}>Purged</option>
                            <option value="Dropped" {{ ($student->status ?? '') == 'Dropped' ? 'selected' : '' }}>Dropped</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 14px 12px; font-weight: 700; color: #475569; vertical-align: middle; border-bottom: 1px solid #f1f5f9;">Contact Number:</td>
                    <td style="padding: 14px 12px; border-bottom: 1px solid #f1f5f9;">
                        <input type="text" name="contact_no" value="{{ $student->contact_no }}" placeholder="09xxxxxxxxx" style="width: 100%; max-width: 400px; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; box-sizing: border-box;">
                    </td>
                </tr>
                <tr>
                    <td style="padding: 14px 12px; font-weight: 700; color: #475569; vertical-align: middle; border-bottom: 1px solid #f1f5f9;">Email Address:</td>
                    <td style="padding: 14px 12px; border-bottom: 1px solid #f1f5f9;">
                        <input type="email" name="email" value="{{ $student->email }}" placeholder="student@example.com" style="width: 100%; max-width: 400px; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; box-sizing: border-box;">
                    </td>
                </tr>
                <tr>
                    <td style="padding: 14px 12px; font-weight: 700; color: #475569; vertical-align: middle; border-bottom: 1px solid #f1f5f9;">Home Address:</td>
                    <td style="padding: 14px 12px; border-bottom: 1px solid #f1f5f9;">
                        <input type="text" name="city_address" value="{{ $student->city_address }}" placeholder="Complete Address" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; box-sizing: border-box;">
                    </td>
                </tr>
            </table>

            <div style="text-align: right; margin-top: 20px;">
                <button type="submit" style="background-color: #0033A0; color: white; border: none; padding: 10px 22px; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer;">
                    <i class="fas fa-save"></i> Save Profile Details
                </button>
            </div>
        </form>
    </div>

    <!-- Student Documents Management Section -->
    <div style="background: white; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); overflow: hidden;">
        <div style="background-color: #1e293b; padding: 18px 24px; font-size: 16px; font-weight: 700; color: white;">
            <span><i class="fas fa-folder-open"></i> Required Documents for ({{ $student->student_type }})</span>
        </div>

        <form action="{{ route('registrar.update-documents', $student->id) }}" method="POST" enctype="multipart/form-data" style="padding: 24px;">
            @csrf
            @method('PUT')

            <p style="font-size: 14px; color: #64748b; margin-top: 0; margin-bottom: 20px;">
                You can review uploaded files or replace them with new scanned documents below.
            </p>

            @php
                $docs = [];
                if ($student->student_type === 'New') {
                    $docs = [
                        ['label' => 'PSA Birth Certificate', 'col' => 'path_psa', 'input' => 'doc_psa'],
                        ['label' => '2x2 ID Picture', 'col' => 'path_2x2', 'input' => 'doc_2x2'],
                        ['label' => 'Form 138 (Report Card)', 'col' => 'path_f138', 'input' => 'doc_f138'],
                        ['label' => 'Good Moral Certificate', 'col' => 'path_good_moral_new', 'input' => 'doc_good_moral_new'],
                        ['label' => 'High School Diploma', 'col' => 'path_diploma', 'input' => 'doc_diploma'],
                    ];
                } elseif ($student->student_type === 'Transferee') {
                    $docs = [
                        ['label' => 'PSA Birth Certificate', 'col' => 'path_psa', 'input' => 'doc_psa'],
                        ['label' => '2x2 ID Picture', 'col' => 'path_2x2', 'input' => 'doc_2x2'],
                        ['label' => 'Transcript of Records (TOR)', 'col' => 'path_tor', 'input' => 'doc_tor'],
                        ['label' => 'Honorable Dismissal', 'col' => 'path_dismissal', 'input' => 'doc_dismissal'],
                        ['label' => 'Good Moral Certificate', 'col' => 'path_good_moral_trans', 'input' => 'doc_good_moral_trans'],
                    ];
                } elseif ($student->student_type === 'Cross-Enrollee') {
                    $docs = [
                        ['label' => 'PSA Birth Certificate', 'col' => 'path_psa', 'input' => 'doc_psa'],
                        ['label' => '2x2 ID Picture', 'col' => 'path_2x2', 'input' => 'doc_2x2'],
                        ['label' => 'Cross-Enrollment Permit', 'col' => 'path_permit', 'input' => 'doc_permit'],
                    ];
                }
            @endphp

            <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                <thead>
                    <tr style="background-color: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                        <th style="padding: 10px; text-align: left; font-size: 13px; color: #475569;">Document Type</th>
                        <th style="padding: 10px; text-align: left; font-size: 13px; color: #475569;">Current Status</th>
                        <th style="padding: 10px; text-align: left; font-size: 13px; color: #475569;">Upload / Replace File</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($docs as $doc)
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 12px; font-size: 14px; font-weight: 600; color: #0f172a; width: 30%;">
                                {{ $doc['label'] }}
                            </td>
                            <td style="padding: 12px; font-size: 13px; width: 30%;">
                                @if($student->{$doc['col']})
                                    <span style="color: #059669; font-weight: 600; display: inline-block; margin-bottom: 4px;">● Uploaded</span><br>
                                    <a href="{{ route('registrar.secure-view', ['student_id' => $student->student_id, 'filename' => $student->{$doc['col']}]) }}" target="_blank" style="color: #0033A0; text-decoration: underline; font-size: 12px;">
                                        View File
                                    </a>
                                @else
                                    <span style="color: #dc2626; font-weight: 600;">○ No File</span>
                                @endif
                            </td>
                            <td style="padding: 12px; width: 40%;">
                                <input type="file" name="{{ $doc['input'] }}" accept=".pdf,.jpg,.jpeg,.png" style="font-size: 12px; width: 100%;">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div style="text-align: right;">
                <button type="submit" style="background-color: #0033A0; color: white; border: none; padding: 10px 22px; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer;">
                    <i class="fas fa-save"></i> Save Document Changes
                </button>
            </div>
        </form>
    </div>
</div>
@endsection