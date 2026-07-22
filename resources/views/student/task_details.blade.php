@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="dashboard-wrapper py-4 px-3">
    <div class="mb-4">
        <a href="{{ route('student.class_details', $task->class_schedule_id) }}?tab={{ strtolower($task->term) }}" class="btn-back">
            <i class="fa-solid fa-arrow-left"></i> Back to Class Tasks
        </a>
    </div>

    @if(session('success'))
        <div class="alert-banner alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert-banner alert-danger"><i class="fa-solid fa-circle-exclamation"></i> {{ session('error') }}</div>
    @endif

    @if($errors->any())
        <div class="alert-banner alert-danger">
            <i class="fa-solid fa-circle-exclamation"></i>
            <ul style="margin: 0; padding-left: 15px; text-align: left;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="class-banner mb-4">
        <div class="banner-top-row">
            <div class="subject-tag-line">
                <span class="code-badge">{{ strtoupper($task->subject_code) }}</span>
                <span class="bullet-sep">•</span>
                <span class="section-badge">{{ strtoupper($task->section_name) }}</span>
            </div>
            <span class="term-tag">{{ strtoupper($task->term) }} TERM</span>
        </div>
        <h1 class="class-title">{{ $task->activity_name }}</h1>
        <div class="banner-meta-row">
            <span class="meta-pill">
                <i class="fa-solid fa-tags"></i> {{ strtoupper($task->activity_type) }}
            </span>
            @if($task->max_score)
                <span class="meta-pill">
                    <i class="fa-solid fa-star"></i> {{ $task->max_score }} points
                </span>
            @endif

            @if($submission && !is_null($submission->grade))
                <span class="meta-pill" style="background: #f0fdf4; border-color: #bbf7d0; color: #166534;">
                    <i class="fa-solid fa-award" style="color: #22c55e;"></i> 
                    <strong>Your Score:</strong> {{ number_format($submission->grade, 2) }} / {{ $task->max_score }}
                </span>
            @endif
            
            @php
                $isOverdue = $task->due_date && \Carbon\Carbon::parse($task->due_date)->endOfDay()->isPast() && !$submission;
                $isPastDeadline = $task->due_date && \Carbon\Carbon::parse($task->due_date)->endOfDay()->isPast();
            @endphp
            <span class="meta-pill {{ $isOverdue ? 'meta-pill-overdue' : '' }}">
                <i class="fa-regular fa-calendar-check"></i>
                {{ $task->due_date ? 'Due ' . \Carbon\Carbon::parse($task->due_date)->format('M d, Y') : 'No Deadline' }}
            </span>
        </div>
    </div>

    <div class="content-panel">

        @if($task->file_path)
            <div class="task-attachment-box">
                <div class="attachment-icon"><i class="fa-solid fa-paperclip"></i></div>
                <div class="attachment-info">
                    <span class="attachment-label">Task Reference File</span>
                    <span class="attachment-name">{{ $task->original_filename ?? 'Attached File' }}</span>
                </div>
                @if($task->file_path)
                    <a href="{{ route('student.task.download', $task->id) }}" class="btn btn-primary">
                        <i class="fa-solid fa-download mr-2"></i> Download Attachment
                    </a>
                @else
                    <span class="text-gray-400 text-sm">No attachment provided for this task.</span>
                @endif
            </div>
        @endif

        <div class="submission-header">
            <h3 class="section-heading">Your Submission</h3>
            @if($submission)
                <span class="submission-status-badge">
                    <i class="fa-solid fa-circle-check"></i> Submitted
                </span>
            @else
                <span class="submission-status-badge submission-status-pending">
                    <i class="fa-regular fa-circle"></i> Not Submitted
                </span>
            @endif
        </div>

        @if($submission)
            <div class="submitted-box">
                <div class="submitted-icon"><i class="fa-solid fa-file-circle-check"></i></div>
                <div class="submitted-info">
                    <span class="submitted-name">{{ $submission->original_filename }}</span>
                    <span class="submitted-label">Submitted {{ \Carbon\Carbon::parse($submission->submitted_at)->format('M d, Y - h:i A') }}</span>
                </div>
            </div>
            @if(!$isPastDeadline)
                <p class="resubmit-note"><i class="fa-solid fa-circle-info"></i> Uploading a new file below will replace your current submission.</p>
            @endif
        @endif

        {{-- BACKEND/FRONTEND PROTECTION: LOCK IF PAST DEADLINE --}}
        @if($isPastDeadline)
            <div class="alert-banner alert-danger" style="display: flex; flex-direction: column; align-items: center; text-align: center; padding: 24px; gap: 8px;">
                <i class="fa-solid fa-lock" style="font-size: 26px; color: #ef4444; margin-bottom: 4px;"></i>
                <div style="font-size: 16px; font-weight: 700;">Submission Period Has Closed</div>
                <p style="margin: 0; font-weight: 500; opacity: 0.9; font-size: 13.5px;">
                    The deadline for this activity was on {{ \Carbon\Carbon::parse($task->due_date)->format('M d, Y') }}. Submissions or file revisions are no longer accepted.
                </p>
            </div>
        @else
            <form action="{{ route('student.submit_task', ['id' => $task->id]) }}" method="POST" enctype="multipart/form-data" onsubmit="handleFormSubmit(this);">
                @csrf

                <div class="dropzone" id="dropzone" onclick="document.getElementById('fileInput').click()">
                    <div class="dz-icon-circle"><i class="fa-solid fa-cloud-arrow-up"></i></div>
                    <p class="dz-title">Drag & drop file here</p>
                    <p class="dz-subtitle">or click to browse — PDF, DOC, PPT, ZIP, JPG, PNG (max 10MB)</p>
                </div>

                <div class="dz-file-preview" id="filePreview" style="display:none;">
                    <i class="fa-solid fa-file-circle-check"></i>
                    <div class="dz-file-info">
                        <span class="dz-file-name" id="fileNameDisplay"></span>
                        <span class="dz-file-size" id="fileSizeDisplay"></span>
                    </div>
                    <button type="button" class="dz-file-remove" id="removeFileBtn">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <input type="file" name="attachment" id="fileInput" class="visually-hidden-input"
                    accept=".pdf,.doc,.docx,.ppt,.pptx,.zip,.jpg,.jpeg,.png" required>

                <div class="submit-btn-row">
                    <button type="submit" id="submitTaskBtn" class="btn-submit-task">
                        <i class="fa-solid fa-paper-plane"></i> 
                        <span id="submitBtnText">{{ $submission ? 'Resubmit Task' : 'Submit Task' }}</span>
                    </button>
                </div>
            </form>
        @endif

    </div>
</div>

<script>
    const dropzone = document.getElementById('dropzone');
    const fileInput = document.getElementById('fileInput');
    const filePreview = document.getElementById('filePreview');
    const fileNameDisplay = document.getElementById('fileNameDisplay');
    const fileSizeDisplay = document.getElementById('fileSizeDisplay');
    const removeFileBtn = document.getElementById('removeFileBtn');

    function showFile(file) {
        if (fileNameDisplay && fileSizeDisplay && dropzone && filePreview) {
            fileNameDisplay.textContent = file.name;
            fileSizeDisplay.textContent = (file.size / 1024).toFixed(0) + ' KB';
            dropzone.style.display = 'none';
            filePreview.style.display = 'flex';
        }
    }

    if (fileInput) {
        fileInput.addEventListener('change', () => {
            if (fileInput.files[0]) showFile(fileInput.files[0]);
        });
    }

    if (dropzone) {
        dropzone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropzone.classList.add('dropzone-active');
        });
        dropzone.addEventListener('dragleave', () => dropzone.classList.remove('dropzone-active'));
        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropzone.classList.remove('dropzone-active');
            if (e.dataTransfer.files[0] && fileInput) {
                fileInput.files = e.dataTransfer.files;
                showFile(e.dataTransfer.files[0]);
            }
        });
    }

    if (removeFileBtn) {
        removeFileBtn.addEventListener('click', () => {
            if (fileInput && dropzone && filePreview) {
                fileInput.value = '';
                dropzone.style.display = 'block';
                filePreview.style.display = 'none';
            }
        });
    }

    function handleFormSubmit(form) {
        const btn = document.getElementById('submitTaskBtn');
        const btnText = document.getElementById('submitBtnText');
        
        if (btn && btnText) {
            btn.disabled = true;
            btn.style.opacity = '0.7';
            btn.style.cursor = 'not-allowed';
            
            if (btnText.innerText.trim() === 'Resubmit Task') {
                btnText.innerText = 'Resubmitting, please wait...';
            } else {
                btnText.innerText = 'Submitting, please wait...';
            }
        }
    }
</script>

<style>
    .dashboard-wrapper { max-width: 900px; margin: 0 auto; font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; }

    .btn-back { display: inline-flex; align-items: center; gap: 8px; color: #1e2a5a; font-weight: 600; text-decoration: none; font-size: 14px; transition: 0.2s; }
    .btn-back:hover { color: #0284c7; }

    .class-banner {
        background: white; border: 1px solid #e2e8f0; border-top: 5px solid #1e2a5a;
        border-radius: 14px; padding: 26px 30px; box-shadow: 0 2px 6px rgba(0,0,0,0.03);
        margin-bottom: 15px;
    }

    .banner-top-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }

    .subject-tag-line { display: flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 800; }
    .code-badge { color: #0284c7; }
    .bullet-sep { color: #94a3b8; }
    .section-badge { color: #1e2a5a; }

    .term-tag {
        background: #f1f5f9; color: #475569; padding: 5px 14px;
        border-radius: 50px; font-size: 10.5px; font-weight: 700; letter-spacing: 0.5px;
        border: 1px solid #cbd5e1;
    }

    .class-title { font-size: 24px; font-weight: 800; color: #1e293b; margin-bottom: 16px; }

    .banner-meta-row { display: flex; flex-wrap: wrap; gap: 10px; }
    .meta-pill {
        display: inline-flex; align-items: center; gap: 7px;
        background: #f8fafc; border: 1px solid #e2e8f0;
        color: #475569; padding: 7px 14px; border-radius: 8px; font-size: 12.5px; font-weight: 600;
    }
    .meta-pill i { color: #1e2a5a; }
    .meta-pill-overdue { background: #fef2f2; border-color: #fecaca; color: #b91c1c; }
    .meta-pill-overdue i { color: #dc2626; }

    .content-panel { background: white; border: 1px solid #e2e8f0; border-radius: 14px; padding: 32px; box-shadow: 0 2px 8px rgba(0,0,0,0.03); }

    .submission-header { 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        border-bottom: 2px solid #f1f5f9; 
        padding-bottom: 10px; 
        margin-bottom: 12px; 
        margin-top: 6px; 
    }
    .section-heading { font-size: 18px; color: #1e293b; font-weight: 700; margin: 0; }

    .submission-status-badge {
        display: inline-flex; align-items: center; gap: 6px;
        background: #dcfce7; color: #15803d; font-size: 12px; font-weight: 700;
        padding: 5px 12px; border-radius: 20px;
    }
    .submission-status-pending { background: #fef3c7; color: #b45309; }

    .alert-banner { display: flex; align-items: center; gap: 10px; padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; font-weight: 600; }
    .alert-success { background: #f0fdf4; border-left: 4px solid #22c55e; color: #166534; }
    .alert-danger { background: #fef2f2; border-left: 4px solid #ef4444; color: #991b1b; }

    .task-attachment-box, .submitted-box {
        display: flex; align-items: center; gap: 16px;
        border: 1px solid #e2e8f0; background: #f8fafc; border-radius: 12px; padding: 18px 20px;
        margin-bottom: 10px;
    }
    .attachment-icon, .submitted-icon {
        width: 44px; height: 44px; flex-shrink: 0; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 18px; background: #eef1fb; color: #1e2a5a;
    }
    .submitted-icon { background: #dcfce7; color: #15803d; }
    .attachment-info, .submitted-info { display: flex; flex-direction: column; flex-grow: 1; min-width: 0; }
    .attachment-label { font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.4px; }
    .attachment-name, .submitted-name { font-size: 14.5px; font-weight: 700; color: #1e293b; }
    .submitted-label { font-size: 12.5px; color: #64748b; margin-top: 2px; }
    .btn-download-attachment {
        background: #1e2a5a; color: white; font-size: 13px; font-weight: 600;
        padding: 9px 18px; border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center; gap: 7px;
        transition: 0.2s; flex-shrink: 0;
    }
    .btn-download-attachment:hover { background: #141d40; color: white; }
    
    .resubmit-note {
        display: flex; align-items: center; gap: 8px;
        font-size: 13px; color: #64748b; background: #f8fafc; border-radius: 8px;
        padding: 10px 14px; margin-bottom: 10px;
    }

    .dropzone {
        border: 2px dashed #cbd5e1; background: #fafbfc; border-radius: 12px;
        padding: 20px 16px; text-align: center; cursor: pointer; transition: 0.2s;
    }
    .dropzone:hover { border-color: #94a3b8; background: #f1f5f9; }
    .dropzone-active { border-color: #1e2a5a; background: #eef1fb; }
    .dz-icon-circle {
        width: 40px; height: 40px; border-radius: 50%; background: #eef1fb;
        display: flex; align-items: center; justify-content: center; margin: 0 auto 8px;
    }
    .dz-icon-circle i { font-size: 16px; color: #1e2a5a; }
    .dz-title { font-size: 14.5px; font-weight: 700; color: #334155; margin: 0; }
    .dz-subtitle { font-size: 12.5px; color: #94a3b8; margin: 5px 0 0; }

    .dz-file-preview {
        display: flex; align-items: center; gap: 14px;
        border: 1px solid #bbf7d0; background: #f0fdf4; border-radius: 12px; padding: 16px 18px;
    }
    .dz-file-preview i { font-size: 22px; color: #15803d; }
    .dz-file-info { display: flex; flex-direction: column; flex-grow: 1; min-width: 0; }
    .dz-file-name { font-size: 14px; font-weight: 700; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .dz-file-size { font-size: 12px; color: #64748b; }
    .dz-file-remove { background: white; border: 1px solid #e2e8f0; color: #94a3b8; cursor: pointer; padding: 6px 8px; border-radius: 6px; font-size: 14px; transition: 0.2s; }
    .dz-file-remove:hover { color: #dc2626; border-color: #fecaca; background: #fef2f2; }

    .visually-hidden-input { position: absolute; width: 1px; height: 1px; overflow: hidden; opacity: 0; pointer-events: none; }

    .submit-btn-row { margin-top: 22px; text-align: right; }
    .btn-submit-task {
        background: #1e2a5a; color: white; border: none; padding: 12px 26px;
        border-radius: 8px; font-weight: 700; font-size: 14px; cursor: pointer;
        display: inline-flex; align-items: center; gap: 9px; transition: 0.2s;
        box-shadow: 0 4px 10px rgba(30, 42, 90, 0.2);
    }
    .btn-submit-task:hover { background: #141d40; transform: translateY(-1px); box-shadow: 0 6px 14px rgba(30, 42, 90, 0.25); }
</style>
@endsection