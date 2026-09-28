@extends('layouts.app')

@section('content')
<div class="submissions-master-container" style="max-width: 1300px; margin: 0 auto; padding: 24px; font-family: 'Inter', sans-serif;">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <a href="{{ route('professor.class_details', ['schedule_id' => $task->class_schedule_id, 'tab' => $task->term]) }}" class="btn-back-custom">
            &leftarrow; Back to Term Panel
        </a>
    </div>

    <div style="background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 24px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
        <div>
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                <span style="background-color: #0033A0; color: #ffffff; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                    {{ \Illuminate\Support\Str::endsWith(strtolower($task->term), 'term') ? strtoupper($task->term) : strtoupper($task->term) . ' TERM' }}
                </span>
                <span style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 1px;">
                    Active Assignment Registry
                </span>
            </div>

            <h3 style="margin: 0; font-size: 24px; font-weight: 700; color: #0f172a; letter-spacing: -0.5px;">{{ $task->activity_name }}</h3>
            
            <div style="display: flex; align-items: center; gap: 12px; margin-top: 8px;">
                <span style="background: #eff6ff; color: #1e40af; font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 6px; text-transform: uppercase; border: 1px solid #bfdbfe;">
                    {{ $task->activity_type }}
                </span>
                <span style="color: #64748b; font-size: 14px;">
                    Target Weight: <strong style="color: #0f172a;">{{ $task->max_score }} PTS</strong>
                </span>
            </div>
        </div>

        <div style="display: flex; gap: 16px;">
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 20px; text-align: center; min-width: 110px;">
                <div style="font-size: 20px; font-weight: 700; color: #0f172a;">{{ $submissions->whereNotNull('original_filename')->count() }}</div>
                <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-top: 2px;">Turned In</div>
            </div>
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 20px; text-align: center; min-width: 110px;">
                <div style="font-size: 20px; font-weight: 700; color: #64748b;">{{ $submissions->whereNull('original_filename')->count() }}</div>
                <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-top: 2px;">Missing</div>
            </div>
        </div>
    </div>

    <div style="background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02); overflow: hidden;">
        <div style="background-color: #ffffff; padding: 20px 24px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-users" style="color: #0033A0; font-size: 16px;"></i>
            <h4 style="margin: 0; font-size: 16px; font-weight: 700; color: #0033A0;">Class Evaluation Directory</h4>
        </div>
        
        <div class="table-responsive">
            <table class="table align-middle m-0" style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr style="background-color: #f8fafc;">
                        <th style="padding: 16px 24px; color: #475569; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #e2e8f0; width: 15%;">Student ID</th>
                        <th style="padding: 16px 24px; color: #475569; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #e2e8f0; width: 30%;">Student Name</th>
                        <th style="padding: 16px 24px; color: #475569; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #e2e8f0; width: 15%;">Status</th>
                        <th style="padding: 16px 24px; color: #475569; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #e2e8f0; width: 25%;">File Submitted</th>
                        <th style="padding: 16px 24px; color: #475569; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #e2e8f0; width: 15%;">Grade Status</th>
                        <th style="padding: 16px 24px; color: #475569; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #e2e8f0; text-align: center; width: 20%;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($submissions as $row)
                        <tr style="transition: background-color 0.15s;" onmouseover="this.style.backgroundColor='#f8fafc'" onmouseout="this.style.backgroundColor='transparent'">
                            <td style="padding: 16px 24px; border-bottom: 1px solid #f1f5f9;">
                                <span style="font-family: monospace; background-color: #0f172a; color: #f8fafc; padding: 4px 10px; border-radius: 5px; font-weight: 700; font-size: 13px;">
                                    {{ $row->student_id }}
                                </span>
                            </td>
                            <td style="padding: 16px 24px; border-bottom: 1px solid #f1f5f9; font-weight: 600; color: #0f172a; text-transform: uppercase; font-size: 14px;">
                                {{ $row->last_name }}, {{ $row->first_name }}
                            </td>
                            <td style="padding: 16px 24px; border-bottom: 1px solid #f1f5f9;">
                                @if(isset($row->grade) && $row->grade !== null)
                                    <span style="display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; text-transform: uppercase; background-color: #eef1fb; color: #1e2a5a;">
                                        Graded
                                    </span>
                                @elseif($row->submission_id && ($row->original_filename || $row->file_path))
                                    <span style="display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; text-transform: uppercase; background-color: #dcfce7; color: #15803d;">
                                        Submitted
                                    </span>
                                @else
                                    <span style="display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; text-transform: uppercase; background-color: #fef2f2; color: #b91c1c;">
                                        Missing
                                    </span>
                                @endif
                            </td>
                            <td style="padding: 16px 24px; border-bottom: 1px solid #f1f5f9;">
                                @if($row->original_filename)
                                    <div style="display: flex; align-items: center; gap: 6px; max-width: 220px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $row->original_filename }}">
                                        <i class="fa-solid fa-file-pdf" style="color: #ef4444; font-size: 14px; flex-shrink: 0;"></i> 
                                        <span style="font-size: 13px; color: #475569; font-weight: 500;">{{ $row->original_filename }}</span>
                                    </div>
                                @else
                                    <span style="color: #94a3b8; font-size: 14px;">—</span>
                                @endif
                            </td>
                            <td style="padding: 16px 24px; border-bottom: 1px solid #f1f5f9;">
                                @if(isset($row->grade) && $row->grade !== null)
                                    <span style="font-weight: 700; color: #16a34a; font-size: 14px;">
                                        {{ $row->grade }} / {{ $task->max_score }} PTS
                                    </span>
                                @elseif($row->submission_id && ($row->original_filename || $row->file_path))
                                    <span style="font-weight: 600; color: #ea580c; font-size: 13px; display: inline-flex; align-items: center; gap: 4px;">
                                        <span style="width: 6px; height: 6px; background-color: #ea580c; border-radius: 50%;"></span> For Checking
                                    </span>
                                @else
                                    <span style="color: #94a3b8; font-size: 13px;">No Grade</span>
                                @endif
                            </td>
                            <td style="padding: 16px 24px; border-bottom: 1px solid #f1f5f9; text-align: center;">
                                @if($row->submission_id)
                                    <a href="{{ route('professor.task_grading', $row->submission_id) }}" style="display: inline-block; background-color: transparent; color: #0033A0; border: 1px solid #0033A0; padding: 6px 14px; border-radius: 6px; font-size: 12.5px; font-weight: 600; text-decoration: none; transition: all 0.15s ease;" onmouseover="this.style.backgroundColor='#0033A0'; this.style.color='#fff';" onmouseout="this.style.backgroundColor='transparent'; this.style.color='#0033A0';">
                                        <i class="fa-solid fa-pen-to-square"></i> Evaluate
                                    </a>
                                @else
                                    <a href="{{ route('professor.evaluate_student', ['task_id' => $task->id, 'student_id' => $row->student_id]) }}" style="display: inline-block; background-color: transparent; color: #0033A0; border: 1px solid #0033A0; padding: 6px 14px; border-radius: 6px; font-size: 12.5px; font-weight: 600; text-decoration: none; transition: all 0.15s ease;" onmouseover="this.style.backgroundColor='#0033A0'; this.style.color='#fff';" onmouseout="this.style.backgroundColor='transparent'; this.style.color='#0033A0';">
                                        <i class="fa-solid fa-pen-to-square"></i> Evaluate
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 48px 0; color: #64748b; font-size: 14px; background-color: #fafafa;">
                                <i class="fa-solid fa-folder-open" style="font-size: 24px; color: #cbd5e1; margin-bottom: 8px; display: block;"></i>
                                No student records or submission models assigned to this activity folder.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .btn-back-custom {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #334155;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        padding: 7px 16px;
        background-color: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.04);
        transition: all 0.15s ease;
    }

    .btn-back-custom:hover {
        background-color: #f8fafc;
        color: #0f172a;
        border-color: #94a3b8;
    }
</style>
@endsection