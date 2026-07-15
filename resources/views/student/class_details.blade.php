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

            <div class="sidebar-navigation-card border-au-blue">
                <div class="panel-title-label d-flex justify-content-between align-items-center">
                    <span>🔮 AU-LINK GRADE SIMULATOR</span>
                </div>
                <p class="text-muted font-xs mb-3">Baguhin ang inputs gamit ang slider o sa pamamagitan ng pag-type sa box para makita ang iyong predicted grade.</p>

                <div class="mb-3">
                    <label class="d-block text-dark-blue font-xs fw-bold mb-1">Class Standing (Quizzes/Tasks):</label>
                    <div class="d-flex align-items-center gap-2">
                        <input type="range" class="prediction-slider flex-grow-1" id="standing_slider" min="50" max="100" value="{{ $currentWrittenWorks > 0 ? round($currentWrittenWorks) : 75 }}" oninput="syncInputs('standing', this.value)">
                        <input type="number" class="form-control form-control-sm text-center font-xs fw-bold slider-number-input" id="standing_number" min="50" max="100" value="{{ $currentWrittenWorks > 0 ? round($currentWrittenWorks) : 75 }}" oninput="syncInputs('standing', this.value)">
                        <span class="font-xs fw-bold text-muted">%</span>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="d-block text-dark-blue font-xs fw-bold mb-1">Attendance & Recitation:</label>
                    <div class="d-flex align-items-center gap-2">
                        <input type="range" class="prediction-slider flex-grow-1" id="attendance_slider" min="50" max="100" value="{{ $currentAttendance > 0 ? round($currentAttendance) : 90 }}" oninput="syncInputs('attendance', this.value)">
                        <input type="number" class="form-control form-control-sm text-center font-xs fw-bold slider-number-input" id="attendance_number" min="50" max="100" value="{{ $currentAttendance > 0 ? round($currentAttendance) : 90 }}" oninput="syncInputs('attendance', this.value)">
                        <span class="font-xs fw-bold text-muted">%</span>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="d-block text-dark-blue font-xs fw-bold mb-1">Target Major Exam Score:</label>
                    <div class="d-flex align-items-center gap-2">
                        <input type="range" class="prediction-slider flex-grow-1 slider-gold-accent" id="exam_slider" min="50" max="100" value="85" oninput="syncInputs('exam', this.value)">
                        <input type="number" class="form-control form-control-sm text-center font-xs fw-bold slider-number-input" id="exam_number" min="50" max="100" value="85" oninput="syncInputs('exam', this.value)">
                        <span class="font-xs fw-bold text-muted">%</span>
                    </div>
                </div>

                <div class="au-prediction-display-box text-center p-3 rounded-10 bg-light-gray">
                    <span class="font-xxs text-uppercase fw-bold text-muted tracking-wider">Your Predicted Point Grade</span>
                    
                    <div class="predicted-grade-number my-2" id="display_point_grade">--</div>
                    
                    <div class="mb-2">
                        <span class="badge" id="display_status_badge">Calculating</span>
                    </div>
                    
                    <p class="font-xs text-muted m-0" id="display_description">Adjust sliders to calculate grade status.</p>
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

<script>
function syncInputs(type, val) {
    // I-sanitize ang lumampas sa limits (50 to 100)
    let sanitizedVal = parseInt(val) || 50;
    if (sanitizedVal > 100) sanitizedVal = 100;
    if (sanitizedVal < 50) sanitizedVal = 50;

    // I-synchronize ang parehong Slider at Number Box
    document.getElementById(`${type}_slider`).value = sanitizedVal;
    document.getElementById(`${type}_number`).value = sanitizedVal;

    // Patakbuhin ang real-time prediction base sa Arellano criteria
    calculateAUPrediction();
}

function calculateAUPrediction() {
    // 1. Kunin ang mga value mula sa active sliders/inputs
    const standing = parseFloat(document.getElementById('standing_slider').value) || 0;
    const attendance = parseFloat(document.getElementById('attendance_slider').value) || 0;
    const exam = parseFloat(document.getElementById('exam_slider').value) || 0;

    // 2. I-compute ang weighted elements (60% Written/Standing, 40% Performance/Attendance)
    // Ang Exam at Quizzes/Tasks ay naghahati sa 60% Written component
    const writtenAverage = (exam + standing) / 2;
    const writtenContribution = writtenAverage * 0.60;

    // Ang attendance at class participation ay naghahati sa 40% Performance component
    const performanceContribution = attendance * 0.40;

    const finalPercentage = writtenContribution + performanceContribution;

    // 3. Arellano University point grade matching algorithm
    let pointGrade = "5.00";
    let desc = "Failure";
    let badgeClass = "badge-failed";

    if (finalPercentage >= 97) {
        pointGrade = "1.00"; desc = "Excellent"; badgeClass = "badge-excellent";
    } else if (finalPercentage >= 94) {
        pointGrade = "1.25"; desc = "Superior"; badgeClass = "badge-excellent";
    } else if (finalPercentage >= 91) {
        pointGrade = "1.50"; desc = "Very Good"; badgeClass = "badge-passed";
    } else if (finalPercentage >= 88) {
        pointGrade = "1.75"; desc = "Above Average"; badgeClass = "badge-passed";
    } else if (finalPercentage >= 85) {
        pointGrade = "2.00"; desc = "Good"; badgeClass = "badge-passed";
    } else if (finalPercentage >= 82) {
        pointGrade = "2.25"; desc = "Satisfactory"; badgeClass = "badge-passed";
    } else if (finalPercentage >= 79) {
        pointGrade = "2.50"; desc = "Fairly Satisfactory"; badgeClass = "badge-passed";
    } else if (finalPercentage >= 76) {
        pointGrade = "2.75"; desc = "Fair / Passing"; badgeClass = "badge-passed";
    } else if (finalPercentage >= 75) {
        pointGrade = "3.00"; desc = "Passing"; badgeClass = "badge-passed";
    }

    // 4. I-update ang front-end elements
    const displayGrade = document.getElementById('display_point_grade');
    const displayBadge = document.getElementById('display_status_badge');
    const displayDesc = document.getElementById('display_description');

    displayGrade.innerText = pointGrade;
    displayDesc.innerText = `Equivalent to ${finalPercentage.toFixed(1)}% (${desc})`;

    // Palitan ang badge text at class style
    displayBadge.className = `badge ${badgeClass}`;
    if (pointGrade === "5.00") {
        displayBadge.innerText = "❌ AT RISK (FAILED)";
    } else {
        displayBadge.innerText = "🟢 PASSED (GOOD STANDING)";
    }
}

// Patakbuhin ang kalkulasyon sa unang load ng website
document.addEventListener("DOMContentLoaded", calculateAUPrediction);
</script>

<style>
    [x-cloak] { display: none !important; }

    /* ARELLANO BLUE/GOLD PORTAL STYLING */
    .border-au-blue { border-top: 5px solid #1e2a5a !important; }
    .text-teal { color: #1e2a5a !important; }
    .bg-light-gray { background-color: #f8fafc; border: 1px solid #e2e8f0; }
    .font-xs { font-size: 12px; }
    .font-xxs { font-size: 10px; letter-spacing: 0.5px; }
    .slider-number-input { width: 55px; border-radius: 6px; border: 1px solid #cbd5e1; height: 28px; padding: 2px 4px; }
    .slider-number-input:focus { border-color: #1e2a5a; box-shadow: 0 0 0 1px #1e2a5a; }

    /* Range Sliders */
    .prediction-slider {
        -webkit-appearance: none;
        width: 100%;
        height: 5px;
        border-radius: 5px;
        background: #cbd5e1;
        outline: none;
    }
    .prediction-slider::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        background: #0d6efd; /* AU Blue Accent */
        cursor: pointer;
        transition: transform 0.1s;
    }
    .prediction-slider::-webkit-slider-thumb:hover { transform: scale(1.2); }
    .slider-gold-accent::-webkit-slider-thumb { background: #eab308 !important; } /* Goal/Exam gold accent */

    /* Results Dashboard Box inside Sidebar */
    .au-prediction-display-box {
        box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
    }
    .predicted-grade-number {
        font-size: 32px;
        font-weight: 850;
        color: #1e2a5a;
        line-height: 1;
    }

    /* Passing and At-Risk Badges */
    .badge-excellent { background-color: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; padding: 4px 8px; font-size: 10px; }
    .badge-passed { background-color: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; padding: 4px 8px; font-size: 10px; }
    .badge-failed { background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca; padding: 4px 8px; font-size: 10px; }

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

    .custom-portal-table { border: 1px solid #f1f5f9; width: 100%; table-layout: fixed; }
    .custom-portal-table th { background: #f8fafc; font-size: 12px; font-weight: 700; text-transform: uppercase; color: #64748b; padding: 14px 12px; border-bottom: 2px solid #e2e8f0; }
    .custom-portal-table td { padding: 14px 12px; font-size: 14px; border-bottom: 1px solid #f1f5f9; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

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