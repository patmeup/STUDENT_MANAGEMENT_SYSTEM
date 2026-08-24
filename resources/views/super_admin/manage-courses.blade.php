@extends('layouts.app')

@section('content')
<div class="subj-container">
    
    <div class="subj-header">
        <div class="header-titles">
            <h2>Course Programs</h2>
            <p>Register and manage college degree programs and their assigned departments.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert-success-custom">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert-error-custom" style="background: #fef2f2; color: #991b1b; padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; font-weight: 600; border-left: 5px solid #dc2626; font-size: 14px;">
            <i class="fas fa-exclamation-triangle"></i> {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert-error-custom" style="background: #fef2f2; color: #991b1b; padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; font-weight: 600; border-left: 5px solid #dc2626; font-size: 14px; display: flex; align-items: flex-start; gap: 10px;">
            <i class="fas fa-exclamation-circle" style="font-size: 16px; margin-top: 2px;"></i>
            <div>
                <strong>Unable to process request:</strong>
                <ul style="margin: 4px 0 0 0; padding-left: 20px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div id="js-alert-error" class="alert-error-custom" style="display: none; background: #fef2f2; color: #991b1b; padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; font-weight: 600; border-left: 5px solid #dc2626; font-size: 14px; align-items: center; gap: 10px;">
        <i class="fas fa-exclamation-triangle"></i>
        <span id="js-alert-message">Please fill out all required fields before submitting.</span>
    </div>

    <div class="subj-card">
        <div class="card-bar">
            <span><i class="fas fa-plus-circle"></i> Add New Course Program</span>
        </div>
        
        <form action="{{ route('super_admin.courses.store') }}" method="POST" id="course-form" autocomplete="off" class="pure-form" novalidate>
            @csrf
            
            <div class="form-grid-2">
                <div class="form-group">
                    <label>Course / Program Acronym Code <span class="required-star" style="color: #dc2626;">*</span></label>
                    <div class="custom-select-wrapper">
                        <select name="course_code" id="course-code-select" class="custom-select-dropdown" required>
                            <option value="" disabled selected hidden>Select Program</option>
                            <option value="BSIT">BSIT</option>
                            <option value="BSCS">BSCS</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Assigned Department <span class="required-star" style="color: #dc2626;">*</span></label>
                    <div class="custom-select-wrapper">
                        <select name="department" id="course-department-select" class="custom-select-dropdown" required>
                            <option value="COLLEGE OF COMPUTER STUDIES" selected>College of Computer Studies</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <label>Full Degree Title <span class="required-star" style="color: #dc2626;">*</span></label>
                    <small style="color: #10b981; font-weight: 600;"><i class="fas fa-magic"></i> Auto-generated</small>
                </div>
                <input type="text" name="course_title" id="course-title-input" placeholder="Select a program above to auto-populate" readonly required style="background-color: #f8fafc; cursor: not-allowed; font-weight: 600; color: #1e293b;">
            </div>

            <div class="form-actions-bar">
                <button type="reset" class="btn-clear" id="btn-reset-form">Clear Form</button>
                <button type="submit" class="btn-submit-subj" style="background-color: #000000;">Save Course</button>
            </div>
        </form>
    </div>

    <div class="subject-list-card">
        <div class="card-header-flex">
            <h3 class="card-title-list">Active Course Programs</h3>
            <div style="display: flex; align-items: center; gap: 15px;">
                <div class="search-input-box-wrapper" style="position: relative; display: flex; align-items: center;">
                    <i class="fas fa-search" style="position: absolute; left: 12px; color: #94a3b8; font-size: 13px; pointer-events: none;"></i>
                    <input type="text" id="course-live-search" placeholder="Search program code or title..." style="border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 12px 6px 34px; font-size: 12.5px; outline: none; width: 220px; transition: all 0.2s ease;">
                </div>
                <span class="badge-count">Total: {{ count($courses) }}</span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="minimal-table">
                <thead>
                    <tr>
                        <th>Course Code</th>
                        <th>Department</th>
                        <th>Full Program Title</th>
                        <th style="text-align: center; width: 100px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($courses as $course)
                        <tr class="course-table-row-item">
                            <td><span class="subject-code-tag search-code" style="color: #10b981; background-color: #ecfdf5;">{{ $course->course_code }}</span></td>
                            <td class="search-dept"><strong>{{ $course->department }}</strong></td>
                            <td class="subject-title-text search-title">{{ $course->course_title }}</td>
                            <td style="text-align: center;">
                                <button type="button" class="btn-edit-action" onclick="openEditCourseModal({{ json_encode($course) }})">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="empty-table-state"> No institutional course programs recorded yet.</td>
                        </tr>
                    @endforelse
                    
                    <tr id="empty-search-fallback-row" style="display: none;">
                        <td colspan="4" class="empty-table-state" style="text-align: center; color: #64748b; padding: 30px !important;">
                            No matching curriculum modules found for your criteria.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="editCourseModal" class="custom-modal-overlay" style="display: none;">
    <div class="custom-modal-content">
        <div class="modal-header-bar">
            <h3><i class="fas fa-edit"></i> Edit Course Program</h3>
            <button type="button" class="close-modal-btn" onclick="closeEditCourseModal()">&times;</button>
        </div>
        
        <form id="editCourseForm" method="POST" autocomplete="off" class="pure-form" style="padding: 20px;">
            @csrf
            @method('PATCH')
            
            <div class="form-group">
                <label>Course / Program Acronym Code</label>
                <div class="custom-select-wrapper">
                    <select name="course_code" id="modal-course-code" class="custom-select-dropdown" required>
                        <option value="BSIT">BSIT</option>
                        <option value="BSCS">BSCS</option>
                    </select>
                </div>
            </div>

            <div class="form-group" style="margin-top: 15px;">
                <label>Assigned Department</label>
                <div class="custom-select-wrapper">
                    <select name="department" id="modal-course-department" class="custom-select-dropdown" required>
                        <option value="COLLEGE OF COMPUTER STUDIES">College of Computer Studies</option>
                    </select>
                </div>
            </div>

            <div class="form-group" style="margin-top: 15px;">
                <label>Full Degree Title</label>
                <input type="text" name="course_title" id="modal-course-title" readonly required style="background-color: #f8fafc; cursor: not-allowed; font-weight: 600; color: #1e293b;">
            </div>

            <div class="form-actions-bar" style="margin-top: 20px; padding-top: 15px;">
                <button type="button" class="btn-clear" onclick="closeEditCourseModal()">Cancel</button>
                <button type="submit" id="modal-submit-btn" disabled class="btn-submit-subj" style="background-color: #10b981;">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
    const existingCourses = @json($courses->pluck('course_code')->toArray());

    const programTitles = {
        'BSIT': 'Bachelor of Science in Information Technology',
        'BSCS': 'Bachelor of Science in Computer Science'
    };

    document.addEventListener('DOMContentLoaded', function() {
        const courseSelect = document.getElementById('course-code-select');
        const titleInput = document.getElementById('course-title-input');
        const resetBtn = document.getElementById('btn-reset-form');
        const courseForm = document.getElementById('course-form');
        const jsAlertError = document.getElementById('js-alert-error');
        const jsAlertMessage = document.getElementById('js-alert-message');

        courseSelect.addEventListener('change', function() {
            titleInput.value = programTitles[this.value] || '';
        });

        resetBtn.addEventListener('click', function() {
            setTimeout(() => { titleInput.value = ''; }, 10);
        });

        if (courseForm) {
            courseForm.addEventListener('submit', function(e) {
                const codeValue = courseSelect.value;
                jsAlertError.style.display = 'none';

                if (!codeValue) {
                    e.preventDefault();
                    jsAlertMessage.textContent = 'Please select a Course / Program Acronym Code.';
                    jsAlertError.style.display = 'flex';
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            });
        }

        const searchInput = document.getElementById('course-live-search');
        const courseRows = document.querySelectorAll('.course-table-row-item');
        const emptyRow = document.getElementById('empty-search-fallback-row');

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

            emptyRow.style.display = (matchesFound === 0 && courseRows.length > 0) ? '' : 'none';
        });
    });

    function openEditCourseModal(course) {
        const modal = document.getElementById('editCourseModal');
        const form = document.getElementById('editCourseForm');
        const submitBtn = document.getElementById('modal-submit-btn');
        
        const originalCode = course.course_code;
        const originalDept = course.department;

        const codeSelect = document.getElementById('modal-course-code');
        const deptSelect = document.getElementById('modal-course-department');
        const titleInput = document.getElementById('modal-course-title');

        codeSelect.value = originalCode;
        deptSelect.value = originalDept;
        titleInput.value = programTitles[originalCode] || course.course_title;

        let updateUrl = "{{ route('super_admin.courses.update', ':id') }}";
        form.action = updateUrl.replace(':id', course.id); 

        submitBtn.disabled = true;

        function checkFormState() {
            const currentCode = codeSelect.value;
            const currentDept = deptSelect.value;

            const hasChanges = (currentCode !== originalCode) || (currentDept !== originalDept);
            const isDuplicate = (currentCode !== originalCode) && existingCourses.includes(currentCode);

            if (isDuplicate) {
                submitBtn.disabled = true;
            } else if (hasChanges) {
                submitBtn.disabled = false;
            } else {
                submitBtn.disabled = true;
            }
        }

        codeSelect.onchange = function() {
            titleInput.value = programTitles[this.value] || '';
            checkFormState();
        };

        deptSelect.onchange = checkFormState;

        modal.style.display = 'flex';
    }

    function closeEditCourseModal() {
        document.getElementById('editCourseModal').style.display = 'none';
    }
</script>

<style>
    .subj-container { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #1e293b; max-width: 900px; margin: 0 auto; padding-bottom: 50px; }
    .subj-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 2px solid #e2e8f0; padding-bottom: 15px; }
    .header-titles h2 { margin: 0 0 5px 0; font-size: 26px; font-weight: 700; color: #0f172a; }
    .header-titles p { margin: 0; font-size: 14px; color: #64748b; }
    .header-badge { color: white; padding: 6px 14px; border-radius: 20px; font-weight: 600; font-size: 12px; }
    
    .subj-card { background: white; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); overflow: hidden; margin-bottom: 30px; }
    .card-bar { background: #ffffff; padding: 18px 24px; font-weight: 700; font-size: 15px; color: #0f172a; border-bottom: 1px solid #f1f5f9; }
    .pure-form { padding: 24px; display: flex; flex-direction: column; gap: 20px; }
    .form-group { display: flex; flex-direction: column; gap: 6px; }
    .form-group label { font-size: 13px; font-weight: 600; color: #475569; }
    .form-group input { border: 1px solid #cbd5e1; border-radius: 6px; padding: 12px; font-size: 14px; outline: none; box-sizing: border-box; width: 100%; color: #0f172a; }
    .form-group input:focus { border-color: #000000; box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.05); }
    .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .field-help { font-size: 11px; color: #94a3b8; margin-top: 2px; }
    .form-actions-bar { display: flex; justify-content: flex-end; gap: 12px; margin-top: 15px; border-top: 1px solid #f1f5f9; padding-top: 20px; }
    .btn-submit-subj { color: white; border: none; padding: 12px 24px; font-size: 14px; font-weight: 700; border-radius: 6px; cursor: pointer; transition: background 0.2s; }
    .btn-submit-subj:disabled { opacity: 0.5; cursor: not-allowed; }
    .btn-clear { background: transparent; color: #64748b; border: 1px solid #cbd5e1; padding: 12px 20px; font-size: 14px; font-weight: 600; border-radius: 6px; cursor: pointer; }
    
    .alert-success-custom { background: #d1e7dd; color: #0f5132; padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; font-weight: 600; border-left: 5px solid #0f5132; font-size: 14px; }
    
    .subject-list-card { background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.02); overflow: hidden; }
    .card-header-flex { background-color: #f8fafc; padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
    .card-title-list { font-size: 15px; font-weight: 700; color: #0f172a; margin: 0; }
    .badge-count { background-color: #e2e8f0; color: #334155; font-size: 0.75rem; font-weight: 700; padding: 4px 12px; border-radius: 9999px; }
    
    .table-responsive { width: 100%; overflow-x: auto; }
    .minimal-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
    .minimal-table th { background-color: #f8fafc; color: #64748b; font-weight: 600; text-transform: uppercase; font-size: 0.75rem; padding: 14px 24px; border-bottom: 1px solid #e2e8f0; text-align: left; }
    .minimal-table td { padding: 14px 24px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
    .subject-code-tag { font-family: ui-monospace, SFMono-Regular, Consolas, monospace; font-weight: 700; padding: 4px 8px; border-radius: 6px; font-size: 0.85rem; }
    .subject-title-text { font-weight: 500; color: #1e293b; }
    .empty-table-state { text-align: center; color: #64748b; padding: 40px !important; font-style: italic; }
    
    .custom-select-wrapper { position: relative; width: 100%; }
    .custom-select-dropdown { width: 100%; padding: 12px 40px 12px 14px; font-size: 14px; color: #0f172a; background-color: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; appearance: none; -webkit-appearance: none; cursor: pointer; outline: none; }
    .custom-select-dropdown:focus { border-color: #000000; box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.05); }
    .custom-select-wrapper::after { content: "▼"; font-size: 0.65rem; color: #64748b; position: absolute; right: 14px; top: 50%; transform: translateY(-50%); pointer-events: none; }
    
    .btn-edit-action { background: #ffffff; color: #334155; border: 1px solid #cbd5e1; padding: 6px 14px; border-radius: 6px; font-weight: 600; font-size: 12.5px; cursor: pointer; transition: all 0.2s ease; display: inline-flex; align-items: center; gap: 5px; }
    .btn-edit-action:hover { background: #f8fafc; border-color: #94a3b8; color: #000000; }

    .custom-modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); display: flex; justify-content: center; align-items: center; z-index: 9999; }
    .custom-modal-content { background: #ffffff; border-radius: 12px; width: 100%; max-width: 480px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); overflow: hidden; animation: modalEntry 0.25s ease-out; }
    .modal-header-bar { background: #f8fafc; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; }
    .modal-header-bar h3 { margin: 0; font-size: 15px; font-weight: 700; color: #0f172a; }
    .close-modal-btn { background: transparent; border: none; font-size: 24px; color: #94a3b8; cursor: pointer; line-height: 1; padding: 0; }
    .close-modal-btn:hover { color: #475569; }

    @keyframes modalEntry {
        from { opacity: 0; transform: scale(0.95) translateY(-10px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
    }
</style>
@endsection