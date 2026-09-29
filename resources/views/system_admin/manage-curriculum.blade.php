@extends('layouts.app')

@section('content')
<div class="subj-container">

    <div class="subj-header">
        <div class="header-titles">
            <h2>Curriculum Management</h2>
            <p>Add and manage subjects assigned to course curriculums.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert-success-custom">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert-danger-custom" style="align-items: flex-start;">
            <i class="fas fa-exclamation-circle" style="margin-top: 3px;"></i>
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

    <div id="js-alert-error" class="alert-danger-custom" style="display: none; align-items: flex-start; gap: 10px;">
        <i class="fas fa-exclamation-triangle" style="margin-top: 3px;"></i>
        <div>
            <strong>Unable to process request:</strong>
            <ul id="js-alert-list" style="margin: 4px 0 0 0; padding-left: 20px;"></ul>
        </div>
    </div>

    <div class="curr-mapping-workspace">

        <div class="subj-card form-panel">
            <div class="card-bar">
                <span><i class="fas fa-network-wired"></i> Assign Subject to Curriculum</span>
            </div>
            <form
            id="curriculumForm"
            action="{{ route('system_admin.curriculum.store') }}"
            method="POST"
            autocomplete="off"
            class="pure-form"
            novalidate>

            @csrf

            <input
                type="hidden"
                id="curriculumId"
                name="curriculum_id">

                <div class="form-group">
                    <label>Course Program <span class="required-star">*</span></label>
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
                        <label>Year Level <span class="required-star">*</span></label>
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
                        <label>Semester <span class="required-star">*</span></label>
                        <div class="custom-select-wrapper">
                            <select name="semester" id="semesterSelect" required class="form-select-custom">
                                <option value="" disabled selected hidden>Choose Semester</option>
                                <option value="1st Semester">1st Semester</option>
                                <option value="2nd Semester">2nd Semester</option>
                                <option value="Summer" id="summerOption" style="display: none;">Summer</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Subject <span class="required-star">*</span></label>
                    <div class="custom-select-wrapper">
                        <select name="subject_id" id="subjectSelect" required disabled class="form-select-custom">
                            <option value="" disabled selected hidden>-- Select Subject --</option>
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
                    <button
                        id="submitButton"
                        type="submit"
                        class="btn-submit-subj">
                        Save to Curriculum
                    </button>

                    <button
                        type="button"
                        id="cancelEditButton"
                        class="btn-cancel-edit"
                        style="display:none;"
                        onclick="cancelEdit()">
                        Cancel
                    </button>
                </div>
            </form>
        </div>

        <div class="subject-list-card table-panel">
            <div class="card-header-flex">
                <div class="card-title-container">
                    <h3 class="card-title-list">Curriculum Masterlist</h3>
                </div>

                <div class="header-controls-flex">
                    <div class="custom-select-wrapper" style="width: 140px;">
                        <select id="yearFilterSelect" class="form-select-custom" style="padding: 6px 30px 6px 12px; font-size: 12.5px;">
                            <option value="">All Years</option>
                            <option value="1st Year">1st Year</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option>
                            <option value="4th Year">4th Year</option>
                        </select>
                    </div>

                    <div class="table-search-box">
                        <svg class="search-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/>
                            <line x1="16.2" y1="16.2" x2="21" y2="21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <input type="text" id="tableSearchInput" placeholder="Search course or subject...">
                    </div>
                    <span class="badge-count" id="curriculumTotalCount">Total: {{ count($mappings ?? []) }}</span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="minimal-table" id="curriculumTable">
                    <thead>
                        <tr>
                            <th style="width: 130px;">Course</th>
                            <th style="width: 120px;">Year Level</th>
                            <th style="width: 150px;">Semester</th>
                            <th>Subject</th>
                            <th style="text-align: center; width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($mappings ?? [] as $map)
                            <tr class="curriculum-row-item" data-year="{{ trim($map->year_level) }}">
                                <td><span class="course-code-badge search-course">{{ $map->course }}</span></td>
                                <td><strong class="search-year">{{ $map->year_level }}</strong></td>
                                <td><span class="sem-badge search-sem">{{ $map->semester }}</span></td>
                                <td>
                                    <div class="subject-inline-flex">
                                        <span class="subject-code-tag search-subj-code">{{ $map->subject->subject_code ?? 'N/A' }}</span>
                                        <span class="table-subtext-title search-subj-title">{{ $map->subject->subject_title ?? 'Deleted Subject' }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button
                                            type="button"
                                            class="btn-action-edit"
                                            onclick="editCurriculum(
                                                {{ $map->id }},
                                                '{{ $map->course }}',
                                                '{{ $map->year_level }}',
                                                '{{ $map->semester }}',
                                                {{ $map->subject_id }}
                                            )">
                                            Edit
                                        </button>

                                        <button
                                            type="button"
                                            class="btn-action-delete"
                                            onclick="openDeleteCurriculum(
                                            {{ $map->id }},
                                            '{{ $map->course }}',
                                            '{{ $map->subject->subject_code ?? 'N/A' }}'
                                            )">
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr class="static-empty-row">
                                <td colspan="5" class="empty-table-state">
                                     There are currently no subject links assigned to the courses.
                                </td>
                            </tr>
                        @endforelse
                        <tr id="curriculumNoResults" style="display:none;">
                            <td colspan="5" class="empty-table-state" style="text-align: center; color: #94a3b8; padding: 40px 0;">
                                <i class="fas fa-database" style="font-size: 20px; display: block; margin-bottom: 8px;"></i>
                                No curriculum entries match your search/filter query.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<div id="deleteCurriculumModal" class="modal-overlay" style="display:none;">
    <div class="modal-box modal-box-sm">
        <div class="modal-header modal-header-danger">
            <h3><i class="fas fa-exclamation-triangle"></i> Confirm Removal</h3>
            <button class="modal-close" onclick="closeModal('deleteCurriculumModal')">&times;</button>
        </div>
        <div class="modal-body-text">
            <p>Are you sure you want to remove subject <strong id="deleteCurriculumSubject"></strong> from course <strong id="deleteCurriculumCourse"></strong>? This action cannot be undone.</p>
        </div>
        <form id="deleteCurriculumForm" method="POST">
            @csrf
            @method('DELETE')
            <div class="modal-actions">
                <button type="button" class="btn-modal-cancel" onclick="closeModal('deleteCurriculumModal')">Cancel</button>
                <button type="submit" class="btn-modal-confirm-delete">Yes, Remove</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const existingMappings = @json($mappings ?? []);

        const searchInput = document.getElementById('tableSearchInput');
        const yearFilterSelect = document.getElementById('yearFilterSelect');
        const tableRows   = document.querySelectorAll('.curriculum-row-item');
        const noResults   = document.getElementById('curriculumNoResults');
        const totalCountBadge = document.getElementById('curriculumTotalCount');
        const initialTotal = tableRows.length;

        function filterTable() {
            const term = searchInput ? searchInput.value.toLowerCase().trim() : '';
            const selectedYear = yearFilterSelect ? yearFilterSelect.value : '';
            let visibleCount = 0;

            tableRows.forEach(row => {
                const rowYear = row.getAttribute('data-year');
                const course = row.querySelector('.search-course').textContent.toLowerCase();
                const year = row.querySelector('.search-year').textContent.toLowerCase();
                const sem = row.querySelector('.search-sem').textContent.toLowerCase();
                const code = row.querySelector('.search-subj-code').textContent.toLowerCase();
                const title = row.querySelector('.search-subj-title').textContent.toLowerCase();

                const matchesYear = (selectedYear === '' || rowYear === selectedYear);
                const matchesSearch = (course.includes(term) || year.includes(term) || sem.includes(term) || code.includes(term) || title.includes(term));

                if (matchesYear && matchesSearch) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (totalCountBadge) {
                totalCountBadge.textContent = (term === '' && selectedYear === '') ? `Total: ${initialTotal}` : `Total: ${visibleCount}`;
            }

            if (noResults) {
                noResults.style.display = (visibleCount === 0 && initialTotal > 0) ? 'table-row' : 'none';
            }
        }

        if (searchInput) {
            searchInput.addEventListener('input', filterTable);
        }

        if (yearFilterSelect) {
            yearFilterSelect.addEventListener('change', filterTable);
        }

        const courseSelect   = document.getElementById('courseSelect');
        const yearSelect     = document.getElementById('yearSelect');
        const semesterSelect = document.getElementById('semesterSelect');
        const summerOption   = document.getElementById('summerOption');
        const subjectSelect  = document.getElementById('subjectSelect');
        const curriculumForm = document.getElementById('curriculumForm');
        const jsAlertError = document.getElementById('js-alert-error');
        const jsAlertList = document.getElementById('js-alert-list');

        function toggleSummerOption() {
            if (!yearSelect || !summerOption || !semesterSelect) return;

            if (yearSelect.value === '3rd Year') {
                summerOption.style.display = '';
            } else {
                summerOption.style.display = 'none';
                if (semesterSelect.value === 'Summer') {
                    semesterSelect.value = '';
                }
            }
        }

        if (yearSelect) {
            yearSelect.addEventListener('change', function () {
                toggleSummerOption();
                fetchFilteredSubjects();
            });
        }

        if (courseSelect) courseSelect.addEventListener('change', fetchFilteredSubjects);
        if (semesterSelect) semesterSelect.addEventListener('change', fetchFilteredSubjects);

        toggleSummerOption();

        if (curriculumForm) {
            curriculumForm.addEventListener('submit', function (e) {
                const courseVal = courseSelect.value;
                const yearVal = yearSelect.value;
                const semVal = semesterSelect.value;
                const subjVal = subjectSelect.value;
                const currId = document.getElementById('curriculumId').value;

                if (jsAlertError) jsAlertError.style.display = 'none';

                let errorMessages = [];

                if (!courseVal || !yearVal || !semVal || !subjVal) {
                    errorMessages.push('Please fill out all required fields before submitting.');
                } else {
                    const isDuplicate = existingMappings.some(m => 
                        m.course === courseVal && 
                        m.year_level === yearVal && 
                        m.semester === semVal && 
                        m.subject_id == subjVal && 
                        (!currId || m.id != currId)
                    );

                    if (isDuplicate) {
                        errorMessages.push('This subject is already added to this course, year level, and semester.');
                    }
                }

                if (errorMessages.length > 0) {
                    e.preventDefault();
                    showJsErrors(errorMessages);
                    return false;
                }
            });
        }

        function showJsErrors(messages) {
            if (jsAlertError && jsAlertList) {
                jsAlertList.innerHTML = '';
                messages.forEach(msg => {
                    const li = document.createElement('li');
                    li.textContent = msg;
                    jsAlertList.appendChild(li);
                });
                jsAlertError.style.display = 'flex';
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        }
    });

    const courseSelect   = document.getElementById('courseSelect');
    const yearSelect     = document.getElementById('yearSelect');
    const semesterSelect = document.getElementById('semesterSelect');
    const subjectSelect  = document.getElementById('subjectSelect');

    function fetchFilteredSubjects() {
        const courseCode = courseSelect.value;
        const yearLevel = yearSelect.value;
        const semester = semesterSelect.value;

        if (courseCode && yearLevel && semester) {
            subjectSelect.disabled = true;
            subjectSelect.innerHTML = '<option value="" disabled selected hidden>Loading matched subjects...</option>';
            const curriculumId = document.getElementById('curriculumId').value;

            const url = "{{ route('system_admin.curriculum.get_subjects') }}" +
                `?course=${encodeURIComponent(courseCode)}` +
                `&year_level=${encodeURIComponent(yearLevel)}` +
                `&semester=${encodeURIComponent(semester)}` +
                `&curriculum_id=${curriculumId}`;

            fetch(url)
                .then(response => {
                    if (!response.ok) throw new Error('Network error');
                    return response.json();
                })
                .then(subjects => {
                    subjectSelect.innerHTML = '<option value="" disabled selected hidden>Choose Subject Catalog Entry</option>';

                    if (subjects.length === 0) {
                        subjectSelect.innerHTML = '<option value="" disabled selected hidden>No subjects available for this setup</option>';
                        subjectSelect.disabled = true;
                        return;
                    }

                    subjects.forEach(subject => {
                        const option = document.createElement('option');
                        option.value = subject.id;
                        option.textContent = `[${subject.subject_code}] — ${subject.subject_title} (Units: ${subject.total_units || 'N/A'})`;
                        subjectSelect.appendChild(option);
                    });

                    subjectSelect.disabled = false;
                })
                .catch(error => {
                    console.error(error);
                    subjectSelect.innerHTML = '<option value="" disabled selected hidden>Error loading data.</option>';
                    subjectSelect.disabled = true;
                });
        } else {
            subjectSelect.innerHTML = '<option value="" disabled selected hidden>Please fill out Course, Year, and Semester first...</option>';
            subjectSelect.disabled = true;
        }
    }

    function openDeleteCurriculum(id, course, subjectCode) {
        document.getElementById('deleteCurriculumCourse').textContent  = course;
        document.getElementById('deleteCurriculumSubject').textContent = subjectCode;
        let deleteUrl = "{{ route('system_admin.curriculum.destroy', ':id') }}";
        deleteUrl = deleteUrl.replace(':id', id);
        document.getElementById('deleteCurriculumForm').action = deleteUrl;

        document.getElementById('deleteCurriculumModal').style.display = 'flex';
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function(e) {
            if (e.target === this) closeModal(this.id);
        });
    });

    function editCurriculum(id, course, year, semester, subjectId) {
        document.getElementById('curriculumId').value = id;
        document.getElementById('courseSelect').value = course;
        document.getElementById('yearSelect').value = year;

        const summerOption = document.getElementById('summerOption');
        if (summerOption) {
            summerOption.style.display = (year === '3rd Year') ? '' : 'none';
        }

        document.getElementById('semesterSelect').value = semester;

        fetchFilteredSubjects();

        setTimeout(() => {
            document.getElementById('subjectSelect').value = subjectId;
        }, 500);

        const form = document.getElementById('curriculumForm');
        form.action = "{{ route('system_admin.curriculum.update', ':id') }}".replace(':id', id);

        if (!document.getElementById('_method')) {
            const method = document.createElement('input');
            method.type = 'hidden';
            method.name = '_method';
            method.value = 'PUT';
            method.id = '_method';
            form.appendChild(method);
        }
        document.getElementById('submitButton').textContent = "Update Curriculum";
        document.getElementById('cancelEditButton').style.display = "inline-block";

        window.scrollTo({ top: 0, behavior: "smooth" });
    }

    function cancelEdit() {
        const form = document.getElementById('curriculumForm');
        form.reset();

        const summerOption = document.getElementById('summerOption');
        if (summerOption) {
            summerOption.style.display = 'none';
        }

        document.getElementById('curriculumId').value = "";
        document.getElementById('subjectSelect').innerHTML = '<option value="" disabled selected hidden>Please fill out Course, Year, and Semester first...</option>';
        document.getElementById('subjectSelect').disabled = true;

        form.action = "{{ route('system_admin.curriculum.store') }}";
        const method = document.getElementById('_method');

        if(method){
            method.remove();
        }

        document.getElementById('submitButton').textContent = "Save to Curriculum";
        document.getElementById('cancelEditButton').style.display = "none";
    }
</script>

<style>
    .subj-container { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #0f172a; max-width: 1650px; margin: 0 auto; padding: 20px 24px; }
    .subj-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 2px solid #e2e8f0; padding-bottom: 18px; }
    .header-titles h2 { margin: 0 0 4px 0; font-size: 26px; font-weight: 800; color: #0f172a; letter-spacing: -0.5px; }
    .header-titles p { margin: 0; font-size: 14px; color: #64748b; }

    .required-star { color: #dc2626; font-weight: 700; }

    .curr-mapping-workspace { display: flex; flex-direction: row; gap: 24px; align-items: flex-start; justify-content: space-between; width: 100%; }
    .form-panel { flex: 0 0 440px; background: #ffffff; }
    .table-panel { flex: 1; background: #ffffff; min-width: 0; }

    .subj-card { border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 10px -2px rgba(0,0,0,0.03); overflow: hidden; }
    .card-bar { background: #f8fafc; padding: 16px 20px; font-weight: 700; font-size: 14.5px; color: #1e293b; border-bottom: 1px solid #e2e8f0; }

    .card-header-flex { background-color: #f8fafc; padding: 16px 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; }
    .card-title-container { display: flex; align-items: center; gap: 12px; }
    .card-title-list { font-size: 14.5px; font-weight: 700; color: #1e293b; margin: 0; }
    .header-controls-flex { display: flex; align-items: center; gap: 12px; }

    .table-search-box { position: relative; display: flex; align-items: center; }
    .table-search-box input { border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 12px 6px 32px; font-size: 12.5px; color: #0f172a; outline: none; background: #ffffff; width: 200px; transition: all 0.2s ease; font-weight: 500; box-sizing: border-box; }
    .table-search-box input:focus { border-color: #1e2a5a; width: 230px; box-shadow: 0 0 0 3px rgba(30, 42, 90, 0.08); }
    .table-search-box .search-icon { position: absolute; left: 12px; color: #94a3b8; width: 14px; height: 14px; pointer-events: none; }

    .pure-form { padding: 24px; display: flex; flex-direction: column; gap: 16px; }
    .form-group { display: flex; flex-direction: column; gap: 6px; }
    .form-group label { font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase; letter-spacing: 0.3px; }

    .custom-select-wrapper { position: relative; width: 100%; }
    .form-select-custom { width: 100%; padding: 10px 40px 10px 14px; font-size: 14px; color: #0f172a; background-color: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; appearance: none; -webkit-appearance: none; cursor: pointer; outline: none; transition: all 0.2s ease; }
    .form-select-custom:disabled { background-color: #f8fafc; color: #94a3b8; cursor: not-allowed; border-color: #e2e8f0; }
    .form-select-custom:hover:not(:disabled) { border-color: #94a3b8; }
    .form-select-custom:focus:not(:disabled) { border-color: #1e2a5a; box-shadow: 0 0 0 3px rgba(30, 42, 90, 0.08); }
    .custom-select-wrapper::after { content: "▼"; font-size: 0.65rem; color: #64748b; position: absolute; right: 14px; top: 50%; transform: translateY(-50%); pointer-events: none; }

    .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    
    .form-actions-bar { display: flex; gap: 10px; margin-top: 12px; border-top: 1px solid #f1f5f9; padding-top: 16px; }
    .btn-submit-subj { background: #1e2a5a; color: white; border: none; padding: 12px 20px; font-size: 14px; font-weight: 700; border-radius: 6px; cursor: pointer; flex: 1; transition: background 0.2s; }
    .btn-submit-subj:hover { background: #152238; }

    .alert-success-custom { background: #ecfdf5; color: #065f46; padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; font-weight: 600; border-left: 5px solid #059669; font-size: 14px; display: flex; align-items: center; gap: 10px; }
    .alert-danger-custom { background: #fef2f2; color: #991b1b; padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; font-weight: 600; border-left: 5px solid #dc2626; font-size: 14px; display: flex; align-items: flex-start; gap: 10px; }

    .subject-list-card { background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 4px 10px -2px rgba(0,0,0,0.03); overflow: hidden; }
    
    .badge-count { background: #e2e8f0; color: #334155; font-size: 11.5px; padding: 4px 12px; border-radius: 20px; font-weight: 700; white-space: nowrap; }

    .table-responsive { width: 100%; overflow-x: auto; }
    .minimal-table { width: 100%; border-collapse: collapse; font-size: 13.5px; table-layout: auto; }
    .minimal-table th { background-color: #f8fafc; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 11.5px; padding: 14px 16px; border-bottom: 1px solid #e2e8f0; text-align: left; white-space: nowrap; }
    .minimal-table td { padding: 15px 16px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
    .minimal-table tbody tr:hover { background-color: #f8fafc; }

    .course-code-badge { font-family: ui-monospace, SFMono-Regular, Consolas, monospace; font-weight: 700; color: #0f172a; background-color: #f1f5f9; border: 1px solid #cbd5e1; padding: 4px 8px; border-radius: 6px; font-size: 12px; display: inline-block; white-space: nowrap; }
    .subject-inline-flex { display: flex; align-items: center; gap: 10px; white-space: nowrap; }
    .subject-code-tag { font-family: ui-monospace, SFMono-Regular, Consolas, monospace; font-weight: 700; color: #0f172a; background-color: #f1f5f9; border: 1px solid #cbd5e1; padding: 4px 8px; border-radius: 6px; font-size: 12px; display: inline-block; white-space: nowrap; }
    .table-subtext-title { font-weight: 700; color: #1e293b; font-size: 14px; white-space: nowrap; }
    .sem-badge{ display:inline-block; white-space:nowrap; font-size:12px; background:#f1f5f9; color:#334155; padding:4px 10px; border-radius:6px; border:1px solid #cbd5e1; font-weight:600; }
    .empty-table-state { text-align: center; color: #94a3b8; padding: 50px 0 !important; font-style: italic; }

    .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.45); z-index: 9999; display: flex; align-items: center; justify-content: center; }
    .modal-box { background: #ffffff; border-radius: 14px; width: 100%; max-width: 500px; box-shadow: 0 20px 60px rgba(0,0,0,0.2); overflow: hidden; }
    .modal-box-sm { max-width: 420px; }
    .modal-header { display: flex; justify-content: space-between; align-items: center; padding: 18px 24px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
    .modal-header h3 { margin: 0; font-size: 15px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 8px; }
    .modal-header-danger { background: #fef2f2; border-bottom-color: #fecaca; }
    .modal-header-danger h3 { color: #dc2626; }
    .modal-close { background: none; border: none; font-size: 20px; color: #94a3b8; cursor: pointer; padding: 0; line-height: 1; }
    .modal-close:hover { color: #0f172a; }
    .modal-body-text { padding: 24px 24px 0 24px; font-size: 14px; color: #334155; line-height: 1.6; }
    .modal-actions { display: flex; justify-content: flex-end; gap: 10px; padding: 16px 24px; border-top: 1px solid #f1f5f9; }
    .btn-modal-cancel { background: transparent; border: 1px solid #cbd5e1; color: #64748b; padding: 9px 18px; font-size: 13px; font-weight: 600; border-radius: 6px; cursor: pointer; }
    .btn-modal-cancel:hover { background: #f8fafc; }
    .btn-modal-confirm-delete { background: #dc2626; color: #ffffff; border: none; padding: 9px 20px; font-size: 13px; font-weight: 700; border-radius: 6px; cursor: pointer; }
    .btn-modal-confirm-delete:hover { background: #b91c1c; }

    .btn-action-edit,
    .btn-action-delete{ display:inline-flex; align-items:center; justify-content:center; font-size:12.5px; font-weight:600; border-radius:6px; cursor:pointer; transition:.2s; white-space: nowrap; padding: 5px 12px; }

    .btn-action-edit{ background-color: transparent; border: 1px solid #cbd5e1; color: #475569; }
    .btn-action-edit:hover{ background-color: #f1f5f9; color: #1e293b; border-color: #94a3b8; }

    .btn-action-delete{ background-color: transparent; border: 1px solid #fecaca; color: #dc2626; }
    .btn-action-delete:hover{ background-color: #fef2f2; color: #991b1b; border-color: #f87171; }

    .btn-cancel-edit{ background:#f8fafc; color:#475569; border:1px solid #cbd5e1; padding:12px 16px; border-radius:6px; cursor:pointer; font-weight:600; transition:.2s; }
    .btn-cancel-edit:hover{ background:#f1f5f9; color:#1e293b; }

    .action-buttons{ display:flex; justify-content:center; align-items:center; gap:6px; }
</style>
@endsection