@extends('layouts.app')

@section('content')
<div class="subj-container">

    <div class="subj-header">
        <div class="header-titles">
            <h2>Curriculum Management & Checklist</h2>
            <p>System Admin Console — Assign Subjects to Curriculum</p>
        </div>
        <div class="header-badge">Admin Mode</div>
    </div>

    @if(session('success'))
        <div class="alert-success-custom">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert-danger-custom">
            <i class="fas fa-exclamation-circle"></i> {{ $errors->first() }}
        </div>
    @endif

    <div class="curr-mapping-workspace">

        {{-- FORM PANEL (LEFT) --}}
        <div class="subj-card form-panel">
            <div class="card-bar">
                <span><i class="fas fa-network-wired"></i> Assign Subject to Curriculum</span>
            </div>
            <form
            id="curriculumForm"
            action="{{ route('system_admin.curriculum.store') }}"
            method="POST"
            autocomplete="off"
            class="pure-form">

            @csrf

            <input
                type="hidden"
                id="curriculumId"
                name="curriculum_id">

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
                            Cancel Edit
                        </button>
                    </div>
            </form>
        </div>

        {{-- TABLE PANEL (RIGHT) --}}
        <div class="subject-list-card table-panel">
            <div class="card-header-flex">
                <div class="card-title-container">
                    <h3 class="card-title-list">Curriculum Masterlist</h3>
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
                            <th style="text-align: center;">Actions</th>
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
                            <tr>
                                <td colspan="4" class="empty-table-state">
                                     There are currently no subject links assigned to the courses.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <div id="curriculumNoResults" style="display:none; text-align:center; color:#94a3b8; padding:40px; font-style:italic; font-size:13.5px;">
                    No curriculum entries match your search.
                </div>
            </div>
        </div>

    </div>
</div>

{{-- ===================== DELETE MODAL ===================== --}}
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
        const searchInput = document.getElementById('tableSearchInput');
        const tableRows   = document.querySelectorAll('#curriculumTable tbody tr');
        const noResults   = document.getElementById('curriculumNoResults');

        if (searchInput) {
            searchInput.addEventListener('keyup', function () {
                const term = searchInput.value.toLowerCase().trim();
                let visibleCount = 0;

                tableRows.forEach(row => {
                    if (row.querySelector('.empty-table-state')) return;

                    const match = row.textContent.toLowerCase().includes(term);

                    row.style.display = match ? '' : 'none';

                    if (match) visibleCount++;
                });

                noResults.style.display =
                    visibleCount === 0 && term !== ''
                        ? 'block'
                        : 'none';
            });
        }

        courseSelect.addEventListener('change', fetchFilteredSubjects);
        yearSelect.addEventListener('change', fetchFilteredSubjects);
        semesterSelect.addEventListener('change', fetchFilteredSubjects);
    });

            // ======== AJAX SUBJECT FILTER ========
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

                    subjectSelect.innerHTML =
                        '<option value="" disabled selected hidden>Loading matched subjects...</option>';

                    const curriculumId = document.getElementById('curriculumId').value;

                    const url =
                        "{{ route('system_admin.curriculum.get_subjects') }}" +
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

                            subjectSelect.innerHTML =
                                '<option value="" disabled selected hidden>Choose Subject Catalog Entry</option>';

                                if (subjects.length === 0) {
                                    subjectSelect.innerHTML =
                                        '<option value="" disabled selected hidden>No subjects available for this setup</option>';

                                    subjectSelect.disabled = true;
                                    return;
                                }

                            subjects.forEach(subject => {
                                const option = document.createElement('option');
                                option.value = subject.id;
                                option.textContent =
                                    `[${subject.subject_code}] — ${subject.subject_title} (Units: ${subject.total_units || 'N/A'})`;
                                subjectSelect.appendChild(option);
                            });

                            subjectSelect.disabled = false;
                        })
                        .catch(error => {
                            console.error(error);

                            subjectSelect.innerHTML =
                                '<option value="" disabled selected hidden>Error loading data.</option>';

                            subjectSelect.disabled = true;
                        });

                } else {

                    subjectSelect.innerHTML =
                        '<option value="" disabled selected hidden>Please fill out Course, Year, and Semester first...</option>';

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
    document.getElementById('semesterSelect').value = semester;

    fetchFilteredSubjects();

    setTimeout(() => {
        document.getElementById('subjectSelect').value = subjectId;
    }, 500);

    const form = document.getElementById('curriculumForm');

    form.action = "{{ route('system_admin.curriculum.update', ':id') }}"
        .replace(':id', id);

    if (!document.getElementById('_method')) {

        const method = document.createElement('input');
        method.type = 'hidden';
        method.name = '_method';
        method.value = 'PUT';
        method.id = '_method';

        form.appendChild(method);
    }
    document.getElementById('submitButton').textContent =
        "Update Curriculum";

    document.getElementById('cancelEditButton').style.display =
        "inline-block";

    window.scrollTo({
        top: 0,
        behavior: "smooth"
    });
    }

    function cancelEdit() {
    const form = document.getElementById('curriculumForm');

    form.reset();

    document.getElementById('curriculumId').value = "";
    document.getElementById('subjectSelect').innerHTML =
        '<option value="" disabled selected hidden>Please fill out Course, Year, and Semester first...</option>';
    document.getElementById('subjectSelect').disabled = true;

    form.action =
        "{{ route('system_admin.curriculum.store') }}";
    const method = document.getElementById('_method');

    if(method){
        method.remove();
    }

    document.getElementById('submitButton').textContent =
        "Establish Curriculum";

    document.getElementById('cancelEditButton').style.display =
        "none";
}

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

    .subj-card { background: white; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); overflow: hidden; }
    .card-bar { background: #ffffff; padding: 18px 24px; font-weight: 700; font-size: 15px; color: #0f172a; border-bottom: 1px solid #f1f5f9; }
    .pure-form { padding: 24px; display: flex; flex-direction: column; gap: 18px; }
    .form-group { display: flex; flex-direction: column; gap: 6px; }
    .form-group label { font-size: 13px; font-weight: 600; color: #475569; }

    .custom-select-wrapper { position: relative; width: 100%; }
    .form-select-custom { width: 100%; padding: 12px 40px 12px 15px; font-size: 14px; color: #0f172a; background-color: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; appearance: none; -webkit-appearance: none; cursor: pointer; outline: none; transition: all 0.2s ease; }
    .form-select-custom:disabled { background-color: #f8fafc; color: #94a3b8; cursor: not-allowed; border-color: #e2e8f0; }
    .form-select-custom:hover:not(:disabled) { border-color: #94a3b8; }
    .form-select-custom:focus:not(:disabled) { border-color: #0f172a; box-shadow: 0 0 0 3px rgba(15,23,42,0.15); }
    .custom-select-wrapper::after { content: "▼"; font-size: 0.65rem; color: #64748b; position: absolute; right: 15px; top: 50%; transform: translateY(-50%); pointer-events: none; }

    .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
    .form-actions-bar { display: flex; justify-content: flex-end; margin-top: 5px; }
    .btn-submit-subj { background: #0f172a; color: white; border: none; padding: 13px; font-size: 14px; font-weight: 700; border-radius: 6px; cursor: pointer; width: 100%; transition: background 0.2s; }
    .btn-submit-subj:hover { background: #1e293b; }

    .alert-success-custom { background: #d1e7dd; color: #0f5132; padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; font-weight: 600; border-left: 5px solid #0f5132; font-size: 14px; }
    .alert-danger-custom { background: #fde8e8; color: #9b1c1c; padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; font-weight: 600; border-left: 5px solid #9b1c1c; font-size: 14px; }

    .subject-list-card { background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.02); overflow: hidden; }
    .card-header-flex { background-color: #f8fafc; padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; gap: 20px; }
    .card-title-container { display: flex; align-items: center; gap: 12px; }
    .card-title-list { font-size: 15px; font-weight: 700; color: #0f172a; margin: 0; }
    .badge-count { background-color: #e2e8f0; color: #334155; font-size: 0.75rem; font-weight: 700; padding: 4px 12px; border-radius: 9999px; }

    .table-search-box { position: relative; max-width: 280px; width: 100%; }
    .table-search-box input { width: 100%; padding: 8px 12px 8px 36px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 6px; outline: none; color: #0f172a; box-sizing: border-box; transition: all 0.2s ease; }
    .table-search-box input:focus { border-color: #0f172a; box-shadow: 0 0 0 3px rgba(15,23,42,0.08); }
    .table-search-box .search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 13px; pointer-events: none; }

    .table-responsive { width: 100%; overflow-x: auto; }
    .minimal-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
    .minimal-table th { background-color: #f8fafc; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 0.75rem; padding: 14px 24px; border-bottom: 1px solid #e2e8f0; text-align: left; }
    .minimal-table td { padding: 14px 24px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
    .minimal-table tbody tr:hover { background-color: #f8fafc; }

    .course-code-badge { font-family: ui-monospace, SFMono-Regular, Consolas, monospace; font-weight: 700; color: #dc2626; background-color: #fef2f2; padding: 4px 8px; border-radius: 6px; font-size: 0.85rem; border: 1px solid #fee2e2; }
    .subject-code-tag { font-family: ui-monospace, SFMono-Regular, Consolas, monospace; font-weight: 700; color: #2563eb; background-color: #eff6ff; padding: 3px 6px; border-radius: 4px; font-size: 0.8rem; border: 1px solid #dbeafe; }
    .table-subtext-title { font-weight: 500; color: #1e293b; margin-top: 5px; font-size: 13.5px; }
    .sem-badge{ display:inline-block; white-space:nowrap; min-width:115px; text-align:center; font-size:12px; background:#f1f5f9; color:#475569; padding:5px 12px; border-radius:999px; border:1px solid #e2e8f0; font-weight:600; }
    .empty-table-state { text-align: center; color: #64748b; padding: 40px !important; font-style: italic; }

    /* ===== MODAL STYLES ===== */
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
    .btn-action-delete{ width: 82px; height: 32px; display:inline-flex; align-items:center; justify-content:center; font-size:12px; font-weight:600; border-radius:6px; cursor:pointer; transition:.2s; }

    .btn-action-edit{ background:#eff6ff; border:1px solid #93c5fd; color:#2563eb; margin-right:6px; }
    .btn-action-edit:hover{ background:#dbeafe; color:#1d4ed8; }

    .btn-action-delete{ background:#fff; border:1px solid #fecaca; color:#dc2626; }

    .btn-action-delete:hover{ background:#fef2f2; border-color:#f87171; color:#991b1b; }

    .form-actions-bar{ display:flex; gap:10px; }

    .btn-submit-subj{ flex:1; }
    .btn-cancel-edit{ background:#e2e8f0; color:#334155; border:none; padding:13px 18px; border-radius:6px; cursor:pointer; font-weight:600; transition:.2s; }
    .btn-cancel-edit:hover{ background:#cbd5e1; }

    .action-buttons{ display:flex; justify-content:center; align-items:center; gap:8px; }
</style>
@endsection
