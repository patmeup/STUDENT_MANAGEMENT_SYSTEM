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
        <p>Piliin ang Year, Semester, at i-type ang Section upang awtomatikong i-plot ang buong Block Section schedule ng estudyante.</p>
        
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

            <input type="text" id="helperSection" placeholder="e.g., A" class="input-helper-text">

            <button type="button" class="btn-helper" id="autoBlockBtn">⚡ Apply Block Section</button>
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
                            <th style="width: 280px; text-align: center;">Select Schedule / Section</th>
                            <th>Year Level</th>
                            <th>Semester</th>
                            <th>Subject Code</th>
                            <th>Subject Description</th>
                            <th style="text-align: center;">Units</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($curriculumSubjects as $curricula)
                            <tr data-year="{{ $curricula->year_level }}" data-sem="{{ $curricula->semester }}">
                                <td style="text-align: center;">
                                    <select name="schedules[{{ $curricula->subject_id }}]" class="select-input table-dropdown sub-selector">
                                        <option value="">-- Not Enrolled --</option>
                                        
                                        @php
                                            $subjectSchedules = $classSchedules->get($curricula->subject_id);
                                        @endphp

                                        @if($subjectSchedules && $subjectSchedules->count() > 0)
                                            @foreach($subjectSchedules as $sched)
                                                <option value="{{ $sched->id }}">
                                                    {{ strtoupper($sched->section_name) }} | {{ $sched->class_days }} ({{ date('g:i A', strtotime($sched->start_time)) }}-{{ date('g:i A', strtotime($sched->end_time)) }}) — {{ $sched->room_assignment }}
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
                const sectionLetter = document.getElementById('helperSection').value.trim().toUpperCase();

                if (!sectionLetter) {
                    alert('Paki-type muna kung anong Section (halimbawa: A, B, o C) ang nais mong i-apply.');
                    return;
                }

                const rows = document.querySelectorAll('#subjectPlotTable tbody tr');
                let matchedCount = 0;
                let totalTargetSubjects = 0;

                rows.forEach(row => {
                    const rowYear = row.getAttribute('data-year');
                    const rowSem = row.getAttribute('data-sem');

                    if (rowYear === targetYear && rowSem === targetSem) {
                        totalTargetSubjects++;
                        const select = row.querySelector('.sub-selector');
                        
                        if (select) {
                            let sectionFound = false;
                            
                            for (let i = 0; i < select.options.length; i++) {
                                const optionText = select.options[i].text.toUpperCase();
                                
                                // KUNIN ANG UNANG PARTE BAGO ANG '|' SYMBOL (e.g., "BSIT1-A ")
                                const sectionPart = optionText.split('|')[0].trim();
                                
                                // TIYAKIN NA ANG SEKSYON AY NAGSAPE-TAPOS SA SAKTONG LETRA (e.g., nagtatapos sa "-B")
                                if (sectionPart.endsWith('-' + sectionLetter) && select.options[i].value !== "") {
                                    select.selectedIndex = i;
                                    sectionFound = true;
                                    matchedCount++;
                                    break;
                                }
                            }
                            
                            if (!sectionFound) {
                                select.value = ""; // I-reset kung walang nahanap na B o C sa subject na ito
                            }
                        }
                    }
                });

                if (matchedCount > 0) {
                    alert(`Success! Na-apply ang Section ${sectionLetter} sa ${matchedCount} na mga asignatura.`);
                } else {
                    alert(`Paumanhin, walang nahanap na mga schedule/sections na nagtatapos sa "-${sectionLetter}" para sa ${targetYear} (${targetSem}).`);
                }
            });
        }
    });
</script>

<style>
    .sa-container { font-family: 'Segoe UI', sans-serif; padding: 20px; max-width: 1200px; margin: 0 auto; }
    .sa-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .header-titles h2 { margin: 0; font-size: 22px; color: #0f172a; font-weight: 800; }
    .header-titles p { margin: 4px 0 0 0; font-size: 14px; color: #64748b; }
    .btn-back { text-decoration: none; background: #64748b; color: white; padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; }
    
    .action-helper-card { background: #f0fdf4; border: 1px solid #bbf7d0; padding: 20px; border-radius: 10px; margin-bottom: 25px; }
    .action-helper-card h4 { margin: 0 0 6px 0; color: #166534; font-size: 15px; }
    .action-helper-card p { margin: 0 0 14px 0; color: #475569; font-size: 13px; }
    
    .helper-controls { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
    .select-input-helper { background-color: #ffffff; border: 1px solid #cbd5e1; padding: 8px 12px; border-radius: 6px; font-size: 13px; color: #334155; width: 150px; cursor: pointer; }
    .input-helper-text { background-color: #ffffff; border: 1px solid #cbd5e1; padding: 8px 12px; border-radius: 6px; font-size: 13px; color: #334155; width: 120px; text-transform: uppercase; }
    .btn-helper { background: #166534; color: white; border: none; padding: 9px 18px; border-radius: 6px; font-weight: 600; font-size: 13px; cursor: pointer; transition: background 0.2s; }
    .btn-helper:hover { background: #14532d; }

    .sa-card { border-radius: 12px; border: 1px solid #e2e8f0; background: #ffffff; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    .card-header-flex { background-color: #f8fafc; padding: 16px 24px; border-bottom: 1px solid #e2e8f0; }
    .card-title-list { font-size: 14px; font-weight: 700; margin: 0; color: #1e293b; }

    .minimal-table { width: 100%; border-collapse: collapse; font-size: 14px; }
    .minimal-table th { background: #f8fafc; padding: 12px 24px; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; text-align: left; }
    .minimal-table td { padding: 14px 24px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
    
    .table-dropdown { width: 100%; max-width: 260px; padding: 8px; border-radius: 6px; border: 1px solid #cbd5e1; background-color: #fff; color: #334155; font-size: 13px; cursor: pointer; }
    .year-tag { font-weight: 700; color: #1e293b; background: #f1f5f9; padding: 4px 8px; border-radius: 6px; font-size: 11px; }
    .sem-tag { font-weight: 600; color: #475569; background: #f8fafc; padding: 4px 8px; border-radius: 6px; font-size: 11px; border: 1px solid #e2e8f0; }
    .code-highlight { color: #2563eb; font-family: monospace; }
    
    .form-actions-footer { padding: 20px 24px; background: #f8fafc; border-top: 1px solid #e2e8f0; text-align: right; }
    .btn-save-matrix { background: #0f172a; color: white; border: none; padding: 12px 24px; border-radius: 6px; font-weight: 600; font-size: 14px; cursor: pointer; }
    .btn-save-matrix:hover { background: #1e293b; }
</style>
@endsection