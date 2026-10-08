@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="rg-page">
    
    <header class="rg-header">
        <div>
            <h2 class="rg-title">Course Programs</h2>
            <p class="rg-subtitle">Register and manage college degree programs and their assigned departments.</p>
        </div>
    </header>

    @if(session('success'))
        <div class="rg-alert rg-alert-success" role="status">
            <i class="fas fa-check-circle"></i> <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="rg-alert rg-alert-error" role="alert">
            <i class="fas fa-exclamation-triangle"></i> <span>{{ session('error') }}</span>
        </div>
    @endif

    @if(isset($errors) && count($errors) > 0)
        <div class="rg-alert rg-alert-error" role="alert">
            <i class="fas fa-exclamation-circle"></i>
            <div>
                <strong style="display:block; margin-bottom: 4px;">Unable to process request:</strong>
                <ul style="margin: 0; padding-left: 20px;">
                    @php $allErrors =$errors->all(); @endphp
                    @for($i = 0; $i < count($allErrors);$i++)
                        <li>{{ $allErrors[$i] }}</li>
                    @endfor
                </ul>
            </div>
        </div>
    @endif

    <div id="js-alert-error" class="rg-alert rg-alert-error" role="alert" style="display: none;">
        <i class="fas fa-exclamation-triangle"></i>
        <div>
            <strong style="display:block; margin-bottom: 4px;">Unable to process request:</strong>
            <span id="js-alert-message">Please fill out all required fields before submitting.</span>
        </div>
    </div>

    <!-- SIDE-BY-SIDE WORKSPACE: FORM LEFT, TABLE RIGHT -->
    <div class="admin-workspace">
        
        <!-- FORM PANEL (LEFT) - DYNAMIC FOR ADD & EDIT -->
        <div class="rg-card form-panel">
            <div class="card-bar" id="form-card-header-bar">
                <span id="form-card-title"><i class="fas fa-plus-circle" style="margin-right: 6px;"></i> Add New Course Program</span>
            </div>
            
            <form action="{{ route('super_admin.courses.store') }}" method="POST" id="course-form" autocomplete="off" class="pure-form" novalidate>
                @csrf
                <div id="form-method-container"></div>
                
                <div class="form-group">
                    <label>Course / Program Acronym Code <span class="required-star">*</span></label>
                    <input type="text" name="course_code" id="course-code-input" class="rg-input" placeholder="E.g., BSIT, BSCS" style="text-transform: uppercase;" required>
                    <small id="course-code-error" style="display: none; color: var(--rg-red); font-weight: 600; font-size: 11.5px; margin-top: 4px;">
                        <i class="fas fa-exclamation-circle"></i> Course code already exists!
                    </small>
                </div>

                <div class="form-group">
                    <label>Assigned Department <span class="required-star">*</span></label>
                    <select name="department" id="course-department-select" class="rg-select" required>
                        <option value="COLLEGE OF COMPUTER STUDIES" selected>College of Computer Studies</option>
                        <option value="COLLEGE OF BUSINESS ADMINISTRATION">College of Business Administration</option>
                        <option value="COLLEGE OF HOSPITALITY MANAGEMENT">College of Hospitality Management</option>
                        <option value="COLLEGE OF ARTS AND SCIENCES">College of Arts and Sciences</option>
                        <option value="COLLEGE OF EDUCATION">College of Education</option>
                        <option value="COLLEGE OF NURSING">College of Nursing</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Full Degree Title <span class="required-star">*</span></label>
                    <input type="text" name="course_title" id="course-title-input" class="rg-input" placeholder="E.g., Bachelor of Science in Information Technology" required>
                </div>

                <div class="form-actions-bar">
                    <button type="button" class="rg-btn rg-btn-ghost" id="btn-cancel-edit" style="display: none;" onclick="resetFormToCreateMode()">Cancel</button>
                    <button type="reset" class="rg-btn rg-btn-ghost" id="btn-reset-form">Clear</button>
                    <button type="submit" id="btn-save-course" class="rg-btn rg-btn-primary" style="flex: 1; justify-content: center;">Save Course</button>
                </div>
            </form>
        </div>

        <!-- TABLE PANEL (RIGHT) -->
        <div class="rg-card table-panel">
            <div class="card-bar">
                <span style="font-size: 15px;"><i class="fa-solid fa-folder-open" style="margin-right: 6px;"></i> Active Course Programs</span>
            </div>
            
            <div class="rg-toolbar">
                <div class="rg-search">
                    <i class="fas fa-search"></i>
                    <input type="text" id="course-live-search" placeholder="Search code or title...">
                </div>

                <div class="employee-count-badge">
                    <i class="fas fa-graduation-cap"></i>
                    <span>Total: <strong id="courseTotalCount">{{ count($courses) }}</strong> courses</span>
                </div>
            </div>

            <div class="rg-table-wrap">
                <table class="rg-table" id="course-table">
                    <thead>
                        <tr>
                            <th style="width: 22%;">Course Code</th>
                            <th style="width: 32%;">Department</th>
                            <th style="width: 34%;">Full Program Title</th>
                            <th style="width: 12%; text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($courses as $course)
                            <tr class="course-table-row-item">
                                <td>
                                    <span class="student-id-tag search-code">{{ $course->course_code }}</span>
                                </td>
                                <td>
                                    <strong style="color: var(--rg-blue-dark); font-size: 13px;" class="search-dept">{{ $course->department }}</strong>
                                </td>
                                <td>
                                    <span class="subject-title-text search-title" style="font-weight: 600; font-size: 13.5px;">{{ $course->course_title }}</span>
                                </td>
                                <td style="text-align: center;">
                                    <button type="button" class="rg-btn-action rg-btn-action-ghost" onclick="loadCourseForEditing({{ json_encode($course) }})">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="empty-table-state">
                                    No course programs recorded yet. Use the form to add one.
                                </td>
                            </tr>
                        @endforelse
                        
                        <tr id="empty-search-fallback-row" style="display: none;">
                            <td colspan="4" class="empty-table-state" style="text-align: center; color: var(--rg-muted); padding: 40px 0;">
                                <i class="fas fa-database" style="font-size: 20px; display: block; margin-bottom: 8px;"></i>
                                No matching curriculum modules found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<script>
    const existingCourses = @json($courses->pluck('course_code')->map(fn($item) => strtoupper($item))->toArray());
    let isEditingMode = false;
    let originalEditingCode = "";

    const storeRouteUrl = "{{ route('super_admin.courses.store') }}";
    const updateRouteTemplate = "{{ route('super_admin.courses.update', ':id') }}";

    document.addEventListener('DOMContentLoaded', function() {
        const courseCodeInput = document.getElementById('course-code-input');
        const courseCodeError = document.getElementById('course-code-error');
        const saveBtn = document.getElementById('btn-save-course');
        const resetBtn = document.getElementById('btn-reset-form');
        const courseForm = document.getElementById('course-form');
        const jsAlertError = document.getElementById('js-alert-error');
        const jsAlertMessage = document.getElementById('js-alert-message');

        courseCodeInput.addEventListener('input', function() {
            const enteredCode = this.value.trim().toUpperCase();
            const isDuplicate = isEditingMode 
                ? (enteredCode !== originalEditingCode && existingCourses.includes(enteredCode))
                : (enteredCode !== "" && existingCourses.includes(enteredCode));

            if (isDuplicate) {
                courseCodeError.style.display = 'block';
                this.style.borderColor = 'var(--rg-red)';
                saveBtn.disabled = true;
                saveBtn.style.opacity = '0.5';
                saveBtn.style.cursor = 'not-allowed';
            } else {
                courseCodeError.style.display = 'none';
                this.style.borderColor = '#CBD5E6';
                saveBtn.disabled = false;
                saveBtn.style.opacity = '1';
                saveBtn.style.cursor = 'pointer';
            }
        });

        resetBtn.addEventListener('click', function() {
            if (!isEditingMode) {
                courseCodeError.style.display = 'none';
                courseCodeInput.style.borderColor = '#CBD5E6';
                saveBtn.disabled = false;
                saveBtn.style.opacity = '1';
                saveBtn.style.cursor = 'pointer';
            }
        });

        if (courseForm) {
            courseForm.addEventListener('submit', function(e) {
                jsAlertError.style.display = 'none';
                const codeValue = courseCodeInput.value.trim().toUpperCase();
                const titleValue = document.getElementById('course-title-input').value.trim();

                const isDuplicate = isEditingMode 
                    ? (codeValue !== originalEditingCode && existingCourses.includes(codeValue))
                    : existingCourses.includes(codeValue);

                if (!codeValue || !titleValue) {
                    e.preventDefault();
                    jsAlertMessage.textContent = 'Please fill out all required fields before submitting.';
                    jsAlertError.style.display = 'flex';
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                } else if (isDuplicate) {
                    e.preventDefault();
                    jsAlertMessage.textContent = 'Cannot submit: This course code already exists!';
                    jsAlertError.style.display = 'flex';
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            });
        }

        const searchInput = document.getElementById('course-live-search');
        const courseRows = document.querySelectorAll('.course-table-row-item');
        const emptyRow = document.getElementById('empty-search-fallback-row');
        const totalCountSpan = document.getElementById('courseTotalCount');
        const initialTotal = courseRows.length;

        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            let matchesFound = 0;

            courseRows.forEach(row => {
                const code = row.querySelector('.search-code').textContent.toLowerCase();
                const dept = row.querySelector('.search-dept').textContent.toLowerCase();
                const title = row.querySelector('.search-title').textContent.toLowerCase();

                if (code.includes(query) || dept.includes(query) || title.includes(query)) {
                    row.style.display = '';
                    matchesFound++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (totalCountSpan) {
                totalCountSpan.textContent = matchesFound;
            }

            emptyRow.style.display = (matchesFound === 0 && initialTotal > 0) ? '' : 'none';
        });
    });

    function loadCourseForEditing(course) {
        isEditingMode = true;
        originalEditingCode = course.course_code.toUpperCase();

        document.getElementById('form-card-title').innerHTML = `<i class="fas fa-edit" style="margin-right: 6px;"></i> Edit Course Program: <strong>${course.course_code}</strong>`;
        
        const form = document.getElementById('course-form');
        form.action = updateRouteTemplate.replace(':id', course.id);

        document.getElementById('form-method-container').innerHTML = '@method("PATCH")';

        document.getElementById('course-code-input').value = course.course_code;
        document.getElementById('course-department-select').value = course.department;
        document.getElementById('course-title-input').value = course.course_title;

        document.getElementById('btn-cancel-edit').style.display = 'inline-flex';
        document.getElementById('btn-reset-form').style.display = 'none';
        document.getElementById('btn-save-course').textContent = 'Update Changes';

        document.getElementById('course-code-error').style.display = 'none';
        document.getElementById('course-code-input').style.borderColor = '#CBD5E6';
        document.getElementById('btn-save-course').disabled = false;
        document.getElementById('btn-save-course').style.opacity = '1';
        document.getElementById('btn-save-course').style.cursor = 'pointer';

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function resetFormToCreateMode() {
        isEditingMode = false;
        originalEditingCode = "";

        document.getElementById('form-card-title').innerHTML = `<i class="fas fa-plus-circle" style="margin-right: 6px;"></i> Add New Course Program`;
        
        const form = document.getElementById('course-form');
        form.action = storeRouteUrl;

        document.getElementById('form-method-container').innerHTML = '';

        form.reset();
        document.getElementById('course-department-select').value = 'COLLEGE OF COMPUTER STUDIES';

        document.getElementById('btn-cancel-edit').style.display = 'none';
        document.getElementById('btn-reset-form').style.display = 'inline-flex';
        document.getElementById('btn-save-course').textContent = 'Save Course';

        document.getElementById('course-code-error').style.display = 'none';
        document.getElementById('course-code-input').style.borderColor = '#CBD5E6';
        document.getElementById('btn-save-course').disabled = false;
        document.getElementById('btn-save-course').style.opacity = '1';
        document.getElementById('btn-save-course').style.cursor = 'pointer';
    }
</script>

<style>
    .rg-page {
        --rg-blue: #0033A0; --rg-blue-dark: #0A1F5C; --rg-blue-tint: #EAF0FF; --rg-gold: #F2B705;
        --rg-ink: #0B1B3A; --rg-muted: #5C6B8A; --rg-line: #E2E8F3; --rg-surface: #F7F9FD;
        --rg-green: #15803D; --rg-green-tint: #E8F7EE; --rg-red: #C62828;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: var(--rg-ink);
        max-width: 1500px; margin: 0 auto; padding: 24px 20px 40px;
    }

    .rg-header { display: flex; justify-content: space-between; align-items: flex-end; gap: 20px; flex-wrap: wrap; padding-bottom: 18px; margin-bottom: 24px; border-bottom: 1px solid var(--rg-line); }
    .rg-title { margin: 0; font-size: 26px; font-weight: 800; letter-spacing: -0.2px; color: var(--rg-blue-dark); }
    .rg-subtitle { margin: 8px 0 0; font-size: 14px; color: var(--rg-muted); }

    .rg-alert { display: flex; gap: 10px; align-items: flex-start; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 13.5px; font-weight: 600; line-height: 1.45; }
    .rg-alert i { margin-top: 2px; }
    .rg-alert-success { background: var(--rg-green-tint); color: #14532D; border: 1px solid #BFE8CE; border-left: 4px solid var(--rg-green); }
    .rg-alert-error { background: #FDECEC; color: #8E1B1B; border: 1px solid #F5C6C6; border-left: 4px solid var(--rg-red); }

    .rg-card { background: #fff; border: 1px solid var(--rg-line); border-radius: 10px; overflow: hidden; box-shadow: 0 1px 2px rgba(10, 31, 92, 0.05); }
    .card-bar { background: var(--rg-surface); padding: 16px 20px; font-weight: 700; font-size: 14.5px; color: var(--rg-blue-dark); border-bottom: 1px solid var(--rg-line); display: flex; align-items: center; gap: 8px; }

    .admin-workspace { display: flex; flex-direction: row; gap: 24px; align-items: flex-start; justify-content: space-between; width: 100%; }
    .form-panel { flex: 0 0 380px; width: 380px; }
    .table-panel { flex: 1; min-width: 0; }

    @media (max-width: 1024px) {
        .admin-workspace { flex-direction: column; }
        .form-panel { flex: none; width: 100%; }
    }

    .pure-form { padding: 24px; display: flex; flex-direction: column; gap: 16px; }
    .form-group { display: flex; flex-direction: column; gap: 6px; }
    .form-group label { font-size: 12px; font-weight: 700; color: var(--rg-muted); text-transform: uppercase; letter-spacing: 0.3px; }
    
    .required-star { color: var(--rg-red); }
    
    .rg-input, .rg-select {
        width: 100%; box-sizing: border-box; height: 40px; padding: 0 14px;
        font-size: 14px; font-weight: 500; color: var(--rg-ink); background: #fff;
        border: 1px solid #CBD5E6; border-radius: 6px; outline: none; transition: border-color 0.2s, box-shadow 0.2s;
    }
    .rg-input:focus, .rg-select:focus { border-color: var(--rg-blue); box-shadow: 0 0 0 3px rgba(0, 51, 160, 0.14); }
    .rg-select {
        padding-right: 36px; appearance: none; -webkit-appearance: none; cursor: pointer;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='2' stroke='%235C6B8A'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7' /%3E%3C/svg%3E");
        background-repeat: no-repeat; background-position: right 12px center; background-size: 16px;
    }

    .form-actions-bar { display: flex; gap: 10px; margin-top: 10px; border-top: 1px solid var(--rg-line); padding-top: 20px; }
    
    .rg-btn { display: inline-flex; align-items: center; gap: 7px; padding: 10px 18px; border-radius: 6px; font-size: 13.5px; font-weight: 700; text-decoration: none; cursor: pointer; border: 1px solid transparent; transition: background .15s, color .15s, border-color .15s; white-space: nowrap; line-height: 1.2; }
    .rg-btn-primary { background: var(--rg-blue); color: #fff; }
    .rg-btn-primary:hover { background: var(--rg-blue-dark); color: #fff; }
    .rg-btn-ghost { background: transparent; color: var(--rg-muted); border-color: #CBD5E6; }
    .rg-btn-ghost:hover { background: var(--rg-surface); color: var(--rg-ink); }

    .rg-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 14px 18px; padding: 14px 20px; border-bottom: 1px solid var(--rg-line); background: #fff; }
    .rg-search { position: relative; flex: 1 1 240px; max-width: 300px; }
    .rg-search i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #8A97B3; font-size: 13px; pointer-events: none; }
    .rg-search input { width: 100%; box-sizing: border-box; padding: 9px 12px 9px 34px; font-size: 13px; color: var(--rg-ink); background: #fff; border: 1px solid #CBD5E6; border-radius: 8px; outline: none; }
    .rg-search input:focus { border-color: var(--rg-blue); box-shadow: 0 0 0 3px rgba(0, 51, 160, 0.14); }

    .employee-count-badge {
        margin-left: auto;
        display: flex;
        align-items: center;
        gap: 8px;
        background: var(--rg-blue-tint);
        border: 1px solid #CFDBFB;
        padding: 8px 14px;
        border-radius: 8px;
        color: var(--rg-blue-dark);
        font-weight: 600;
        font-size: 13.5px;
    }
    .employee-count-badge i { color: var(--rg-blue); font-size: 14px; }
    .employee-count-badge strong { color: var(--rg-blue); font-weight: 800; }

    .rg-table-wrap { overflow-x: auto; width: 100%; }
    .rg-table { width: 100%; min-width: 650px; border-collapse: collapse; font-size: 14px; table-layout: auto; }
    .rg-table th { background: var(--rg-surface); padding: 14px 18px; font-size: 11.5px; font-weight: 700; color: var(--rg-muted); text-transform: uppercase; text-align: left; border-bottom: 1px solid var(--rg-line); letter-spacing: 0.3px; }
    .rg-table td { padding: 16px 18px; border-bottom: 1px solid #EEF2F9; color: var(--rg-ink); vertical-align: middle; }
    .rg-table tbody tr:last-child td { border-bottom: none; }
    .rg-table tbody tr:hover { background: #F5F8FF; }

    .student-id-tag { font-family: ui-monospace, monospace; font-weight: 700; color: var(--rg-blue); background: var(--rg-blue-tint); padding: 3px 8px; border-radius: 6px; font-size: 12px; border: 1px solid #CFDBFB; display: inline-block; }

    .rg-btn-action { border: 1px solid transparent; padding: 6px 12px; font-size: 12px; font-weight: 600; border-radius: 6px; cursor: pointer; transition: 0.15s; white-space: nowrap; }
    .rg-btn-action-ghost { background: transparent; border-color: #CBD5E6; color: var(--rg-muted); }
    .rg-btn-action-ghost:hover { background: var(--rg-surface); color: var(--rg-ink); }

    .empty-table-state { text-align: center; color: var(--rg-muted) !important; padding: 50px 0 !important; font-style: italic; }
</style>
@endsection