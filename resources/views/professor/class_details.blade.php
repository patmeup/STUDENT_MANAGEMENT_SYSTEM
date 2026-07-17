@extends('layouts.app')

@section('content')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="prof-container py-4" x-data="{ 
        currentTab: new URLSearchParams(window.location.search).get('tab') || 'masterlist', 
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
            if (this.isPastDate() || this.hasSaved) return; // Lock check
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
            const formData = new FormData(e.target); // Kunin ang data mula sa form

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

            this.attendanceState = {};
            this.remarksState = {};

            const studentList = @js($students ?? []);
            studentList.forEach(student => {
                this.attendanceState[student.student_id] = null;
                this.remarksState[student.student_id] = '';
            });

            const initialRecords = @js($attendanceRecords ?? []);
            if (initialRecords && initialRecords.length > 0) {
                initialRecords.forEach(rec => {
                    this.attendanceState[rec.student_id] = rec.status;
                    this.remarksState[rec.student_id] = rec.remarks || '';
                });
                this.hasSaved = true; 
            }
        }
    }"

        x-init="initAttendance()"

                @if(isset($attendanceRecords) && $attendanceRecords->isNotEmpty())
                    @foreach($attendanceRecords as $rec)
                        this.attendanceState['{{ $rec->student_id }}'] = '{{ $rec->status }}';
                        this.remarksState['{{ $rec->student_id }}'] = '{{ $rec->remarks }}';
                    @endforeach
                @else
                    @foreach($students as $student)
                        this.attendanceState['{{ $student->student_id }}'] = 'Present';
                        this.remarksState['{{ $student->student_id }}'] = '';
                    @endforeach
                @endif
                
                loadAttendanceData();
            ">
        
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
                
                <button class="nav-tab-btn" :class="currentTab === 'masterlist' ? 'active-tab' : ''" @click="currentTab = 'masterlist'">
                    <i class="fa-solid fa-users-rectangle"></i> Student Masterlist
                </button>

                <button class="nav-tab-btn" :class="currentTab === 'attendance' ? 'active-tab' : ''" @click="currentTab = 'attendance'">
                    <i class="fa-solid fa-calendar-check"></i> Class Attendance
                </button>
                
                <div class="panel-title-label mt-4">TERM GRADE ENCODING</div>
                @foreach(['prelim' => 'Prelim Term', 'midterm' => 'Midterm Term', 'semifinal' => 'Semi-Final Term', 'final' => 'Final Term'] as $key => $label)
                    <button class="nav-tab-btn" :class="currentTab === '{{ $key }}' ? 'active-tab' : ''" @click="currentTab = '{{ $key }}'">
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
                            <h4 class="m-0 fw-bold text-dark-blue">📋 Student Masterlist Roster</h4>
                            <p class="small text-muted m-0">Official list of enrolled class members</p>
                        </div>
                        <span class="students-count-badge">{{ count($students) }} Students Enrolled</span>
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
                    @endif
                </div>
            </div>

            <div x-show="currentTab === 'attendance'" x-transition style="display: none;">
                <div class="workspace-card">
                    <form @submit.prevent="saveAttendanceForm($event)">
                        @csrf

                        <!-- Header Section -->
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
                            <!-- Main Container -->
                            <div class="table-responsive position-relative rounded-3 border">
                                <!-- Loading Overlay -->
                                <div x-show="loadingAttendance" class="position-absolute top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center bg-white bg-opacity-75" style="z-index: 10; display: none;">
                                    <div class="text-center text-primary">
                                        <i class="fa-solid fa-spinner fa-spin fa-2x mb-2"></i>
                                        <p class="small fw-bold m-0">Syncing record entries...</p>
                                    </div>
                                </div>

                                <div x-show="!loadingAttendance && !hasSaved && isPastDate()" class="empty-attendance-container">
                                    <div class="empty-attendance-icon">
                                        <i class="fa-solid fa-calendar-xmark"></i>
                                    </div>
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
                                                    <input type="hidden" 
                                                        :name="'attendance[' + '{{ $student->student_id }}' + ']'" 
                                                        :value="attendanceState['{{ $student->student_id }}']">

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
                                                            <button type="button" 
                                                                class="attendance-toggle-btn"
                                                                :class="attendanceState['{{ $student->student_id }}'] === 'Present' ? 'active-present' : ''"
                                                                @click="attendanceState['{{ $student->student_id }}'] = 'Present'">
                                                                Present
                                                            </button>
                                                            <button type="button" 
                                                                class="attendance-toggle-btn"
                                                                :class="attendanceState['{{ $student->student_id }}'] === 'Late' ? 'active-late' : ''"
                                                                @click="attendanceState['{{ $student->student_id }}'] = 'Late'">
                                                                Late
                                                            </button>
                                                            <button type="button" 
                                                                class="attendance-toggle-btn"
                                                                :class="attendanceState['{{ $student->student_id }}'] === 'Absent' ? 'active-absent' : ''"
                                                                @click="attendanceState['{{ $student->student_id }}'] = 'Absent'">
                                                                Absent
                                                            </button>
                                                        </div>
                                                    </template>
                                                </td>

                                                <td class="align-middle text-center">
                                                    <template x-if="hasSaved">
                                                        <span class="text-muted small" x-text="remarksState['{{ $student->student_id }}'] && remarksState['{{ $student->student_id }}'].trim() !== '' ? remarksState['{{ $student->student_id }}'] : '—'"></span>
                                                    </template>
                                                    <template x-if="!hasSaved">
                                                        <input type="text" 
                                                            :name="'remarks[' + '{{ $student->student_id }}' + ']'" 
                                                            x-model="remarksState['{{ $student->student_id }}']" 
                                                            placeholder="Add remarks..." 
                                                            class="form-control attendance-remarks-field">
                                                    </template>
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Footer Buttons -->
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
                @php $termTasks = $tasks->where('term', $key); @endphp
                <div x-show="currentTab === '{{ $key }}'" x-transition style="display: none;">
                    <div class="workspace-card">
                        
                        <div class="card-workspace-header d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h4 class="m-0 fw-bold text-dark-blue">{{ $termLabel }} Term Panel</h4>
                                <p class="small text-muted m-0">Manage activities, assignments, and quizzes for this term allocation</p>
                            </div>
                            <div class="action-form-buttons">
                                <button type="button" class="btn-add-activity" @click="openCreateModal('{{ $key }}')">
                                    <i class="fa-solid fa-plus"></i> Create New Activity / Assignment
                                </button>
                            </div>
                        </div>

                        <div class="activity-list-container">
                            @if($termTasks->isEmpty())
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
                                                <th>Activity Title</th>
                                                <th>Type</th>
                                                <th>Max Score</th>
                                                <th>Due Date</th>
                                                <th class="text-center">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($termTasks as $task)
                                                <tr>
                                                    <td><strong class="text-dark-blue">{{ $task->activity_name }}</strong></td>
                                                    <td><span class="badge bg-secondary text-uppercase">{{ $task->activity_type }}</span></td>
                                                    <td>{{ $task->max_score }} pts</td>
                                                    <td>{{ $task->due_date ?? 'No Deadline' }}</td>
                                                    <td class="text-center">
                                                        <div class="action-btn-group">
                                                            <a href="{{ route('professor.task-submissions', $task->id) }}" class="btn-sm-outline btn-grade">
                                                                <i class="fa fa-star"></i> Grade
                                                            </a>
                                                            <button type="button" class="btn-sm-outline btn-edit"
                                                                @click="openEditModal({ id: {{ $task->id }}, term: '{{ $key }}', activity_name: @js($task->activity_name), activity_type: '{{ $task->activity_type }}', max_score: {{ $task->max_score }}, due_date: '{{ $task->due_date }}' })">
                                                                <i class="fa-solid fa-pen"></i>
                                                            </button>
                                                            <button type="button" class="btn-sm-outline btn-delete" @click="deleteTask({{ $task->id }}, '{{ $key }}')">
                                                                <i class="fa-solid fa-trash"></i>
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
                        <input type="date" name="due_date" class="field-input" x-model="formDueDate">
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
                            <p class="text-xs text-gray-400 mt-1" style="font-size: 11px; opacity: 0.8;">Recommended size: Less than 25MB</p>
                        </div>

                        <div x-show="fileSizeError" x-cloak style="color: #dc3545; font-size: 13px; margin-top: 8px; font-weight: 600;">
                            The file you selected is too large! Maximum allowed size is 25MB. Please compress it or choose a smaller file.
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
                    <button type="button" class="btn-cancel-portal" @click="showModal = false">
                        Cancel
                    </button>

                    <button type="submit" 
                            class="btn-primary-portal" 
                            :disabled="fileSizeError || publishing"
                            :style="(fileSizeError || publishing) ? 'opacity: 0.6; cursor: not-allowed;' : ''">
                        <span x-html="publishing ? '<i class=\'fa-solid fa-spinner fa-spin\' style=\'margin-right: 6px;\'></i> Publishing...' : (editingTaskId ? 'Save Changes' : 'Publish Task')"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }

    .page-header-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .text-dark-blue { color: #1e2a5a; }
    .text-dark-slate { color: #334155; }
    
    .btn-back-link { 
        text-decoration: none; 
        color: #475569; 
        font-weight: 600; 
        font-size: 13.5px; 
        transition: 0.2s; 
    }
    .btn-back-link:hover { color: #1e2a5a; }
    
    .badge-portal { 
        background: #f1f5f9; 
        color: #475569; 
        font-weight: 700; 
        font-size: 11px; 
        padding: 6px 14px; 
        border-radius: 30px; 
        text-transform: uppercase; 
        border: 1px solid #cbd5e1; 
    }

    .dashboard-grid-wrapper { display: flex; gap: 24px; align-items: stretch; width: 100%; }
    .unified-sidebar { width: 300px; flex-shrink: 0; display: flex; flex-direction: column; gap: 16px; }
    .unified-workspace { flex-grow: 1; min-width: 0; }

    .sidebar-subject-card { 
        background: white; 
        border-radius: 12px; 
        padding: 20px; 
        border: 1px solid #e2e8f0; 
        border-top: 5px solid #1e2a5a; 
    }
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
    
    .nav-tab-btn { 
        width: 100%; 
        display: flex; 
        align-items: center; 
        gap: 10px; 
        background: transparent; 
        border: none; 
        text-align: left; 
        padding: 10px 14px; 
        border-radius: 8px; 
        font-size: 13.5px; 
        font-weight: 600; 
        color: #475569; 
        margin-bottom: 4px; 
        transition: 0.2s; 
        cursor: pointer; 
    }
    .nav-tab-btn:hover { background: #f8fafc; color: #1e2a5a; }
    .active-tab { background: #1e2a5a !important; color: white !important; }
    .active-tab i { color: #facc15 !important; }

    /* ==========================================================================
       WORKSPACE PANELS & TABLES
       ========================================================================== */
    .workspace-card { background: white; border-radius: 12px; border: 1px solid #e2e8f0; padding: 24px; min-height: 520px; }
    .students-count-badge { background: #eff6ff; color: #1e40af; font-size: 12.5px; font-weight: 700; padding: 6px 14px; border-radius: 30px; }

    .custom-portal-table { border: 1px solid #f1f5f9; width: 100%; }
    .custom-portal-table th { 
        background: #f8fafc; 
        font-size: 12px; 
        font-weight: 700; 
        text-transform: uppercase; 
        color: #64748b; 
        padding: 14px 12px; 
        border-bottom: 2px solid #e2e8f0; 
    }
    .custom-portal-table td { padding: 14px 12px; font-size: 14px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    .badge-enrolled-status { background: #dcfce7; color: #15803d; font-size: 12px; padding: 4px 12px; font-weight: 700; border-radius: 20px; }

    .btn-add-activity { 
        background: #1e2a5a; 
        border: none; 
        color: white; 
        font-size: 13px; 
        font-weight: 600; 
        padding: 8px 16px; 
        border-radius: 6px; 
        cursor: pointer; 
        display: flex; 
        align-items: center; 
        gap: 6px; 
    }
    .btn-add-activity:hover { background: #141d40; }

    .empty-state-box { 
        display: flex; 
        flex-direction: column; 
        align-items: center; 
        justify-content: center; 
        text-align: center; 
        padding: 56px 24px; 
        border: 2px dashed #e2e8f0; 
        border-radius: 10px; 
        background: #fafbfc; 
    }
    .empty-state-icon { 
        width: 56px; 
        height: 56px; 
        border-radius: 50%; 
        background: #eef1fb; 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        margin-bottom: 16px; 
    }
    .empty-state-icon i { font-size: 20px; color: #1e2a5a; }
    .empty-state-title { font-weight: 700; color: #334155; font-size: 15px; margin: 0 0 6px; }
    .empty-state-text { font-size: 13px; color: #94a3b8; max-width: 340px; margin: 0; }

    .action-btn-group { display: flex; gap: 6px; justify-content: center; }
    .btn-sm-outline {
        background: white; border: 1px solid #cbd5e1; color: #475569;
        font-size: 12px; font-weight: 600; padding: 6px 10px;
        border-radius: 6px; cursor: pointer; transition: 0.15s;
        display: inline-flex; align-items: center; gap: 4px;
        text-decoration: none;
    }
    .btn-grade:hover { border-color: #1e2a5a; color: #1e2a5a; background: #eef1fb; }
    .btn-edit:hover { border-color: #d97706; color: #d97706; background: #fffbeb; }
    .btn-delete:hover { border-color: #dc2626; color: #dc2626; background: #fef2f2; }

    .btn-group-toggle {
        display: flex;
        gap: 5px;
        justify-content: center;
    }

    .attendance-toggle-btn {
        padding: 6px 12px;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        color: #64748b;
        font-size: 11px;
        font-weight: 800;
        cursor: pointer;
        transition: all 0.2s ease;
        border-radius: 6px;
        text-transform: uppercase;
    }

    .attendance-toggle-btn:hover { background: #f8fafc; }

    .active-present { background: #15803d !important; color: white !important; border-color: #15803d !important; }
    .active-late { background: #b45309 !important; color: white !important; border-color: #b45309 !important; }
    .active-absent { background: #b91c1c !important; color: white !important; border-color: #b91c1c !important; }

    .bulk-actions-wrapper {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 15px;
    }

    .attendance-control-panel-wrapper {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    }

    .control-panel-dividerMy {
        border-top: 1px dashed #e2e8f0;
        opacity: 1;
    }

    .attendance-quick-stats {
        display: flex;
        gap: 8px;
    }

    .stat-pill {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #475569;
        font-size: 13px;
        font-weight: 600;
        padding: 6px 14px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .stat-pill strong {
        color: #1e2a5a;
        font-weight: 700;
    }

    .modern-date-input-box {
        display: flex;
        align-items: center;
        gap: 10px;
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        padding: 6px 14px;
        border-radius: 8px;
        transition: border-color 0.2s ease;
    }

    .modern-date-input-box:focus-within {
        border-color: #1e2a5a;
        box-shadow: 0 0 0 2px rgba(30, 42, 90, 0.05);
    }

    .modern-date-label {
        font-size: 11px;
        font-weight: 800;
        color: #64748b;
        letter-spacing: 0.5px;
    }

    .input-date-inner {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .modern-date-picker {
        border: none;
        background: transparent;
        font-weight: 700;
        color: #1e2a5a;
        font-size: 13.5px;
        outline: none;
        cursor: pointer;
        padding: 0;
    }

    .modern-bulk-action-group {
        display: flex;
        gap: 10px;
    }

    .btn-modern-bulk {
        border: none;
        outline: none;
        font-size: 12px;
        font-weight: 700;
        padding: 8px 16px;
        border-radius: 8px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s ease;
    }

    .status-bulk-present {
        background-color: #e6f7ed;
        color: #1e7e34;
        border: 1px solid #c3e6cb;
    }

    .status-bulk-present:hover:not(:disabled) {
        background-color: #1e7e34;
        color: #ffffff;
        border-color: #1e7e34;
        box-shadow: 0 4px 10px rgba(30, 126, 52, 0.15);
    }

    .status-bulk-absent {
        background-color: #fde8e8;
        color: #bd2130;
        border: 1px solid #f5c6cb;
    }

    .status-bulk-absent:hover:not(:disabled) {
        background-color: #bd2130;
        color: #ffffff;
        border-color: #bd2130;
        box-shadow: 0 4px 10px rgba(189, 33, 48, 0.15);
    }

    .attendance-radio-group {
        display: flex;
        background: #f1f5f9;
        padding: 4px;
        border-radius: 8px;
        border: 1px solid #cbd5e1;
        justify-content: center;
    }

    .attendance-label-btn {
        margin: 0;
        padding: 0;
        cursor: pointer;
    }

    .attendance-radio-input {
        position: absolute;
        width: 1px;
        height: 1px;
        overflow: hidden;
        opacity: 0;
    }

    .btn-state {
        display: block;
        text-align: center;
        font-size: 11px;
        font-weight: 800;
        padding: 6px 14px;
        border-radius: 6px;
        color: #64748b;
        transition: all 0.15s ease-in-out;
        text-transform: uppercase;
    }
    .btn-disabled-gray {
        background-color: #e2e8f0 !important; /* Light gray background */
        border-color: #cbd5e1 !important;     /* Slate gray border */
        color: #94a3b8 !important;            /* Muted text color */
        cursor: not-allowed !important;       /* Palitang "not-allowed" ang mouse cursor */
        pointer-events: none;                 /* I-block ang anumang mouse clicks o hover effects */
        box-shadow: none !important;          /* Tanggalin ang mga anino o shadow */
    }
    
    /* Siguraduhing hindi mag-iiba ang itsura ng icon sa loob ng disabled button */
    .btn-disabled-gray i {
        color: #94a3b8 !important;
    }

    .attendance-label-btn:hover .btn-state {
        background: rgba(226, 232, 240, 0.9);
        color: #334155;
    }
    
    .attendance-radio-input:checked + .btn-present,
    .attendance-radio-input:checked + .btn-present:hover {
        background: #15803d !important;
        color: white !important;
        box-shadow: 0 2px 4px rgba(21, 128, 61, 0.25);
    }

    .attendance-radio-input:checked + .btn-late,
    .attendance-radio-input:checked + .btn-late:hover {
        background: #b45309 !important;
        color: white !important;
        box-shadow: 0 2px 4px rgba(180, 83, 9, 0.25);
    }

    .attendance-radio-input:checked + .btn-absent,
    .attendance-radio-input:checked + .btn-absent:hover {
        background: #b91c1c !important;
        color: white !important;
        box-shadow: 0 2px 4px rgba(185, 28, 28, 0.25);
    }

    .attendance-remarks-field {
        font-size: 12.5px !important; 
        padding: 6px 12px !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 6px !important;
        background-color: white !important;
        transition: all 0.15s ease;
        width: 100%;
        max-width: 200px;
        margin: 0 auto;
        display: block;
    }
    
    .attendance-remarks-field:focus {
        border-color: #1e2a5a !important;
        box-shadow: 0 0 0 3px rgba(30,42,90,0.1) !important;
        outline: none;
    }

    /* Past Date Read Only Visual Styling */
    .bg-light-read-only {
        background-color: #f8fafc !important;
        opacity: 0.92;
    }
    .cursor-not-allowed {
        cursor: not-allowed !important;
    }

    /* ==========================================================================
       MODAL POPUPS & UPLOAD CONTROLS
       ========================================================================== */
    .custom-modal-backdrop { 
        position: fixed; top:0; left:0; width:100%; height:100%; 
        background: rgba(15, 23, 42, 0.6); display:flex; 
        justify-content:center; align-items:center; z-index:9999; 
        backdrop-filter: blur(2px); 
    }
    .custom-modal-card { 
        background: white; width: 550px; border-radius: 12px; 
        box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); overflow: hidden; 
        animation: slideUp 0.2s ease-out; 
    }
    .modal-card-header { padding: 16px 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
    .modal-card-header h4 { margin:0; font-size: 16px; font-weight: 700; color: #1e293b; }
    .close-modal-btn { background: none; border:none; font-size: 24px; color: #94a3b8; cursor: pointer; }
    .modal-card-body { padding: 20px; }
    .modal-card-footer { padding: 14px 20px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 8px; }
    
    .btn-primary-portal { background: #1e2a5a; color: white; border:none; padding: 9px 18px; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer; transition: 0.15s; }
    .btn-primary-portal:hover:not(:disabled) { background: #141d40; }
    .btn-cancel-portal { background: white; color: #475569; border: 1px solid #cbd5e1; padding: 9px 18px; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer; transition: 0.15s; }
    .btn-cancel-portal:hover:not(:disabled) { background: #f8fafc; }

    .modal-term-tag { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.4px; }

    .field-group { margin-bottom: 16px; }
    .field-row { display: flex; gap: 16px; }
    .field-row .field-group { flex: 1; }
    .field-label { display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 6px; }
    .field-optional { font-weight: 400; color: #94a3b8; }
    .field-input {
        width: 100%; box-sizing: border-box;
        padding: 10px 12px; font-size: 14px; color: #1e293b;
        border: 1px solid #cbd5e1; border-radius: 8px; background: #fff;
        transition: 0.15s;
    }
    .field-input:focus { outline: none; border-color: #1e2a5a; box-shadow: 0 0 0 3px rgba(30,42,90,0.1); }
    select.field-input { cursor: pointer; }

    .visually-hidden-input { position: absolute; width: 1px; height: 1px; overflow: hidden; opacity: 0; pointer-events: none; }

    .dropzone {
        border: 2px dashed #cbd5e1;
        background: #f8fafc;
        border-radius: 10px;
        padding: 28px 16px;
        text-align: center;
        cursor: pointer;
        transition: 0.2s;
    }
    .dropzone:hover { border-color: #94a3b8; background: #f1f5f9; }
    .dropzone-active { border-color: #1e2a5a; background: #eef1fb; }
    .dropzone i { font-size: 26px; color: #94a3b8; margin-bottom: 8px; }
    .dropzone-active i { color: #1e2a5a; }
    .dz-title { font-size: 13.5px; font-weight: 700; color: #334155; margin: 0; }
    .dz-subtitle { font-size: 12px; color: #94a3b8; margin: 4px 0 0; }

    .dz-file-preview {
        display: flex; align-items: center; gap: 12px;
        border: 1px solid #e2e8f0; background: #f8fafc;
        border-radius: 10px; padding: 12px 14px;
    }
    .dz-file-preview i { font-size: 20px; color: #15803d; }
    .dz-file-info { display: flex; flex-direction: column; flex-grow: 1; min-width: 0; }
    .dz-file-name { font-size: 13px; font-weight: 700; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .dz-file-size { font-size: 11.5px; color: #94a3b8; }
    .dz-file-remove { background: none; border: none; color: #94a3b8; cursor: pointer; padding: 4px; }
    .dz-file-remove:hover { color: #dc2626; }

    .empty-attendance-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 300px; /* Adjust base sa gusto mong taas */
        text-align: center;
        padding: 2rem;
        color: #64748b; /* Grayish text */
    }

    .empty-attendance-icon {
        font-size: 3rem;
        color: #cbd5e1; /* Light gray color para sa icon */
        margin-bottom: 1rem;
    }

    @keyframes slideUp { from { transform: translateY(10px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
</style>
@endsection