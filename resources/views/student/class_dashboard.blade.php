@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="dashboard-wrapper py-4 px-3">
    <div class="mb-4">
        <a href="{{ route('student.my_classes') }}" class="btn-back">
            <i class="fa-solid fa-arrow-left"></i> Back to My Classes
        </a>
    </div>

    <div class="class-banner mb-4">
        <div class="banner-body">
            <span class="subject-tag">{{ strtoupper($classDetails->subject_code) }}</span>
            <h1 class="class-title">{{ $classDetails->subject_title }}</h1>
            <p class="class-subtitle mb-0">
                <i class="fa-solid fa-layer-group me-1"></i> Section: <strong>{{ $classDetails->section_name }}</strong> 
                <span class="mx-2">|</span> 
                <i class="fa-solid fa-location-dot me-1"></i> Room: <strong>{{ $classDetails->room_assignment }}</strong>
            </p>
        </div>
    </div>

    <div class="workspace-grid">
        <div class="workspace-sidebar">
            <div class="nav-card mb-3">
                <div id="tab-materials-btn" class="nav-item-btn active">
                    <i class="fa-solid fa-book-open"></i> Class Materials
                </div>
                <div id="tab-tasks-btn" class="nav-item-btn">
                    <i class="fa-solid fa-tasks"></i> Assignments & Tasks
                </div>
            </div>

            <div class="term-filter-card">
                <div class="term-header">TERM GRADE ENCODING</div>
                <a href="{{ url()->current() }}?tab=tasks&term=prelim" class="term-link {{ request('term') === 'prelim' ? 'active' : '' }}">
                    <i class="fa-regular fa-pen-to-square"></i> Prelim Term
                </a>
                <a href="{{ url()->current() }}?tab=tasks&term=midterm" class="term-link {{ request('term') === 'midterm' ? 'active' : '' }}">
                    <i class="fa-regular fa-pen-to-square"></i> Midterm Term
                </a>
                <a href="{{ url()->current() }}?tab=tasks&term=semifinal" class="term-link {{ request('term') === 'semifinal' ? 'active' : '' }}">
                    <i class="fa-regular fa-pen-to-square"></i> Semi-Final Term
                </a>
                <a href="{{ url()->current() }}?tab=tasks&term=final" class="term-link {{ request('term') === 'final' ? 'active' : '' }}">
                    <i class="fa-regular fa-pen-to-square"></i> Final Term
                </a>
            </div>
        </div>

        <div class="workspace-content">
            
            <div id="panel-materials" class="content-panel">
                <h3 class="section-heading">Learning Materials Masterlist</h3>
                <div class="empty-state py-5">
                    <i class="fa-solid fa-folder-open mb-3"></i>
                    <p class="main-msg">No posted materials found</p>
                    <p class="sub-msg">Your professor hasn't uploaded modular files or handouts yet.</p>
                </div>
            </div>

            <div id="panel-tasks" class="content-panel" style="display: none;">
                <h3 class="section-heading">
                    Class Activities & Assessments
                    @if(request('term'))
                        <span class="fs-6 text-muted fw-normal">({{ strtoupper(request('term')) }} FILTER ACTIVE)</span>
                    @endif
                </h3>
                
                @if($activities->isEmpty())
                    <div class="empty-state py-5">
                        <i class="fa-solid fa-pen-to-square mb-3"></i>
                        <p class="main-msg">No pending tasks found</p>
                        <p class="sub-msg">Your professor hasn't uploaded any items for this filter target.</p>
                    </div>
                @else
                    <div class="activity-list d-flex flex-column gap-3">
                        @foreach($activities as $activity)
                            <div class="activity-card-row">
                                <div class="activity-main-info">
                                    <div class="activity-icon-wrapper {{ in_array(strtolower($activity->activity_type), ['exam', 'quiz']) ? 'type-exam' : 'type-assignment' }}">
                                        <i class="fa-solid {{ in_array(strtolower($activity->activity_type), ['exam', 'quiz']) ? 'fa-file-invoice' : 'fa-book-bookmark' }}"></i>
                                    </div>
                                    <div class="activity-text-details">
                                        <div class="activity-title-line">
                                            <h4 class="activity-name mb-0">{{ $activity->activity_name }}</h4>
                                            <span class="badge-term">{{ strtoupper($activity->term) }}</span>
                                        </div>
                                        <div class="activity-meta-sub text-muted mt-1">
                                            <span class="meta-type-tag"><i class="fa-solid fa-tags me-1"></i>{{ strtoupper($activity->activity_type) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="activity-action-pane">
                                    <div class="deadline-box text-end">
                                        <span class="deadline-label d-block text-muted">DUE DATE</span>
                                        <span class="deadline-date"><i class="fa-regular fa-calendar-check me-1.5"></i>{{ \Carbon\Carbon::parse($activity->due_date)->format('M d, Y') }}</span>
                                    </div>
                                    <a href="{{ route('student.view-task', $activity->id) }}" class="btn-action-view">
                                        View Task <i class="fa-solid fa-chevron-right ms-1"></i>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const matBtn = document.getElementById("tab-materials-btn");
        const tasksBtn = document.getElementById("tab-tasks-btn");
        
        const matPanel = document.getElementById("panel-materials");
        const tasksPanel = document.getElementById("panel-tasks");

        // Helper parameters check to persist state on term URL filters
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('tab') === 'tasks' || urlParams.has('term')) {
            switchToTasks();
        }

        matBtn.addEventListener("click", function () {
            switchToMaterials();
        });

        tasksBtn.addEventListener("click", function () {
            switchToTasks();
        });

        function switchToMaterials() {
            matBtn.classList.add("active");
            tasksBtn.classList.remove("active");
            matPanel.style.display = "block";
            tasksPanel.style.display = "none";
        }

        function switchToTasks() {
            tasksBtn.classList.add("active");
            matBtn.classList.remove("active");
            tasksPanel.style.display = "block";
            matPanel.style.display = "none";
        }
    });
</script>

<style>
    /* Base Core Dashboard Structure */
    .dashboard-wrapper {
        max-width: 1200px;
        margin: 0 auto;
        font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
    }

    /* Back Route Button Action Text */
    .btn-back {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #1e2a5a;
        font-weight: 600;
        text-decoration: none;
        font-size: 14px;
        transition: color 0.2s ease;
    }
    .btn-back:hover {
        color: #0284c7;
    }

    /* Subject Header Display Hero Area Banner */
    .class-banner {
        background: #1e2a5a;
        color: white;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }
    .banner-body {
        padding: 30px;
    }
    .subject-tag {
        background: rgba(255, 255, 255, 0.15);
        color: #facc15;
        padding: 4px 14px;
        border-radius: 50px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.5px;
        display: inline-block;
        margin-bottom: 12px;
    }
    .class-title {
        font-size: 26px;
        font-weight: 700;
        margin-bottom: 8px;
    }
    .class-subtitle {
        font-size: 14px;
        color: #cbd5e1;
    }

    /* Double Layout Content Workspace Flex Splitter Grid */
    .workspace-grid {
        display: grid;
        grid-template-columns: 280px 1fr;
        gap: 30px;
        align-items: start;
    }

    @media (max-width: 768px) {
        .workspace-grid {
            grid-template-columns: 1fr;
        }
    }

    /* Left Frame Vertical Tab Layout Options Navigation */
    .nav-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }
    .nav-item-btn {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px 18px;
        color: #475569;
        font-weight: 600;
        font-size: 14px;
        border-radius: 8px;
        transition: all 0.2s ease;
        cursor: pointer;
        user-select: none;
    }
    .nav-item-btn i {
        font-size: 16px;
        width: 20px;
        text-align: center;
        opacity: 0.8;
    }
    .nav-item-btn:hover {
        background: #f8fafc;
        color: #1e2a5a;
    }
    .nav-item-btn.active {
        background: #1e2a5a;
        color: white;
    }

    /* Term Filter Section Cards Custom Style Elements */
    .term-filter-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 14px 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }
    .term-header {
        font-size: 10px;
        text-transform: uppercase;
        font-weight: 700;
        color: #94a3b8;
        padding-left: 16px;
        margin-bottom: 8px;
        letter-spacing: 0.5px;
    }
    .term-link {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 16px;
        color: #475569;
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
        border-radius: 8px;
        transition: all 0.2s ease;
    }
    .term-link i {
        font-size: 14px;
        color: #64748b;
    }
    .term-link:hover {
        background: #f8fafc;
        color: #1e2a5a;
    }
    .term-link.active {
        background: #f1f5f9;
        color: #1e2a5a;
        font-weight: 700;
    }

    /* Core Panel Window Blocks Template */
    .content-panel {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 30px;
        min-height: 400px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }
    .section-heading {
        font-size: 18px;
        color: #1e293b;
        font-weight: 700;
        border-bottom: 2px solid #f1f5f9;
        padding-bottom: 15px;
        margin-bottom: 25px;
    }

    /* Empty Status Elements Visual Setup styling */
    .empty-state {
        text-align: center;
        color: #94a3b8;
        padding: 60px 0;
    }
    .empty-state i {
        font-size: 56px;
        color: #e2e8f0;
    }
    .empty-state .main-msg {
        font-size: 16px;
        font-weight: 600;
        color: #475569;
        margin-top: 10px;
        margin-bottom: 4px;
    }
    .empty-state .sub-msg {
        font-size: 13px;
        max-width: 340px;
        margin: 0 auto;
        color: #94a3b8;
    }

    /* Modern Card Layout Row Formats components design */
    .activity-list {
        margin-top: 5px;
    }
    .activity-card-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 18px 24px;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .activity-card-row:hover {
        border-color: #cbd5e1;
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(30, 42, 90, 0.05);
    }
    .activity-main-info {
        display: flex;
        align-items: center;
        gap: 18px;
    }
    .activity-icon-wrapper {
        width: 46px;
        height: 46px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }
    .activity-icon-wrapper.type-exam {
        background: #fef2f2;
        color: #ef4444;
    }
    .activity-icon-wrapper.type-assignment {
        background: #eff6ff;
        color: #3b82f6;
    }
    .activity-title-line {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    .activity-name {
        font-size: 16px;
        font-weight: 700;
        color: #1e293b;
    }
    .badge-term {
        font-size: 11px;
        background: #f1f5f9;
        color: #475569;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 4px;
        letter-spacing: 0.5px;
    }
    .activity-meta-sub {
        font-size: 13px;
    }
    .activity-action-pane {
        display: flex;
        align-items: center;
        gap: 30px;
    }
    .deadline-box .deadline-label {
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.5px;
        color: #94a3b8 !important;
    }
    .deadline-box .deadline-date {
        font-size: 14px;
        font-weight: 600;
        color: #334155;
    }
    .btn-action-view {
        display: inline-flex;
        align-items: center;
        background: #1e2a5a;
        color: white;
        font-size: 13px;
        font-weight: 600;
        padding: 10px 20px;
        border-radius: 30px;
        text-decoration: none;
        transition: background 0.2s ease;
    }
    .btn-action-view:hover {
        background: #0284c7;
        color: white;
    }

    /* Mobile Adaptability Viewport Controls */
    @media (max-width: 576px) {
        .activity-card-row {
            flex-direction: column;
            align-items: flex-start;
            gap: 15px;
            padding: 16px;
        }
        .activity-action-pane {
            width: 100%;
            justify-content: space-between;
            border-top: 1px dashed #e2e8f0;
            padding-top: 12px;
        }
        .deadline-box {
            text-align: left !important;
        }
    }
</style>
@endsection