@extends('layouts.app')

@section('content')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="prof-container py-4"
    x-data="{ currentTab: new URLSearchParams(window.location.search).get('tab') || 'prelim' }">

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
                <div class="panel-title-label">TERM GRADE ENCODING</div>
                @foreach(['prelim' => 'Prelim Term', 'midterm' => 'Midterm Term', 'semifinal' => 'Semi-Final Term', 'final' => 'Final Term'] as $key => $label)
                    <button class="nav-tab-btn" :class="currentTab === '{{ $key }}' ? 'active-tab' : ''" @click="currentTab = '{{ $key }}'">
                        <i class="fa-regular fa-pen-to-square"></i> {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        <div class="unified-workspace">

            @foreach(['prelim' => 'Prelim', 'midterm' => 'Midterm', 'semifinal' => 'Semi-Final', 'final' => 'Final'] as $key => $termLabel)
                @php
                    $termAll = $activities->where('term', $key);
                    $termModules = $termAll->where('activity_type', 'module');
                    $termTasks = $termAll->whereNotIn('activity_type', ['module']);
                @endphp
                <div x-show="currentTab === '{{ $key }}'" x-transition style="display: none;">

                    <div class="workspace-card" style="margin-bottom: 32px;">
                        <div class="card-workspace-header mb-3">
                            <h4 class="m-0 fw-bold text-dark-blue">{{ $termLabel }} Learning Materials</h4>
                            <p class="small text-muted m-0">Modules, reviewers, and handouts for this term</p>
                        </div>

                        @if($termModules->isEmpty())
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
                            </div>
                        @endif
                    </div>

                    <div class="workspace-card">
                        <div class="card-workspace-header mb-4">
                            <h4 class="m-0 fw-bold text-dark-blue">{{ $termLabel }} Tasks & Assessments</h4>
                            <p class="small text-muted m-0">Quizzes, assignments, and exams assigned for this term</p>
                        </div>

                        @if($termTasks->isEmpty())
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
                                                <td>{{ $task->due_date ? \Carbon\Carbon::parse($task->due_date)->format('M d, Y') : 'No Deadline' }}</td>
                                                <td class="text-center">
                                                    <a href="{{ route('student.view-task', $task->id) }}" class="btn-sm-outline btn-view-task">
                                                        <i class="fa-solid fa-eye"></i> View Task
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
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

    .page-header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }

    .text-dark-blue { color: #1e2a5a; }
    .text-dark-slate { color: #334155; }
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
    .sidebar-divider { margin: 14px 0; border-color: #e2e8f0; }
    .sidebar-meta-list { display: flex; flex-direction: column; gap: 12px; }
    .meta-item { display: flex; gap: 10px; font-size: 13px; color: #475569; }

    .sidebar-navigation-card { background: white; border-radius: 12px; padding: 16px; border: 1px solid #e2e8f0; }
    .panel-title-label { font-size: 10.5px; font-weight: 800; color: #94a3b8; letter-spacing: 0.5px; padding-left: 8px; margin-bottom: 8px; }
    .nav-tab-btn { width: 100%; display: flex; align-items: center; gap: 10px; background: transparent; border: none; text-align: left; padding: 10px 14px; border-radius: 8px; font-size: 13.5px; font-weight: 600; color: #475569; margin-bottom: 4px; transition: 0.2s; cursor: pointer; }
    .nav-tab-btn:hover { background: #f8fafc; color: #1e2a5a; }
    .active-tab { background: #1e2a5a !important; color: white !important; }
    .active-tab i { color: #facc15 !important; }

    .workspace-card { background: white; border-radius: 12px; border: 1px solid #e2e8f0; padding: 24px; }
    .card-workspace-header { margin-bottom: 16px; }

    .custom-portal-table { border: 1px solid #f1f5f9; width: 100%; }
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
</style>
@endsection