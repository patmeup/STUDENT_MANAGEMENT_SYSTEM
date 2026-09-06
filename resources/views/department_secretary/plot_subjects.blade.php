@extends('layouts.app')

@section('content')
<div class="sa-container">
    <div class="sa-header">
        <div class="header-titles">
            <h2>Plot & Encode Subjects</h2>
            <p>Student: <strong>{{ strtoupper($student->last_name) }}, {{ $student->first_name }}</strong> ({{ $student->student_id }})</p>
        </div>
        <a href="{{ route('department_secretary.students') }}" class="btn-back">Back to List</a>
    </div>

    @if(session('error'))
        <div style="background: #fef2f2; color: #991b1b; padding: 12px; border-radius: 6px; margin-bottom: 15px; border-left: 5px solid #dc2626; font-weight: 600;">
            {{ session('error') }}
        </div>
    @endif

    <div class="action-helper-card">
        <h4><i class="fas fa-magic"></i> Regular / Block Section Student Helper</h4>
        <p>Select the Year, Semester, and Block Section to automatically select the matching schedules for the student.</p>
        
        <div class="helper-controls">
            <select id="helperYear" class="select-input-helper">
                <option value="1st Year">1st Year</option>
                <option value="2nd Year">2nd Year</option>
                <option value="3rd Year">3rd Year</option>
                <option value="4th Year">4th Year</option>
            </select>

            <select id="helperSem" class="select-input-helper">
                <option value="1st Semester">1st Semester</option>
                <option value="2nd Semester">2nd Semester</option>
            </select>

            <select id="helperSection" class="select-input-helper" style="width: 180px;">
                <option value="">-- Select Block Section --</option>
                @foreach($availableSections as $sec)
                    <option value="{{ $sec }}">{{ strtoupper($sec) }}</option>
                @endforeach
            </select>

            <button type="button" class="btn-helper" id="autoBlockBtn">Apply Block Section</button>
        </div>
    </div>

    <form action="{{ route('department_secretary.save_subjects') }}" method="POST">
        @csrf
        <input type="hidden" name="student_id" value="{{ $student->student_id }}">

        <div class="sa-card">
            <div class="card-header-flex">
                <h3 class="card-title-list">Available Curriculum Subjects for {{ $student->course }}</h3>
            </div>

            <div class="table-responsive">
                <table class="minimal-table" id="subjectPlotTable">
                    <thead>
                        <tr>
                            <th style="width: 380px; text-align: center;">Select Block Section Schedule (LEC + LAB)</th>
                            <th>Year Level</th>
                            <th>Semester</th>
                            <th>Subject Code</th>
                            <th>Subject Description</th>
                            <th style="text-align: center;">Units</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($curriculumSubjects as $curricula)
                            @php
                                $subjectSchedules = $classSchedules->where('subject_id', $curricula->subject_id)->groupBy('section_name');
                                $currentSection = $enrolledSections[$curricula->subject_id] ?? null;
                            @endphp
                            <tr data-year="{{ $curricula->year_level }}" data-sem="{{ $curricula->semester }}">
                                <td style="text-align: center;">
                                    <select name="schedules[{{ $curricula->subject_id }}]" class="select-input table-dropdown sub-selector">
                                        <option value="">-- Not Enrolled --</option>

                                        @if($subjectSchedules && $subjectSchedules->count() > 0)
                                            @foreach($subjectSchedules as $secName => $schedGroup)
                                                @php
                                                    $details = [];
                                                    foreach($schedGroup as $s) {
                                                        $timeStr = date('g:i A', strtotime($s->start_time)) . '-' . date('g:i A', strtotime($s->end_time));
                                                        $details[] = "{$s->schedule_type}: {$s->class_days} ({$timeStr}) [{$s->room_assignment}]";
                                                    }
                                                    $label = strtoupper($secName) . ' — ' . implode(' | ', $details);
                                                @endphp
                                                <option value="{{ $secName }}" {{ $currentSection == $secName ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        @else
                                            <option value="" disabled>No Active Sections Created</option>
                                        @endif
                                    </select>
                                </td>
                                <td><span class="year-tag">{{ $curricula->year_level }}</span></td>
                                <td><span class="sem-tag">{{ $curricula->semester }}</span></td>
                                <td><strong class="code-highlight">{{ $curricula->subject_code }}</strong></td>
                                <td>{{ $curricula->subject_title }}</td>
                                <td style="text-align: center;">{{ $curricula->lecture_units + $curricula->lab_units }} Units</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div class="form-actions-footer">
                <button type="submit" class="btn-save-matrix">Save Enrolled Subjects</button>
            </div>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const autoBlockBtn = document.getElementById('autoBlockBtn');

        if (autoBlockBtn) {
            autoBlockBtn.addEventListener('click', function () {
                const targetYear = document.getElementById('helperYear').value;
                const targetSem = document.getElementById('helperSem').value;
                const selectedSection = document.getElementById('helperSection').value.trim();

                if (!selectedSection) {
                    alert('Please select a Block Section from the dropdown.');
                    return;
                }

                const rows = document.querySelectorAll('#subjectPlotTable tbody tr');
                let matchedCount = 0;

                rows.forEach(row => {
                    const rowYear = row.getAttribute('data-year');
                    const rowSem = row.getAttribute('data-sem');

                    if (rowYear === targetYear && rowSem === targetSem) {
                        const select = row.querySelector('.sub-selector');
                        
                        if (select) {
                            let sectionFound = false;
                            
                            for (let i = 0; i < select.options.length; i++) {
                                const optionVal = select.options[i].value;
                                
                                if (optionVal.toLowerCase() === selectedSection.toLowerCase()) {
                                    select.selectedIndex = i;
                                    sectionFound = true;
                                    matchedCount++;
                                    break;
                                }
                            }
                            
                            if (!sectionFound) {
                                select.value = "";
                            }
                        }
                    }
                });

                if (matchedCount > 0) {
                    alert(`Success! Block Section "${selectedSection}" has been applied to ${matchedCount} subjects.`);
                } else {
                    alert(`Sorry, no schedules matching block section "${selectedSection}" were found for ${targetYear} (${targetSem}).`);
                }
            });
        }
    });
</script>

<style>
    .sa-container { font-family: 'Segoe UI', sans-serif; padding: 20px; max-width: 1250px; margin: 0 auto; }
    .sa-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .header-titles h2 { margin: 0; font-size: 22px; color: #0f172a; font-weight: 800; }
    .header-titles p { margin: 4px 0 0 0; font-size: 14px; color: #64748b; }
    .btn-back { text-decoration: none; background: #64748b; color: white; padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; }
    
    .action-helper-card { background: #f0fdf4; border: 1px solid #bbf7d0; padding: 20px; border-radius: 10px; margin-bottom: 25px; }
    .action-helper-card h4 { margin: 0 0 6px 0; color: #166534; font-size: 15px; }
    .action-helper-card p { margin: 0 0 14px 0; color: #475569; font-size: 13px; }
    
    .helper-controls { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
    .select-input-helper { background-color: #ffffff; border: 1px solid #cbd5e1; padding: 8px 12px; border-radius: 6px; font-size: 13px; color: #334155; width: 150px; cursor: pointer; }
    .input-helper-text { background-color: #ffffff; border: 1px solid #cbd5e1; padding: 8px 12px; border-radius: 6px; font-size: 13px; color: #334155; width: 140px; text-transform: uppercase; }
    .btn-helper { background: #166534; color: white; border: none; padding: 9px 18px; border-radius: 6px; font-weight: 600; font-size: 13px; cursor: pointer; transition: background 0.2s; }
    .btn-helper:hover { background: #14532d; }

    .sa-card { border-radius: 12px; border: 1px solid #e2e8f0; background: #ffffff; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    .card-header-flex { background-color: #f8fafc; padding: 16px 24px; border-bottom: 1px solid #e2e8f0; }
    .card-title-list { font-size: 14px; font-weight: 700; margin: 0; color: #1e293b; }

    .minimal-table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
    .minimal-table th { background: #f8fafc; padding: 12px 18px; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; text-align: left; }
    .minimal-table td { padding: 12px 18px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
    
    .table-dropdown { width: 100%; max-width: 360px; padding: 7px 10px; border-radius: 6px; border: 1px solid #cbd5e1; background-color: #fff; color: #334155; font-size: 12.5px; cursor: pointer; }
    .year-tag { font-weight: 700; color: #1e293b; background: #f1f5f9; padding: 4px 8px; border-radius: 6px; font-size: 11px; }
    .sem-tag { font-weight: 600; color: #475569; background: #f8fafc; padding: 4px 8px; border-radius: 6px; font-size: 11px; border: 1px solid #e2e8f0; }
    .code-highlight { color: #2563eb; font-family: monospace; }
    
    .form-actions-footer { padding: 20px 24px; background: #f8fafc; border-top: 1px solid #e2e8f0; text-align: right; }
    .btn-save-matrix { background: #0f172a; color: white; border: none; padding: 12px 24px; border-radius: 6px; font-weight: 600; font-size: 14px; cursor: pointer; }
    .btn-save-matrix:hover { background: #1e293b; }
</style>
@endsection