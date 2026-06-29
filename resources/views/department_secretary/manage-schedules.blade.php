@extends('layouts.app')

@section('content')
<div class="sa-container">
    
    <div class="sa-header">
        <div class="header-titles">
            <h2>Class Schedule Matrix</h2>
            <p>Department Secretary Panel — Section & Room Deployment Center</p>
        </div>
        <div class="header-badge" style="background-color: #0284c7; color: #ffffff; padding: 6px 14px; border-radius: 20px; font-weight: 700; font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.5px;">Secretary Mode</div>
    </div>

    @if(session('success'))
        <div class="sa-alert-success">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="sa-alert-error" style="background: #fef2f2; color: #991b1b; padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; font-weight: 600; border-left: 5px solid #dc2626; font-size: 14px;">
            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
        </div>
    @endif

    <div class="sa-workspace">

        <div class="sa-card form-panel">
            <div class="card-bar">
                <span id="formPanelTitle"><i class="fas fa-calendar-plus"></i> Deployment Specifications Form</span>
            </div>
            
            <form id="scheduleMainForm" action="{{ route('department_secretary.schedules.store') }}" method="POST" autocomplete="off" class="pure-form">
                @csrf
                
                <div id="methodContainer"></div>

                <div class="form-group" id="subjectSelectGroup">
                    <label>Target Course / Subject</label>
                        <select name="subject_id" id="formSubjectId" class="select-input" required>
                            <option value="">-- Select Cataloged Subject --</option>
                            @foreach($subjects as $subj)
                                <option value="{{ $subj->id }}">
                                    [{{ strtoupper($subj->subject_code) }}] — {{ $subj->subject_title }} 
                                    ({{ $subj->year_level }}, {{ $subj->semester }})
                                </option>
                            @endforeach
                        </select>
                    <small class="field-help">Choose the curriculum module to be given an active deployment slot.</small>
                </div>

                <div class="form-group" id="subjectReadOnlyGroup" style="display: none;">
                    <label>Selected Subject</label>
                    <input type="text" id="formSubjectReadOnly" class="select-input" style="background-color: #f8fafc; color: #64748b;" readonly>
                </div>

                <div class="form-group">
                    <label>Class Section / Block Name</label>
                    <input type="text" name="section_name" id="formSectionName" placeholder="E.G., BSIT 2-A, BSIT 3-B, ACT 1-A" class="uppercase-input" required>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Class Days</label>
                        <select name="class_days" id="formClassDays" class="select-input" required>
                            <option value="">-- Select Days --</option>
                            <option value="Monday">Monday</option>
                            <option value="Tuesday">Tuesday</option>
                            <option value="Wednesday">Wednesday</option>
                            <option value="Thursday">Thursday</option>
                            <option value="Friday">Friday</option>
                            <option value="Saturday">Saturday</option>
                            <option value="MW">MW</option>
                            <option value="TTH">TTH</option>
                            <option value="FS">FS</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Room / Laboratory</label>
                        <input type="text" name="room_assignment" id="formRoomAssignment" placeholder="E.G., IT LAB 1, RM 304" class="uppercase-input" required>
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Start Time</label>
                        <input type="time" name="start_time" id="formStartTime" required>
                    </div>
                    <div class="form-group">
                        <label>End Time</label>
                        <input type="time" name="end_time" id="formEndTime" required>
                    </div>
                </div>

                <div class="form-actions-bar">
                    <button type="button" id="btnCancelEdit" class="btn-clear" style="display: none; margin-right: auto;">Cancel Edit</button>
                    <button type="reset" id="btnResetForm" class="btn-clear">Reset Form</button>
                    <button type="submit" id="btnSubmitForm" class="btn-submit-subj">Deploy Class</button>
                </div>
            </form>
        </div>

        <div class="sa-card table-panel">
            <div class="card-header-flex" style="display: flex; flex-direction: column; gap: 12px; align-items: stretch;">
                <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                    <h3 class="card-title-list">Active Deployment Matrix</h3>
                    <span class="badge-count" id="visibleClassesCount">{{ isset($schedules) ? $schedules->count() : 0 }} Active Classes</span>
                </div>
                
                <div style="display: flex; gap: 12px; flex-wrap: wrap; width: 100%;">
                    <div style="position: relative; flex: 2; min-width: 250px;">
                        <i class="fas fa-search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 14px;"></i>
                        <input type="text" id="scheduleSearchInput" placeholder="Mag-hanap gamit ang Subject Code, Pamagat, Seksyon, o Silid-Aralan..." 
                            style="width: 100%; padding: 10px 10px 10px 40px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; outline: none; box-sizing: border-box;">
                    </div>

                    <div style="flex: 1; min-width: 130px;">
                        <select id="filterYearLevel" class="select-input" style="padding: 9px 12px; font-size: 13px;">
                            <option value="">-- All Years --</option>
                            <option value="1st Year">1st Year</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option>
                            <option value="4th Year">4th Year</option>
                        </select>
                    </div>

                    <div style="flex: 1; min-width: 140px;">
                        <select id="filterSemester" class="select-input" style="padding: 9px 12px; font-size: 13px;">
                            <option value="">-- All Semesters --</option>
                            <option value="1st Semester">1st Semester</option>
                            <option value="2nd Semester">2nd Semester</option>
                        </select>
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
                            <th style="text-align: center; width: 140px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($schedules) && $schedules->count() > 0)
                            @foreach($schedules as $sched)
                                <tr class="schedule-row-item"
                                    data-year="{{ $sched->year_level ?? '' }}"
                                    data-sem="{{ $sched->semester ?? '' }}">

                                    {{-- SUBJECT INFO --}}
                                    <td>
                                        <span class="subject-code-tag">{{ strtoupper($sched->subject_code ?? '') }}</span>
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

                                    {{-- ASSIGNED FACULTY - also update edit button data attributes --}}
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
                                            <button type="button" class="btn-action-edit"
                                                    data-id="{{ $sched->id }}"
                                                    data-subject-id="{{ $sched->subject_id }}"
                                                    data-subject-text="[{{ strtoupper($sched->subject_code ?? '') }}] — {{ $sched->subject_title ?? '' }}"
                                                    data-section="{{ $sched->section_name }}"
                                                    data-days="{{ $sched->class_days }}"
                                                    data-room="{{ $sched->room_assignment }}"
                                                    data-start="{{ $sched->start_time }}"
                                                    data-end="{{ $sched->end_time }}">
                                                Edit
                                            </button>
                                            <form action="{{ route('department_secretary.schedules.delete', $sched->id) }}" method="POST"
                                                onsubmit="return confirm('Sigurado ka ba?');"
                                                style="display: inline-block; margin: 0; padding: 0;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-action-delete">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="5" class="empty-table-state">
                                    No active class scheduling or section deployments documented yet.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        
        // --- 1. Multi-Criteria Search & Filter Dropdown Engine ---
        const searchInput = document.getElementById('scheduleSearchInput');
        const filterYearLevel = document.getElementById('filterYearLevel');
        const filterSemester = document.getElementById('filterSemester');
        const tableRows = document.querySelectorAll('#scheduleTable tbody tr');
        const visibleClassesCount = document.getElementById('visibleClassesCount');

        function filterScheduleMatrixTable() {
            const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
            const selectedYear = filterYearLevel ? filterYearLevel.value : '';
            const selectedSem = filterSemester ? filterSemester.value : '';
            let visibleCount = 0;

            tableRows.forEach(row => {
                // Laktawan ang empty layout warning row kung sakali
                if (row.querySelector('.empty-table-state')) return;

                // Basahin ang content at data attributes ng row
                const rowText = row.innerText.toLowerCase();
                const rowYear = row.getAttribute('data-year') || '';
                const rowSem = row.getAttribute('data-sem') || '';

                // Kondisyon para sa pagsasala (Filters matching check)
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

            // I-update ang badge counter nang real-time base sa nakikitang rows
            if (visibleClassesCount) {
                visibleClassesCount.textContent = `${visibleCount} Active Classes`;
            }
        }

        // Makinig sa bawat event change at input fields
        if (searchInput) searchInput.addEventListener('input', filterScheduleMatrixTable);
        if (filterYearLevel) filterYearLevel.addEventListener('change', filterScheduleMatrixTable);
        if (filterSemester) filterSemester.addEventListener('change', filterScheduleMatrixTable);

        // Patakbuhin ang filter engine agad pagka-load ng pahina
        filterScheduleMatrixTable();


        // --- 2. Deployment Matrix Form CRUD (Edit, Revert & Cancel) Logic ---
        const mainForm = document.getElementById('scheduleMainForm');
        const formPanelTitle = document.querySelector('.form-panel .card-bar span');
        const btnSubmitForm = document.querySelector('.btn-submit-subj');
        const btnResetForm = document.getElementById('btnResetForm');
        
        let methodInput = mainForm.querySelector('input[name="_method"]');
        if (!methodInput) {
            methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            mainForm.appendChild(methodInput);
        }

        let btnCancelEdit = document.getElementById('btnCancelEdit');
        if (!btnCancelEdit) {
            btnCancelEdit = document.createElement('button');
            btnCancelEdit.type = 'button';
            btnCancelEdit.id = 'btnCancelEdit';
            btnCancelEdit.className = 'btn-clear';
            btnCancelEdit.textContent = 'Cancel Edit';
            btnCancelEdit.style.display = 'none';
            btnCancelEdit.style.marginRight = '8px';
            btnSubmitForm.parentNode.insertBefore(btnCancelEdit, btnSubmitForm);
        }

        const subjectSelect = mainForm.querySelector('select[name="subject_id"]');
        let subjectReadOnly = document.getElementById('formSubjectReadOnly');
        const subjectSelectGroup = document.getElementById('subjectSelectGroup');
        const subjectReadOnlyGroup = document.getElementById('subjectReadOnlyGroup');

        const defaultStoreAction = mainForm.action;
        const editButtons = document.querySelectorAll('.btn-action-edit');

        editButtons.forEach(button => {
            button.addEventListener('click', function () {
                const schedId = this.getAttribute('data-id');
                const subjectId = this.getAttribute('data-subject-id');
                const subjectText = this.getAttribute('data-subject-text');
                const section = this.getAttribute('data-section');
                const days = this.getAttribute('data-days');
                const room = this.getAttribute('data-room');
                const start = this.getAttribute('data-start');
                const end = this.getAttribute('data-end');

                if(subjectSelect) subjectSelect.value = subjectId;
                if(subjectReadOnly) subjectReadOnly.value = subjectText;
                
                const inputSection = mainForm.querySelector('input[name="section_name"]');
                const selectDays = mainForm.querySelector('select[name="class_days"]');
                const inputRoom = mainForm.querySelector('input[name="room_assignment"]');
                const inputStart = mainForm.querySelector('input[name="start_time"]');
                const inputEnd = mainForm.querySelector('input[name="end_time"]');

                if(inputSection) inputSection.value = section;
                if(selectDays) selectDays.value = days;
                if(inputRoom) inputRoom.value = room;
                if(inputStart) inputStart.value = start.substring(0, 5);
                if(inputEnd) inputEnd.value = end.substring(0, 5);

                if(subjectSelectGroup) subjectSelectGroup.style.display = 'none';
                if(subjectSelect) subjectSelect.removeAttribute('required');
                if(subjectReadOnlyGroup) subjectReadOnlyGroup.style.display = 'block';

                methodInput.value = 'PUT';

                if(formPanelTitle) formPanelTitle.innerHTML = '<i class="fas fa-edit"></i> Update Deployment Specifications';
                if(btnSubmitForm) {
                    btnSubmitForm.textContent = 'Update Class';
                    btnSubmitForm.style.backgroundColor = '#0284c7';
                }

                if(btnResetForm) btnResetForm.style.display = 'none';
                btnCancelEdit.style.display = 'inline-block';

                mainForm.action = `/department-secretary/manage-schedules/update/${schedId}`;

                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        });

        function revertToCreateMode() {
            mainForm.reset();
            mainForm.action = defaultStoreAction;
            methodInput.value = ''; 

            if(subjectSelectGroup) subjectSelectGroup.style.display = 'block';
            if(subjectSelect) subjectSelect.setAttribute('required', 'required');
            if(subjectReadOnlyGroup) subjectReadOnlyGroup.style.display = 'none';

            if(formPanelTitle) formPanelTitle.innerHTML = '<i class="fas fa-calendar-plus"></i> Deployment Specifications Form';
            if(btnSubmitForm) {
                btnSubmitForm.textContent = 'Deploy Class';
                btnSubmitForm.style.backgroundColor = '#0033A0';
            }

            if(btnResetForm) btnResetForm.style.display = 'inline-block';
            btnCancelEdit.style.display = 'none';
        }

        if (btnCancelEdit) btnCancelEdit.addEventListener('click', revertToCreateMode);
        if (btnResetForm) btnResetForm.addEventListener('click', revertToCreateMode);
    });
</script>

<style>
    .sa-container { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #0f172a; max-width: 1400px; margin: 0 auto; padding: 20px; }
    .sa-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 2px solid #e2e8f0; padding-bottom: 18px; }
    .header-titles h2 { margin: 0 0 4px 0; font-size: 28px; font-weight: 800; color: #0f172a; letter-spacing: -0.5px; }
    .header-titles p { margin: 0; font-size: 14.5px; color: #64748b; }
    .sa-workspace { display: flex; flex-direction: row; gap: 28px; align-items: flex-start; justify-content: space-between; width: 100%; }
    .form-panel { flex: 1; min-width: 390px; background: #ffffff; position: sticky; top: 20px; }
    .table-panel { flex: 2; background: #ffffff; }
    .sa-card { border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 10px -2px rgba(0, 0, 0, 0.03); overflow: hidden; }
    .card-bar { background: #f8fafc; padding: 16px 22px; font-weight: 700; font-size: 14.5px; color: #1e293b; border-bottom: 1px solid #e2e8f0; }
    .card-header-flex { background-color: #f8fafc; padding: 16px 22px; border-bottom: 1px solid #e2e8f0; }
    .card-title-list { font-size: 14.5px; font-weight: 700; color: #1e293b; margin: 0; }
    .pure-form { padding: 24px; display: flex; flex-direction: column; gap: 16px; }
    .form-group { display: flex; flex-direction: column; gap: 6px; }
    .form-group label { font-size: 12px; font-weight: 600; color: #475569; }
    .form-group input, .select-input { border: 1px solid #cbd5e1; border-radius: 6px; padding: 10px 14px; font-size: 14px; color: #0f172a; outline: none; background: #ffffff; box-sizing: border-box; width: 100%; }
    .form-group input:focus, .select-input:focus { border-color: #0033A0; box-shadow: 0 0 0 3px rgba(0, 51, 160, 0.15); }
    .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .uppercase-input { text-transform: uppercase; }
    .field-help { font-size: 11px; color: #94a3b8; margin-top: 2px; }
    .select-input { background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e"); background-position: right 12px center; background-repeat: no-repeat; background-size: 20px; -webkit-appearance: none; -moz-appearance: none; appearance: none; padding-right: 40px; cursor: pointer; }
    .form-actions-bar { display: flex; justify-content: flex-end; gap: 12px; margin-top: 12px; border-top: 1px solid #f1f5f9; padding-top: 16px; }
    .btn-submit-subj { background: #0033A0; color: #ffffff; border: none; padding: 10px 20px; font-size: 14px; font-weight: 700; border-radius: 6px; cursor: pointer; transition: background 0.2s; text-align: center; }
    .btn-submit-subj:hover { opacity: 0.9; }
    .btn-clear { background: transparent; color: #64748b; border: 1px solid #cbd5e1; padding: 10px 16px; font-size: 14px; font-weight: 600; border-radius: 6px; cursor: pointer; }
    .btn-clear:hover { background: #f8fafc; color: #334155; }
    .table-responsive { width: 100%; overflow-x: auto; }
    .minimal-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 13.5px; }
    .minimal-table th { background: #f8fafc; padding: 14px 20px; font-size: 11.5px; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; }
    .minimal-table td { padding: 15px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
    .minimal-table tbody tr:hover { background-color: #f8fafc; }
    .subject-code-tag { font-family: ui-monospace, SFMono-Regular, Consolas, monospace; font-weight: 700; color: #2563eb; background-color: #eff6ff; padding: 4px 8px; border-radius: 6px; font-size: 12px; display: inline-block; }
    .sched-info-tag { font-size: 11.5px; font-weight: 600; color: #1e293b; background-color: #f1f5f9; padding: 3px 8px; border-radius: 4px; display: inline-block; margin-right: 4px; border: 1px solid #e2e8f0; }
    .sched-info-tag i { color: #475569; margin-right: 3px; }
    .prof-assigned-tag { font-size: 12.5px; font-weight: 600; color: #047857; background-color: #ecfdf5; border: 1px solid #a7f3d0; padding: 4px 10px; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px; }
    .prof-unassigned-tag { font-size: 12px; font-weight: 500; color: #64748b; background-color: #f1f5f9; padding: 4px 10px; border-radius: 6px; display: inline-block; font-style: italic; }
    .empty-table-state { text-align: center; color: #94a3b8; padding: 50px 0 !important; font-style: italic; font-size: 13.5px; }
    .btn-action-edit { background-color: transparent; border: 1px solid #cbd5e1; color: #475569; padding: 5px 12px; font-size: 12px; font-weight: 600; border-radius: 6px; cursor: pointer; transition: all 0.2s; }
    .btn-action-edit:hover { background-color: #f1f5f9; color: #1e293b; border-color: #94a3b8; }
    .btn-action-delete { background-color: transparent; border: 1px solid #fee2e2; color: #dc2626; padding: 5px 12px; font-size: 12px; font-weight: 600; border-radius: 6px; cursor: pointer; transition: all 0.2s; }
    .btn-action-delete:hover { background-color: #fef2f2; border-color: #fca5a5; }
    .sa-alert-success { background: #ecfdf5; color: #065f46; padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; font-weight: 600; border-left: 5px solid #059669; font-size: 14px; }
    .badge-count { background: #e2e8f0; color: #334155; font-size: 11.5px; padding: 4px 12px; border-radius: 20px; font-weight: 700; }
</style>
@endsection