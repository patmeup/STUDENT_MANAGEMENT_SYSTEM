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
        }
    }">

    <div class="page-header-row">
        <a href="{{ route('professor.manage-classes') }}" class="btn-back-link">
            <i class="fa-solid fa-arrow-left-long"></i> Back to My Classes List
        </a>
        <span class="badge-portal">Class Control Panel</span>
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

            @foreach(['prelim' => 'Prelim', 'midterm' => 'Midterm', 'semifinal' => 'Semi-Final', 'final' => 'Final'] as $key => $termLabel)
                @php $termTasks = $tasks->where('term', $key); @endphp
                <div x-show="currentTab === '{{ $key }}'" x-transition style="display: none;">
                    <div class="workspace-card">
                        
                        <div class="card-workspace-header d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h4 class="m-0 fw-bold text-dark-blue">📝 {{ $termLabel }} Term Panel</h4>
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

    .workspace-card { background: white; border-radius: 12px; border: 1px solid #e2e8f0; padding: 24px; min-height: 520px; }
    .students-count-badge { background: #eff6ff; color: #1e40af; font-size: 12.5px; font-weight: 700; padding: 6px 14px; border-radius: 30px; }

    .custom-portal-table { border: 1px solid #f1f5f9; width: 100%; }
    .custom-portal-table th { background: #f8fafc; font-size: 12px; font-weight: 700; text-transform: uppercase; color: #64748b; padding: 14px 12px; border-bottom: 2px solid #e2e8f0; }
    .custom-portal-table td { padding: 14px 12px; font-size: 14px; border-bottom: 1px solid #f1f5f9; }
    .badge-enrolled-status { background: #dcfce7; color: #15803d; font-size: 12px; padding: 4px 12px; font-weight: 700; border-radius: 20px; }

    .btn-add-activity { background: #1e2a5a; border: none; color: white; font-size: 13px; font-weight: 600; padding: 8px 16px; border-radius: 6px; cursor: pointer; display: flex; align-items: center; gap: 6px; }
    .btn-add-activity:hover { background: #141d40; }

    .empty-state-box { display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 56px 24px; border: 2px dashed #e2e8f0; border-radius: 10px; background: #fafbfc; }
    .empty-state-icon { width: 56px; height: 56px; border-radius: 50%; background: #eef1fb; display: flex; align-items: center; justify-content: center; margin-bottom: 16px; }
    .empty-state-icon i { font-size: 20px; color: #1e2a5a; }
    .empty-state-title { font-weight: 700; color: #334155; font-size: 15px; margin: 0 0 6px; }
    .empty-state-text { font-size: 13px; color: #94a3b8; max-width: 340px; margin: 0; }

    .custom-modal-backdrop { position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(15, 23, 42, 0.6); display:flex; justify-content:center; align-items:center; z-index:9999; backdrop-filter: blur(2px); }
    .custom-modal-card { background: white; width: 550px; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); overflow: hidden; animation: slideUp 0.2s ease-out; }
    .modal-card-header { padding: 16px 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
    .modal-card-header h4 { margin:0; font-size: 16px; font-weight: 700; color: #1e293b; }
    .close-modal-btn { background: none; border:none; font-size: 24px; color: #94a3b8; cursor: pointer; }
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
    .modal-card-body { padding: 20px; }
    .modal-card-footer { padding: 14px 20px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 8px; }
    .btn-primary-portal { background: #1e2a5a; color: white; border:none; padding: 9px 18px; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer; transition: 0.15s; }
    .btn-primary-portal:hover { background: #141d40; }
    .btn-cancel-portal { background: white; color: #475569; border: 1px solid #cbd5e1; padding: 9px 18px; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer; transition: 0.15s; }
    .btn-cancel-portal:hover { background: #f8fafc; }

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

    @keyframes slideUp { from { transform: translateY(10px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
</style>
@endsection