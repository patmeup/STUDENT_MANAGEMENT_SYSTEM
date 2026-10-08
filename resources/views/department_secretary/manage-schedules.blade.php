@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="rg-page">

    <header class="rg-header">
        <div>
            <h1 class="rg-title">Department Student Registry</h1>
            <p class="rg-subtitle">View, search, and manage student records and block sections.</p>
        </div>
    </header>

    @if(session('success'))
        <div class="rg-alert rg-alert-success">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="rg-alert rg-alert-error">
            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
        </div>
    @endif

    <div class="sa-workspace">

        <div class="rg-card form-panel">
            <div class="card-bar">
                <span id="formPanelTitle"><i class="fas fa-calendar-plus"></i> Add Class Schedule</span>
            </div>
            
            <form id="scheduleMainForm" action="{{ route('department_secretary.schedules.store') }}" method="POST" autocomplete="off" class="pure-form">
                @csrf
                
                <div id="methodContainer"></div>

                <div id="formValidationAlert" class="rg-alert-error" style="display: none; margin-bottom: 15px;">
                    <i class="fas fa-exclamation-triangle"></i> Please fill out all required fields.
                </div>

                <div class="form-group" id="subjectSelectGroup">
                    <label>Select Subject</label>
                    <select name="subject_id" id="formSubjectId" class="select-input custom-validate">
                        <option value="">-- Select Cataloged Subject --</option>
                        @foreach($subjects as $subj)
                            <option value="{{ $subj->id }}">
                                [{{ strtoupper($subj->subject_code) }}] — {{ $subj->subject_title }} 
                                ({{ $subj->year_level }}, {{ $subj->semester }})
                            </option>
                        @endforeach
                    </select>
                    <small class="field-help">Choose a subject from the curriculum to create a schedule.</small>
                </div>

                <div class="form-group" id="subjectReadOnlyGroup" style="display: none;">
                    <label>Selected Subject</label>
                    <input type="text" id="formSubjectReadOnly" class="select-input" style="background-color: var(--rg-surface); color: var(--rg-muted);" readonly>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Schedule Type</label>
                        <select name="schedule_type" id="formScheduleType" class="select-input custom-validate">
                            <option value="">-- Select Type --</option>
                            <option value="LEC">Lecture (LEC)</option>
                            <option value="LAB">Laboratory (LAB)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Class Section / Block Name</label>
                        <input type="text" name="section_name" id="formSectionName" list="sectionSuggestions" placeholder="E.G., BSIT 2-A" class="select-input uppercase-input custom-validate">
                        <datalist id="sectionSuggestions">
                            @foreach(($sections ?? []) as $sectionOption)
                                <option value="{{ $sectionOption }}"></option>
                            @endforeach
                        </datalist>
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Class Days</label>
                        <select name="class_days" id="formClassDays" class="select-input custom-validate">
                            <option value="">-- Select Days --</option>
                            <option value="Monday">Monday</option>
                            <option value="Tuesday">Tuesday</option>
                            <option value="Wednesday">Wednesday</option>
                            <option value="Thursday">Thursday</option>
                            <option value="Friday">Friday</option>
                            <option value="Saturday">Saturday</option>
                            <option value="Monday and Wednesday">Monday & Wednesday</option>
                            <option value="Tuesday and Thursday">Tuesday & Thursday</option>
                            <option value="Friday and Saturday">Friday & Saturday</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Room / Laboratory</label>
                        <input type="text" name="room_assignment" id="formRoomAssignment" placeholder="E.G., IT LAB 1, RM 304" class="select-input uppercase-input custom-validate">
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Start Time</label>
                        <input type="time" name="start_time" id="formStartTime" class="select-input custom-validate">
                    </div>
                    <div class="form-group">
                        <label>End Time</label>
                        <input type="time" name="end_time" id="formEndTime" class="select-input custom-validate">
                    </div>
                </div>

                <div class="form-group">
                    <label for="formProfessorId">Assigned Faculty / Instructor</label>
                    <select name="professor_id" id="formProfessorId" class="select-input custom-validate">
                        <option value="">-- Select Assigned Faculty / Dean --</option>
                        @foreach($professors as $prof)
                            <option value="{{ $prof->id }}">
                                {{ strtoupper($prof->last_name) }}, {{ $prof->first_name }} ({{ strtoupper($prof->role) }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-actions-bar">
                    <button type="button" id="btnCancelEdit" class="rg-btn rg-btn-ghost" style="display: none; margin-right: auto;">Cancel</button>
                    <button type="reset" id="btnResetForm" class="rg-btn rg-btn-ghost">Reset</button>
                    <button type="submit" id="btnSubmitForm" class="rg-btn rg-btn-primary">Deploy Schedule</button>
                </div>
            </form>
        </div>

        <div class="rg-card table-panel">
            <div class="card-header-flex">
                <div class="table-header-top">
                    <h3 class="card-title-list"><i class="fas fa-list-alt"></i> List of Schedules</h3>
                    <span class="rg-tab-count" id="visibleClassesCount">{{ isset($schedules) ?$schedules->count() : 0 }} Active Classes</span>
                </div>

                <div class="table-filters-row">
                    <div class="search-box-wrap">
                        <input type="text" id="scheduleSearchInput" placeholder="Search by Code, Title, Section, or Room..." class="select-input" style="padding-left: 12px !important;">
                    </div>

                    <div class="filters-group-container">
                        <div style="display: flex; gap: 4px; align-items: center;">
                            <span class="filter-label">YEAR:</span>
                            <button type="button" class="filter-pill year-pill active" data-year="">All</button>
                            <button type="button" class="filter-pill year-pill" data-year="1st Year">1st</button>
                            <button type="button" class="filter-pill year-pill" data-year="2nd Year">2nd</button>
                            <button type="button" class="filter-pill year-pill" data-year="3rd Year">3rd</button>
                            <button type="button" class="filter-pill year-pill" data-year="4th Year">4th</button>
                        </div>

                        <div style="display: flex; gap: 4px; align-items: center;">
                            <span class="filter-label">SEM:</span>
                            <button type="button" class="filter-pill sem-pill active" data-sem="">All</button>
                            <button type="button" class="filter-pill sem-pill" data-sem="1st Semester">1st</button>
                            <button type="button" class="filter-pill sem-pill" data-sem="2nd Semester">2nd</button>
                            <button type="button" class="filter-pill sem-pill" data-sem="Summer" id="btnSummer" style="display: none;">Summer</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="rg-table" id="scheduleTable">
                    <thead>
                        <tr>
                            <th>Subject Info</th>
                            <th>Section</th>
                            <th>Schedule Details</th>
                            <th>Assigned Faculty</th>
                            <th class="col-actions" style="text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($schedules) &&$schedules->count() > 0)
                            @foreach($schedules as $sched)
                                <tr class="schedule-row-item"
                                    data-year="{{ $sched->year_level ?? '' }}"
                                    data-sem="{{ $sched->semester ?? '' }}">

                                    <td>
                                        <div style="display: flex; align-items: center; gap: 6px;">
                                            <span class="subject-code-tag">{{ strtoupper($sched->subject_code ?? '') }}</span>
                                            @if(($sched->schedule_type ?? 'LEC') === 'LAB')
                                                <span class="badge-lab">LAB</span>
                                            @else
                                                <span class="badge-lec">LEC</span>
                                            @endif
                                        </div>
                                        <div style="font-size: 12px; color: var(--rg-muted); margin-top: 4px; font-weight: 500;">
                                            {{ $sched->subject_title ?? '' }}
                                        </div>
                                    </td>
                                        
                                    <td class="col-section"><strong style="color: var(--rg-ink);">{{ strtoupper($sched->section_name) }}</strong></td>

                                    <td>
                                        <span class="sched-info-tag">
                                            <i class="fas fa-calendar-day"></i> {{ $sched->class_days }}
                                        </span>
                                        <span class="sched-info-tag">
                                            <i class="fas fa-clock"></i> {{ date('g:i A', strtotime($sched->start_time)) }} - {{ date('g:i A', strtotime($sched->end_time)) }}
                                        </span>
                                        <div style="font-size: 11.5px; color: #475569; margin-top: 4px; font-weight: 600;">
                                            <i class="fas fa-door-open" style="color: var(--rg-muted);"></i> Room: {{ strtoupper($sched->room_assignment) }}
                                        </div>
                                    </td>

                                    <td class="col-faculty">
                                        @if($sched->prof_last_name ?? null)
                                            <span class="prof-assigned-tag">
                                                <i class="fas fa-user-tie"></i>
                                                {{ $sched->prof_last_name }}, {{ substr($sched->prof_first_name, 0, 1) }}.
                                            </span>
                                        @else
                                            <span class="prof-unassigned-tag">Vacant Pool</span>
                                        @endif
                                    </td>

                                    <td class="col-actions" style="text-align: center;">
                                        <div style="display: flex; gap: 6px; justify-content: center; align-items: center; flex-wrap: nowrap;">
                                            <button type="button" class="rg-btn rg-btn-ghost btn-view-students" style="padding: 5px 10px; font-size: 11.5px;"
                                                    data-id="{{ $sched->id }}">
                                                <i class="fas fa-users"></i> {{ $sched->enrolled_count ?? 0 }}
                                            </button>
                                            <button type="button" class="rg-btn rg-btn-outline-navy btn-action-edit" style="padding: 5px 10px; font-size: 11.5px;"
                                                    data-id="{{ $sched->id }}"
                                                    data-subject-id="{{ $sched->subject_id }}"
                                                    data-subject-text="[{{ strtoupper($sched->subject_code ?? '') }}] — {{ $sched->subject_title ?? '' }}"
                                                    data-schedule-type="{{ $sched->schedule_type ?? 'LEC' }}"
                                                    data-section="{{ $sched->section_name }}"
                                                    data-days="{{ $sched->class_days }}"
                                                    data-room="{{ $sched->room_assignment }}"
                                                    data-start="{{ $sched->start_time }}"
                                                    data-end="{{ $sched->end_time }}"
                                                    data-professor-id="{{ $sched->professor_id ?? '' }}">
                                                Edit
                                            </button>
                                            <form action="{{ route('department_secretary.schedules.delete', $sched->id) }}" method="POST"
                                                onsubmit="return confirm('Are you sure you want to delete this schedule?');"
                                                style="display: inline-block; margin: 0; padding: 0;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rg-btn rg-btn-icon rg-btn-danger" style="width: 28px; height: 28px;" title="Delete schedule">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach

                            <tr id="noMatchingSchedulesRow" style="display: none;">
                                <td colspan="5" class="empty-table-state">
                                    <div style="padding: 30px 10px; text-align: center;">
                                        <i class="fas fa-calendar-times" style="font-size: 32px; color: var(--rg-muted); margin-bottom: 10px;"></i>
                                        <p style="margin: 0; font-weight: 600; color: var(--rg-muted); font-size: 14px;">No class schedules found for the selected filter.</p>
                                        <small style="color: var(--rg-muted); font-size: 12px;">Try selecting another Year Level or Semester, or deploy a new schedule using the form.</small>
                                    </div>
                                </td>
                            </tr>
                        @else
                            <tr>
                                <td colspan="5" class="empty-table-state">
                                    <div style="padding: 40px 10px; text-align: center;">
                                        <i class="fas fa-folder-open" style="font-size: 36px; color: var(--rg-muted); margin-bottom: 12px;"></i>
                                        <p style="margin: 0; font-weight: 700; color: var(--rg-ink); font-size: 15px;">No Class Schedules Documented Yet</p>
                                        <small style="color: var(--rg-muted); font-size: 12.5px;">There are no schedules deployed in the system. Use the form on the left to add your first schedule.</small>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<div id="studentsModal" class="sm-overlay" style="display:none;">
    <div class="sm-box">
        <div class="sm-header">
            <div>
                <h3 id="smTitle" style="margin:0; font-size:16px; font-weight:800; color:var(--rg-ink);">Enrolled Students</h3>
                <div id="smSub" style="font-size:12.5px; color:var(--rg-muted); margin-top:3px;"></div>
            </div>
            <button type="button" id="smClose" class="sm-close">&times;</button>
        </div>
        <div class="sm-body">
            <div id="smState" style="padding:30px; text-align:center; color:var(--rg-muted); font-size:13px;">Loading...</div>
            <table id="smTable" class="sm-table" style="display:none;">
                <thead>
                    <tr>
                        <th style="width:40px;">#</th>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Course</th>
                        <th>Year Level</th>
                    </tr>
                </thead>
                <tbody id="smBody"></tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const studentsUrlTemplate = "{{ route('department_secretary.schedules.students', ':id') }}";
        const overlay = document.getElementById('studentsModal');
        const smTitle = document.getElementById('smTitle');
        const smSub   = document.getElementById('smSub');
        const smState = document.getElementById('smState');
        const smTable = document.getElementById('smTable');
        const smBody  = document.getElementById('smBody');

        function closeModal() { overlay.style.display = 'none'; }
        document.getElementById('smClose').addEventListener('click', closeModal);
        overlay.addEventListener('click', e => { if (e.target === overlay) closeModal(); });
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

        document.querySelectorAll('.btn-view-students').forEach(btn => {
            btn.addEventListener('click', async function () {
                smTitle.textContent = 'Enrolled Students';
                smSub.textContent = '';
                smBody.innerHTML = '';
                smTable.style.display = 'none';
                smState.style.display = 'block';
                smState.textContent = 'Loading...';
                overlay.style.display = 'flex';

                try {
                    const res = await fetch(studentsUrlTemplate.replace(':id', btn.dataset.id), {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    if (!res.ok) throw new Error('Request failed');
                    const data = await res.json();

                    const sc = data.schedule;
                    smTitle.textContent = (sc.subject_code || '').toUpperCase() + ' (' + (sc.schedule_type || '') + ') - ' + (sc.section_name || '').toUpperCase();
                    smSub.textContent = (sc.subject_title || '') + ' - ' + data.students.length + ' enrolled';

                    if (data.students.length === 0) {
                        smState.textContent = 'No students are enrolled in this class yet.';
                        return;
                    }

                    data.students.forEach((s, i) => {
                        const tr = document.createElement('tr');
                        const mid = s.middle_name ? ' ' + s.middle_name.charAt(0) + '.' : '';
                        const cells = [
                            i + 1,
                            s.student_id,
                            (s.last_name || '').toUpperCase() + ', ' + (s.first_name || '') + mid,
                            s.course || '',
                            s.year_level || ''
                        ];
                        cells.forEach(val => {
                            const td = document.createElement('td');
                            td.textContent = val;
                            tr.appendChild(td);
                        });
                        smBody.appendChild(tr);
                    });

                    smState.style.display = 'none';
                    smTable.style.display = 'table';
                } catch (err) {
                    smState.textContent = 'Could not load the student list. Please try again.';
                }
            });
        });
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const mainForm = document.getElementById('scheduleMainForm');
        const validationAlert = document.getElementById('formValidationAlert');
        const validateFields = document.querySelectorAll('.custom-validate');

        if (mainForm) {
            mainForm.addEventListener('submit', function (e) {
                let hasError = false;

                validateFields.forEach(field => {
                    field.classList.remove('input-error');
                });

                validateFields.forEach(field => {
                    if (field.offsetParent === null) return;

                    if (!field.value || field.value.trim() === '') {
                        field.classList.add('input-error');
                        hasError = true;
                    }
                });

                if (hasError) {
                    e.preventDefault(); 
                    validationAlert.style.display = 'block';
                    validationAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else {
                    validationAlert.style.display = 'none';
                }
            });

            validateFields.forEach(field => {
                field.addEventListener('input', function() {
                    if (this.value.trim() !== '') this.classList.remove('input-error');
                });
                field.addEventListener('change', function() {
                    if (this.value.trim() !== '') this.classList.remove('input-error');
                });
            });
        }

        const searchInput = document.getElementById('scheduleSearchInput');
        const yearPills = document.querySelectorAll('.year-pill');
        const semPills = document.querySelectorAll('.sem-pill');
        const btnSummer = document.getElementById('btnSummer');
        const tableRows = document.querySelectorAll('#scheduleTable tbody tr.schedule-row-item');
        const noMatchRow = document.getElementById('noMatchingSchedulesRow');
        const visibleClassesCount = document.getElementById('visibleClassesCount');

        let selectedYear = '';
        let selectedSem = '';

        function updateSummerButtonVisibility() {
            if (!btnSummer) return;
            const isThirdYear = selectedYear === '3rd Year';
            if (isThirdYear) {
                btnSummer.style.display = 'inline-block';
            } else {
                btnSummer.style.display = 'none';
                if (selectedSem === 'Summer') {
                    selectedSem = '';
                    semPills.forEach(p => p.classList.remove('active'));
                    document.querySelector('.sem-pill[data-sem=""]').classList.add('active');
                }
            }
        }

        function filterScheduleMatrixTable() {
            const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
            let visibleCount = 0;

            tableRows.forEach(row => {
                const rowText = row.innerText.toLowerCase();
                const rowYear = row.getAttribute('data-year') || '';
                const rowSem = row.getAttribute('data-sem') || '';

                const matchesSearch = rowText.includes(searchTerm);
                const matchesYear = selectedYear === '' || rowYear === selectedYear;
                const matchesSem = selectedSem === '' || rowSem === selectedSem;

                if (matchesSearch && matchesYear && matchesSem) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (noMatchRow) {
                if (visibleCount === 0 && tableRows.length > 0) {
                    noMatchRow.style.display = '';
                } else {
                    noMatchRow.style.display = 'none';
                }
            }

            if (visibleClassesCount) {
                visibleClassesCount.textContent = `${visibleCount} Active Classes`;
            }
        }

        if (searchInput) searchInput.addEventListener('input', filterScheduleMatrixTable);

        yearPills.forEach(pill => {
            pill.addEventListener('click', function () {
                yearPills.forEach(p => p.classList.remove('active'));
                this.classList.add('active');
                selectedYear = this.getAttribute('data-year');
                updateSummerButtonVisibility();
                filterScheduleMatrixTable();
            });
        });

        semPills.forEach(pill => {
            pill.addEventListener('click', function () {
                semPills.forEach(p => p.classList.remove('active'));
                this.classList.add('active');
                selectedSem = this.getAttribute('data-sem');
                filterScheduleMatrixTable();
            });
        });

        updateSummerButtonVisibility();
        filterScheduleMatrixTable();

        const formPanelTitle = document.querySelector('.form-panel .card-bar span');
        const btnSubmitForm = document.querySelector('.btn-submit-subj');
        const btnResetForm = document.getElementById('btnResetForm');
        
        let methodInput = mainForm ? mainForm.querySelector('input[name="_method"]') : null;
        if (mainForm && !methodInput) {
            methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            mainForm.appendChild(methodInput);
        }

        let btnCancelEdit = document.getElementById('btnCancelEdit');
        const subjectSelect = mainForm ? mainForm.querySelector('select[name="subject_id"]') : null;
        let subjectReadOnly = document.getElementById('formSubjectReadOnly');
        const subjectSelectGroup = document.getElementById('subjectSelectGroup');
        const subjectReadOnlyGroup = document.getElementById('subjectReadOnlyGroup');
        const defaultStoreAction = mainForm ? mainForm.action : '';
        const editButtons = document.querySelectorAll('.btn-action-edit');

        editButtons.forEach(button => {
            button.addEventListener('click', function () {
                if (validationAlert) validationAlert.style.display = 'none';
                validateFields.forEach(f => f.classList.remove('input-error'));

                const schedId = this.getAttribute('data-id');
                const subjectId = this.getAttribute('data-subject-id');
                const subjectText = this.getAttribute('data-subject-text');
                const section = this.getAttribute('data-section');
                const days = this.getAttribute('data-days');
                const room = this.getAttribute('data-room');
                const start = this.getAttribute('data-start');
                const end = this.getAttribute('data-end');
                const professorId = this.getAttribute('data-professor-id');
                const scheduleType = this.getAttribute('data-schedule-type');
                
                if (subjectSelect) subjectSelect.value = subjectId;
                if (subjectReadOnly) subjectReadOnly.value = subjectText;
                
                const inputSection = mainForm.querySelector('input[name="section_name"]');
                const selectDays = mainForm.querySelector('select[name="class_days"]');
                const inputRoom = mainForm.querySelector('input[name="room_assignment"]');
                const inputStart = mainForm.querySelector('input[name="start_time"]');
                const inputEnd = mainForm.querySelector('input[name="end_time"]');
                const selectProfessor = mainForm.querySelector('select[name="professor_id"]');
                const selectScheduleType = mainForm.querySelector('select[name="schedule_type"]');

                if (inputSection) inputSection.value = section;
                if (selectDays) selectDays.value = days;
                if (inputRoom) inputRoom.value = room;
                if (inputStart) inputStart.value = start ? start.substring(0, 5) : '';
                if (inputEnd) inputEnd.value = end ? end.substring(0, 5) : '';
                if (selectScheduleType) selectScheduleType.value = scheduleType || 'LEC';
                if (selectProfessor) selectProfessor.value = professorId || '';

                if (subjectSelectGroup) subjectSelectGroup.style.display = 'none';
                if (subjectSelect) subjectSelect.classList.remove('custom-validate'); 
                if (subjectReadOnlyGroup) subjectReadOnlyGroup.style.display = 'block';

                if (methodInput) methodInput.value = 'PUT';

                if (formPanelTitle) formPanelTitle.innerHTML = '<i class="fas fa-edit"></i> Update Deployment Specifications';
                if (btnSubmitForm) {
                    btnSubmitForm.textContent = 'Update Class';
                }

                if (btnResetForm) btnResetForm.style.display = 'none';
                if (btnCancelEdit) btnCancelEdit.style.display = 'inline-block';

                if (mainForm) {
                    mainForm.action = `/department-secretary/manage-schedules/update/${schedId}`;
                }

                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        });

        function revertToCreateMode() {
            if (!mainForm) return;
            if (validationAlert) validationAlert.style.display = 'none';
            validateFields.forEach(f => f.classList.remove('input-error'));

            mainForm.reset();
            mainForm.action = defaultStoreAction;
            if (methodInput) methodInput.value = ''; 

            if (subjectSelectGroup) subjectSelectGroup.style.display = 'block';
            if (subjectSelect) subjectSelect.classList.add('custom-validate');
            if (subjectReadOnlyGroup) subjectReadOnlyGroup.style.display = 'none';

            if (formPanelTitle) formPanelTitle.innerHTML = '<i class="fas fa-calendar-plus"></i> Add Class Schedule';
            if (btnSubmitForm) {
                btnSubmitForm.textContent = 'Deploy Schedule';
            }

            if (btnResetForm) btnResetForm.style.display = 'inline-block';
            if (btnCancelEdit) btnCancelEdit.style.display = 'none';
        }

        if (btnCancelEdit) btnCancelEdit.addEventListener('click', revertToCreateMode);
        if (btnResetForm) btnResetForm.addEventListener('click', revertToCreateMode);
    });
</script>

<style>
    .rg-page {
        --rg-blue: #0033A0;
        --rg-blue-dark: #0A1F5C;
        --rg-blue-tint: #EAF0FF;
        --rg-gold: #F2B705;
        --rg-ink: #0B1B3A;
        --rg-muted: #5C6B8A;
        --rg-line: #E2E8F3;
        --rg-surface: #F7F9FD;
        --rg-green: #15803D;
        --rg-green-tint: #E8F7EE;
        --rg-red: #C62828;

        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: var(--rg-ink);
        max-width: 1450px;
        margin: 0 auto;
        padding: 24px 20px 40px;
    }

    .rg-header { display: flex; justify-content: space-between; align-items: flex-end; gap: 20px; flex-wrap: wrap; padding-bottom: 18px; margin-bottom: 20px; border-bottom: 1px solid var(--rg-line); }
    .rg-title { margin: 0; font-size: 26px; font-weight: 800; letter-spacing: -0.2px; color: var(--rg-blue-dark); }
    .rg-subtitle { margin: 5px 0 0; font-size: 14px; color: var(--rg-muted); }

    .sa-workspace { display: flex; flex-direction: row; gap: 24px; align-items: flex-start; justify-content: space-between; width: 100%; }
    .form-panel { flex: 0 0 clamp(340px, 30%, 440px); min-width: 0; background: #ffffff; position: sticky; top: 24px; }
    .table-panel { flex: 1 1 0; min-width: 0; background: #ffffff; }

    .rg-card { border-radius: 10px; border: 1px solid var(--rg-line); box-shadow: 0 1px 2px rgba(10, 31, 92, 0.05); overflow: hidden; }
    .card-bar { background: var(--rg-surface); padding: 14px 20px; font-weight: 700; font-size: 14px; color: var(--rg-blue-dark); border-bottom: 1px solid var(--rg-line); }
    
    .card-header-flex { background-color: var(--rg-surface); padding: 14px 20px; border-bottom: 1px solid var(--rg-line); display: flex; flex-direction: column; gap: 14px; }
    .table-header-top { display: flex; justify-content: space-between; align-items: center; width: 100%; }
    .card-title-list { font-size: 14px; font-weight: 700; color: var(--rg-blue-dark); margin: 0; display: flex; align-items: center; gap: 8px; }
    
    .table-filters-row { display: flex; gap: 16px; flex-wrap: nowrap; width: 100%; align-items: center; justify-content: space-between; }
    .search-box-wrap { flex-grow: 1; min-width: 0; }
    .search-box-wrap input { width: 100%; padding-left: 12px !important; }

    .filters-group-container { display: flex; gap: 16px; align-items: center; flex-shrink: 0; flex-wrap: wrap; }

    .filter-label { font-size: 11px; font-weight: 700; color: var(--rg-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-right: 2px; }
    .filter-pill { background: var(--rg-surface); border: 1px solid var(--rg-line); color: var(--rg-muted); padding: 5px 12px; font-size: 11.5px; font-weight: 600; border-radius: 16px; cursor: pointer; transition: all 0.15s ease; }
    .filter-pill:hover { border-color: var(--rg-blue); color: var(--rg-blue); }
    .filter-pill.active { background: var(--rg-blue); color: #ffffff; border-color: var(--rg-blue); box-shadow: 0 2px 4px rgba(0, 51, 160, 0.2); }

    .pure-form { padding: 22px; display: flex; flex-direction: column; gap: 16px; }
    .form-group { display: flex; flex-direction: column; gap: 5px; }
    .form-group label { font-size: 11.5px; font-weight: 700; color: var(--rg-muted); text-transform: uppercase; letter-spacing: 0.3px; }
    
    .select-input { border: 1px solid #CBD5E6; border-radius: 8px; padding: 10px 12px; font-size: 13.5px; color: var(--rg-ink); outline: none; background: #ffffff; box-sizing: border-box; width: 100%; transition: all 0.15s; }
    .select-input:focus { border-color: var(--rg-blue); box-shadow: 0 0 0 3px rgba(0, 51, 160, 0.14); }
    
    .input-error { border-color: var(--rg-red) !important; background-color: #fef2f2 !important; }
    .rg-alert-error { background: #FDECEC; color: #8E1B1B; padding: 12px 16px; border-radius: 8px; font-weight: 600; border-left: 4px solid var(--rg-red); font-size: 13.5px; margin-bottom: 16px; }
    .rg-alert-success { background: var(--rg-green-tint); color: #14532D; padding: 12px 16px; border-radius: 8px; font-weight: 600; border-left: 4px solid var(--rg-green); font-size: 13.5px; margin-bottom: 16px; }
    
    .form-grid-2 { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 14px; align-items: end; }
    .form-grid-2 > .form-group { min-width: 0; }
    .uppercase-input { text-transform: uppercase; }
    .field-help { font-size: 11px; color: var(--rg-muted); margin-top: 1px; }
    
    .form-actions-bar { display: flex; justify-content: flex-end; gap: 10px; margin-top: 8px; border-top: 1px solid var(--rg-line); padding-top: 14px; }
    
    .table-responsive { width: 100%; overflow-x: auto; }
    .rg-table { width: 100%; min-width: 680px; border-collapse: collapse; font-size: 14px; }
    .rg-table th { background: var(--rg-surface); padding: 11px 20px; font-size: 12px; font-weight: 700; color: var(--rg-muted); text-align: left; border-bottom: 1px solid var(--rg-line); white-space: nowrap; }
    .rg-table td { padding: 13px 20px; border-bottom: 1px solid #EEF2F9; color: var(--rg-ink); vertical-align: middle; }
    .rg-table tbody tr:hover { background-color: #F5F8FF; }
    
    /* Keep these on a single line (no more "BSIT1-" / "A" or "DE GUZMAN," / "P.") */
    .rg-table .col-section,
    .rg-table .col-faculty,
    .rg-table .col-actions { white-space: nowrap; }
    .rg-table .col-actions { width: 1%; }
    .subject-code-tag, .badge-lab, .badge-lec { white-space: nowrap; }

    .subject-code-tag { font-family: ui-monospace, monospace; font-weight: 700; color: var(--rg-blue); background-color: var(--rg-blue-tint); padding: 3px 7px; border-radius: 6px; font-size: 11.5px; border: 1px solid #CFDBFB; }
    .badge-lab { background-color: #fef3c7; color: #92400e; font-size: 9.5px; font-weight: 800; padding: 2px 5px; border-radius: 4px; border: 1px solid #fde68a; }
    .badge-lec { background-color: var(--rg-blue-tint); color: var(--rg-blue); font-size: 9.5px; font-weight: 800; padding: 2px 5px; border-radius: 4px; border: 1px solid #CFDBFB; }

    .sched-info-tag { white-space: nowrap; margin-bottom: 3px; font-size: 11px; font-weight: 600; color: var(--rg-ink); background-color: var(--rg-surface); padding: 2px 6px; border-radius: 4px; display: inline-block; margin-right: 3px; border: 1px solid var(--rg-line); }
    .prof-assigned-tag { font-size: 12px; font-weight: 600; color: var(--rg-green); background-color: var(--rg-green-tint); border: 1px solid #BFE8CE; padding: 3px 8px; border-radius: 6px; display: inline-flex; align-items: center; gap: 5px; }
    .prof-unassigned-tag { font-size: 11.5px; font-weight: 500; color: var(--rg-muted); background-color: var(--rg-surface); padding: 3px 8px; border-radius: 6px; font-style: italic; border: 1px solid var(--rg-line); }
    .empty-table-state { text-align: center; color: var(--rg-muted); padding: 40px 0 !important; font-style: italic; }
    
    .sm-overlay { position: fixed; inset: 0; background: rgba(15,23,42,0.5); z-index: 9999; display: flex; align-items: center; justify-content: center; padding: 20px; }
    .sm-box { background: #fff; border-radius: 12px; width: 100%; max-width: 640px; max-height: 80vh; display: flex; flex-direction: column; box-shadow: 0 20px 40px rgba(0,0,0,0.25); }
    .sm-header { display: flex; justify-content: space-between; align-items: flex-start; padding: 18px 22px; border-bottom: 1px solid var(--rg-line); }
    .sm-close { background: none; border: none; font-size: 26px; line-height: 1; color: var(--rg-muted); cursor: pointer; }
    .sm-body { overflow-y: auto; padding: 0; }
    .sm-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .sm-table th { background: var(--rg-surface); padding: 10px 16px; font-size: 11px; font-weight: 700; color: var(--rg-muted); text-transform: uppercase; text-align: left; border-bottom: 1px solid var(--rg-line); position: sticky; top: 0; }
    .sm-table td { padding: 10px 16px; border-bottom: 1px solid #EEF2F9; color: var(--rg-ink); }
    
    .rg-btn { display: inline-flex; align-items: center; gap: 7px; padding: 7px 14px; border-radius: 7px; font-size: 12.5px; font-weight: 700; text-decoration: none; cursor: pointer; border: 1px solid transparent; transition: background .15s, color .15s, border-color .15s; white-space: nowrap; line-height: 1.2; }
    .rg-btn-primary { background: var(--rg-blue); color: #fff; }
    .rg-btn-primary:hover { background: var(--rg-blue-dark); color: #fff; }
    .rg-btn-ghost { background: #fff; color: var(--rg-blue); border-color: #B9C8F0; }
    .rg-btn-ghost:hover { background: var(--rg-blue-tint); color: var(--rg-blue-dark); }
    .rg-btn-outline-navy { background: #fff; color: var(--rg-blue-dark); border-color: var(--rg-blue-dark); }
    .rg-btn-outline-navy:hover { background: var(--rg-blue-dark); color: #fff; }
    .rg-btn-icon { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 7px; background: #fff; border: 1px solid var(--rg-line); color: var(--rg-muted); cursor: pointer; transition: background .15s, color .15s, border-color .15s; }
    .rg-btn-danger:hover { background: #FDECEC; border-color: #F0B4B4; color: var(--rg-red); }

    @media (max-width: 1500px) {
        .rg-table th { padding: 10px 12px; }
        .rg-table td { padding: 12px 12px; }
        .table-filters-row { flex-wrap: wrap; }
    }

    @media (max-width: 1100px) {
        .sa-workspace { flex-direction: column; }
        .form-panel { position: static; flex: none; width: 100%; }
        .table-panel { flex: none; width: 100%; }
    }

    .rg-tab-count { background: var(--rg-line); color: var(--rg-muted); font-size: 11.5px; font-weight: 700; padding: 3px 10px; border-radius: 10px; }
</style>
@endsection