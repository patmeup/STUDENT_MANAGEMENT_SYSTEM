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

    <div class="action-helper-card">
        <h4><i class="fas fa-magic"></i> Freshmen / Regular Student Helper</h4>
        <p>Kung ang estudyante ay regular na 1st Year, i-click ang button sa ibaba para awtomatikong mapili ang lahat ng subjects para sa 1st Year - 1st Semester.</p>
        <button type="button" class="btn-helper" id="loadFreshmanBtn">Select 1st Year - 1st Sem Subjects</button>
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
                            <th style="width: 50px; text-align: center;">Select</th>
                            <th>Year Level</th>
                            <th>Semester</th>
                            <th>Subject Code</th>
                            <th>Subject Description</th>
                            <th style="text-align: center;">Units</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($curriculumSubjects as $sub)
                            <tr data-year="{{ $sub->year_level }}" data-sem="{{ $sub->semester }}">
                                <td style="text-align: center;">
                                    <input type="checkbox" name="subjects[]" value="{{ $sub->subject_id }}" class="sub-checkbox">
                                </td>
                                <td><span class="year-tag">{{ $sub->year_level }}</span></td>
                                <td><span class="sem-tag">{{ $sub->semester }}</span></td>
                                <td><strong class="code-highlight">{{ $sub->subject_code }}</strong></td>
                                <td>{{ $sub->subject_title }}</td>
                                <td style="text-align: center;">{{ $sub->lecture_units + $sub->lab_units }} Units</td>
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
        const loadFreshmanBtn = document.getElementById('loadFreshmanBtn');
        const checkboxes = document.querySelectorAll('.sub-checkbox');

        if (loadFreshmanBtn) {
            loadFreshmanBtn.addEventListener('click', function () {
                // I-uncheck muna ang lahat bago iselect ang para sa 1st year
                checkboxes.forEach(cb => cb.checked = false);

                // Hanapin ang mga rows na 1st Year at 1st Semester
                const targetRows = document.querySelectorAll('#subjectPlotTable tbody tr');
                targetRows.forEach(row => {
                    const year = row.getAttribute('data-year');
                    const sem = row.getAttribute('data-sem');

                    if (year === '1st Year' && sem === '1st Semester') {
                        const checkbox = row.querySelector('.sub-checkbox');
                        if (checkbox) checkbox.checked = true;
                    }
                });
                
                alert('1st Year - 1st Semester subjects have been selected! Review the list below then click Save.');
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
    .btn-helper { background: #166534; color: white; border: none; padding: 10px 18px; border-radius: 6px; font-weight: 600; font-size: 13px; cursor: pointer; transition: background 0.2s; }
    .btn-helper:hover { background: #14532d; }

    .sa-card { border-radius: 12px; border: 1px solid #e2e8f0; background: #ffffff; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    .card-header-flex { background-color: #f8fafc; padding: 16px 24px; border-bottom: 1px solid #e2e8f0; }
    .card-title-list { font-size: 14px; font-weight: 700; margin: 0; color: #1e293b; }

    .minimal-table { width: 100%; border-collapse: collapse; font-size: 14px; }
    .minimal-table th { background: #f8fafc; padding: 12px 24px; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; text-align: left; }
    .minimal-table td { padding: 14px 24px; border-bottom: 1px solid #f1f5f9; color: #334155; }
    
    .sub-checkbox { width: 17px; height: 17px; cursor: pointer; }
    .year-tag { font-weight: 700; color: #1e293b; background: #f1f5f9; padding: 4px 8px; border-radius: 6px; font-size: 11px; }
    .sem-tag { font-weight: 600; color: #475569; background: #f8fafc; padding: 4px 8px; border-radius: 6px; font-size: 11px; border: 1px solid #e2e8f0; }
    .code-highlight { color: #2563eb; font-family: monospace; }
    
    .form-actions-footer { padding: 20px 24px; background: #f8fafc; border-top: 1px solid #e2e8f0; text-align: right; }
    .btn-save-matrix { background: #0f172a; color: white; border: none; padding: 12px 24px; border-radius: 6px; font-weight: 600; font-size: 14px; cursor: pointer; }
    .btn-save-matrix:hover { background: #1e293b; }
</style>
@endsection