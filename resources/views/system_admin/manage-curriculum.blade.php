@extends('layouts.app')

@section('content')
<div class="subj-container">
    
    <div class="subj-header">
        <div class="header-titles">
            <h2>Curriculum Subject Mapping</h2>
            <p>Admin Control Panel — Course Curriculum Core Management</p>
        </div>
        <div class="header-badge">Admin Mode</div>
    </div>

    @if(session('success'))
        <div class="alert-success-custom">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert-danger-custom" style="background: #fde8e8; color: #9b1c1c; padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; font-weight: 600; border-left: 5px solid #9b1c1c; font-size: 14px;">
            <i class="fas fa-exclamation-circle"></i> {{ $errors->first() }}
        </div>
    @endif

    <div class="curr-mapping-workspace">
        
        {{-- 1. CURRICULUM MAPPING FORM PANEL (LEFT) --}}
        <div class="subj-card form-panel">
            <div class="card-bar">
                <span><i class="fas fa-network-wired"></i> Map Subject Link Structure</span>
            </div>
            
            <form action="{{ route('system_admin.curriculum.store') }}" method="POST" autocomplete="off" class="pure-form">
                @csrf
                
                <div class="form-group">
                    <label>Select Course Program</label>
                    <div class="custom-select-wrapper">
                        <select name="course" id="courseSelect" required class="form-select-custom">
                            <option value="" disabled selected hidden>Choose Course</option>
                            @foreach($courses ?? [] as $course)
                                <option value="{{ $course->course_code }}">{{ $course->course_code }} - {{ $course->course_title }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Year Level</label>
                        <div class="custom-select-wrapper">
                            <select name="year_level" id="yearSelect" required class="form-select-custom">
                                <option value="" disabled selected hidden>Choose Year</option>
                                <option value="1st Year">1st Year</option>
                                <option value="2nd Year">2nd Year</option>
                                <option value="3rd Year">3rd Year</option>
                                <option value="4th Year">4th Year</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Semester</label>
                        <div class="custom-select-wrapper">
                            <select name="semester" id="semesterSelect" required class="form-select-custom">
                                <option value="" disabled selected hidden>Choose Semester</option>
                                <option value="1st Semester">1st Semester</option>
                                <option value="2nd Semester">2nd Semester</option>
                                <option value="Summer">Summer</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Select Subject to Bind</label>
                    <div class="custom-select-wrapper">
                        <select name="subject_id" id="subjectSelect" required class="form-select-custom">
                            <option value="" disabled selected hidden>-- Select Cataloged Subject --</option>
                            @if(isset($subjects) && $subjects->count() > 0)
                                @foreach($subjects as $subj)
                                    <option value="{{ $subj->id }}">
                                        [{{ strtoupper($subj->subject_code) }}] — {{ $subj->subject_title }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>

                <div class="form-actions-bar">
                    <button type="submit" class="btn-submit-subj">Establish Curriculum</button>
                </div>
            </form>
        </div>

        {{-- 2. CURRICULUM LIVE MASTERLIST MAP PANEL (RIGHT) --}}
        <div class="subject-list-card table-panel">
            <div class="card-header-flex">
                <div class="card-title-container">
                    <h3 class="card-title-list">Active Curriculum Structures</h3>
                    <span class="badge-count">{{ count($mappings ?? []) }} Links Active</span>
                </div>
                
                <div class="table-search-box">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="tableSearchInput" placeholder="Search course, year, or subject...">
                </div>
            </div>

            <div class="table-responsive">
                <table class="minimal-table" id="curriculumTable">
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Year Level</th>
                            <th>Semester</th>
                            <th>Subject</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($mappings ?? [] as $map)
                            <tr>
                                <td><span class="course-code-badge">{{ $map->course }}</span></td>
                                <td><strong>{{ $map->year_level }}</strong></td>
                                <td><span class="sem-badge">{{ $map->semester }}</span></td>
                                <td>
                                    <span class="subject-code-tag">{{ $map->subject->subject_code ?? 'N/A' }}</span>
                                    <div class="table-subtext-title">{{ $map->subject->subject_title ?? 'Deleted Subject' }}</div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="empty-table-state">
                                     There are currently no subject links assigned to the courses.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('tableSearchInput');
        const tableRows = document.querySelectorAll('#curriculumTable tbody tr');

        if (searchInput) {
            searchInput.addEventListener('keyup', function () {
                const searchTerm = searchInput.value.toLowerCase().trim();
                tableRows.forEach(row => {
                    if (row.querySelector('.empty-table-state')) return;
                    const courseText = row.cells[0]?.textContent.toLowerCase() || '';
                    const yearText = row.cells[1]?.textContent.toLowerCase() || '';
                    const semText = row.cells[2]?.textContent.toLowerCase() || '';
                    const subjectText = row.cells[3]?.textContent.toLowerCase() || '';
                    const combinedText = `${courseText} ${yearText} ${semText} ${subjectText}`;

                    if (combinedText.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }

        const courseSelect = document.getElementById('courseSelect');
        const yearSelect = document.getElementById('yearSelect');
        const semesterSelect = document.getElementById('semesterSelect');
        const subjectSelect = document.getElementById('subjectSelect');

        function fetchFilteredSubjects() {
            const courseCode = courseSelect.value;
            const yearLevel = yearSelect.value;
            const semester = semesterSelect.value;

            // Gagana lang ang AJAX filter kapag kumpleto ang tatlo, pero HINDI na natin idi-disable ang select box kung hindi pa kumpleto
            if (courseCode && yearLevel && semester) {
                subjectSelect.innerHTML = '<option value="" disabled selected hidden>Loading matched subjects...</option>';

                const url = `/system-admin/curriculum/get-subjects-by-course?course=${encodeURIComponent(courseCode)}&year_level=${encodeURIComponent(yearLevel)}&semester=${encodeURIComponent(semester)}`;

                fetch(url)
                    .then(response => {
                        if (!response.ok) throw new Error('Network response failure');
                        return response.json();
                    })
                    .then(subjects => {
                        subjectSelect.innerHTML = '<option value="" disabled selected hidden>Choose Subject Catalog Entry</option>';
                        
                        if (subjects.length === 0) {
                            subjectSelect.innerHTML = '<option value="" disabled selected hidden>No subjects available for this setup</option>';
                            return;
                        }

                        subjects.forEach(subject => {
                            const option = document.createElement('option');
                            option.value = subject.id;
                            option.textContent = `[${subject.subject_code}] — ${subject.subject_title} (Units: ${subject.total_units || 'N/A'})`;
                            subjectSelect.appendChild(option);
                        });
                    })
                    .catch(error => {
                        console.error('Error fetching filtered subjects:', error);
                    });
            }
        }

        courseSelect.addEventListener('change', fetchFilteredSubjects);
        yearSelect.addEventListener('change', fetchFilteredSubjects);
        semesterSelect.addEventListener('change', fetchFilteredSubjects);
    });
</script>

<style>
    .subj-container { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #1e293b; max-width: 1250px; margin: 0 auto; padding-bottom: 50px; }
    .subj-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 2px solid #e2e8f0; padding-bottom: 15px; }
    .header-titles h2 { margin: 0 0 5px 0; font-size: 26px; font-weight: 700; color: #0f172a; }
    .header-titles p { margin: 0; font-size: 14px; color: #64748b; }
    .header-badge { background-color: #0f172a; color: white; padding: 6px 14px; border-radius: 20px; font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }
    
    .curr-mapping-workspace { display: flex; gap: 28px; align-items: flex-start; }
    .form-panel { flex: 1; min-width: 380px; }
    .table-panel { flex: 1.8; }

    .subj-card { background: white; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); overflow: hidden; }
    .card-bar { background: #ffffff; padding: 18px 24px; font-weight: 700; font-size: 15px; color: #0f172a; border-bottom: 1px solid #f1f5f9; }
    .pure-form { padding: 24px; display: flex; flex-direction: column; gap: 18px; }
    .form-group { display: flex; flex-direction: column; gap: 6px; }
    .form-group label { font-size: 13px; font-weight: 600; color: #475569; }
    
    .custom-select-wrapper { position: relative; width: 100%; }
    .form-select-custom { width: 100%; padding: 12px 40px 12px 15px; font-size: 14px; color: #0f172a; background-color: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; appearance: none; -webkit-appearance: none; -moz-appearance: none; cursor: pointer; outline: none; transition: all 0.2s ease; }
    .form-select-custom:disabled { background-color: #f8fafc; color: #94a3b8; cursor: not-allowed; border-color: #e2e8f0; }
    .form-select-custom:hover:not(:disabled) { border-color: #94a3b8; }
    .form-select-custom:focus:not(:disabled) { border-color: #0f172a; box-shadow: 0 0 0 3px rgba(15, 23, 42, 0.15); }
    .custom-select-wrapper::after { content: "▼"; font-size: 0.65rem; color: #64748b; position: absolute; right: 15px; top: 50%; transform: translateY(-50%); pointer-events: none; }

    .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
    .form-actions-bar { display: flex; justify-content: flex-end; margin-top: 5px; }
    .btn-submit-subj { background: #0f172a; color: white; border: none; padding: 13px; font-size: 14px; font-weight: 700; border-radius: 6px; cursor: pointer; width: 100%; transition: background 0.2s; }
    .btn-submit-subj:hover { background: #1e293b; }
    
    .alert-success-custom { background: #d1e7dd; color: #0f5132; padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; font-weight: 600; border-left: 5px solid #0f5132; font-size: 14px; }
    .alert-danger-custom { background: #fde8e8; color: #9b1c1c; padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; font-weight: 600; border-left: 5px solid #9b1c1c; font-size: 14px; }
    
    .subject-list-card { background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.02); overflow: hidden; }
    .card-header-flex { background-color: #f8fafc; padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; gap: 20px; }
    .card-title-container { display: flex; align-items: center; gap: 12px; }
    .card-title-list { font-size: 15px; font-weight: 700; color: #0f172a; margin: 0; }
    .badge-count { background-color: #e2e8f0; color: #334155; font-size: 0.75rem; font-weight: 700; padding: 4px 12px; border-radius: 9999px; }
    
    .table-search-box { position: relative; max-width: 280px; width: 100%; }
    .table-search-box input { width: 100%; padding: 8px 12px 8px 36px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 6px; outline: none; color: #0f172a; box-sizing: border-box; transition: all 0.2s ease; }
    .table-search-box input:focus { border-color: #0f172a; box-shadow: 0 0 0 3px rgba(15, 23, 42, 0.08); }
    .table-search-box .search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 13px; pointer-events: none; }

    .table-responsive { width: 100%; overflow-x: auto; }
    .minimal-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
    .minimal-table th { background-color: #f8fafc; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 0.75rem; padding: 14px 24px; border-bottom: 1px solid #e2e8f0; text-align: left; }
    .minimal-table td { padding: 14px 24px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
    .minimal-table tbody tr:hover { background-color: #f8fafc; }
    
    .course-code-badge { font-family: ui-monospace, SFMono-Regular, Consolas, monospace; font-weight: 700; color: #dc2626; background-color: #fef2f2; padding: 4px 8px; border-radius: 6px; font-size: 0.85rem; border: 1px solid #fee2e2; }
    .subject-code-tag { font-family: ui-monospace, SFMono-Regular, Consolas, monospace; font-weight: 700; color: #2563eb; background-color: #eff6ff; padding: 3px 6px; border-radius: 4px; font-size: 0.8rem; border: 1px solid #dbeafe; }
    .table-subtext-title { font-weight: 500; color: #1e293b; margin-top: 5px; font-size: 13.5px; }
    .sem-badge { font-size: 0.8rem; background: #f1f5f9; color: #475569; padding: 4px 10px; border-radius: 20px; font-weight: 600; border: 1px solid #e2e8f0; }
    .empty-table-state { text-align: center; color: #64748b; padding: 40px !important; font-style: italic; }
</style>
@endsection