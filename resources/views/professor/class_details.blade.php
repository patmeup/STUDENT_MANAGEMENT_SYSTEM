@extends('layouts.app')

@section('content')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="prof-container py-4" x-data="{
        currentTab: new URLSearchParams(window.location.search).get('tab') || 'masterlist',
        hasUnsavedChanges: false,

        switchTab(tabName) {
            if (this.hasUnsavedChanges) {
                if (!confirm('You have unsaved grade changes. Are you sure you want to leave without saving?')) {
                    return;
                }
            }
            this.currentTab = tabName;
            const url = new URL(window.location.href);
            url.searchParams.set('tab', tabName);
            window.history.replaceState({}, '', url);
        },

        showModal: false,
        modalTerm: '',
        editingTaskId: null,
        formTitle: '',
        formType: 'quiz',
        formMaxScore: 100,
        formDueDate: '',
        publishing: false,
        fileSizeError: false,

        selectedDate: new Date().toLocaleDateString('en-CA'),
        currentDate: new Date().toLocaleDateString('en-CA'),
        attendanceState: {
            @foreach($students as $student)
                '{{ $student->student_id }}': null,
            @endforeach
        },
        remarksState: {},
        isInitialized: false,
        loadingAttendance: false,
        saveStatus: { show: false, success: true, message: '' },
        hasSaved: false,

        isPastDate() {
            return this.selectedDate < this.currentDate;
        },

        openCreateModal(term) {
            this.modalTerm = term;
            this.editingTaskId = null;
            this.formTitle = '';
            this.formType = 'quiz';
            this.formMaxScore = 100;
            this.formDueDate = '';
            this.publishing = false;
            this.fileSizeError = false;
            this.showModal = true;
        },
        openEditModal(task) {
            this.modalTerm = task.term;
            this.editingTaskId = task.id;
            this.formTitle = task.activity_name;
            this.formType = task.activity_type;
            this.formMaxScore = task.max_score;
            this.formDueDate = task.due_date;
            this.publishing = false;
            this.fileSizeError = false;
            this.showModal = true;
        },
        setQuickDate(daysToAdd, hours = 23, minutes = 59) {
            let d = new Date();
            d.setDate(d.getDate() + daysToAdd);
            d.setHours(hours, minutes, 0, 0);

            let pad = (n) => String(n).padStart(2, '0');
            let year = d.getFullYear();
            let month = pad(d.getMonth() + 1);
            let day = pad(d.getDate());
            let hh = pad(d.getHours());
            let mm = pad(d.getMinutes());

            this.formDueDate = `${year}-${month}-${day}T${hh}:${mm}`;
        },
        deleteTask(id, term) {
            if (!confirm('Are you sure you want to delete this task?')) return;
            fetch(`/professor/delete-activity/${id}`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.href = window.location.pathname + '?tab=' + term;
                } else {
                    alert('Error deleting task');
                }
            });
        },
        bulkMark(status) {
            if (this.isPastDate() || this.hasSaved) return;
            Object.keys(this.attendanceState).forEach(key => {
                this.attendanceState[key] = status;
            });
        },

        markAll(status) {
            if (this.hasSaved) return;
            Object.keys(this.attendanceState).forEach(studentId => {
                this.attendanceState[studentId] = status;
            });
        },

        loadAttendanceData() {
            this.loadingAttendance = true;
            this.isInitialized = false;

            fetch(`/professor/class/{{ $classDetails->schedule_id }}/attendance?date=${this.selectedDate}`)
            .then(res => res.json())
            .then(data => {
                const attendanceKeys = Object.keys(data.existingAttendance || {});
                this.hasSaved = attendanceKeys.length > 0;

                this.attendanceState = {};
                this.remarksState = {};

            data.students.forEach(student => {
                const record = data.existingAttendance[student.student_id] || {};
                this.attendanceState[student.student_id] = record.status || null;
                this.remarksState[student.student_id] = record.remarks || '';
            });

                this.isInitialized = true;
            })
            .finally(() => {
                this.loadingAttendance = false;
            });
        },

        resetGridValues() {
            if (this.hasSaved) return;

            Object.keys(this.attendanceState).forEach(studentId => {
                this.attendanceState[studentId] = null;
                this.remarksState[studentId] = '';
            });

            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        saveAttendanceForm(e) {
            const allStudentsMarked = Object.values(this.attendanceState).every(status => status !== null);

            if (!allStudentsMarked) {
                this.showToast(false, 'Please choose a status for every student.');
                return;
            }

            this.loadingAttendance = true;
            const formData = new FormData(e.target);

            fetch(`/professor/class/{{ $classDetails->schedule_id }}/attendance/save`, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    this.hasSaved = true;
                    this.showToast(true, 'Attendance saved successfully!');
                } else {
                    this.showToast(false, 'Error: ' + data.message);
                }
            })
            .finally(() => { this.loadingAttendance = false; });
        },

        isLocked() {
            return (this.isPastDate() && !this.hasSaved) || this.hasSaved;
        },

        showToast(success, message) {
            this.saveStatus.show = true;
            this.saveStatus.success = success;
            this.saveStatus.message = message;
            setTimeout(() => { this.saveStatus.show = false; }, 4000);
        },

        initAttendance() {
            this.loadAttendanceData();
        }
    }",

    x-init="initAttendance()">

    <div class="page-header-row">
        <a href="{{ route('professor.manage-classes') }}" class="btn-back-link">
            <i class="fa-solid fa-arrow-left-long"></i> Back to My Classes List
        </a>
        <span class="badge-portal">Class Control Panel</span>
    </div>

    <div x-show="saveStatus.show"
         x-transition
         class="alert mb-4 d-flex align-items-center justify-content-between py-3 px-4 rounded-3 border transition"
         :class="saveStatus.success ? 'bg-success-subtle text-success border-success-subtle' : 'bg-danger-subtle text-danger border-danger-subtle'"
         style="display: none; font-weight: 600;">
        <span x-text="saveStatus.message"></span>
        <button type="button" class="btn-close" @click="saveStatus.show = false" style="font-size: 12px;"></button>
    </div>

    <div class="dashboard-grid-wrapper">

        <!-- SIDEBAR -->
        <div class="unified-sidebar">
            <div class="sidebar-subject-card">
                <div class="subject-tag-line mb-2">
                    <span class="code-badge">{{ strtoupper($classDetails->subject_code) }}</span>
                    <span class="bullet-sep">•</span>
                    <span class="section-badge">{{ strtoupper($classDetails->section_name) }}</span>
                </div>
                <h3 class="sidebar-subject-title">{{ $classDetails->subject_title }}</h3>
                <hr class="sidebar-divider">
                <div class="sidebar-meta-list">
                    <div class="meta-item">
                        <i class="fa-regular fa-clock"></i>
                        <div>
                            <strong>{{ $classDetails->class_days }}</strong>
                            <span class="d-block text-muted small">{{ date('h:i A', strtotime($classDetails->start_time)) }} - {{ date('h:i A', strtotime($classDetails->end_time)) }}</span>
                        </div>
                    </div>
                    <div class="meta-item">
                        <i class="fa-solid fa-location-dot"></i>
                        <div><span>Room: <strong>{{ strtoupper($classDetails->room_assignment) }}</strong></span></div>
                    </div>
                </div>
            </div>

            <div class="sidebar-navigation-card">
                <div class="panel-title-label">ACADEMIC WORKSPACE</div>

                <button class="nav-tab-btn" :class="currentTab === 'masterlist' ? 'active-tab' : ''" @click="switchTab('masterlist')">
                    <i class="fa-solid fa-users-rectangle"></i> Student Masterlist
                </button>

                <button class="nav-tab-btn" :class="currentTab === 'attendance' ? 'active-tab' : ''" @click="switchTab('attendance')">
                    <i class="fa-solid fa-calendar-check"></i> Class Attendance
                </button>

                <div class="panel-title-label mt-4">TERM GRADE ENCODING</div>
                @foreach(['prelim' => 'Prelim Term', 'midterm' => 'Midterm', 'semifinal' => 'Semi-Final Term', 'final' => 'Final Term'] as $key => $label)
                    <button class="nav-tab-btn" :class="currentTab === '{{ $key }}' ? 'active-tab' : ''" @click="switchTab('{{ $key }}')">
                        <i class="fa-regular fa-pen-to-square"></i> {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        <div class="unified-workspace">

            <div x-show="currentTab === 'masterlist'" x-transition>
                <div class="workspace-card">
                    <div class="card-workspace-header d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h4 class="m-0 fw-bold text-dark-blue">Student Masterlist Roster</h4>
                            <p class="small text-muted m-0">Official list of enrolled class members</p>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="students-count-badge">{{ count($students) }} Students Enrolled</span>
                            <button type="button" onclick="window.print()" class="btn-print-attendance">
                                <i class="fa-solid fa-print me-1"></i> Print / Download PDF
                            </button>
                        </div>
                    </div>

                    @if($students->isEmpty())
                        <div class="p-5 text-center text-muted">No students officially enrolled in this schedule section.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table custom-portal-table align-middle m-0">
                                <thead>
                                    <tr>
                                        <th style="width: 65px;">#</th>
                                        <th>Student Number</th>
                                        <th>Full Name</th>
                                        <th class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($students as $student)
                                        <tr>
                                            <td><strong>{{ $loop->iteration }}</strong></td>
                                            <td class="fw-semibold text-secondary">{{ $student->student_id }}</td>
                                            <td class="text-uppercase fw-bold text-dark-slate">{{ $student->last_name }}, {{ $student->first_name }}</td>
                                            <td class="text-center"><span class="badge-enrolled-status">Enrolled</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div id="printable-masterlist-area" class="printable-only">
                            <div class="print-header text-center">
                                <h2 class="school-name">ARELLANO UNIVERSITY</h2>
                                <p class="report-subtitle">OFFICIAL STUDENT MASTERLIST</p>
                                <table class="meta-info-table">
                                    <tr>
                                        <td><strong>Subject:</strong> {{ strtoupper($classDetails->subject_code) }} — {{ $classDetails->subject_title }}</td>
                                        <td><strong>Section:</strong> {{ strtoupper($classDetails->section_name) }}</td>
                                        <td><strong>Schedule:</strong> {{ $classDetails->class_days }} ({{ date('h:i A', strtotime($classDetails->start_time)) }} - {{ date('h:i A', strtotime($classDetails->end_time)) }})</td>
                                    </tr>
                                </table>
                            </div>
                            <table class="print-data-table">
                                <thead>
                                    <tr>
                                        <th style="width: 8%;">#</th>
                                        <th style="width: 27%;">Student Number</th>
                                        <th style="width: 45%;">Full Name</th>
                                        <th style="width: 20%;">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($students as $index => $student)
                                        <tr>
                                            <td class="text-center">#{{ $index + 1 }}</td>
                                            <td>{{ $student->student_id }}</td>
                                            <td class="text-uppercase fw-bold">{{ $student->last_name }}, {{ $student->first_name }}</td>
                                            <td class="text-center">Enrolled</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div x-show="currentTab === 'attendance'" x-transition style="display: none;">
                <div class="workspace-card">
                    <form @submit.prevent="saveAttendanceForm($event)">
                        @csrf
                        <div class="attendance-control-panel-wrapper mb-4">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                                <div>
                                    <h4 class="m-0 fw-bold text-dark-blue d-flex align-items-center gap-2">
                                        <span>Class Attendance Logs</span>
                                        <template x-if="hasSaved">
                                            <span class="badge bg-amber-subtle text-amber border border-amber-subtle rounded-pill font-semibold px-2 py-1" style="font-size: 11px; background-color: #fef3c7; color: #d97706; border-color: #fde68a;">
                                                <i class="fa-solid fa-lock me-1"></i> Read-Only Log
                                            </span>
                                        </template>
                                    </h4>
                                    <p class="small text-muted m-0 mt-1">Manage, evaluate, and mass-assign student logs efficiently.</p>
                                </div>

                                <template x-if="hasSaved">
                                    <button type="button" onclick="window.print()" class="btn-print-attendance">
                                        <i class="fa-solid fa-print me-1"></i> Print / Download PDF
                                    </button>
                                </template>
                            </div>

                            <div id="printable-attendance-area" class="printable-only">
                                <div class="print-header text-center">
                                    <h2 class="school-name">ARELLANO UNIVERSITY</h2>
                                    <p class="report-subtitle">OFFICIAL CLASS ATTENDANCE REPORT</p>
                                    <table class="meta-info-table">
                                        <tr>
                                            <td><strong>Subject:</strong> {{ strtoupper($classDetails->subject_code) }} — {{ $classDetails->subject_title }}</td>
                                            <td><strong>Section:</strong> {{ strtoupper($classDetails->section_name) }}</td>
                                            <td><strong>Date:</strong> <span x-text="selectedDate"></span></td>
                                        </tr>
                                    </table>
                                </div>
                                <table class="print-data-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 6%;">#</th>
                                            <th style="width: 18%;">Student ID</th>
                                            <th style="width: 38%;">Full Name</th>
                                            <th style="width: 18%;">Status</th>
                                            <th style="width: 20%;">Internal Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($students as $index => $student)
                                            <tr>
                                                <td class="text-center">#{{ $index + 1 }}</td>
                                                <td>{{ $student->student_id }}</td>
                                                <td class="text-uppercase fw-bold">{{ $student->last_name }}, {{ $student->first_name }}</td>
                                                <td class="text-center">
                                                    <span x-text="attendanceState['{{ $student->student_id }}'] || 'N/A'"></span>
                                                </td>
                                                <td class="text-center">
                                                    <span x-text="remarksState['{{ $student->student_id }}'] && remarksState['{{ $student->student_id }}'].trim() !== '' ? remarksState['{{ $student->student_id }}'] : '—'"></span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <hr class="control-panel-dividerMy my-3">

                            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-stretch align-items-sm-center gap-3">
                                <div class="modern-date-input-box">
                                    <span class="modern-date-label">LOG DATE:</span>
                                    <div class="input-date-inner">
                                        <i class="fa-regular fa-calendar-check text-muted"></i>
                                        <input type="date"
                                            name="attendance_date"
                                            x-model="selectedDate"
                                            @change="loadAttendanceData()"
                                            class="modern-date-picker"
                                            max="{{ now()->setTimezone('Asia/Manila')->format('Y-m-d') }}">
                                    </div>
                                </div>

                                <div class="bulk-actions-wrapper" x-show="!hasSaved && !isPastDate()">
                                    <button type="button" @click="bulkMark('Present')" class="btn-modern-bulk status-bulk-present">
                                        <i class="fa-solid fa-square-check"></i> Mark All Present
                                    </button>
                                    <button type="button" @click="bulkMark('Absent')" class="btn-modern-bulk status-bulk-absent">
                                        <i class="fa-solid fa-square-xmark"></i> Mark All Absent
                                    </button>
                                </div>
                            </div>
                        </div>

                        @if($students->isEmpty())
                            <div class="p-5 text-center text-muted">Cannot encode attendance. No students enrolled.</div>
                        @else
                            <div class="table-responsive position-relative rounded-3 border">
                                <div x-show="loadingAttendance" class="position-absolute top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center bg-white bg-opacity-75" style="z-index: 10; display: none;">
                                    <div class="text-center text-primary">
                                        <i class="fa-solid fa-spinner fa-spin fa-2x mb-2"></i>
                                        <p class="small fw-bold m-0">Syncing record entries...</p>
                                    </div>
                                </div>

                                <div x-show="!loadingAttendance && !hasSaved && isPastDate()" class="empty-attendance-container" style="display: none;">
                                    <div class="empty-attendance-icon"><i class="fa-solid fa-calendar-xmark"></i></div>
                                    <h5 class="fw-bold text-dark-slate">No attendance for that day</h5>
                                    <p class="small">No records were found for the selected date.</p>
                                </div>

                                <div x-show="!loadingAttendance && (hasSaved || !isPastDate())">
                                    <table class="table custom-portal-table align-middle m-0">
                                        <thead>
                                            <tr>
                                                <th style="width: 70px;" class="text-center">#</th>
                                                <th>Student ID</th>
                                                <th>Full Name</th>
                                                <th style="width: 310px;" class="text-center">Status Allocation</th>
                                                <th class="text-center">Internal Remarks</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($students as $index => $student)
                                            <tr>
                                                <td class="text-center text-muted fw-bold">#{{ $index + 1 }}</td>
                                                <td class="fw-semibold text-secondary">{{ $student->student_id }}</td>
                                                <td class="text-uppercase fw-bold text-dark-slate">{{ $student->last_name }}, {{ $student->first_name }}</td>

                                                <td class="text-center align-middle">
                                                    <input type="hidden" :name="'attendance[' + '{{ $student->student_id }}' + ']'" :value="attendanceState['{{ $student->student_id }}']">

                                                    <template x-if="hasSaved">
                                                        <span class="badge"
                                                            :class="{
                                                                'bg-success': attendanceState['{{ $student->student_id }}'] === 'Present',
                                                                'bg-warning': attendanceState['{{ $student->student_id }}'] === 'Late',
                                                                'bg-danger': attendanceState['{{ $student->student_id }}'] === 'Absent',
                                                                'bg-secondary': !attendanceState['{{ $student->student_id }}']
                                                            }"
                                                            x-text="attendanceState['{{ $student->student_id }}'] || 'N/A'">
                                                        </span>
                                                    </template>

                                                    <template x-if="!hasSaved">
                                                        <div class="btn-group-toggle">
                                                            <button type="button" class="attendance-toggle-btn" :class="attendanceState['{{ $student->student_id }}'] === 'Present' ? 'active-present' : ''" @click="attendanceState['{{ $student->student_id }}'] = 'Present'">Present</button>
                                                            <button type="button" class="attendance-toggle-btn" :class="attendanceState['{{ $student->student_id }}'] === 'Late' ? 'active-late' : ''" @click="attendanceState['{{ $student->student_id }}'] = 'Late'">Late</button>
                                                            <button type="button" class="attendance-toggle-btn" :class="attendanceState['{{ $student->student_id }}'] === 'Absent' ? 'active-absent' : ''" @click="attendanceState['{{ $student->student_id }}'] = 'Absent'">Absent</button>
                                                        </div>
                                                    </template>
                                                </td>

                                                <td class="align-middle text-center">
                                                    <template x-if="hasSaved">
                                                        <span class="text-muted small" x-text="remarksState['{{ $student->student_id }}'] && remarksState['{{ $student->student_id }}'].trim() !== '' ? remarksState['{{ $student->student_id }}'] : '—'"></span>
                                                    </template>
                                                    <template x-if="!hasSaved">
                                                        <input type="text" :name="'remarks[' + '{{ $student->student_id }}' + ']'" x-model="remarksState['{{ $student->student_id }}']" placeholder="Add remarks..." class="form-control attendance-remarks-field">
                                                    </template>
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <template x-if="!hasSaved && !isPastDate()">
                                <div class="d-flex justify-content-end align-items-center mt-4 pt-3 border-top gap-3">
                                    <button type="button" @click="resetGridValues()" class="btn-cancel-portal px-4 py-2">
                                        <i class="fa-solid fa-arrows-rotate me-2"></i> Reset
                                    </button>
                                    <button type="submit" class="btn-primary-portal px-4 py-2" :disabled="loadingAttendance">
                                        <i class="fa-solid fa-floppy-disk me-2"></i> Save Attendance
                                    </button>
                                </div>
                            </template>
                        @endif
                    </form>
                </div>
            </div>

            @foreach(['prelim' => 'Prelim', 'midterm' => 'Midterm', 'semifinal' => 'Semi-Final', 'final' => 'Final'] as $key => $termLabel)
                @php
                    $termCourseWork = $gCourseWork->where('term', $key);
                @endphp
                <div x-show="currentTab === '{{ $key }}'" x-transition style="display: none;"
                    x-data="{
                            termSubTab: new URLSearchParams(window.location.search).get('subtab') || 'deliverables'
                        }">
                    <div class="workspace-card">

                        <div class="card-workspace-header mb-3">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                                <div>
                                    <h4 class="m-0 fw-bold text-dark-blue">{{ $termLabel }} Term Panel</h4>
                                    <p class="small text-muted m-0">Manage activities, deliverables, and official grade entries for this term.</p>
                                </div>

                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <template x-if="termSubTab === 'deliverables'">
                                        <button type="button" class="btn-add-activity" @click="openCreateModal('{{ $key }}')">
                                            <i class="fa-solid fa-plus"></i> Add Task
                                        </button>
                                    </template>

                                </div>
                            </div>
                        </div>

                        <div class="term-subtab-bar">
                            <button type="button" class="term-subtab-btn" :class="termSubTab === 'deliverables' ? 'term-subtab-active' : ''" @click="termSubTab = 'deliverables'">
                                <i class="fa-solid fa-folder-open"></i> Deliverables
                            </button>
                            <button type="button" class="term-subtab-btn" :class="termSubTab === 'encoding' ? 'term-subtab-active' : ''" @click="termSubTab = 'encoding'">
                                <i class="fa-solid fa-calculator"></i> Grade Encoding
                            </button>
                        </div>

                        <div x-show="termSubTab === 'deliverables'">

                            @php
                                $mergedDeliverables = collect();$currentTermTasks = collect($termTasks->get($key, []));

                                $systemTaskTitles =$currentTermTasks->pluck('activity_name')->map(fn($t) => strtolower(trim($t)))->toArray();

                                foreach ($currentTermTasks as$task) {
                                    $isGoogleTask = !empty($task->google_coursework_id);

                                    $gLink = null;
                                    if ($isGoogleTask) {$gLink = DB::table('google_classroom_coursework')
                                            ->where('google_coursework_id', $task->google_coursework_id)
                                            ->value('alternate_link');
                                    }

                                    $mergedDeliverables->push((object)[
                                        'source'     => $isGoogleTask ? 'gclass_coursework' : 'system',
                                        'id'         => $task->id,
                                        'title'      => $task->activity_name,
                                        'type'       => $task->activity_type,
                                        'max_score'  => $task->max_score,
                                        'due_date'   => $task->due_date,
                                        'link'       => $gLink,
                                        'sort_date'  => $task->due_date ?? '1970-01-01',
                                    ]);
                                }

                                if (isset($gConnection) &&$gConnection && isset($gLinkedCourse) &&$gLinkedCourse) {
                                    if (isset($gCourseWork)) {$filteredGCourseWork = $gCourseWork->where('term',$key);
                                        foreach ($filteredGCourseWork as$cw) {
                                            $existsInDb = DB::table('student_tasks')->where('google_coursework_id', $cw->google_coursework_id)->exists();$titleMatches = in_array(strtolower(trim($cw->title)),$systemTaskTitles);

                                            if (!$existsInDb && !$titleMatches) {$mergedDeliverables->push((object)[
                                                    'source'     => 'gclass_coursework',
                                                    'id'         => $cw->id,
                                                    'title'      => $cw->title,
                                                    'type'       => 'coursework',
                                                    'max_score'  => $cw->max_points ?? null,
                                                    'due_date'   => $cw->due_date,
                                                    'link'       => $cw->alternate_link,
                                                    'sort_date'  => $cw->due_date ?? '1970-01-01',
                                                ]);
                                            }
                                        }
                                    }

                                    if (isset($gMaterials)) {
                                        $termMaterials =$gMaterials->filter(function($mat) use ($key, $termPeriods) {$postDate = $mat->posted_at ? date('Y-m-d', strtotime($mat->posted_at)) : null;
                                            if (!$postDate) return false;
                                            if (isset($termPeriods) && count($termPeriods) > 0) {$matched = $termPeriods->first(fn($p) => $postDate >=$p->start_date && $postDate <=$p->end_date);
                                                return $matched && $matched->term ===$key;
                                            }
                                            return isset($mat->term) && $mat->term ===$key;
                                        });

                                        foreach ($termMaterials as $mat) {$mergedDeliverables->push((object)[
                                                'source'     => 'gclass_material',
                                                'id'         => $mat->id,
                                                'title'      => $mat->title,
                                                'type'       => 'material',
                                                'max_score'  => null,
                                                'due_date'   => null,
                                                'posted_at'  => $mat->created_at ?? null,
                                                'link'       => $mat->alternate_link ?? null,
                                                'sort_date'  => $mat->created_at ?? '1970-01-01',
                                            ]);
                                        }
                                    }
                                }

                                $mergedDeliverables =$mergedDeliverables->sortByDesc('sort_date')->values();
                            @endphp

                            @if($gConnection && !$gLinkedCourse)
                                <div class="gclass-link-box mb-3">
                                    <p class="gclass-link-text">Select which Google Classroom course corresponds to this class section to include its posts here.</p>
                                    @if($gAvailableCourses->isEmpty())
                                        <p class="text-muted small">No synced courses found. Try syncing your Google Classroom account first.</p>
                                    @else
                                        <form action="{{ route('professor.google-classroom.link', $classDetails->schedule_id) }}" method="POST" class="d-flex gap-2">
                                            @csrf
                                            <select name="google_course_id" class="field-input" style="max-width: 280px;" required>
                                                <option value="">Select a course...</option>
                                                @foreach($gAvailableCourses as $course)
                                                    <option value="{{ $course->id }}">{{ $course->course_name }} {{ $course->course_section ? '(' . $course->course_section . ')' : '' }}</option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="btn-add-activity">
                                                <i class="fa-solid fa-link"></i> Link Course
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            @endif

                            @if($gConnection && $gLinkedCourse)
                                <div class="gclass-linked-row mb-3">
                                    
                                    <span class="gclass-linked-info">
                                        <i class="fa-brands fa-google-classroom"></i>
                                        Linked to: <strong>{{ $gLinkedCourse->course_name }}</strong>
                                    </span>                          
                                    
                                    <div class="gclass-sync-group">
                                        
                                        <form action="{{ route('professor.google-classroom.sync') }}" method="POST" class="m-0">
                                            @csrf
                                            <button type="submit" class="btn-sync-icon-sm" title="Sync Now">
                                                <i class="fa-solid fa-arrows-rotate"></i>
                                            </button>
                                        </form>
                                        
                                        @php
                                            $syncTime = $gLinkedCourse->last_synced_at 
                                                ?? $gLinkedCourse->updated_at 
                                                ?? ($gConnection->last_synced_at ?? null);
                                        @endphp
                                        <span class="gclass-sync-timestamp">
                                            Last synced: {{ $syncTime ? \Carbon\Carbon::parse($syncTime)->diffForHumans() : 'Never' }}
                                        </span>
                                        
                                    </div>
                                </div>
                            @endif

                            <div class="activity-list-container">
                                @if($mergedDeliverables->isEmpty())
                                    <div class="empty-state-box">
                                        <div class="empty-state-icon"><i class="fa-solid fa-folder-plus"></i></div>
                                        <h5 class="empty-state-title">No {{ $termLabel }} Deliverables Configured Yet</h5>
                                        <p class="empty-state-text">Click the button above to add standard quizzes, classworks, or assignments.</p>
                                    </div>
                                @else
                                    <div class="table-responsive">
                                        <table class="table custom-portal-table align-middle">
                                            <thead>
                                                <tr>
                                                    <th>Title</th>
                                                    <th>Type</th>
                                                    <th>Max Score</th>
                                                    <th>Due Date</th>
                                                    <th class="text-center">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($mergedDeliverables as $item)
                                                    <tr>
                                                        <td>
                                                            <strong class="text-dark-blue">{{ $item->title }}</strong>
                                                            @if($item->source !== 'system')
                                                                <span class="gclass-source-tag"><i class="fa-brands fa-google-classroom"></i> Google Classroom</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if($item->source === 'system')
                                                                <span class="badge bg-secondary text-uppercase">{{ $item->type }}</span>
                                                            @elseif($item->source === 'gclass_coursework')
                                                                <span class="gclass-type-badge gclass-type-coursework">Coursework</span>
                                                            @elseif($item->source === 'gclass_material')
                                                                <span class="gclass-type-badge" style="background: #e0e7ff; color: #3730a3;">Material</span>
                                                            @else
                                                                <span class="gclass-type-badge gclass-type-announcement">Announcement</span>
                                                            @endif
                                                        </td>
                                                        <td>{{ $item->max_score ? $item->max_score . ' pts' : '—' }}</td>
                                                        <td>
                                                            @if($item->due_date)
                                                                {{ \Carbon\Carbon::parse($item->due_date)->format('M d, Y h:i A') }}
                                                            @elseif(isset($item->posted_at) && $item->posted_at)
                                                                Posted {{ \Carbon\Carbon::parse($item->posted_at)->format('M d, Y') }}
                                                            @else
                                                                No Deadline
                                                            @endif
                                                        </td>
                                                        <td class="text-center">
                                                            @if($item->source === 'system')
                                                                <div class="action-btn-group">
                                                                    <button type="button" class="btn-sm-outline btn-edit"
                                                                        @click="openEditModal({ id: {{ $item->id }}, term: '{{ $key }}', activity_name: @js($item->title), activity_type: '{{ $item->type }}', max_score: {{ $item->max_score }}, due_date: '{{ $item->due_date }}' })">
                                                                        <i class="fa-solid fa-pen"></i>
                                                                    </button>
                                                                    <a href="{{ route('professor.task-submissions', $item->id) }}" class="btn-sm-outline btn-grade">
                                                                        <i class="fa fa-star"></i> Grade
                                                                    </a>
                                                                    <button type="button" class="btn-sm-outline btn-delete" @click="deleteTask({{ $item->id }}, '{{ $key }}')">
                                                                        <i class="fa-solid fa-trash"></i>
                                                                    </button>
                                                                </div>
                                                            @elseif($item->source === 'gclass_coursework')
                                                                <div class="action-btn-group">
                                                                    @if($item->link)
                                                                        <a href="{{ $item->link }}" target="_blank" class="btn-sm-outline" title="Open in Google Classroom">
                                                                            <i class="fa-solid fa-arrow-up-right-from-square"></i> Open
                                                                        </a>
                                                                    @endif
                                                                    <a href="{{ route('professor.task-submissions', $item->id) }}" class="btn-sm-outline btn-grade" title="Grade Google Classroom Submissions">
                                                                        <i class="fa fa-star"></i> Grade
                                                                    </a>
                                                                </div>
                                                            @elseif($item->source === 'gclass_material')
                                                                @if($item->link)
                                                                    <div class="action-btn-group">
                                                                        <a href="{{ $item->link }}" target="_blank" class="btn-sm-outline">
                                                                            <i class="fa-solid fa-arrow-up-right-from-square"></i> Open
                                                                        </a>
                                                                    </div>
                                                                @else
                                                                    <span class="text-muted small">—</span>
                                                                @endif
                                                            @else
                                                                <span class="text-muted small">—</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                        </div>

                        @php
                            $gradeModes = [];
                            $manualGrades = [];

                            $termDraftCollection = $draftGrades->get($key);
                            $isTermSubmitted = $termDraftCollection ? $termDraftCollection->where('is_submitted', 1)->count() > 0 : false;

                            foreach ($students as $student) {
                                $draft = optional(
                                    $draftGrades->get($key)?->firstWhere('student_number', $student->student_id)
                                );

                                $gradeModes[$student->student_id] = ($draft && $draft->is_manual) ? 'manual' : 'auto';

                                $autoGradeStudent = $autoGrades[$key][$student->student_id] ?? [];

                                $manualGrades[$student->student_id] = [
                                    'class_standing' => $draft->class_standing ?? $autoGradeStudent['class_standing'] ?? '',
                                    'major_exam'     => $draft->major_exam ?? $autoGradeStudent['major_exam'] ?? '',
                                    'equivalent'     => $draft->equivalent ?? $autoGradeStudent['equivalent'] ?? '',
                                ];
                            }
                        @endphp

                        <div x-show="termSubTab === 'encoding'" style="display: none;"
                            x-data="{
                                gradeMode: @js($gradeModes),
                                manualGrades: @js($manualGrades),
                                cachedManualGrades: JSON.parse(JSON.stringify(@js($manualGrades))),
                                autoGradesData: @js($autoGrades[$key] ?? []),
                                hasUnsavedChanges: false,
                                isSubmitted: @js($isTermSubmitted),

                                init() {
                                    window.addEventListener('beforeunload', (event) => {
                                        if (this.hasUnsavedChanges) {
                                            event.preventDefault();
                                            event.returnValue = '';
                                        }
                                    });
                                },

                                async setMode(sid, mode) {
                                    if (this.gradeMode[sid] === 'manual' && mode === 'auto') {
                                        this.cachedManualGrades[sid] = JSON.parse(JSON.stringify(this.manualGrades[sid]));
                                    }

                                    this.gradeMode[sid] = mode;
                                    this.hasUnsavedChanges = true;

                                    if (mode === 'auto') {
                                        let response = await fetch('{{ route('professor.recalculate-grade') }}', {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/json',
                                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                            },
                                            body: JSON.stringify({
                                                schedule_id: {{ $classDetails->schedule_id }},
                                                student_number: sid,
                                                term: '{{ $key }}'
                                            })
                                        });

                                        let data = await response.json();
                                        if (data.success) {
                                            this.autoGradesData[sid] = data.computed;
                                            this.manualGrades[sid] = {
                                                class_standing: data.computed.class_standing ?? '',
                                                major_exam: data.computed.major_exam ?? '',
                                                equivalent: data.computed.equivalent ?? ''
                                            };
                                        }
                                    } else {
                                        if (this.cachedManualGrades[sid]) {
                                            this.manualGrades[sid] = JSON.parse(JSON.stringify(this.cachedManualGrades[sid]));
                                        }
                                    }
                                },

                                markChanged() {
                                    this.hasUnsavedChanges = true;
                                },

                                saveDraftGrades() {
                                    let grades = [];
                                    for (let sid in this.manualGrades) {
                                        grades.push({
                                            student_number: sid,
                                            schedule_id: {{ $classDetails->schedule_id }},
                                            term: '{{ $key }}',
                                            class_standing: this.manualGrades[sid].class_standing,
                                            major_exam: this.manualGrades[sid].major_exam,
                                            equivalent: this.manualGrades[sid].equivalent,
                                            is_manual: this.gradeMode[sid] === 'manual'
                                        });
                                    }

                                    fetch('{{ route('professor.save-grades') }}', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                        },
                                        body: JSON.stringify({ grades: grades })
                                    })
                                    .then(res => res.json())
                                    .then(data => {
                                        if(data.success) {
                                            this.hasUnsavedChanges = false;
                                            alert('Grades saved successfully!');
                                            this.cachedManualGrades = JSON.parse(JSON.stringify(this.manualGrades));
                                        } else {
                                            alert('Error saving grades.');
                                        }
                                    });
                                },

                                submitToRegistrar() {
                                    if (this.hasUnsavedChanges) {
                                        alert('Please save your draft changes before submitting to the registrar.');
                                        return;
                                    }

                                    if (!confirm('Are you sure you want to submit these grades to the registrar? This action cannot be undone.')) {
                                        return;
                                    }

                                    fetch('{{ route('professor.submit-registrar') }}', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                        },
                                        body: JSON.stringify({
                                            schedule_id: {{ $classDetails->schedule_id }},
                                            term: '{{ $key }}'
                                        })
                                    })
                                    .then(res => res.json())
                                    .then(data => {
                                        if (data.success) {
                                            alert('Grades successfully submitted to the registrar!');
                                            window.location.reload();
                                        } else {
                                            alert('Error: ' + (data.message || 'Failed to submit grades.'));
                                        }
                                    })
                                    .catch(err => {
                                        console.error(err);
                                        alert('Something went wrong. Please try again.');
                                    });
                                }
                            }"
                            x-init="init()">

                            <div class="grade-top-row mb-3">
                                <div class="encoding-legend-bar">
                                    <span class="legend-item"><i class="fa-solid fa-wand-magic-sparkles"></i> Auto — computed from task scores & attendance</span>
                                    <span class="legend-item"><i class="fa-solid fa-pen"></i> Manual — professor enters custom score</span>
                                </div>

                                <template x-if="!isSubmitted">
                                    <div class="grade-action-buttons">
                                        <button
                                            type="button"
                                            class="btn-sm-outline text-dark-blue fw-bold bg-white"
                                            @click="saveDraftGrades()">
                                            <i class="fa-solid fa-floppy-disk me-1"></i> Save Draft
                                        </button>
                                        <button 
                                            type="button" 
                                            class="btn-primary-portal fw-bold" 
                                            style="font-size: 13px;" 
                                            @click="submitToRegistrar()">
                                            <i class="fa-solid fa-paper-plane me-1"></i> Submit to Registrar
                                        </button>
                                    </div>
                                </template>

                                <template x-if="isSubmitted">
                                    <div class="submitted-status-pill">
                                        <i class="fa-solid fa-circle-check"></i>
                                        <span>Grades Submitted to Registrar</span>
                                    </div>
                                </template>
                            </div>

                            @if($students->isEmpty())
                                <div class="empty-state-box">
                                    <div class="empty-state-icon"><i class="fa-solid fa-calculator"></i></div>
                                    <h5 class="empty-state-title">No Students Available</h5>
                                    <p class="empty-state-text">Grade encoding needs enrolled students to display.</p>
                                </div>
                            @else
                                <div class="table-responsive">
                                    <table class="table custom-portal-table align-middle encoding-table">
                                        <thead>
                                            <tr>
                                                <th style="width: 5%;">#</th>
                                                <th style="width: 15%;">Student ID</th>
                                                <th style="width: 22%;">Full Name</th>
                                                <th style="width: 18%;" class="text-center">Class Standing (60%)</th>
                                                <th style="width: 13%;" class="text-center">Major Exam (40%)</th>
                                                <th style="width: 9%;" class="text-center">GWA</th>
                                                <th style="width: 18%;" class="text-center">Mode</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($students as $index => $student)
                                            @php
                                                $sid = $student->student_id;
                                                $auto = $autoGrades[$key][$sid] ?? ['class_standing' => null, 'major_exam' => null, 'equivalent' => null];
                                            @endphp
                                            <tr>
                                                <td class="text-muted fw-bold">{{ $index + 1 }}</td>
                                                <td class="fw-semibold text-secondary">{{ $sid }}</td>
                                                <td class="text-uppercase fw-bold text-dark-slate">{{ $student->last_name }}, {{ $student->first_name }}</td>

                                                <td class="text-center">
                                                    <div class="encoding-cell-row">
                                                        <input type="number" min="0" max="100" step="0.01" class="encoding-input"
                                                            x-model.number="manualGrades['{{ $sid }}'].class_standing"
                                                            placeholder="—"
                                                            :readonly="(gradeMode['{{ $sid }}'] || 'auto') !== 'manual' || isSubmitted"
                                                            :disabled="isSubmitted"
                                                            :class="(gradeMode['{{ $sid }}'] || 'auto') === 'manual' && !isSubmitted ? 'encoding-input-active' : 'encoding-input-muted'"
                                                            @input="
                                                                markChanged();
                                                                let value = parseFloat($event.target.value);
                                                                if (isNaN(value)) {
                                                                    manualGrades['{{ $sid }}'].class_standing = '';
                                                                } else {
                                                                    if (value > 100) value = 100;
                                                                    if (value < 0) value = 0;
                                                                    manualGrades['{{ $sid }}'].class_standing = value;
                                                                    $event.target.value = value;
                                                                }
                                                            ">

                                                        <a href="{{ route('professor.grades.breakdown', ['schedule_id' => $classDetails->schedule_id, 'student_id' => $sid, 'term' => $key]) }}" class="breakdown-icon-link" title="View individual activity scores and attendance breakdown">
                                                            <i class="fa-solid fa-eye"></i>
                                                        </a>
                                                    </div>
                                                </td>

                                                <td class="text-center">
                                                    <input type="number" min="0" max="100" step="0.01" class="encoding-input"
                                                        x-model.number="manualGrades['{{ $sid }}'].major_exam"
                                                        placeholder="—"
                                                        :readonly="(gradeMode['{{ $sid }}'] || 'auto') !== 'manual' || isSubmitted"
                                                        :disabled="isSubmitted"
                                                        :class="(gradeMode['{{ $sid }}'] || 'auto') === 'manual' && !isSubmitted ? 'encoding-input-active' : 'encoding-input-muted'"
                                                        @input="
                                                            markChanged();
                                                            let value = parseFloat($event.target.value);
                                                            if (isNaN(value)) {
                                                                manualGrades['{{ $sid }}'].major_exam = '';
                                                            } else {
                                                                if (value > 100) value = 100;
                                                                if (value < 0) value = 0;
                                                                manualGrades['{{ $sid }}'].major_exam = value;
                                                                $event.target.value = value;
                                                            }
                                                        ">
                                                </td>

                                                <td class="text-center">
                                                    <span class="equivalent-pill">
                                                        <input type="text" class="equivalent-input"
                                                            x-model="manualGrades['{{ $sid }}'].equivalent"
                                                            placeholder="—"
                                                            maxlength="4"
                                                            :readonly="(gradeMode['{{ $sid }}'] || 'auto') !== 'manual' || isSubmitted"
                                                            :disabled="isSubmitted"
                                                            :class="(gradeMode['{{ $sid }}'] || 'auto') === 'manual' && !isSubmitted ? 'encoding-input-active' : 'encoding-input-muted'"
                                                            @input="
                                                                markChanged();
                                                                let v = $event.target.value.replace(/[^0-9.]/g, '');
                                                                const parts = v.split('.');
                                                                if (parts.length > 2) v = parts[0] + '.' + parts.slice(1).join('');
                                                                if (parts[0] && parts[0].length > 1) parts[0] = parts[0].slice(0, 1);
                                                                if (parts[1]) parts[1] = parts[1].slice(0, 2);
                                                                v = parts[1] !== undefined ? parts[0] + '.' + parts[1] : (parts[0] || '');
                                                                manualGrades['{{ $sid }}'].equivalent = v;
                                                            ">
                                                    </span>
                                                </td>

                                                <td class="text-center">
                                                    <div class="mode-toggle-group" :class="isSubmitted ? 'disabled-group' : ''">
                                                        <button type="button"
                                                            class="mode-toggle-btn"
                                                            :class="(gradeMode['{{ $sid }}'] || 'auto') === 'auto' ? 'mode-toggle-active-auto' : ''"
                                                            :disabled="isSubmitted"
                                                            @click="if(!isSubmitted) setMode('{{ $sid }}', 'auto')">
                                                            <i class="fa-solid fa-wand-magic-sparkles"></i> Auto
                                                        </button>
                                                        
                                                        <button type="button"
                                                            class="mode-toggle-btn"
                                                            :class="gradeMode['{{ $sid }}'] === 'manual' ? 'mode-toggle-active-manual' : ''"
                                                            :disabled="isSubmitted"
                                                            @click="if(!isSubmitted) setMode('{{ $sid }}', 'manual')">
                                                            <i class="fa-solid fa-pen"></i> Manual
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="custom-modal-backdrop" x-show="showModal" style="display: none;" x-transition
        x-data="{ dragOver: false, selectedFile: null, fileName: '', fileSize: '' }">
        <div class="custom-modal-card" @click.away="showModal = false">
            <div class="modal-card-header">
                <div>
                    <h4><i class="fa-solid fa-folder-plus text-primary"></i> <span x-text="editingTaskId ? 'Edit Task' : 'Add New Task'"></span></h4>
                    <span class="modal-term-tag text-capitalize" x-text="modalTerm + ' Term'"></span>
                </div>
                <button type="button" class="close-modal-btn" @click="showModal = false">&times;</button>
            </div>

            <form id="addActivityForm" @submit.prevent="
                publishing = true;
                let formData = new FormData($el);
                formData.append('term', modalTerm);
                formData.append('schedule_id', '{{ $classDetails->schedule_id }}');
                if (selectedFile) formData.append('attachment', selectedFile);

                let url = editingTaskId
                    ? `/professor/update-activity/${editingTaskId}`
                    : '{{ route('professor.save-activity') }}';

                fetch(url, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        window.location.reload();
                    } else {
                        alert(data.message || 'Error saving data');
                        publishing = false;
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Something went wrong. Please try again.');
                    publishing = false;
                });
            ">

                <div class="modal-card-body">
                    <div class="field-group">
                        <label class="field-label">Task Title</label>
                        <input type="text" name="title" class="field-input" x-model="formTitle" placeholder="e.g., Quiz 1: Introduction to HTML" required>
                    </div>

                    <div class="field-row">
                        <div class="field-group">
                            <label class="field-label">Type</label>
                            <select name="type" class="field-input" x-model="formType" required>
                                <option value="quiz">Quiz</option>
                                <option value="assignment">Assignment</option>
                                <option value="project">Project / Laboratory</option>
                                <option value="exam">Major Exam</option>
                                <option value="module">Module / Learning Material</option>
                            </select>
                        </div>

                        <div class="field-group" x-show="formType !== 'module'">
                            <label class="field-label">Highest Score</label>
                            <input type="number" name="max_score" class="field-input" x-model="formMaxScore" min="1" :required="formType !== 'module'">
                        </div>
                    </div>

                    <div class="field-group">
                        <label class="field-label">Due Date <span class="field-optional">(Optional)</span></label>
                        <input type="datetime-local" name="due_date" class="field-input" x-model="formDueDate">
                        
                        <div class="quick-date-options">
                            <button type="button" class="btn-quick-date" @click="setQuickDate(0, 23, 59)">
                                <i class="fa-regular fa-clock"></i> Today 11:59 PM
                            </button>
                            <button type="button" class="btn-quick-date" @click="setQuickDate(1, 23, 59)">
                                <i class="fa-solid fa-calendar-day"></i> Tomorrow 11:59 PM
                            </button>
                            <button type="button" class="btn-quick-date" @click="setQuickDate(7, 23, 59)">
                                <i class="fa-solid fa-calendar-week"></i> Next Week 11:59 PM
                            </button>
                            <button type="button" class="btn-quick-date btn-quick-clear" @click="formDueDate = ''" x-show="formDueDate">
                                <i class="fa-solid fa-xmark"></i> Clear
                            </button>
                        </div>
                    </div>

                    <div class="field-group" style="margin-bottom:0;">
                        <label class="field-label">Attachment <span class="field-optional">(Optional)</span></label>

                        <div class="dropzone"
                            :class="dragOver ? 'dropzone-active' : ''"
                            @dragover.prevent="dragOver = true"
                            @dragleave.prevent="dragOver = false"
                            @drop.prevent="
                                dragOver = false;
                                const file = $event.dataTransfer.files[0];
                                if (file) {
                                    if (file.size > 25 * 1024 * 1024) {
                                        fileSizeError = true;
                                        selectedFile = null;
                                        $refs.fileInput.value = '';
                                    } else {
                                        fileSizeError = false;
                                        selectedFile = file;
                                        fileName = file.name;
                                        fileSize = (file.size / 1024).toFixed(0) + ' KB';
                                    }
                                }
                            "
                            @click="$refs.fileInput.click()"
                            x-show="!selectedFile">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                            <p class="dz-title">Drag & drop file here</p>
                            <p class="dz-subtitle">or click to browse — PDF, DOC, PPT, ZIP, JPG, PNG</p>
                        </div>

                        <div x-show="fileSizeError" x-cloak style="color: #dc3545; font-size: 13px; margin-top: 8px; font-weight: 600;">
                            The file you selected is too large! Maximum allowed size is 25MB.
                        </div>

                        <div class="dz-file-preview" x-show="selectedFile" x-cloak>
                            <i class="fa-solid fa-file-circle-check"></i>
                            <div class="dz-file-info">
                                <span class="dz-file-name" x-text="fileName"></span>
                                <span class="dz-file-size" x-text="fileSize"></span>
                            </div>
                            <button type="button" class="dz-file-remove" @click="selectedFile = null; fileName = ''; fileSize = ''; fileSizeError = false; $refs.fileInput.value = ''">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>

                        <input type="file" x-ref="fileInput" class="visually-hidden-input"
                            accept=".pdf,.doc,.docx,.ppt,.pptx,.zip,.jpg,.jpeg,.png"
                            @change="
                                const file = $event.target.files[0];
                                if (file) {
                                    if (file.size > 25 * 1024 * 1024) {
                                        fileSizeError = true;
                                        selectedFile = null;
                                        $event.target.value = '';
                                    } else {
                                        fileSizeError = false;
                                        selectedFile = file;
                                        fileName = file.name;
                                        fileSize = (file.size / 1024).toFixed(0) + ' KB';
                                    }
                                }
                            ">
                    </div>
                </div>
                <div class="modal-card-footer">
                    <button type="button" class="btn-cancel-portal" @click="showModal = false">Cancel</button>
                    <button type="submit" class="btn-primary-portal" :disabled="fileSizeError || publishing">
                        <span x-html="publishing ? '<i class=\'fa-solid fa-spinner fa-spin\' style=\'margin-right: 6px;\'></i> Publishing...' : (editingTaskId ? 'Save Changes' : 'Publish Task')"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
    .page-header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .text-dark-blue { color: #1e2a5a; }
    .text-dark-slate { color: #334155; }
    .btn-back-link { text-decoration: none; color: #475569; font-weight: 600; font-size: 13.5px; transition: 0.2s; }
    .btn-back-link:hover { color: #1e2a5a; }
    .badge-portal { background: #f1f5f9; color: #475569; font-weight: 700; font-size: 11px; padding: 6px 14px; border-radius: 30px; text-transform: uppercase; border: 1px solid #cbd5e1; }
    .dashboard-grid-wrapper { display: flex; gap: 24px; align-items: stretch; width: 100%; }
    .unified-sidebar { width: 300px; flex-shrink: 0; display: flex; flex-direction: column; gap: 16px; }
    .unified-workspace { flex-grow: 1; min-width: 0; }
    .sidebar-subject-card { background: white; border-radius: 12px; padding: 20px; border: 1px solid #e2e8f0; border-top: 5px solid #1e2a5a; }
    .subject-tag-line { display: flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 800; }
    .code-badge { color: #0284c7; }
    .bullet-sep { color: #94a3b8; }
    .section-badge { color: #1e2a5a; }
    .sidebar-subject-title { font-size: 16px; font-weight: 800; color: #1e293b; margin: 4px 0; }
    .sidebar-divider { margin: 14px 0; border-color: #e2e8f0; }
    .sidebar-meta-list { display: flex; flex-direction: column; gap: 12px; }
    .meta-item { display: flex; gap: 10px; font-size: 13px; color: #475569; }
    .sidebar-navigation-card { background: white; border-radius: 12px; padding: 16px; border: 1px solid #e2e8f0; }
    .panel-title-label { font-size: 10.5px; font-weight: 800; color: #94a3b8; letter-spacing: 0.5px; padding-left: 8px; margin-bottom: 8px; }
    .nav-tab-btn { width: 100%; display: flex; align-items: center; gap: 10px; background: transparent; border: none; text-align: left; padding: 10px 14px; border-radius: 8px; font-size: 13.5px; font-weight: 600; color: #475569; margin-bottom: 4px; transition: 0.2s; cursor: pointer; }
    .nav-tab-btn:hover { background: #f8fafc; color: #1e2a5a; }
    .active-tab { background: #1e2a5a !important; color: white !important; }
    .active-tab i { color: #facc15 !important; }
    .workspace-card { background: white; border-radius: 12px; border: 1px solid #e2e8f0; padding: 24px; min-height: 520px; box-shadow: 0 1px 3px rgba(0,0,0,0.01); }
    .students-count-badge { background: #eff6ff; color: #1e40af; font-size: 12.5px; font-weight: 700; padding: 6px 14px; border-radius: 30px; }
    .custom-portal-table { border: 1px solid #f1f5f9; width: 100%; }
    .custom-portal-table th { background: #f8fafc; font-size: 11.5px; font-weight: 700; text-transform: uppercase; color: #64748b; padding: 14px 12px; border-bottom: 2px solid #e2e8f0; letter-spacing: 0.3px; }
    .custom-portal-table td { padding: 12px; font-size: 13.5px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    .badge-enrolled-status { background: #dcfce7; color: #15803d; font-size: 12px; padding: 4px 12px; font-weight: 700; border-radius: 20px; }

    .term-subtab-bar {
        display: flex;
        gap: 4px;
        border-bottom: 2px solid #f1f5f9;
        margin-bottom: 22px;
    }
    .term-subtab-btn {
        background: transparent;
        border: none;
        padding: 10px 18px;
        font-size: 13px;
        font-weight: 700;
        color: #94a3b8;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        border-bottom: 2px solid transparent;
        margin-bottom: -2px;
        transition: 0.2s;
    }
    .term-subtab-btn:hover {
        color: #475569;
    }
    .term-subtab-active {
        color: #1e2a5a !important;
        border-bottom-color: #1e2a5a;
    }

    .gclass-linked-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        padding: 8px 14px;
        border-radius: 8px;
        flex-wrap: wrap;
        gap: 10px;
    }

    .gclass-linked-info {
        font-size: 12.5px;
        color: #166534;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .gclass-sync-group {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .gclass-sync-timestamp {
        font-size: 13px;
        color: #374151;
        font-weight: 500;
        white-space: nowrap;
    }

    .gclass-linked-info i { font-size: 14px; }
    .gclass-linked-info strong { color: #14532d; }

    .btn-sync-icon-sm {
        width: 26px; height: 26px;
        border-radius: 50%;
        border: none;
        background: white;
        color: #15803d;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        cursor: pointer;
        transition: 0.2s;
        box-shadow: 0 1px 2px rgba(0,0,0,0.06);
    }
    .btn-sync-icon-sm:hover { background: #15803d; color: white; transform: rotate(90deg); }

    .btn-google-classroom-connect {
        background-color: #ffffff;
        color: #15803d;
        border: 1px solid #bbf7d0;
        font-weight: 600;
        font-size: 13px;
        padding: 7px 14px;
        border-radius: 6px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s ease;
        white-space: nowrap;
    }

    .btn-google-classroom-connect:hover {
        background-color: #f0fdf4;
        border-color: #86efac;
        color: #166534;
    }

    .google-connected-badge {
        background-color: #f0fdf4;
        border: 1px solid #bbf7d0;
        padding: 6px 12px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        font-size: 12.5px;
    }

    .google-connected-badge i {
        font-size: 18px;
    }

    .google-connection-info {
        display: flex;
        flex-direction: column;
    }

    .connected-label {
        font-weight: 700;
        color: #166534;
        line-height: 1.2;
    }

    .connected-email {
        font-size: 11.5px;
        color: #475569;
    }

    .gclass-section { border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; background: #fafbfc; }
    .gclass-section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
    .gclass-section-title { font-size: 13.5px; font-weight: 700; color: #1e293b; margin: 0; display: flex; align-items: center; gap: 8px; }

    .gclass-link-box { text-align: left; }
    .gclass-link-text { font-size: 13px; color: #64748b; margin-bottom: 10px; }

    .gclass-feed-list { display: flex; flex-direction: column; gap: 10px; }
    .gclass-feed-item { display: flex; gap: 12px; padding: 14px; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; }
    .gclass-feed-icon { width: 36px; height: 36px; border-radius: 8px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 14px; }
    .gclass-icon-coursework { background: #eff6ff; color: #2563eb; }
    .gclass-icon-announcement { background: #fef3c7; color: #b45309; }
    .gclass-feed-content { flex: 1; min-width: 0; }
    .gclass-feed-title-row { display: flex; align-items: center; gap: 10px; margin-bottom: 5px; }
    .gclass-feed-title { font-weight: 700; color: #1e293b; font-size: 13.5px; }
    .gclass-type-badge { font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 20px; text-transform: uppercase; }
    .gclass-type-coursework { background: #dbeafe; color: #1e40af; }
    .gclass-type-announcement { background: #fef3c7; color: #92400e; }
    .gclass-feed-text { font-size: 12.5px; color: #475569; margin: 0 0 6px; white-space: pre-line; }
    .gclass-feed-meta { display: flex; align-items: center; gap: 14px; font-size: 11.5px; color: #94a3b8; }
    .gclass-open-link { color: #0284c7; text-decoration: none; font-weight: 600; }
    .gclass-open-link:hover { text-decoration: underline; }

    .gclass-source-tag {
        display: inline-flex; align-items: center; gap: 4px;
        font-size: 10px; color: #64748b; margin-left: 8px;
        background: #f1f5f9; padding: 2px 8px; border-radius: 10px;
    }

    .btn-add-activity { background: #1e2a5a; border: none; color: white; font-size: 13px; font-weight: 600; padding: 8px 16px; border-radius: 6px; cursor: pointer; display: flex; align-items: center; gap: 6px; }
    .btn-add-activity:hover { background: #141d40; }
    .empty-state-box { display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 56px 24px; border: 2px dashed #e2e8f0; border-radius: 10px; background: #fafbfc; }
    .empty-state-icon { width: 56px; height: 56px; border-radius: 50%; background: #eef1fb; display: flex; align-items: center; justify-content: center; margin-bottom: 16px; }
    .empty-state-icon i { font-size: 20px; color: #1e2a5a; }
    .empty-state-title { font-weight: 700; color: #334155; font-size: 15px; margin: 0 0 6px; }
    .empty-state-text { font-size: 13px; color: #94a3b8; max-width: 340px; margin: 0; }
    .action-btn-group { display: flex; gap: 6px; justify-content: center; }
    .btn-sm-outline { background: white; border: 1px solid #cbd5e1; color: #475569; font-size: 12px; font-weight: 600; padding: 6px 10px; border-radius: 6px; cursor: pointer; transition: 0.15s; display: inline-flex; align-items: center; gap: 4px; text-decoration: none; }
    .btn-grade:hover { border-color: #1e2a5a; color: #1e2a5a; background: #eef1fb; }
    .btn-edit:hover { border-color: #d97706; color: #d97706; background: #fffbeb; }
    .btn-delete:hover { border-color: #dc2626; color: #dc2626; background: #fef2f2; }
    .breakdown-link { font-size: 11px; color: #0284c7; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; transition: 0.15s; }
    .breakdown-link:hover { color: #0369a1; text-decoration: underline; }
    .override-toggle-btn { width: 30px; height: 30px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; transition: 0.2s; }
    .btn-group-toggle { display: flex; gap: 5px; justify-content: center; }
    .attendance-toggle-btn { padding: 6px 12px; border: 1px solid #e2e8f0; background: #ffffff; color: #64748b; font-size: 11px; font-weight: 800; cursor: pointer; transition: all 0.2s ease; border-radius: 6px; text-transform: uppercase; }
    .active-present { background: #15803d !important; color: white !important; border-color: #15803d !important; }
    .active-late { background: #b45309 !important; color: white !important; border-color: #b45309 !important; }
    .active-absent { background: #b91c1c !important; color: white !important; border-color: #b91c1c !important; }
    .bulk-actions-wrapper { display: flex; justify-content: flex-end; gap: 10px; margin-top: 15px; }
    .attendance-control-panel-wrapper { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
    .control-panel-dividerMy { border-top: 1px dashed #e2e8f0; opacity: 1; }
    .modern-date-input-box { display: flex; align-items: center; gap: 10px; background: #f8fafc; border: 1px solid #cbd5e1; padding: 6px 14px; border-radius: 8px; transition: border-color 0.2s ease; }
    .modern-date-label { font-size: 11px; font-weight: 800; color: #64748b; letter-spacing: 0.5px; }
    .input-date-inner { display: flex; align-items: center; gap: 6px; }
    .modern-date-picker { border: none; background: transparent; font-weight: 700; color: #1e2a5a; font-size: 13.5px; outline: none; cursor: pointer; padding: 0; }
    .btn-modern-bulk { border: none; outline: none; font-size: 12px; font-weight: 700; padding: 8px 16px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: all 0.2s ease; }
    .status-bulk-present { background-color: #e6f7ed; color: #1e7e34; border: 1px solid #c3e6cb; }
    .status-bulk-absent { background-color: #fde8e8; color: #bd2130; border: 1px solid #f5c6cb; }
    .attendance-remarks-field { font-size: 12.5px !important; padding: 6px 12px !important; border: 1px solid #cbd5e1 !important; border-radius: 6px !important; background-color: white !important; width: 100%; max-width: 200px; margin: 0 auto; display: block; }
    .custom-modal-backdrop { position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(15, 23, 42, 0.6); display:flex; justify-content:center; align-items:center; z-index:9999; backdrop-filter: blur(2px); }
    .custom-modal-card { background: white; width: 550px; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); overflow: hidden; }
    .modal-card-header { padding: 16px 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
    .modal-card-header h4 { margin:0; font-size: 16px; font-weight: 700; color: #1e293b; }
    .close-modal-btn { background: none; border:none; font-size: 24px; color: #94a3b8; cursor: pointer; }
    .modal-card-body { padding: 20px; }
    .modal-card-footer { padding: 14px 20px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 8px; }
    .btn-primary-portal { background: #1e2a5a; color: white; border:none; padding: 9px 18px; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer; transition: 0.15s; }
    .btn-cancel-portal { background: white; color: #475569; border: 1px solid #cbd5e1; padding: 9px 18px; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer; }
    .modal-term-tag { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.4px; }
    .field-group { margin-bottom: 16px; }
    .field-row { display: flex; gap: 16px; }
    .field-row .field-group { flex: 1; }
    .field-label { display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 6px; }
    .field-optional { font-weight: 400; color: #94a3b8; }
    .field-input { width: 100%; box-sizing: border-box; padding: 10px 12px; font-size: 14px; color: #1e293b; border: 1px solid #cbd5e1; border-radius: 8px; background: #fff; }
    .dropzone { border: 2px dashed #cbd5e1; background: #f8fafc; border-radius: 10px; padding: 28px 16px; text-align: center; cursor: pointer; }
    .dz-file-preview { display: flex; align-items: center; gap: 12px; border: 1px solid #e2e8f0; background: #f8fafc; border-radius: 10px; padding: 12px 14px; }
    .empty-attendance-container { display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 300px; text-align: center; padding: 2rem; color: #64748b; }
    .empty-attendance-icon { font-size: 3rem; color: #cbd5e1; margin-bottom: 1rem; }

    .quick-date-options {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 8px;
    }

    .btn-quick-date {
        background: #f1f5f9;
        border: 1px solid #cbd5e1;
        color: #475569;
        font-size: 11.5px;
        font-weight: 600;
        padding: 5px 10px;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.15s ease;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .btn-quick-date:hover {
        background: #e2e8f0;
        color: #1e2a5a;
        border-color: #94a3b8;
    }

    .btn-quick-clear {
        background: #fef2f2;
        color: #dc2626;
        border-color: #fca5a5;
    }

    .btn-quick-clear:hover {
        background: #fee2e2;
        color: #b91c1c;
    }

    .grade-top-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
        padding: 10px 16px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
    }

    .encoding-legend-bar {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
        font-size: 12px;
        color: #64748b;
    }
    .legend-item { display: inline-flex; align-items: center; gap: 6px; }
    .legend-item i { font-size: 11px; color: #94a3b8; }

    .encoding-table th { text-align: center; }
    .encoding-table th:nth-child(2),
    .encoding-table th:nth-child(3) { text-align: left; }

    .encoding-table th:nth-child(4) { width: 22%; } 
    .encoding-table th:nth-child(5) { width: 16%; } 
    .encoding-table th:nth-child(6) { width: 12%; } 
    .encoding-table th:nth-child(7) { width: 20%; } 

    .encoding-cell-row {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .breakdown-icon-link {
        width: 30px; height: 30px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #0284c7;
        font-size: 12px;
        text-decoration: none;
        transition: 0.15s;
        flex-shrink: 0;
    }
    .breakdown-icon-link:hover {
        background: #eff6ff;
        border-color: #93c5fd;
        color: #0369a1;
    }

    .encoding-input {
        width: 100%; 
        max-width: 130px; 
        text-align: center; 
        font-weight: 700; 
        color: #94a3b8;
        padding: 8px 12px; 
        border: 1px solid #e2e8f0; 
        border-radius: 8px;
        font-size: 13.5px; 
        background: #f1f5f9; 
        transition: 0.15s;
    }

    .encoding-input:read-only { cursor: not-allowed; }
    .encoding-input-active {
        background: #fff; border-color: #1e2a5a; color: #1e2a5a; cursor: text;
    }
    .encoding-input:focus { outline: none; border-color: #1e2a5a; box-shadow: 0 0 0 3px rgba(30,42,90,0.08); }

    .equivalent-pill { display: inline-block; }

    .equivalent-input {
        width: 100%;
        max-width: 95px;
        text-align: center; 
        font-weight: 800; 
        color: #94a3b8;
        padding: 7px 10px; 
        border: 1px solid #e2e8f0; 
        border-radius: 20px;
        font-size: 13px; 
        background: #f1f5f9;
    }

    .equivalent-input:focus { outline: none; border-color: #15803d; }

    .disabled-group {
        opacity: 0.55;
        cursor: not-allowed;
    }

    .disabled-group .mode-toggle-btn {
        pointer-events: none; 
    }

    .grade-action-row {
        display: flex;
        justify-content: flex-end;
    }

    .grade-action-buttons {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .submitted-status-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background-color: #d1fae5;
        color: #065f46;
        border: 1px solid #a7f3d0;
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 700;
        font-size: 13.5px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.04);
    }

    .submitted-status-pill i {
        font-size: 15px;
        color: #16a34a;
    }

    .mode-toggle-group {
        display: inline-flex;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 3px;
        gap: 2px;
    }
    .mode-toggle-btn {
        border: none;
        background: transparent;
        padding: 6px 12px;
        font-size: 11px;
        font-weight: 700;
        color: #94a3b8;
        border-radius: 6px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        transition: 0.15s;
    }
    .mode-toggle-btn:hover { color: #475569; }
    .mode-toggle-active-auto {
        background: #eef1fb !important;
        color: #1e2a5a !important;
    }
    .mode-toggle-active-manual {
        background: #fef3c7 !important;
        color: #b45309 !important;
    }

    .encoding-input-muted, .equivalent-input-muted {
        background: #f1f5f9 !important;
        color: #64748b !important;
        opacity: 1 !important;
        filter: none !important;
        cursor: not-allowed;
    }

    /* PRINT BUTTON */
    .btn-print-attendance {
        background-color: #ffffff;
        color: #1e2a5a;
        border: 1px solid #1e2a5a;
        font-size: 12.5px;
        font-weight: 700;
        padding: 7px 16px;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
    }
    .btn-print-attendance:hover {
        background-color: #1e2a5a;
        color: #ffffff;
    }

    /* SCREEN VIEW: hide the dedicated print containers */
    .printable-only {
        display: none !important;
    }

    /* PRINT / PDF EXPORT */
    @media print {
        @page {
            size: A4 portrait;
            margin: 12mm 15mm 12mm 15mm;
        }

        body * {
            visibility: hidden !important;
        }

        #printable-masterlist-area,
        #printable-masterlist-area *,
        #printable-attendance-area,
        #printable-attendance-area * {
            visibility: visible !important;
        }

        #printable-masterlist-area,
        #printable-attendance-area {
            display: block !important;
            position: absolute !important;
            left: 0 !important;
            top: 0 !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            background: #ffffff !important;
        }

        .school-name {
            margin: 0 !important;
            font-size: 20px !important;
            font-weight: 800 !important;
            color: #1e2a5a !important;
            text-align: center !important;
        }

        .report-subtitle {
            margin: 2px 0 14px 0 !important;
            font-size: 11px !important;
            font-weight: 700 !important;
            color: #475569 !important;
            text-align: center !important;
            letter-spacing: 0.5px !important;
        }

        .meta-info-table {
            width: 100% !important;
            border-collapse: collapse !important;
            margin-bottom: 16px !important;
            font-size: 11px !important;
            background: #f8fafc !important;
            border: 1px solid #cbd5e1 !important;
        }

        .meta-info-table td {
            padding: 8px 10px !important;
            border: 1px solid #cbd5e1 !important;
            color: #1e293b !important;
        }

        .print-data-table {
            width: 100% !important;
            border-collapse: collapse !important;
            table-layout: fixed !important;
        }

        .print-data-table th {
            background-color: #f1f5f9 !important;
            color: #0f172a !important;
            border: 1px solid #94a3b8 !important;
            padding: 8px 10px !important;
            font-size: 11px !important;
            text-transform: uppercase !important;
            text-align: center !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .print-data-table td {
            border: 1px solid #cbd5e1 !important;
            padding: 8px 10px !important;
            font-size: 12px !important;
            color: #0f172a !important;
        }

        .text-center { text-align: center !important; }
        .text-uppercase { text-transform: uppercase !important; }
        .fw-bold { font-weight: 700 !important; }
    }
</style>
@endsection