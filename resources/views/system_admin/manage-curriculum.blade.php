@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="rg-page">

    <header class="rg-header">
        <div>
            <h2 class="rg-title">Curriculum Management</h2>
            <p class="rg-subtitle">Add and manage subjects assigned to course curriculums.</p>
        </div>
    </header>

    @if(session('success'))
        <div class="rg-alert rg-alert-success" role="status">
            <i class="fas fa-check-circle"></i> <span>{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="rg-alert rg-alert-error" role="alert">
            <i class="fas fa-exclamation-circle"></i>
            <div>
                <strong style="display:block; margin-bottom: 4px;">Unable to process request:</strong>
                <ul style="margin: 0; padding-left: 20px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div id="js-alert-error" class="rg-alert rg-alert-error" role="alert" style="display: none;">
        <i class="fas fa-exclamation-triangle"></i>
        <div>
            <strong style="display:block; margin-bottom: 4px;">Unable to process request:</strong>
            <ul id="js-alert-list" style="margin: 0; padding-left: 20px;"></ul>
        </div>
    </div>

    <div class="admin-workspace">

        <div class="rg-card form-panel">
            <div class="card-bar">
                <span id="form-panel-title"><i class="fas fa-network-wired"></i> Assign Subject to Curriculum</span>
            </div>

            <form id="curriculumForm" action="{{ route('system_admin.curriculum.store') }}" method="POST" autocomplete="off" class="pure-form" novalidate>
                @csrf
                <input type="hidden" id="curriculumId" name="curriculum_id">

                <div class="form-group">
                    <label>Course Program <span class="required-star">*</span></label>
                    <select name="course" id="courseSelect" required class="rg-select">
                        <option value="" disabled selected hidden>Choose Course</option>
                        @foreach($courses ?? [] as $course)
                            <option value="{{ $course->course_code }}">{{ $course->course_code }} - {{$course->course_title }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Year Level <span class="required-star">*</span></label>
                        <select name="year_level" id="yearSelect" required class="rg-select">
                            <option value="" disabled selected hidden>Choose Year</option>
                            <option value="1st Year">1st Year</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option>
                            <option value="4th Year">4th Year</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Semester <span class="required-star">*</span></label>
                        <select name="semester" id="semesterSelect" required class="rg-select">
                            <option value="" disabled selected hidden>Choose Semester</option>
                            <option value="1st Semester">1st Semester</option>
                            <option value="2nd Semester">2nd Semester</option>
                            <option value="Summer" id="summerOption" style="display: none;">Summer</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Subject <span class="required-star">*</span></label>
                    <select name="subject_id" id="subjectSelect" required disabled class="rg-select">
                        <option value="" disabled selected hidden>-- Select Subject --</option>
                        @if(isset($subjects) &&$subjects->count() > 0)
                            @foreach($subjects as $subj)
                                <option value="{{ $subj->id }}">
                                    [{{ strtoupper($subj->subject_code) }}] — {{$subj->subject_title }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <div class="form-actions-bar">
                    <button type="button" id="cancelEditButton" class="rg-btn rg-btn-ghost" style="display:none;" onclick="cancelEdit()">Cancel</button>
                    <button type="submit" id="submitButton" class="rg-btn rg-btn-primary" style="flex: 1; justify-content: center;">Save to Curriculum</button>
                </div>
            </form>
        </div>

        <div class="rg-card table-panel">
            <div class="card-bar">
                <span style="font-size: 15px;"><i class="fa-solid fa-folder-open" style="margin-right: 6px;"></i> Curriculum Masterlist</span>
            </div>

            <div class="rg-toolbar">
                <div class="rg-search">
                    <i class="fas fa-search"></i>
                    <input type="text" id="tableSearchInput" placeholder="Search course or subject...">
                </div>

                <div class="rg-filter" role="group" aria-label="Filter by year level">
                    <span class="rg-filter-label">Year Level</span>
                    <button type="button" class="filter-pill active" data-filter-value="">All</button>
                    <button type="button" class="filter-pill" data-filter-value="1st Year">1st</button>
                    <button type="button" class="filter-pill" data-filter-value="2nd Year">2nd</button>
                    <button type="button" class="filter-pill" data-filter-value="3rd Year">3rd</button>
                    <button type="button" class="filter-pill" data-filter-value="4th Year">4th</button>
                </div>

                <span class="rg-result-count" id="curriculumTotalCount">Total: {{ count($mappings ?? []) }}</span>
            </div>

            <div class="rg-table-wrap">
                <table class="rg-table" id="curriculumTable">
                    <thead>
                        <tr>
                            <th style="width: 130px;">Course</th>
                            <th style="width: 140px;">Year & Sem</th>
                            <th>Subject</th>
                            <th class="rg-text-center" style="width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($mappings ?? [] as $map)
                            <tr class="curriculum-row-item" data-year="{{ trim($map->year_level) }}">
                                <td><span class="student-id-tag search-course">{{ $map->course }}</span></td>
                                <td>
                                    <span class="rg-chip rg-chip-plain search-year-sem">
                                        {{ $map->year_level }} • {{$map->semester }}
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <span class="student-id-tag search-subj-code">{{ $map->subject->subject_code ?? 'N/A' }}</span>
                                        <span style="font-weight: 700; color: var(--rg-blue-dark);" class="search-subj-title">{{ $map->subject->subject_title ?? 'Deleted Subject' }}</span>
                                    </div>
                                </td>
                                <td class="rg-text-center">
                                    <div class="action-btn-group">
                                        <button type="button" class="rg-btn-action rg-btn-action-ghost"
                                            onclick="editCurriculum(
                                                {{ $map->id }},
                                                '{{ $map->course }}',
                                                '{{ $map->year_level }}',
                                                '{{ $map->semester }}',
                                                {{ $map->subject_id }}
                                            )">
                                            Edit
                                        </button>
                                        <button type="button" class="rg-btn-action rg-btn-action-danger"
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
                                <td colspan="4" class="empty-table-state">
                                     There are currently no subject links assigned to the courses.
                                </td>
                            </tr>
                        @endforelse
                        <tr id="curriculumNoResults" style="display:none;">
                            <td colspan="4" class="empty-table-state" style="text-align: center; color: var(--rg-muted); padding: 40px 0;">
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

<div id="deleteCurriculumModal" class="rg-modal-overlay" style="display:none;">
    <div class="rg-modal-card" style="max-width: 420px;">
        <div class="rg-modal-header" style="background: #FEF2F2; color: #DC2626; border-bottom: 1px solid #FECACA;">
            <h3><i class="fas fa-exclamation-triangle"></i> Confirm Removal</h3>
            <button type="button" class="rg-modal-close" onclick="closeModal('deleteCurriculumModal')" style="color: #F87171;">&times;</button>
        </div>
        <div class="rg-modal-body" style="padding: 24px;">
            <p style="margin: 0 0 20px; font-size: 14px; line-height: 1.5; color: var(--rg-ink);">Are you sure you want to remove subject <strong id="deleteCurriculumSubject"></strong> from course <strong id="deleteCurriculumCourse"></strong>? This action cannot be undone.</p>
            <form id="deleteCurriculumForm" method="POST">
                @csrf
                @method('DELETE')
                <div style="display: flex; justify-content: flex-end; gap: 10px; padding-top: 16px; border-top: 1px solid var(--rg-line);">
                    <button type="button" class="rg-btn rg-btn-ghost" onclick="closeModal('deleteCurriculumModal')">Cancel</button>
                    <button type="submit" class="rg-btn" style="background: #DC2626; color: #fff;">Yes, Remove</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const existingMappings = @json($mappings ?? []);

        const searchInput = document.getElementById('tableSearchInput');
        const filterPills = document.querySelectorAll('.filter-pill');
        const tableRows   = document.querySelectorAll('.curriculum-row-item');
        const noResults   = document.getElementById('curriculumNoResults');
        const totalCountBadge = document.getElementById('curriculumTotalCount');
        const initialTotal = tableRows.length;
        let activeYear = '';

        function filterTable() {
            const term = searchInput ? searchInput.value.toLowerCase().trim() : '';
            let visibleCount = 0;

            tableRows.forEach(row => {
                const rowYear = row.getAttribute('data-year');
                const course = row.querySelector('.search-course').textContent.toLowerCase();
                const yearSem = row.querySelector('.search-year-sem').textContent.toLowerCase();
                const code = row.querySelector('.search-subj-code').textContent.toLowerCase();
                const title = row.querySelector('.search-subj-title').textContent.toLowerCase();

                const matchesYear = (activeYear === '' || rowYear === activeYear);
                const matchesSearch = (course.includes(term) || yearSem.includes(term) || code.includes(term) || title.includes(term));

                if (matchesYear && matchesSearch) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (totalCountBadge) {
                totalCountBadge.textContent = visibleCount + (visibleCount === 1 ? ' entry' : ' entries');
            }

            if (noResults) {
                noResults.style.display = (visibleCount === 0 && initialTotal > 0) ? 'table-row' : 'none';
            }
        }

        if (searchInput) searchInput.addEventListener('input', filterTable);

        filterPills.forEach(pill => {
            pill.addEventListener('click', function () {
                filterPills.forEach(p => p.classList.remove('active'));
                this.classList.add('active');
                activeYear = this.getAttribute('data-filter-value');
                filterTable();
            });
        });

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

    document.querySelectorAll('.rg-modal-overlay').forEach(overlay => {
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
        document.getElementById('cancelEditButton').style.display = "inline-flex";

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
    .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    
    .required-star { color: var(--rg-red); }
    
    .rg-select, .rg-input {
        width: 100%; box-sizing: border-box; height: 40px; padding: 0 14px;
        font-size: 14px; font-weight: 500; color: var(--rg-ink); background: #fff;
        border: 1px solid #CBD5E6; border-radius: 6px; outline: none; transition: border-color 0.2s, box-shadow 0.2s;
    }
    .rg-select:focus, .rg-input:focus { border-color: var(--rg-blue); box-shadow: 0 0 0 3px rgba(0, 51, 160, 0.14); }
    .rg-select {
        padding-right: 36px; appearance: none; -webkit-appearance: none; cursor: pointer;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='2' stroke='%235C6B8A'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7' /%3E%3C/svg%3E");
        background-repeat: no-repeat; background-position: right 12px center; background-size: 16px;
    }
    .rg-select:disabled { background-color: var(--rg-surface); color: var(--rg-muted); cursor: not-allowed; }

    .form-actions-bar { display: flex; gap: 10px; margin-top: 10px; border-top: 1px solid var(--rg-line); padding-top: 20px; }
    
    .rg-btn { display: inline-flex; align-items: center; gap: 7px; padding: 10px 18px; border-radius: 6px; font-size: 13.5px; font-weight: 700; text-decoration: none; cursor: pointer; border: 1px solid transparent; transition: background .15s, color .15s, border-color .15s; white-space: nowrap; line-height: 1.2; }
    .rg-btn-primary { background: var(--rg-blue); color: #fff; }
    .rg-btn-primary:hover { background: var(--rg-blue-dark); color: #fff; }
    .rg-btn-ghost { background: transparent; color: var(--rg-muted); border-color: #CBD5E6; }
    .rg-btn-ghost:hover { background: var(--rg-surface); color: var(--rg-ink); }

    .rg-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 14px 18px; padding: 14px 20px; border-bottom: 1px solid var(--rg-line); background: #fff; }
    .rg-search { position: relative; flex: 1 1 260px; max-width: 380px; }
    .rg-search i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #8A97B3; font-size: 13px; pointer-events: none; }
    .rg-search input { width: 100%; box-sizing: border-box; padding: 10px 12px 10px 34px; font-size: 13.5px; color: var(--rg-ink); background: #fff; border: 1px solid #CBD5E6; border-radius: 8px; outline: none; }
    .rg-search input:focus { border-color: var(--rg-blue); box-shadow: 0 0 0 3px rgba(0, 51, 160, 0.14); }

    .rg-filter { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
    .rg-filter-label { font-size: 12.5px; font-weight: 600; color: var(--rg-muted); margin-right: 2px; }
    .filter-pill { background: #fff; border: 1px solid #CBD5E6; color: var(--rg-muted); padding: 5px 12px; font-size: 12.5px; font-weight: 600; border-radius: 16px; cursor: pointer; transition: background .15s, color .15s, border-color .15s; }
    .filter-pill:hover { border-color: var(--rg-blue); color: var(--rg-blue); }
    .filter-pill.active { background: var(--rg-blue); border-color: var(--rg-blue); color: #fff; }

    .rg-result-count { margin-left: auto; font-size: 12.5px; font-weight: 600; color: var(--rg-muted); }

    .rg-table-wrap { overflow-x: auto; width: 100%; }
    .rg-table { width: 100%; min-width: 650px; border-collapse: collapse; font-size: 14px; }
    .rg-table th { background: var(--rg-surface); padding: 12px 18px; font-size: 11.5px; font-weight: 700; color: var(--rg-muted); text-transform: uppercase; text-align: left; border-bottom: 1px solid var(--rg-line); }
    .rg-table td { padding: 15px 18px; border-bottom: 1px solid #EEF2F9; color: var(--rg-ink); vertical-align: middle; }
    .rg-table tbody tr:last-child td { border-bottom: none; }
    .rg-table tbody tr:hover { background: #F5F8FF; }
    .rg-text-center { text-align: center !important; }

    .student-id-tag { font-family: ui-monospace, monospace; font-weight: 700; color: var(--rg-blue); background: var(--rg-blue-tint); padding: 3px 9px; border-radius: 6px; font-size: 12.5px; border: 1px solid #CFDBFB; display: inline-block; }
    .rg-chip { display: inline-block; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; white-space: nowrap; border: 1px solid transparent; }
    .rg-chip-plain { background: var(--rg-surface); color: #3B4A6B; border: 1px solid var(--rg-line); }

    .action-btn-group { display: flex; gap: 6px; justify-content: center; }
    .rg-btn-action { border: 1px solid transparent; padding: 6px 12px; font-size: 12px; font-weight: 600; border-radius: 6px; cursor: pointer; transition: 0.15s; }
    .rg-btn-action-ghost { background: transparent; border-color: #CBD5E6; color: var(--rg-muted); }
    .rg-btn-action-ghost:hover { background: var(--rg-surface); color: var(--rg-ink); }
    .rg-btn-action-danger { background: transparent; border-color: #FECACA; color: var(--rg-red); }
    .rg-btn-action-danger:hover { background: #FEF2F2; color: #991B1B; border-color: #F87171; }

    .empty-table-state { text-align: center; color: var(--rg-muted) !important; padding: 50px 0 !important; font-style: italic; }

    .rg-modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(11, 27, 58, 0.6); z-index: 1000; display: flex; justify-content: center; align-items: center; padding: 20px; box-sizing: border-box; }
    .rg-modal-card { background: #fff; width: 100%; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.15); overflow: hidden; animation: fadeIn 0.2s ease-in-out; }
    .rg-modal-header { display: flex; justify-content: space-between; align-items: center; padding: 18px 24px; }
    .rg-modal-header h3 { margin: 0; font-size: 16px; font-weight: 700; display: flex; align-items: center; gap: 8px; }
    .rg-modal-close { background: transparent; border: none; font-size: 24px; cursor: pointer; transition: 0.15s; padding: 0; line-height: 1; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
</style>
@endsection