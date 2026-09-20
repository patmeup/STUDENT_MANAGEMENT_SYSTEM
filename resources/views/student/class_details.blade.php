@extends('layouts.app')

@section('content')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="prof-container py-4"
    x-data="{ currentTab: '{{ $activeTab }}' }">

    <div class="page-header-row">
        <a href="{{ route('student.my_classes') }}" class="btn-back-link">
            <i class="fa-solid fa-arrow-left-long"></i> Back to My Classes
        </a>
        <span class="badge-portal">Class Workspace</span>
    </div>

    @if(session('success'))
        <div class="alert-banner alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert-banner alert-danger">{{ session('error') }}</div>
    @endif

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
                <div class="panel-title-label">CLASSWORK & RECORDS</div>

                <button class="nav-tab-btn" :class="currentTab === 'attendance' ? 'active-tab' : ''" @click="currentTab = 'attendance'">
                    <i class="fa-solid fa-calendar-check"></i> Attendance Logs
                </button>

                <div class="panel-title-label mt-3">TERM GRADE ENCODING</div>
                @foreach(['prelim' => 'Prelim Term', 'midterm' => 'Midterm Term', 'semifinal' => 'Semi-Final Term', 'final' => 'Final Term'] as $key => $label)
                    <button class="nav-tab-btn" :class="currentTab === '{{ $key }}' ? 'active-tab' : ''" @click="currentTab = '{{ $key }}'">
                        <i class="fa-regular fa-pen-to-square"></i> {{ $label }}
                    </button>
                @endforeach
            </div>

            <div class="sidebar-navigation-card border-au-blue" 
                x-show="currentTab !== 'attendance'" 
                x-transition
                x-data="{
                grades: {
                    prelim: @js($termGrades['prelim']),
                    midterm: @js($termGrades['midterm']),
                    semifinal: @js($termGrades['semifinal']),
                    final: @js($termGrades['final'])
                }
            }">
                <div class="panel-title-label d-flex justify-content-between align-items-center mb-1">
                    <span><i class="fa-solid fa-chart-pie me-1 text-dark-blue"></i> <span x-text="currentTab.toUpperCase()"></span> STANDING & GRADE</span>
                </div>
                <p class="text-muted font-xs mb-3">Predictive Grade based on current term performance.</p>

                <div class="performance-breakdown-modern mb-3">
                    <div class="breakdown-row">
                        <span class="text-muted"><i class="fa-solid fa-file-pen text-secondary me-1"></i> Quiz:</span>
                        <strong class="text-dark-blue" x-text="grades[currentTab].quizAverage !== null ? grades[currentTab].quizAverage.toFixed(1) + '%' : 'No grades yet'"></strong>
                    </div>
                    <div class="breakdown-row">
                        <span class="text-muted"><i class="fa-solid fa-list-check text-secondary me-1"></i> Activities:</span>
                        <strong class="text-dark-blue" x-text="grades[currentTab].activityAverage !== null ? grades[currentTab].activityAverage.toFixed(1) + '%' : 'No grades yet'"></strong>
                    </div>
                    <div class="breakdown-row">
                        <span class="text-muted"><i class="fa-solid fa-book text-secondary me-1"></i> Assignment:</span>
                        <strong class="text-dark-blue" x-text="grades[currentTab].assignmentAverage !== null ? grades[currentTab].assignmentAverage.toFixed(1) + '%' : 'No grades yet'"></strong>
                    </div>
                    <div class="breakdown-row">
                        <span class="text-muted"><i class="fa-solid fa-calendar-check text-secondary me-1"></i> Attendance:</span>
                        <strong class="text-dark-blue" x-text="grades[currentTab].currentAttendance !== null ? grades[currentTab].currentAttendance.toFixed(1) + '%' : 'No logs yet'"></strong>
                    </div>
                    <div class="breakdown-row border-0 pb-0">
                        <span class="text-muted"><i class="fa-solid fa-graduation-cap text-secondary me-1"></i> Major Exam:</span>
                        <strong class="text-dark-blue" x-text="grades[currentTab].majorExam !== null ? grades[currentTab].majorExam.toFixed(1) + '%' : 'Not yet taken'"></strong>
                    </div>
                </div>

                <hr class="sidebar-divider">

                <div class="au-prediction-display-box text-center p-3 rounded-10 bg-light-gray" :class="{ 'au-borderline': grades[currentTab].equivalent === '3.00' }">
                    <span class="font-xxs text-uppercase fw-bold text-muted tracking-wider d-block mb-1">Current Computed Grade</span>
                    
                    <div class="predicted-grade-number my-1" x-text="grades[currentTab].equivalent ?? '--'"></div>
                    
                    <div class="mb-2">
                        <span class="badge px-2 py-1" :class="{
                            'badge-excellent': grades[currentTab].currentCalculatedGrade !== null && grades[currentTab].currentCalculatedGrade >= 94,
                            'badge-passed': grades[currentTab].currentCalculatedGrade !== null && grades[currentTab].currentCalculatedGrade >= 75 && grades[currentTab].currentCalculatedGrade < 94 && grades[currentTab].equivalent !== '3.00',
                            'badge-warning': grades[currentTab].equivalent === '3.00',
                            'badge-failed': grades[currentTab].currentCalculatedGrade !== null && grades[currentTab].currentCalculatedGrade < 75,
                            'd-none': grades[currentTab].currentCalculatedGrade === null
                        }" x-text="grades[currentTab].currentCalculatedGrade === null ? '' : (grades[currentTab].equivalent === '3.00' ? 'PASSED (BORDERLINE)' : (grades[currentTab].currentCalculatedGrade >= 75 ? 'PASSED (GOOD STANDING)' : 'AT RISK (FAILED)'))"></span>
                    </div>
                    
                    <p class="font-xs text-muted m-0" x-text="
                        grades[currentTab].currentCalculatedGrade !== null 
                            ? 'Equivalent to ' + grades[currentTab].currentCalculatedGrade.toFixed(2) + '%' 
                            : 'This will be updated once you have grades recorded for this term.'
                    "></p>

                    <div class="au-early-warning" x-show="grades[currentTab].equivalent === '3.00'" x-cloak>
                        <div class="au-early-warning-title"><i class="fa-solid fa-triangle-exclamation me-1"></i> Early Academic Warning</div>
                        <p class="m-0">You are only barely passing this term. A small drop in your next scores may put you below the 75% passing mark. Focus on your remaining tasks and exam, and consider consulting your professor.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="unified-workspace">

            <div x-show="currentTab === 'attendance'" x-transition>
                <div class="workspace-card">
                    <div class="card-workspace-header mb-3">
                        <h4 class="m-0 fw-bold text-dark-blue">My Attendance Logs</h4>
                        <p class="small text-muted m-0">Total Records: {{ $attendanceRecords->count() }}</p>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table custom-portal-table">
                            <thead>
                                <tr>
                                    <th style="width: 30%;">Date</th>
                                    <th style="width: 30%;">Status</th>
                                    <th style="width: 40%;">Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($attendanceRecords as $rec)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($rec->attendance_date)->format('M d, Y') }}</td>
                                        <td>{{ $rec->status }}</td>
                                        <td>{{ $rec->remarks ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center">No records found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @php
                // Google Classroom coursework is also mirrored as a local task row (needed for grading).
                // Collect the Google IDs shown on this page so the mirror rows are not listed twice.
                // student_tasks.google_coursework_id stores Google's own ID (google_classroom_coursework.google_coursework_id),
                // not the local table id.
                $allGoogleIds = collect($termGoogleData ?? [])
                    ->flatMap(fn($d) => $d['coursework'] ?? collect())
                    ->pluck('google_coursework_id')
                    ->map(fn($id) => (string) $id)
                    ->all();
            @endphp

            @foreach(['prelim' => 'Prelim', 'midterm' => 'Midterm', 'semifinal' => 'Semi-Final', 'final' => 'Final'] as $key => $termLabel)
                @php
                    $termAll = $activities->where('term', $key);
                    $termModules = $termAll->where('activity_type', 'module');
                    $termTasks = $termAll
                        ->whereNotIn('activity_type', ['module'])
                        ->filter(fn($t) => empty($t->google_coursework_id) || !in_array((string) $t->google_coursework_id, $allGoogleIds, true));

                    // Scores of mirrored Google tasks, so the Google Classroom row can still show the student's grade
                    $gMirrorGrades = $activities
                        ->filter(fn($t) => !empty($t->google_coursework_id))
                        ->keyBy('google_coursework_id');

                    $termMaterials = collect($materials)->where('term', $key);
                    
                    $gMaterials = $termGoogleData[$key]['materials'] ?? collect();
                    $gCourseWork = $termGoogleData[$key]['coursework'] ?? collect();
                    $hasGClass = isset($gLinkedCourse) && $gLinkedCourse;
                @endphp

                <div x-show="currentTab === '{{ $key }}'" x-transition style="display: none;">

                    <div class="workspace-card" style="margin-bottom: 32px;">
                        <div class="card-workspace-header mb-3">
                            <h4 class="m-0 fw-bold text-dark-blue">{{ $termLabel }} Learning Materials</h4>
                            <p class="small text-muted m-0">Modules, reviewers, and handouts for this term</p>
                        </div>

                        @if($termModules->isEmpty() && (!$hasGClass || $gMaterials->isEmpty()))
                            <div class="empty-state-box">
                                <div class="empty-state-icon"><i class="fa-solid fa-folder-open"></i></div>
                                <h5 class="empty-state-title">No {{ $termLabel }} Materials Found</h5>
                                <p class="empty-state-text">Your professor hasn't uploaded modules or handouts for this term yet.</p>
                            </div>
                        @else
                            <div class="material-list">
                                @foreach($termModules as $module)
                                    <div class="material-row">
                                        <div class="material-icon"><i class="fa-solid fa-file-lines"></i></div>
                                        <div class="material-info">
                                            <span class="material-title">{{ $module->activity_name }}</span>
                                            <span class="material-filename">{{ $module->original_filename ?? 'No file attached' }}</span>
                                        </div>
                                        @if($module->file_path)
                                            <a href="{{ route('professor.task-file', $module->id) }}" class="btn-sm-outline btn-download" target="_blank">
                                                <i class="fa-solid fa-download"></i> Download
                                            </a>
                                        @endif
                                    </div>
                                @endforeach

                                @if($hasGClass)
                                    @foreach($gMaterials as $mat)
                                        <div class="material-row">
                                            <div class="material-icon"><i class="fa-brands fa-google-drive"></i></div>
                                            <div class="material-info">
                                                <span class="material-title">
                                                    {{ $mat->title }} 
                                                    <span class="gclass-badge">Google Classroom</span>
                                                </span>
                                                
                                                <span class="material-filename">
                                                    {{ $mat->drive_file_id ? ($mat->drive_file_name ?? 'Attached File') : 'No file attached' }}
                                                </span>
                                            </div>

                                            @if($mat->drive_file_id)
                                                <a href="{{ route('student.google-classroom.material-download', $mat->id) }}" class="btn-sm-outline btn-download">
                                                    <i class="fa-solid fa-download"></i> Download
                                                </a>
                                            @endif
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        @endif
                    </div>

                    <div class="workspace-card">
                        <div class="card-workspace-header mb-4">
                            <h4 class="m-0 fw-bold text-dark-blue">{{ $termLabel }} Tasks & Assessments</h4>
                            <p class="small text-muted m-0">Quizzes, assignments, and exams assigned for this term</p>
                        </div>

                        @if($termTasks->isEmpty() && (!$hasGClass || $gCourseWork->isEmpty()))
                            <div class="empty-state-box">
                                <div class="empty-state-icon"><i class="fa-solid fa-pen-to-square"></i></div>
                                <h5 class="empty-state-title">No {{ $termLabel }} Tasks Found</h5>
                                <p class="empty-state-text">Your professor hasn't uploaded any items for this term yet.</p>
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table custom-portal-table align-middle">
                                    <thead>
                                        <tr>
                                            <th style="width: 25%;">Activity Title</th>
                                            <th style="width: 15%;">Type</th>
                                            <th style="width: 15%;">Max Score</th>
                                            <th style="width: 15%;">Your Score</th>
                                            <th style="width: 15%;">Due Date</th>
                                            <th style="width: 15%;" class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($termTasks as $task)
                                            <tr>
                                                <td style="width: 25%;"><strong class="text-dark-blue">{{ $task->activity_name }}</strong></td>
                                                <td style="width: 15%;"><span class="badge bg-secondary text-uppercase">{{ $task->activity_type }}</span></td>
                                                <td style="width: 15%;">{{ $task->max_score }} pts</td>
                                                <td style="width: 15%;">
                                                    @if(!is_null($task->student_grade))
                                                        <strong class="text-success">{{ $task->student_grade }}</strong> <span class="text-muted">/{{ $task->max_score }}</span>
                                                    @else
                                                        <span class="text-muted">--</span>
                                                    @endif
                                                </td>
                                                <td style="width: 15%;">{{ $task->due_date ? \Carbon\Carbon::parse($task->due_date)->format('M d, Y') : 'No Deadline' }}</td>
                                                <td style="width: 15%;" class="text-center">
                                                    <a href="{{ route('student.view-task', $task->id) }}" class="btn-sm-outline btn-view-task">
                                                        <i class="fa-solid fa-eye"></i> View Task
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach

                                        @if($hasGClass)
                                            @foreach($gCourseWork as $cw)
                                                <tr>
                                                    <td style="width: 25%;">
                                                        <strong class="text-dark-blue">{{ $cw->title }}</strong>
                                                        <span class="gclass-badge ms-1">Google Classroom</span>
                                                    </td>
                                                    <td style="width: 15%;"><span class="badge bg-secondary text-uppercase">{{ $cw->work_type ?? 'coursework' }}</span></td>
                                                    <td style="width: 15%;">{{ $cw->max_points ?? '—' }} pts</td>
                                                    <td style="width: 15%;">
                                                        @php $mirror = $gMirrorGrades->get($cw->google_coursework_id); @endphp
                                                        @if($mirror && !is_null($mirror->student_grade))
                                                            <strong class="text-success">{{ $mirror->student_grade }}</strong> <span class="text-muted">/{{ $mirror->max_score }}</span>
                                                        @else
                                                            <span class="text-muted">--</span>
                                                        @endif
                                                    </td>
                                                    <td style="width: 15%;">{{ $cw->due_date ? \Carbon\Carbon::parse($cw->due_date)->format('M d, Y') : 'No Deadline' }}</td>
                                                    <td style="width: 15%;" class="text-center">
                                                        <a href="{{ route('student.view-google-task', $cw->id) }}" class="btn-sm-outline btn-view-task">
                                                            <i class="fa-solid fa-eye"></i> View Task
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach

        </div>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }

    .border-au-blue { border-top: 5px solid #1e2a5a !important; }
    .font-xs { font-size: 12px; }
    .font-xxs { font-size: 10px; letter-spacing: 0.5px; }
    .text-dark-blue { color: #1e2a5a; }

    .performance-breakdown-modern {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 10px 12px;
    }
    
    .breakdown-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 12px;
        padding: 6px 0;
        border-bottom: 1px dashed #e2e8f0;
    }

    .au-prediction-display-box {
        background-color: #ffffff;
        border: 1px solid #cbd5e1;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        border-radius: 12px;
        padding: 18px !important;
        text-align: center !important;
    }
    
    .predicted-grade-number {
        font-size: 38px;
        font-weight: 900;
        color: #1e2a5a;
        line-height: 1.1;
        letter-spacing: -1px;
    }

    .badge-excellent { background-color: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; font-size: 10px; font-weight: 700; }
    .badge-passed { background-color: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; font-size: 10px; font-weight: 700; }
    .badge-warning { background-color: #fef3c7; color: #92400e; border: 1px solid #fde68a; font-size: 10px; font-weight: 700; }
    .au-prediction-display-box.au-borderline { border: 1px solid #f59e0b; background-color: #fffbeb; }
    .au-prediction-display-box.au-borderline .predicted-grade-number { color: #b45309; }
    .au-early-warning { margin-top: 12px; padding: 10px 12px; background: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 8px; text-align: left; font-size: 11.5px; color: #78350f; line-height: 1.45; }
    .au-early-warning-title { font-weight: 800; font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.3px; margin-bottom: 4px; }
    .badge-failed { background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca; font-size: 10px; font-weight: 700; }

    .sidebar-navigation-card { 
        background: white; 
        border-radius: 12px; 
        padding: 18px; 
        border: 1px solid #e2e8f0; 
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    }
    
    .panel-title-label { 
        font-size: 11px; 
        font-weight: 800; 
        color: #475569; 
        letter-spacing: 0.5px; 
    }
    
    .sidebar-divider { 
        margin: 16px 0; 
        border-color: #e2e8f0; 
    }
        
    .page-header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .text-dark-blue { color: #1e2a5a; }
    .btn-back-link { text-decoration: none; color: #475569; font-weight: 600; font-size: 13.5px; transition: 0.2s; }
    .btn-back-link:hover { color: #1e2a5a; }
    .badge-portal { background: #f1f5f9; color: #475569; font-weight: 700; font-size: 11px; padding: 6px 14px; border-radius: 30px; text-transform: uppercase; border: 1px solid #cbd5e1; }

    .alert-banner { padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; font-weight: 600; }
    .alert-success { background: #f0fdf4; border-left: 4px solid #22c55e; color: #166534; }
    .alert-danger { background: #fef2f2; border-left: 4px solid #ef4444; color: #991b1b; }

    .dashboard-grid-wrapper { display: flex; gap: 24px; align-items: stretch; width: 100%; }
    .unified-sidebar { width: 300px; flex-shrink: 0; display: flex; flex-direction: column; gap: 16px; }
    .unified-workspace { flex-grow: 1; min-width: 0; }

    .sidebar-subject-card { background: white; border-radius: 12px; padding: 20px; border: 1px solid #e2e8f0; border-top: 5px solid #1e2a5a; }
    .subject-tag-line { display: flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 800; }
    .code-badge { color: #0284c7; }
    .bullet-sep { color: #94a3b8; }
    .section-badge { color: #1e2a5a; }
    .sidebar-subject-title { font-size: 16px; font-weight: 800; color: #1e293b; margin: 4px 0; }
    .sidebar-meta-list { display: flex; flex-direction: column; gap: 12px; }
    .meta-item { display: flex; gap: 10px; font-size: 13px; color: #475569; }

    .nav-tab-btn { width: 100%; display: flex; align-items: center; gap: 10px; background: transparent; border: none; text-align: left; padding: 10px 14px; border-radius: 8px; font-size: 13.5px; font-weight: 600; color: #475569; margin-bottom: 4px; transition: 0.2s; cursor: pointer; }
    .nav-tab-btn:hover { background: #f8fafc; color: #1e2a5a; }
    .active-tab { background: #1e2a5a !important; color: white !important; }
    .active-tab i { color: #facc15 !important; }

    .workspace-card { background: white; border-radius: 12px; border: 1px solid #e2e8f0; padding: 24px; }
    .card-workspace-header { margin-bottom: 16px; }

    .custom-portal-table { border: 1px solid #f1f5f9; width: 100%; table-layout: fixed; }
    .custom-portal-table th { background: #f8fafc; font-size: 12px; font-weight: 700; text-transform: uppercase; color: #64748b; padding: 14px 12px; border-bottom: 2px solid #e2e8f0; }
    .custom-portal-table td { padding: 14px 12px; font-size: 14px; border-bottom: 1px solid #f1f5f9; }

    .empty-state-box { display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 40px 24px; border: 2px dashed #e2e8f0; border-radius: 10px; background: #fafbfc; }
    .empty-state-icon { width: 48px; height: 48px; border-radius: 50%; background: #eef1fb; display: flex; align-items: center; justify-content: center; margin-bottom: 14px; }
    .empty-state-icon i { font-size: 18px; color: #1e2a5a; }
    .empty-state-title { font-weight: 700; color: #334155; font-size: 14px; margin: 0 0 4px; }
    .empty-state-text { font-size: 12.5px; color: #94a3b8; max-width: 340px; margin: 0; }

    .btn-sm-outline {
        background: white; border: 1px solid #cbd5e1; color: #475569;
        font-size: 12px; font-weight: 600; padding: 6px 12px;
        border-radius: 6px; cursor: pointer; transition: 0.15s;
        display: inline-flex; align-items: center; gap: 6px; text-decoration: none;
    }
    .btn-view-task:hover { border-color: #1e2a5a; color: #1e2a5a; background: #eef1fb; }
    .btn-download:hover { border-color: #15803d; color: #15803d; background: #f0fdf4; }

    .material-list { display: flex; flex-direction: column; gap: 10px; }
    .material-row {
        display: flex; align-items: center; gap: 14px;
        border: 1px solid #e2e8f0; background: #f8fafc; border-radius: 10px; padding: 14px 16px;
    }
    .material-icon { font-size: 18px; color: #1e2a5a; }
    .material-info { display: flex; flex-direction: column; flex-grow: 1; min-width: 0; }
    .material-title { font-size: 14px; font-weight: 700; color: #1e293b; }
    .material-filename { font-size: 12px; color: #94a3b8; }

    .gclass-badge {
        display: inline-block;
        background-color: #f1f5f9;
        color: #475569;
        border: 1px solid #cbd5e1;
        font-size: 10px;
        font-weight: 600;
        padding: 1px 8px;
        border-radius: 12px;
        margin-left: 6px;
        vertical-align: middle;
        letter-spacing: 0.2px;
    }
</style>
@endsection