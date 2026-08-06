@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="grading-container py-4">
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
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <div class="student-name-info">
                        <h4 class="student-name m-0">{{ $submission->last_name }}, {{ $submission->first_name }}</h4>
                        <span class="student-id-tag">{{ $submission->student_id }}</span>
                    </div>
                </div>

                <hr class="section-divider">

                <div class="task-info-section mb-4">
                    <span class="info-label">ASSIGNED TASK</span>
                    <h5 class="task-title text-dark-blue">{{ $submission->activity_name }}</h5>
                    <div class="task-meta-row">
                        <span class="task-meta-chip">
                            <i class="fa-regular fa-clock"></i>
                            Submitted {{ $submission->submitted_at ? \Carbon\Carbon::parse($submission->submitted_at)->format('M d, Y \a\t h:i A') : 'N/A' }}
                        </span>
                        @if(!is_null($submission->grade))
                            <span class="task-meta-chip task-meta-chip-graded">
                                <i class="fa-solid fa-circle-check"></i> Already graded
                            </span>
                        @else
                            <span class="task-meta-chip task-meta-chip-pending">
                                <i class="fa-solid fa-hourglass-half"></i> Awaiting grade
                            </span>
                        @endif
                    </div>
                </div>

                <div class="file-attachment-box">
                    <div class="file-icon-side">
                        <i class="fa-solid fa-file-lines"></i>
                    </div>
                    <div class="file-details-side">
                        <span class="file-name-label">{{ $submission->original_filename ?? 'No file name' }}</span>
                        <span class="file-size-label">Student Attachment</span>
                    </div>
                    <div class="file-action-side">
                        @if($submission && $submission->file_path)
                            <a href="{{ route('professor.submission.download', $submission->id) }}" class="btn-download-file">
                                <i class="fa-solid fa-file-arrow-down"></i> Download File
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
                    <p class="small text-muted m-0">Input the score for this student</p>
                </div>

                <form action="{{ route('professor.submit-grade', $submission->id) }}" method="POST">
                    @csrf

                    <div class="score-field-group">
                        <label for="grade" class="form-label-custom">SCORE / GRADE</label>
                        <div class="custom-score-group">
                            <input type="number" name="grade" id="grade" class="form-control-score"
                                   value="{{ $submission->grade }}" step="0.1" min="0" max="{{ $submission->max_score }}" required placeholder="0"
                                   oninput="clampGradeInput(this)">
                            <span class="score-max-label">/ {{ $submission->max_score }} PTS</span>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit-assessment">
                        <i class="fa-solid fa-floppy-disk"></i> Save Grade
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>

<script>
    function clampGradeInput(input) {
        const max = parseFloat(input.max);
        const min = parseFloat(input.min);

        if (input.value === '') {
            return;
        }

        let value = parseFloat(input.value);

        if (isNaN(value)) {
            return;
        }

        if (!isNaN(max) && value > max) {
            input.value = max;
        } else if (!isNaN(min) && value < min) {
            input.value = min;
        }
    }
</script>

<style>
    .text-dark-blue { color: #1e2a5a; }

    .grading-container {
        max-width: 980px;
        margin: 0 auto;
        padding-left: 1.5rem;
        padding-right: 1.5rem;
    }

    .page-header-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .btn-back-link { text-decoration: none; color: #475569; font-weight: 600; font-size: 14px; transition: 0.2s; }
    .btn-back-link:hover { color: #1e2a5a; }
    .badge-portal { background: #f1f5f9; color: #475569; font-weight: 700; font-size: 11px; padding: 6px 14px; border-radius: 30px; text-transform: uppercase; border: 1px solid #cbd5e1; }

    .alert-banner { padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; font-weight: 600; }
    .alert-success { background: #f0fdf4; border-left: 4px solid #22c55e; color: #166534; }

    .grading-grid-wrapper {
        display: grid;
        grid-template-columns: 1fr 340px;
        gap: 24px;
        align-items: start;
    }

    @media (max-width: 860px) {
        .grading-grid-wrapper { grid-template-columns: 1fr; }
    }

    .submission-viewer-panel { min-width: 0; }
    .grading-input-panel { width: 100%; position: sticky; top: 20px; }

    .workspace-card {
        background: white;
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        padding: 26px;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02), 0 2px 4px -1px rgba(0,0,0,0.01);
    }
    .assessment-card { border-top: 6px solid #1e2a5a; }

    .student-meta-box {
        display: flex; align-items: center; gap: 16px;
        background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px 18px;
    }
    .avatar-placeholder { width: 52px; height: 52px; background: #eef1fb; color: #1e2a5a; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-size: 20px; flex-shrink: 0; }
    .student-name-info { min-width: 0; }
    .student-name { font-size: 19px; font-weight: 800; color: #1e293b; }
    .student-id-tag { font-size: 12px; font-weight: 700; color: #64748b; background: white; border: 1px solid #e2e8f0; padding: 3px 10px; border-radius: 6px; display: inline-block; margin-top: 6px; }
    .section-divider { border-color: #e2e8f0; margin: 20px 0; }

    .info-label { font-size: 11px; font-weight: 800; color: #94a3b8; letter-spacing: 0.8px; display: block; margin-bottom: 6px; }
    .task-title { font-size: 17px; font-weight: 700; margin-bottom: 10px; line-height: 1.4; }

    .task-meta-row { display: flex; flex-wrap: wrap; gap: 8px; }
    .task-meta-chip {
        display: inline-flex; align-items: center; gap: 6px; font-size: 11.5px; font-weight: 600;
        background: #f1f5f9; color: #475569; padding: 5px 11px; border-radius: 20px;
    }
    .task-meta-chip-graded { background: #f0fdf4; color: #15803d; }
    .task-meta-chip-pending { background: #fffbeb; color: #b45309; }

    .file-attachment-box { display: flex; align-items: center; gap: 16px; border: 1px solid #cbd5e1; background: #f8fafc; border-radius: 12px; padding: 18px; margin-top: 20px; }
    .file-icon-side { font-size: 28px; color: #3b82f6; flex-shrink: 0; }
    .file-details-side { display: flex; flex-direction: column; flex-grow: 1; min-width: 0; }
    .file-name-label { font-size: 14.5px; font-weight: 700; color: #1e293b; word-break: break-all; margin-bottom: 2px; }
    .file-size-label { font-size: 12px; color: #64748b; font-weight: 600; }

    .btn-download-file {
        background: #16a34a; color: white; text-decoration: none; font-weight: 600; font-size: 13px;
        padding: 9px 16px; border-radius: 8px; display: inline-flex; align-items: center; gap: 7px; transition: 0.15s; white-space: nowrap;
    }
    .btn-download-file:hover { background: #15803d; color: white; }

    .form-label-custom { font-size: 12px; font-weight: 800; color: #475569; letter-spacing: 0.5px; display: block; margin-bottom: 8px; }

    .score-field-group { margin-bottom: 28px; }

    .custom-score-group {
        display: flex; align-items: stretch; border: 2px solid #cbd5e1; border-radius: 12px; overflow: hidden;
        background: #f8fafc; transition: all 0.2s;
    }
    .custom-score-group:focus-within { border-color: #1e2a5a; box-shadow: 0 0 0 4px rgba(30, 42, 90, 0.1); background: white; }
    .form-control-score { flex: 1 1 auto; min-width: 0; border: none; background: transparent; padding: 20px 14px; font-size: 30px; font-weight: 800; color: #1e2a5a; text-align: center; outline: none; -moz-appearance: textfield; }
    .form-control-score::-webkit-outer-spin-button,
    .form-control-score::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    .score-max-label { flex-shrink: 0; background: #e2e8f0; color: #475569; font-weight: 700; font-size: 13px; padding: 14px 16px; border-left: 2px solid #cbd5e1; display: flex; align-items: center; white-space: nowrap; }

    .btn-submit-assessment { width: 100%; background: #1e2a5a; border: none; color: white; font-weight: 700; font-size: 14.5px; padding: 13px; border-radius: 10px; cursor: pointer; transition: 0.2s; display: flex; align-items: center; justify-content: center; gap: 8px; box-shadow: 0 4px 6px rgba(30, 42, 90, 0.15); }
    .btn-submit-assessment:hover { background: #131b3b; transform: translateY(-1px); }
</style>
@endsection