@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="container py-4">
    <div class="page-header-row mb-4">
        <a href="javascript:history.back()" class="btn-back-link">
            <i class="fa-solid fa-arrow-left"></i> Back to Submissions List
        </a>
        <span class="badge-portal">Grading Workspace</span>
    </div>

    @if(session('success'))
        <div class="alert-banner alert-success">{{ session('success') }}</div>
    @endif

    <div class="grading-grid-wrapper">
        
        <div class="submission-viewer-panel">
            <div class="workspace-card">
                <div class="student-meta-box mb-4">
                    <div class="avatar-placeholder">
                        <i class="fa-solid fa-user-gradient"></i>
                    </div>
                    <div>
                        <h4 class="student-name m-0">{{ $submission->last_name }}, {{ $submission->first_name }}</h4>
                        <span class="student-id-tag">{{ $submission->student_id }}</span>
                    </div>
                </div>

                <hr class="section-divider">

                <div class="task-info-section mb-4">
                    <span class="info-label">ASSIGNED TASK</span>
                    <h5 class="task-title text-dark-blue">{{ $submission->activity_name }}</h5>
                    <p class="text-muted small m-0">
                        <i class="fa-regular fa-clock"></i> Submitted on: 
                        <strong>{{ \Carbon\Carbon::parse($submission->submitted_at)->format('M d, Y @ h:i A') }}</strong>
                    </p>
                </div>

                <div class="file-attachment-box">
                    <div class="file-icon-side">
                        <i class="fa-solid fa-file-pdf"></i>
                    </div>
                    <div class="file-details-side">
                        <span class="file-name-label">{{ $submission->original_filename }}</span>
                        <span class="file-size-label">Student Attachment</span>
                    </div>
                    <div class="file-action-side">
                        @if($submission && $submission->file_path)
                            <a href="{{ route('professor.submission.download', $submission->id) }}" class="btn btn-sm btn-success">
                                <i class="fa-solid fa-file-arrow-down mr-1"></i> Download File
                            </a>
                        @else
                            <span class="text-muted" style="font-size: 13px;">No Submission Yet</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="grading-input-panel">
            <div class="workspace-card assessment-card">
                <div class="panel-header mb-4">
                    <h5 class="m-0 fw-bold text-dark-blue"><i class="fa-solid fa-star text-warning"></i> Assessment</h5>
                    <p class="small text-muted m-0">Input score</p>
                </div>

                <form action="{{ route('professor.submit-grade', $submission->id) }}" method="POST">
                    @csrf
                    
                    <div class="mb-4">
                        <label for="grade" class="form-label-custom">SCORE / GRADE</label>
                        <div class="custom-score-group">
                            <input type="number" name="grade" id="grade" class="form-control-score" 
                                   value="{{ $submission->grade }}" step="0.1" min="0" max="{{ $submission->max_score }}" required placeholder="0">
                            <span class="score-max-label">/ {{ $submission->max_score }} PTS</span>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit-assessment">
                        <i class="fa-solid fa-floppy-disk"></i> Save & Release Grade
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>

<style>
    .text-dark-blue { color: #1e2a5a; }
    .btn-back-link { text-decoration: none; color: #475569; font-weight: 600; font-size: 13.5px; transition: 0.2s; }
    .btn-back-link:hover { color: #1e2a5a; }
    .badge-portal { background: #f1f5f9; color: #475569; font-weight: 700; font-size: 11px; padding: 6px 14px; border-radius: 30px; text-transform: uppercase; border: 1px solid #cbd5e1; }
    
    .alert-banner { padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; font-weight: 600; }
    .alert-success { background: #f0fdf4; border-left: 4px solid #22c55e; color: #166534; }

    /* Layout Grid */
    .grading-grid-wrapper { display: flex; gap: 24px; align-items: flex-start; width: 100%; }
    .submission-viewer-panel { flex-grow: 1; min-width: 0; }
    .grading-input-panel { width: 360px; flex-shrink: 0; }

    /* Cards */
    .workspace-card { background: white; border-radius: 12px; border: 1px solid #e2e8f0; padding: 24px; box-shadow: 0 2px 4px rgba(0,0,0,0.01); }
    .assessment-card { border-top: 5px solid #1e2a5a; }

    /* Student Info Header */
    .student-meta-box { display: flex; align-items: center; gap: 14px; }
    .avatar-placeholder { width: 44px; height: 44px; background: #eef1fb; color: #1e2a5a; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-size: 18px; }
    .student-name { font-size: 18px; font-weight: 800; color: #1e293b; }
    .student-id-tag { font-size: 12px; font-weight: 700; color: #64748b; background: #f1f5f9; padding: 2px 8px; border-radius: 4px; }
    .section-divider { border-color: #e2e8f0; margin: 18px 0; }

    /* Task details */
    .info-label { font-size: 10px; font-weight: 800; color: #94a3b8; letter-spacing: 0.5px; display: block; margin-bottom: 4px; }
    .task-title { font-size: 16px; font-weight: 700; margin-bottom: 4px; }

    /* File Download Box */
    .file-attachment-box { display: flex; align-items: center; gap: 14px; border: 1px solid #cbd5e1; background: #f8fafc; border-radius: 10px; padding: 16px; margin-top: 15px; }
    .file-icon-side { font-size: 28px; color: #ef4444; }
    .file-details-side { display: flex; flex-direction: column; flex-grow: 1; min-width: 0; }
    .file-name-label { font-size: 14px; font-weight: 700; color: #1e293b; word-break: break-all; }
    .file-size-label { font-size: 11px; color: #94a3b8; font-weight: 600; }
    .btn-download-file { background: white; border: 1px solid #1e2a5a; color: #1e2a5a; font-size: 12px; font-weight: 700; padding: 8px 14px; border-radius: 6px; cursor: pointer; text-decoration: none; transition: 0.2s; white-space: nowrap; }
    .btn-download-file:hover { background: #1e2a5a; color: white; }

    /* Form Styles */
    .form-label-custom { font-size: 11px; font-weight: 800; color: #64748b; letter-spacing: 0.5px; display: block; margin-bottom: 6px; }
    .custom-score-group { display: flex; align-items: center; border: 1px solid #cbd5e1; border-radius: 8px; overflow: hidden; background: #f8fafc; }
    .form-control-score { flex-grow: 1; border: none; background: transparent; padding: 12px; font-size: 16px; font-weight: 700; color: #1e293b; text-align: center; width: 100%; outline: none; }
    .score-max-label { background: #e2e8f0; color: #475569; font-weight: 700; font-size: 13px; padding: 14px 16px; border-left: 1px solid #cbd5e1; }

    .btn-submit-assessment { width: 100%; background: #1e2a5a; border: none; color: white; font-weight: 700; font-size: 14px; padding: 12px; border-radius: 8px; cursor: pointer; transition: 0.2s; }
    .btn-submit-assessment:hover { background: #131b3b; }
</style>
@endsection