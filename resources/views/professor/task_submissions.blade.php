@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="page-header-row mb-4">
        <a href="javascript:history.back()" class="btn-back-link">
            <i class="fa-solid fa-arrow-left"></i> Back to Term Panel
        </a>
        <span class="badge-portal">Submissions List</span>
    </div>

    <div class="workspace-card mb-4">
        <span class="info-label">ACTIVITY DETAILS</span>
        <h4 class="text-dark-blue fw-bold m-0">{{ $task->activity_name }}</h4>
        <p class="text-muted small m-0 mt-1">
            Type: <strong>{{ strtoupper($task->activity_type) }}</strong> | 
            Max Score: <strong>{{ $task->max_score }} PTS</strong>
        </p>
    </div>

    <div class="workspace-card p-0 overflow-hidden">
        <div class="p-3 bg-light border-bottom">
            <h5 class="m-0 text-dark-blue fw-bold"><i class="fa-solid fa-users"></i> Student Submissions</h5>
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover align-middle m-0" style="font-size: 14px;">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Student ID</th>
                        <th>Student Name</th>
                        <th>Status</th>
                        <th>File Submitted</th>
                        <th>Grade Status</th>
                        <th class="text-end pe-4">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($submissions as $row)
                        <tr>
                            <td class="ps-4 fw-bold text-secondary">{{ $row->student_id }}</td>
                            <td class="fw-bold text-dark">{{ $row->last_name }}, {{ $row->first_name }}</td>
                            <td>
                                @if($row->submission_id)
                                    <span class="badge bg-success text-white">Submitted</span>
                                @else
                                    <span class="badge bg-danger text-white">No Submission</span>
                                @endif
                            </td>
                            <td>
                                @if($row->original_filename)
                                    <small class="text-muted"><i class="fa-solid fa-file-pdf text-danger"></i> {{ $row->original_filename }}</small>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($row->submission_id)
                                    <span class="text-warning">For Evaluation</span>
                                @else
                                    <span class="text-muted">No Grade</span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                @if($row->submission_id)
                                    <a href="{{ route('professor.task_grading', $row->submission_id) }}" class="btn-download-file pt-2 pb-2">
                                        <i class="fa-solid fa-pen-to-square"></i> Evaluate
                                    </a>
                                @else
                                    <button class="btn btn-sm btn-light border" disabled>No submission</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No students enrolled in this section.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection