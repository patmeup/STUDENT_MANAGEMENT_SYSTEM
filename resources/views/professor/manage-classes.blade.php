@extends('layouts.app')

@section('content')
<div class="prof-container">
    
    <div class="prof-header">
        <div class="header-titles">
            <h2>Academic Class Management</h2>
            <p>Faculty Load — Current Semester Active Loading & Roster Controls</p>
        </div>
        <div class="header-badge prof-level">Professor Portal</div>
    </div>

    @if(session('error'))
        <div class="prof-alert-danger">
            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
        </div>
    @endif

    <div class="prof-workspace-full">
        <div class="prof-card table-panel-full">
            <div class="card-header-flex">
                <h3 class="card-title-list">My Assigned Teaching Load</h3>
                <span class="badge-count">{{ count($classes) }} Active Subjects</span>
            </div>

            <div class="table-responsive">
                <table class="minimal-table">
                    <thead>
                        <tr>
                            <th>Subject Info</th>
                            <th>Section</th>
                            <th>Schedule & Day</th>
                            <th>Room Location</th>
                            <th style="text-align: center;">Academic Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($classes as $class)
                            <tr>
                                <td>
                                    <strong class="subject-code-badge">{{ $class->subject_code }}</strong>
                                    <span class="subject-title-text">{{ $class->subject_title }}</span>
                                    <small class="units-text">({{ $class->total_units ?? ($class->lecture_units + $class->lab_units) }} Units)</small>
                                </td>
                                <td><span class="section-tag">{{ $class->section ?? 'TBA' }}</span></td>
                                <td>
                                    <div class="sched-wrapper">
                                        <i class="far fa-calendar-alt" style="color: #64748b; margin-right: 4px;"></i>
                                        <strong>{{ $class->schedule_day ?? 'TBA' }}</strong> 
                                        <span class="time-text">| {{ $class->schedule_time ?? 'To Be Arranged' }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="room-text">
                                        <i class="fas fa-door-open" style="color: #94a3b8; margin-right: 4px;"></i>
                                        {{ $class->room ?? 'TBA' }}
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <a href="#" class="btn-action-view font-600">
                                        <i class="fas fa-users"></i> View Roster
                                    </a>
                                    <a href="#" class="btn-action-grade font-600">
                                        <i class="fas fa-pen-alt"></i> Encode Grades
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="empty-table-state">
                                    No teaching subjects have been officially assigned to your roster yet for this term.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    .prof-container { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; max-width: 1400px; margin: 0 auto; }
    .prof-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 2px solid #e2e8f0; padding-bottom: 15px; }
    .header-titles h2 { margin: 0; font-size: 24px; color: #0f172a; font-weight: 800; letter-spacing: -0.5px; }
    .header-titles p { margin: 4px 0 0 0; font-size: 14px; color: #64748b; }
    .prof-level { background-color: #0f172a; color: white; padding: 6px 14px; border-radius: 20px; font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }

    .prof-workspace-full { width: 100%; }
    .prof-card { border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); overflow: hidden; background: #ffffff; }
    .card-header-flex { background-color: #f8fafc; padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
    .card-title-list { font-size: 15px; font-weight: 700; margin: 0; color: #1e293b; }
    
    .minimal-table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
    .minimal-table th { background: #f8fafc; padding: 14px 24px; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; text-align: left; letter-spacing: 0.5px; }
    .minimal-table td { padding: 16px 24px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
    .minimal-table tbody tr:hover { background-color: #f8fafc; }
    
    .subject-code-badge { background: #e0f2fe; color: #0369a1; padding: 4px 8px; border-radius: 6px; font-family: monospace; font-size: 12.5px; margin-right: 8px; }
    .subject-title-text { font-weight: 700; color: #0f172a; font-size: 14.5px; }
    .units-text { color: #64748b; font-size: 12px; margin-left: 4px; }
    .section-tag { font-weight: 700; color: #0f172a; background: #f1f5f9; padding: 4px 10px; border-radius: 4px; font-size: 12.5px; border: 1px solid #cbd5e1; }
    
    .sched-wrapper { font-size: 13.5px; color: #1e293b; }
    .time-text { color: #64748b; font-weight: 400; }
    .room-text { font-weight: 600; color: #475569; }

    /* Action Buttons Style */
    .btn-action-view { text-decoration: none; display: inline-block; background-color: #ffffff; color: #2563eb; border: 1px solid #bfdbfe; padding: 6px 14px; border-radius: 6px; font-size: 12.5px; transition: all 0.2s; margin-right: 6px; }
    .btn-action-view:hover { background-color: #2563eb; color: white; border-color: #2563eb; }
    
    .btn-action-grade { text-decoration: none; display: inline-block; background-color: #ffffff; color: #16a34a; border: 1px solid #bbf7d0; padding: 6px 14px; border-radius: 6px; font-size: 12.5px; transition: all 0.2s; }
    .btn-action-grade:hover { background-color: #16a34a; color: white; border-color: #16a34a; }
    
    .badge-count { background: #0f172a; color: white; font-size: 11.5px; padding: 4px 12px; border-radius: 20px; font-weight: 700; }
    .empty-table-state { text-align: center; color: #94a3b8; padding: 80px 0 !important; font-style: italic; }
    .font-600 { font-weight: 600; }

    .prof-alert-danger { background: #fef2f2; color: #991b1b; padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; border-left: 5px solid #dc2626; font-size: 14px; }
</style>
@endsection