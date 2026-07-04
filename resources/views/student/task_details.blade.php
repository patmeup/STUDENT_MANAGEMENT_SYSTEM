@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="task-details-container py-4 px-3">
    <div class="mb-4">
        <a href="{{ route('student.class_details', $task->class_schedule_id ?? '') }}" class="btn-back-dashboard">
            <i class="fa-solid fa-arrow-left"></i> Back to Class Workspace
        </a>
    </div>

    <div class="task-workspace-grid">
        
        <div class="task-main-card">
            <div class="task-meta-ribbon d-flex align-items-center gap-2 mb-3">
                <span class="badge-type-indicator {{ in_array(strtolower($task->activity_type), ['exam', 'quiz']) ? 'bg-danger-soft' : 'bg-primary-soft' }}">
                    <i class="fa-solid {{ in_array(strtolower($task->activity_type), ['exam', 'quiz']) ? 'fa-file-invoice' : 'fa-book-bookmark' }} me-1"></i>
                    {{ strtoupper($task->activity_type) }}
                </span>
                <span class="badge-term-indicator">
                    {{ strtoupper($task->term) }} TERM
                </span>
            </div>

            <h1 class="task-display-title text-dark fw-bold mb-2">{{ $task->activity_name }}</h1>
            
            <div class="task-score-benchmark text-muted d-flex align-items-center gap-2 mb-4">
                <i class="fa-solid fa-calculator text-secondary"></i>
                <span>Max Assessment Evaluation Scale: <strong>{{ $task->max_score ?? '100' }} Points Total</strong></span>
            </div>

            <hr class="divider-line my-4">

            <div class="task-instructions-section">
                <h3 class="instruction-heading mb-3">
                    <i class="fa-solid fa-book-open me-2 text-muted"></i>  Activity Guidelines & Instructions
                </h3>
                <div class="instruction-markdown-body">
                    @if(!empty($task->description))
                        {!! nl2br(e($task->description)) !!}
                    @else
                        <p class="text-muted small italic mt-2">No instructions provided for this activity.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="task-sidebar-card">
            <h3 class="sidebar-box-title mb-3">Your Work Submission</h3>
            
            <div class="deadline-status-alert mb-4">
                <div class="alert-label-header text-muted">SUBMISSION DEADLINE TARGET</div>
                <div class="alert-timestamp-date text-dark fw-bold mt-0.5 d-flex align-items-center gap-2">
                    <i class="fa-regular fa-calendar-check text-danger fs-5"></i>
                    <span>{{ \Carbon\Carbon::parse($task->due_date)->format('M d, Y') }}</span>
                </div>
            </div>

            <form action="#" method="POST" class="submission-locked-form">
                @csrf
                <div class="upload-zone-wrapper mb-3 text-center">
                    <i class="fa-solid fa-cloud-arrow-up fs-3 text-muted mb-2 opacity-50"></i>
                    <label class="form-label d-block small fw-bold text-secondary mb-1">Upload Homework File Attachment</label>
                    <input type="file" class="form-control form-control-sm mx-auto" style="max-width: 240px;" disabled>
                </div>

                <button type="button" class="btn btn-lock-submit w-100 py-2.5 fw-bold text-white rounded-pill mb-2" disabled>
                    <i class="fa-solid fa-lock me-2"></i>Turn In Task
                </button>
                
                <div class="security-notice-tag">
                    <i class="fa-solid fa-circle-info me-1"></i>Submission pipelines are currently locked.
                </div>
            </form>
        </div>

    </div>
</div>

<style>
    /* Scope Base Styling Definitions Container */
    .task-details-container {
        max-width: 1200px;
        margin: 0 auto;
        font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
    }

    /* Back Route Nav Ribbon Link */
    .btn-back-dashboard {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #1e2a5a;
        font-weight: 600;
        text-decoration: none;
        font-size: 14px;
        transition: color 0.2s ease;
    }
    .btn-back-dashboard:hover {
        color: #0284c7;
    }

    /* Modern Dynamic Split Grid Layout Core Elements */
    .task-workspace-grid {
        display: grid;
        grid-template-columns: 1fr 340px;
        gap: 25px;
        align-items: start;
    }

    @media (max-width: 992px) {
        .task-workspace-grid {
            grid-template-columns: 1fr;
        }
    }

    /* Left Frame Primary Card Structure */
    .task-main-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 35px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.01);
    }

    .task-display-title {
        font-size: 28px;
        letter-spacing: -0.5px;
    }

    .task-score-benchmark {
        font-size: 14px;
    }

    .divider-line {
        color: #e2e8f0;
    }

    /* Custom Metadata Badge Ribbon Parameters */
    .badge-type-indicator {
        font-size: 11px;
        font-weight: 700;
        padding: 5px 12px;
        border-radius: 6px;
        letter-spacing: 0.5px;
    }
    .badge-type-indicator.bg-danger-soft {
        background: #fef2f2;
        color: #ef4444;
    }
    .badge-type-indicator.bg-primary-soft {
        background: #eff6ff;
        color: #3b82f6;
    }

    .badge-term-indicator {
        font-size: 11px;
        background: #f1f5f9;
        color: #475569;
        font-weight: 700;
        padding: 5px 12px;
        border-radius: 6px;
        letter-spacing: 0.5px;
    }

    /* Instructions Content Renderers Space */
    .instruction-heading {
        font-size: 16px;
        color: #475569;
        font-weight: 700;
        letter-spacing: -0.2px;
    }
    .instruction-markdown-body {
        font-size: 15px;
        line-height: 1.65;
        color: #334155;
        background: #f8fafc;
        border-radius: 8px;
        padding: 20px;
        border-left: 4px solid #cbd5e1;
    }

    /* Right Frame Pipeline Card Container Layout Structure */
    .task-sidebar-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 28px 24px;
        box-shadow: 0 4px 20px rgba(30, 42, 90, 0.04);
        border-top: 5px solid #1e2a5a;
    }

    .sidebar-box-title {
        font-size: 16px;
        color: #0f172a;
        font-weight: 700;
        letter-spacing: -0.2px;
    }

    /* Target Alert Component Box Area styles */
    .deadline-status-alert {
        background: #fff7ed;
        border: 1px solid #ffedd5;
        padding: 14px 16px;
        border-radius: 10px;
    }
    .alert-label-header {
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 0.8px;
        color: #c2410c !important;
    }
    .alert-timestamp-date {
        font-size: 14.5px;
        color: #431407;
    }

    /* Custom upload zone card layout component */
    .upload-zone-wrapper {
        background: #f8fafc !important;
        border: 2px dashed #cbd5e1 !important;
        border-radius: 12px;
        padding: 24px 16px !important;
    }

    /* Styling the native browser input tag button seamlessly */
    .upload-zone-wrapper input[type="file"] {
        font-size: 13px;
        color: #64748b;
        margin-top: 10px;
    }
    .upload-zone-wrapper input[type="file"]::file-selector-button {
        border: none;
        background: #e2e8f0;
        color: #334155;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        cursor: not-allowed;
        margin-right: 10px;
    }

    /* Locked Pipeline Form Action Button Layouts */
    .btn-lock-submit {
        background: #94a3b8 !important;
        border: none;
        font-size: 14px;
        font-weight: 700;
        letter-spacing: 0.2px;
        padding: 11px;
        cursor: not-allowed;
        box-shadow: none !important;
        opacity: 0.85;
    }

    /* Informative Context Status Label Text components */
    .security-notice-tag {
        font-size: 12px;
        font-weight: 600;
        color: #ef4444 !important;
        background: #fef2f2;
        padding: 8px;
        border-radius: 8px;
        margin-top: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }
</style>
@endsection