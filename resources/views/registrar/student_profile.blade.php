@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

@php
    $typeKey  = strtolower(trim((string) $student->student_type));
    $currentStatus = $student->status ?? 'Enrolled';

    $docCatalog = [
        'psa'              => ['label' => 'PSA Birth Certificate',      'col' => 'path_psa',              'input' => 'doc_psa'],
        '2x2'              => ['label' => '2x2 ID Picture',             'col' => 'path_2x2',              'input' => 'doc_2x2'],
        'f138'             => ['label' => 'Form 138 (Report Card)',     'col' => 'path_f138',             'input' => 'doc_f138'],
        'good_moral_new'   => ['label' => 'Good Moral Certificate',     'col' => 'path_good_moral_new',   'input' => 'doc_good_moral_new'],
        'diploma'          => ['label' => 'High School Diploma',        'col' => 'path_diploma',          'input' => 'doc_diploma'],
        'tor'              => ['label' => 'Transcript of Records (TOR)','col' => 'path_tor',              'input' => 'doc_tor'],
        'dismissal'        => ['label' => 'Honorable Dismissal',        'col' => 'path_dismissal',        'input' => 'doc_dismissal'],
        'good_moral_trans' => ['label' => 'Good Moral Certificate',     'col' => 'path_good_moral_trans', 'input' => 'doc_good_moral_trans'],
        'permit'           => ['label' => 'Cross-Enrollment Permit',    'col' => 'path_permit',           'input' => 'doc_permit'],
        'curriculum'       => ['label' => 'Old Curriculum',             'col' => 'path_curriculum',       'input' => 'doc_curriculum'],
    ];

    $requiredByType = [
        'new'            => ['psa', '2x2', 'f138', 'good_moral_new', 'diploma'],
        'transferee'     => ['psa', '2x2', 'tor', 'dismissal', 'good_moral_trans'],
        'cross-enrollee' => ['psa', '2x2', 'permit'],
        'old'            => [],
    ];

    $requiredKeys = $requiredByType[$typeKey] ?? [];
    $shownKeys    = $requiredKeys;

    foreach ($docCatalog as $key => $doc) {
        if (!in_array($key, $shownKeys, true) && !empty($student->{$doc['col']})) {
            $shownKeys[] = $key;
        }
    }

    $docs = [];
    foreach ($shownKeys as $key) {
        $docs[] = $docCatalog[$key] + ['required' => in_array($key, $requiredKeys, true)];
    }

    $uploadedCount = collect($docs)->filter(fn ($d) => !empty($student->{$d['col']}))->count();
@endphp

<div class="rg-page">

    <!-- Moved Back Button Above Header -->
    <div style="margin-bottom: 16px;">
        <a href="{{ route('registrar.manage-students') }}" class="rg-btn rg-btn-ghost">
            <i class="fas fa-arrow-left"></i> Back to Student List
        </a>
    </div>

    <header class="rg-header">
        <div>
            <h1 class="rg-title">Student Profile</h1>
            <p class="rg-subtitle">View and update student records and document requirements.</p>
        </div>
    </header>

    @if(session('Success') || session('success'))
        <div class="rg-alert rg-alert-success" role="status">
            <i class="fas fa-check-circle"></i>
            <span>{{ session('Success') ?? session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="rg-alert rg-alert-error" role="alert">
            <i class="fas fa-exclamation-circle"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="rg-alert rg-alert-error" role="alert">
            <i class="fas fa-exclamation-circle"></i>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rg-profile-grid">

        <div class="rg-card">
            <div class="rg-card-header-dark">
                <i class="fas fa-user-graduate"></i> Student Record — {{ $student->student_id }}
            </div>

            <form action="{{ route('registrar.update-info', $student->id) }}" method="POST" class="rg-card-body">
                @csrf
                @method('PUT')

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
                                <td>
                                    <select id="statusSelect" name="status" class="rg-select" style="max-width: 250px;">
                                        <option value="Enrolled" {{ $currentStatus == 'Enrolled' ? 'selected' : '' }}>Enrolled</option>
                                        <option value="On-Hold"  {{ $currentStatus == 'On-Hold' ? 'selected' : '' }}>On-Hold</option>
                                        <option value="Dropped"  {{ $currentStatus == 'Dropped' ? 'selected' : '' }}>Dropped</option>
                                        <option value="Purged"   {{ $currentStatus == 'Purged' ? 'selected' : '' }}>Purged</option>
                                    </select>
                                </td>
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

                <div class="form-actions" style="margin-top: 16px; text-align: right;">
                    <button type="submit" class="rg-btn rg-btn-primary">
                        <i class="fas fa-save"></i> Save Academic Status
                    </button>
                </div>
            </form>
        </div>

        <div class="rg-card">
            <div class="rg-card-header-gray" style="justify-content: space-between;">
                <span><i class="fas fa-folder-open"></i> Required Documents for ({{ $student->student_type }})</span>
                @if(count($docs) > 0)
                    <span style="background: rgba(255,255,255,0.2); padding: 3px 10px; border-radius: 10px; font-size: 11.5px;">
                        {{ $uploadedCount }} / {{ count($docs) }} uploaded
                    </span>
                @endif
            </div> 

            <form action="{{ route('registrar.update-documents', $student->id) }}" method="POST" enctype="multipart/form-data" class="rg-card-body">
                @csrf
                @method('PUT')

                @if(count($docs) > 0)
                    <p style="margin: 0 0 16px; font-size: 13.5px; color: var(--rg-muted);">
                        Choose a scanned file for a document, then click <strong>Save Document Changes</strong>.
                    </p>

                    <div class="rg-table-wrap">
                        <table class="rg-table">
                            <thead>
                                <tr>
                                    <th style="width: 30%;">Document Type</th>
                                    <th style="width: 25%;">Current Status</th>
                                    <th style="width: 45%;">Upload / Replace</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($docs as $doc)
                                    <tr>
                                        <td>
                                            <strong>{{ $doc['label'] }}</strong>
                                            @if(!$doc['required'])
                                                <span class="doc-extra-tag">On file</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($student->{$doc['col']})
                                                <div class="rg-doc-status">
                                                    <span class="rg-status-uploaded"><i class="fas fa-check-circle"></i> Uploaded</span>
                                                    <a href="{{ route('registrar.secure-view', ['student_id' => $student->student_id, 'filename' => $student->{$doc['col']}]) }}" target="_blank" class="rg-link">View File</a>
                                                </div>
                                            @else
                                                <span class="rg-status-missing"><i class="fas fa-times-circle"></i> No File</span>
                                            @endif
                                        </td>
                                        <td>
                                            <input type="file" name="{{ $doc['input'] }}" accept=".pdf,.jpg,.jpeg,.png" class="rg-file">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="form-actions" style="margin-top: 16px; text-align: right;">
                        <button type="submit" class="rg-btn rg-btn-primary">
                            <i class="fas fa-save"></i> Save Document Changes
                        </button>
                    </div>
                @else
                    <div style="text-align: center; padding: 30px 0; color: var(--rg-muted);">
                        <i class="fas fa-folder-open" style="font-size: 28px; color: #B9C8F0; margin-bottom: 10px; display: block;"></i>
                        <strong style="color: var(--rg-ink);">No documents required</strong>
                        <div style="font-size: 13px; margin-top: 4px;">This student is a continuing (Old) student and has no documents on file.</div>
                    </div>
                @endif
            </form>
        </div>

    </div>
</div>

<style>
    .rg-page {
        --rg-blue: #0033A0; --rg-blue-dark: #0A1F5C; --rg-blue-tint: #EAF0FF; --rg-gold: #F2B705;
        --rg-ink: #0B1B3A; --rg-muted: #5C6B8A; --rg-line: #E2E8F3; --rg-surface: #F7F9FD;
        --rg-green: #15803D; --rg-green-tint: #E8F7EE; --rg-red: #C62828;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: var(--rg-ink);
        max-width: 1300px; margin: 0 auto; padding: 24px 20px 40px;
    }

    .rg-header { display: flex; justify-content: flex-start; align-items: flex-end; gap: 20px; flex-wrap: wrap; padding-bottom: 18px; margin-bottom: 24px; border-bottom: 1px solid var(--rg-line); }
    .rg-title { margin: 0; font-size: 26px; font-weight: 800; letter-spacing: -0.2px; color: var(--rg-blue-dark); }
    .rg-subtitle { margin: 8px 0 0; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; font-size: 14px; color: var(--rg-muted); }

    .rg-alert { display: flex; gap: 10px; align-items: flex-start; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 13.5px; font-weight: 600; line-height: 1.45; }
    .rg-alert i { margin-top: 2px; }
    .rg-alert ul { margin: 0; padding-left: 18px; }
    .rg-alert-success { background: var(--rg-green-tint); color: #14532D; border: 1px solid #BFE8CE; border-left: 4px solid var(--rg-green); }
    .rg-alert-error { background: #FDECEC; color: #8E1B1B; border: 1px solid #F5C6C6; border-left: 4px solid var(--rg-red); }

    .rg-profile-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; align-items: start; }

    .rg-card { background: #fff; border: 1px solid var(--rg-line); border-radius: 10px; overflow: hidden; box-shadow: 0 1px 2px rgba(10, 31, 92, 0.05); }
    .rg-card-header-dark { background: var(--rg-blue-dark); color: #fff; padding: 16px 20px; font-size: 15px; font-weight: 700; display: flex; align-items: center; gap: 10px; }
    .rg-card-header-gray { background: var(--rg-gold); color: #fff; padding: 16px 20px; font-size: 15px; font-weight: 700; display: flex; align-items: center; gap: 10px; }
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
    
    .doc-extra-tag { display: inline-block; margin-left: 6px; font-size: 10.5px; font-weight: 700; color: var(--rg-muted); background: var(--rg-surface); border: 1px solid var(--rg-line); padding: 1px 7px; border-radius: 10px; vertical-align: middle; }

    .rg-select { width: 100%; box-sizing: border-box; height: 38px; padding: 0 12px; font-size: 13.5px; font-weight: 600; color: var(--rg-ink); background: #fff; border: 1px solid #CBD5E6; border-radius: 6px; cursor: pointer; }
    .rg-select:focus { outline: none; border-color: var(--rg-blue); box-shadow: 0 0 0 3px rgba(0, 51, 160, 0.14); }

    .rg-file { width: 100%; font-size: 12.5px; color: var(--rg-muted); }
    .rg-file::file-selector-button { margin-right: 10px; padding: 6px 12px; font-size: 12px; font-weight: 700; color: var(--rg-blue); background: #fff; border: 1px solid #B9C8F0; border-radius: 6px; cursor: pointer; transition: background .15s; }
    .rg-file::file-selector-button:hover { background: var(--rg-blue-tint); }

    .rg-btn { display: inline-flex; align-items: center; gap: 7px; padding: 8px 16px; border-radius: 7px; font-size: 13px; font-weight: 700; text-decoration: none; cursor: pointer; border: 1px solid transparent; transition: background .15s, color .15s, border-color .15s; white-space: nowrap; line-height: 1.2; }
    .rg-btn-primary { background: var(--rg-blue); color: #fff; }
    .rg-btn-primary:hover { background: var(--rg-blue-dark); color: #fff; }
    .rg-btn-ghost { background: #fff; color: var(--rg-blue); border-color: #B9C8F0; }
    .rg-btn-ghost:hover { background: var(--rg-blue-tint); color: var(--rg-blue-dark); }
    .rg-btn:focus-visible { outline: 2px solid var(--rg-blue); outline-offset: 2px; }

    @media (max-width: 1024px) {
        .rg-profile-grid { display: grid; grid-template-columns: 4fr 6fr; gap: 24px; align-items: start; }
    }
</style>
@endsection