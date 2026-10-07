@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="sa-container">
    
    <div class="sa-header">
        <div class="header-titles">
            <h2>Department Student Registry</h2>
            <p>View, search, and manage student records and block sections.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="sa-alert-success">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="sa-alert-error">
            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
        </div>
    @endif

    <div class="sa-workspace">

        <div class="sa-card form-panel">
            <div class="card-bar">
                <span id="formPanelTitle"><i class="fas fa-calendar-plus"></i> Add Class Schedule</span>
            </div>
            
            <form id="scheduleMainForm" action="{{ route('department_secretary.schedules.store') }}" method="POST" autocomplete="off" class="pure-form">
                @csrf
                
                <div id="methodContainer"></div>

                <div id="formValidationAlert" class="sa-alert-error" style="display: none; margin-bottom: 15px;">
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
                    <input type="text" id="formSubjectReadOnly" class="select-input" style="background-color: #f8fafc; color: #64748b;" readonly>
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
                    <button type="button" id="btnCancelEdit" class="btn-clear" style="display: none; margin-right: auto;">Cancel</button>
                    <button type="reset" id="btnResetForm" class="btn-clear">Reset</button>
                    <button type="submit" id="btnSubmitForm" class="btn-submit-subj">Deploy Schedule</button>
                </div>
            </form>
        </div>

        <div class="sa-card table-panel">
            <div class="card-header-flex">
                <div class="table-header-top">
                    <h3 class="card-title-list"><i class="fas fa-list-alt"></i> List of Schedules</h3>
                    <span class="badge-count" id="visibleClassesCount">{{ isset($schedules) ? $schedules->count() : 0 }} Active Classes</span>
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
                <table class="minimal-table" id="scheduleTable">
                    <thead>
                        <tr>
                            <th>Subject Info</th>
                            <th>Section</th>
                            <th>Schedule Details</th>
                            <th>Assigned Faculty</th>
                            <th style="text-align: center; width: 210px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($schedules) && $schedules->count() > 0)
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
                                        <div style="font-size: 12px; color: #64748b; margin-top: 4px; font-weight: 500;">
                                            {{ $sched->subject_title ?? '' }}
                                        </div>
                                    </td>
                                        
                                    <td><strong style="color: #0f172a;">{{ strtoupper($sched->section_name) }}</strong></td>

                                    <td>
                                        <span class="sched-info-tag">
                                            <i class="fas fa-calendar-day"></i> {{ $sched->class_days }}
                                        </span>
                                        <span class="sched-info-tag">
                                            <i class="fas fa-clock"></i> {{ date('g:i A', strtotime($sched->start_time)) }} - {{ date('g:i A', strtotime($sched->end_time)) }}
                                        </span>
                                        <div style="font-size: 11.5px; color: #475569; margin-top: 4px; font-weight: 600;">
                                            <i class="fas fa-door-open" style="color: #94a3b8;"></i> Room: {{ strtoupper($sched->room_assignment) }}
                                        </div>
                                    </td>

                                    <td>
                                        @if($sched->prof_last_name ?? null)
                                            <span class="prof-assigned-tag">
                                                <i class="fas fa-user-tie"></i>
                                                {{ $sched->prof_last_name }}, {{ substr($sched->prof_first_name, 0, 1) }}.
                                            </span>
                                        @else
                                            <span class="prof-unassigned-tag">Vacant Pool</span>
                                        @endif
                                    </td>

                                    <td style="text-align: center;">
                                        <div style="display: flex; gap: 6px; justify-content: center; align-items: center;">
                                            <button type="button" class="btn-action-students btn-view-students"
                                                    data-id="{{ $sched->id }}">
                                                <i class="fas fa-users"></i> {{ $sched->enrolled_count ?? 0 }}
                                            </button>
                                            <button type="button" class="btn-action-edit"
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
                                                <button type="submit" class="btn-action-delete">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach

                            <tr id="noMatchingSchedulesRow" style="display: none;">
                                <td colspan="5" class="empty-table-state">
                                    <div style="padding: 30px 10px; text-align: center;">
                                        <i class="fas fa-calendar-times" style="font-size: 32px; color: #cbd5e1; margin-bottom: 10px;"></i>
                                        <p style="margin: 0; font-weight: 600; color: #64748b; font-size: 14px;">No class schedules found for the selected filter.</p>
                                        <small style="color: #94a3b8; font-size: 12px;">Try selecting another Year Level or Semester, or deploy a new schedule using the form.</small>
                                    </div>
                                </td>
                            </tr>
                        @else
                            <tr>
                                <td colspan="5" class="empty-table-state">
                                    <div style="padding: 40px 10px; text-align: center;">
                                        <i class="fas fa-folder-open" style="font-size: 36px; color: #cbd5e1; margin-bottom: 12px;"></i>
                                        <p style="margin: 0; font-weight: 700; color: #475569; font-size: 15px;">No Class Schedules Documented Yet</p>
                                        <small style="color: #94a3b8; font-size: 12.5px;">There are no schedules deployed in the system. Use the form on the left to add your first schedule.</small>
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
                <h3 id="smTitle" style="margin:0; font-size:16px; font-weight:800; color:#0f172a;">Enrolled Students</h3>
                <div id="smSub" style="font-size:12.5px; color:#64748b; margin-top:3px;"></div>
            </div>
            <button type="button" id="smClose" class="sm-close">&times;</button>
        </div>
        <div class="sm-body">
            <div id="smState" style="padding:30px; text-align:center; color:#64748b; font-size:13px;">Loading...</div>
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
                    btnSubmitForm.style.backgroundColor = '#0284c7';
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
                btnSubmitForm.style.backgroundColor = '#0033A0';
            }

            if (btnResetForm) btnResetForm.style.display = 'inline-block';
            if (btnCancelEdit) btnCancelEdit.style.display = 'none';
        }

        if (btnCancelEdit) btnCancelEdit.addEventListener('click', revertToCreateMode);
        if (btnResetForm) btnResetForm.addEventListener('click', revertToCreateMode);
    });
</script>

<style>
    .sa-container { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #0f172a; max-width: 1450px; margin: 0 auto; padding: 24px; }
    .sa-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 2px solid #e2e8f0; padding-bottom: 16px; }
    .header-titles h2 { margin: 0 0 4px 0; font-size: 26px; font-weight: 800; color: #0f172a; }
    .header-titles p { margin: 0; font-size: 13.5px; color: #64748b; }
    .header-badge { background-color: #0284c7; color: #ffffff; padding: 6px 14px; border-radius: 20px; font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }

    .sa-workspace { display: flex; flex-direction: row; gap: 24px; align-items: flex-start; justify-content: space-between; width: 100%; }
    .form-panel { flex: 1; min-width: 380px; background: #ffffff; position: sticky; top: 24px; }
    .table-panel { flex: 2.2; background: #ffffff; }

    .sa-card { border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02); overflow: hidden; }
    .card-bar { background: #f8fafc; padding: 16px 20px; font-weight: 700; font-size: 14px; color: #1e293b; border-bottom: 1px solid #e2e8f0; }
    
    .card-header-flex { background-color: #f8fafc; padding: 16px 20px; border-bottom: 1px solid #e2e8f0; display: flex; flex-direction: column; gap: 14px; }
    .table-header-top { display: flex; justify-content: space-between; align-items: center; width: 100%; }
    .card-title-list { font-size: 14px; font-weight: 700; color: #1e293b; margin: 0; display: flex; align-items: center; gap: 8px; }
    
    .table-filters-row { display: flex; gap: 16px; flex-wrap: nowrap; width: 100%; align-items: center; justify-content: space-between; }
    .search-box-wrap { flex-grow: 1; min-width: 0; }
    .search-box-wrap input { width: 100%; padding-left: 12px !important; }

    .filters-group-container { display: flex; gap: 16px; align-items: center; flex-shrink: 0; flex-wrap: wrap; }

    .filter-label { font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; margin-right: 2px; }
    .filter-pill { background: #f1f5f9; border: 1px solid #cbd5e1; color: #475569; padding: 5px 10px; font-size: 11px; font-weight: 600; border-radius: 20px; cursor: pointer; transition: all 0.15s ease; }
    .filter-pill:hover { background: #e2e8f0; color: #0f172a; }
    .filter-pill.active { background: #0f172a; color: #ffffff; border-color: #0f172a; box-shadow: 0 2px 4px rgba(15, 23, 42, 0.2); }

    .pure-form { padding: 20px; display: flex; flex-direction: column; gap: 14px; }
    .form-group { display: flex; flex-direction: column; gap: 5px; }
    .form-group label { font-size: 11.5px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.3px; }
    
    .select-input { border: 1px solid #cbd5e1; border-radius: 6px; padding: 9px 12px; font-size: 13.5px; color: #0f172a; outline: none; background: #ffffff; box-sizing: border-box; width: 100%; transition: all 0.15s; }
    .select-input:focus { border-color: #0033a0; box-shadow: 0 0 0 3px rgba(0, 51, 160, 0.1); }
    
    .input-error { border-color: #ef4444 !important; background-color: #fef2f2 !important; }
    .sa-alert-error { background: #fef2f2; color: #991b1b; padding: 12px 16px; border-radius: 6px; font-weight: 600; border-left: 4px solid #dc2626; font-size: 13px; }
    .sa-alert-success { background: #ecfdf5; color: #065f46; padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; font-weight: 600; border-left: 4px solid #059669; font-size: 13px; }
    
    .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .uppercase-input { text-transform: uppercase; }
    .field-help { font-size: 11px; color: #94a3b8; margin-top: 1px; }
    
    .form-actions-bar { display: flex; justify-content: flex-end; gap: 10px; margin-top: 8px; border-top: 1px solid #f1f5f9; padding-top: 14px; }
    .btn-submit-subj { background: #0033A0; color: #ffffff; border: none; padding: 9px 18px; font-size: 13.5px; font-weight: 700; border-radius: 6px; cursor: pointer; transition: background 0.15s; }
    .btn-submit-subj:hover { background: #002270; }
    .btn-clear { background: transparent; color: #64748b; border: 1px solid #cbd5e1; padding: 9px 14px; font-size: 13.5px; font-weight: 600; border-radius: 6px; cursor: pointer; }
    .btn-clear:hover { background: #f8fafc; color: #334155; }
    
    .table-responsive { width: 100%; overflow-x: auto; }
    .minimal-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 13px; }
    .minimal-table th { background: #f8fafc; padding: 12px 16px; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; }
    .minimal-table td { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
    .minimal-table tbody tr:hover { background-color: #f8fafc; }
    
    .subject-code-tag { font-family: ui-monospace, monospace; font-weight: 700; color: #2563eb; background-color: #eff6ff; padding: 3px 7px; border-radius: 4px; font-size: 11.5px; }
    .badge-lab { background-color: #fef3c7; color: #92400e; font-size: 9.5px; font-weight: 800; padding: 2px 5px; border-radius: 4px; border: 1px solid #fde68a; }
    .badge-lec { background-color: #e0f2fe; color: #075985; font-size: 9.5px; font-weight: 800; padding: 2px 5px; border-radius: 4px; border: 1px solid #bae6fd; }

    .sched-info-tag { font-size: 11px; font-weight: 600; color: #1e293b; background-color: #f1f5f9; padding: 2px 6px; border-radius: 4px; display: inline-block; margin-right: 3px; border: 1px solid #e2e8f0; }
    .prof-assigned-tag { font-size: 12px; font-weight: 600; color: #047857; background-color: #ecfdf5; border: 1px solid #a7f3d0; padding: 3px 8px; border-radius: 5px; display: inline-flex; align-items: center; gap: 5px; }
    .prof-unassigned-tag { font-size: 11.5px; font-weight: 500; color: #64748b; background-color: #f1f5f9; padding: 3px 8px; border-radius: 5px; font-style: italic; }
    .empty-table-state { text-align: center; color: #94a3b8; padding: 40px 0 !important; font-style: italic; }
    
    .btn-action-edit { background-color: transparent; border: 1px solid #cbd5e1; color: #475569; padding: 4px 10px; font-size: 11.5px; font-weight: 600; border-radius: 5px; cursor: pointer; }
    .btn-action-edit:hover { background-color: #f1f5f9; color: #1e293b; }
    .btn-action-delete { background-color: transparent; border: 1px solid #fca5a5; color: #dc2626; padding: 4px 10px; font-size: 11.5px; font-weight: 600; border-radius: 5px; cursor: pointer; }
    .btn-action-delete:hover { background-color: #fef2f2; }
    .btn-action-students { background-color: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; padding: 4px 10px; font-size: 11.5px; font-weight: 700; border-radius: 5px; cursor: pointer; }
    .btn-action-students:hover { background-color: #dbeafe; }
    .sm-overlay { position: fixed; inset: 0; background: rgba(15,23,42,0.5); z-index: 9999; align-items: center; justify-content: center; padding: 20px; }
    .sm-box { background: #fff; border-radius: 12px; width: 100%; max-width: 640px; max-height: 80vh; display: flex; flex-direction: column; box-shadow: 0 20px 40px rgba(0,0,0,0.25); }
    .sm-header { display: flex; justify-content: space-between; align-items: flex-start; padding: 18px 22px; border-bottom: 1px solid #e2e8f0; }
    .sm-close { background: none; border: none; font-size: 26px; line-height: 1; color: #64748b; cursor: pointer; }
    .sm-body { overflow-y: auto; padding: 0; }
    .sm-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .sm-table th { background: #f8fafc; padding: 10px 16px; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; text-align: left; border-bottom: 1px solid #e2e8f0; position: sticky; top: 0; }
    .sm-table td { padding: 10px 16px; border-bottom: 1px solid #f1f5f9; color: #334155; }
    .badge-count { background: #e2e8f0; color: #334155; font-size: 11px; padding: 3px 10px; border-radius: 20px; font-weight: 700; }
</style>
@endsection