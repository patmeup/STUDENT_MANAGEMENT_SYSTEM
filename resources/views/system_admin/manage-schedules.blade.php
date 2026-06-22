@extends('layouts.app')

@section('content')
<div class="sa-container">
    
    <div class="sa-header">
        <div class="header-titles">
            <h2>Class Schedule Matrix</h2>
            <p>Admin Control Panel — Section & Room Deployment Center</p>
        </div>
        <div class="header-badge system-level">Admin Mode</div>
    </div>

    @if(session('success'))
        <div class="sa-alert-success">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    <div class="sa-workspace">

        <div class="sa-card form-panel">
            <div class="card-bar">
                <span><i class="fas fa-calendar-plus"></i> Deployment Specifications Form</span>
            </div>
            
            <form action="{{ route('system_admin.schedules.store') }}" method="POST" autocomplete="off" class="pure-form">
                @csrf
                
                <div class="form-group">
                    <label>Target Course / Subject</label>
                    <select name="subject_id" class="select-input" required>
                        <option value="">-- Select Cataloged Subject --</option>
                        @if(isset($subjects) && $subjects->count() > 0)
                            @foreach($subjects as $subj)
                                <option value="{{ $subj->id }}">
                                    [{{ strtoupper($subj->subject_code) }}] — {{ $subj->subject_title }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                    <small class="field-help">Choose the curriculum module to be given an active deployment slot.</small>
                </div>

                <div class="form-group">
                    <label>Class Section / Block Name</label>
                    <input type="text" name="section_name" placeholder="e.g., BSIT 2-A, BSIT 3-B, ACT 1-A" class="uppercase-input" required>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Class Days</label>
                        <select name="class_days" class="select-input" required>
                            <option value="">-- Select Days --</option>
                            <option value="MW">MW (Monday / Wednesday)</option>
                            <option value="TTH">TTH (Tuesday / Thursday)</option>
                            <option value="FS">FS (Friday / Saturday)</option>
                            <option value="Monday">Monday Only</option>
                            <option value="Tuesday">Tuesday Only</option>
                            <option value="Wednesday">Wednesday Only</option>
                            <option value="Thursday">Thursday Only</option>
                            <option value="Friday">Friday Only</option>
                            <option value="Saturday">Saturday Only</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Room / Laboratory</label>
                        <input type="text" name="room_assignment" placeholder="e.g., IT LAB 1, RM 304" class="uppercase-input" required>
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Start Time</label>
                        <input type="time" name="start_time" required>
                    </div>
                    <div class="form-group">
                        <label>End Time</label>
                        <input type="time" name="end_time" required>
                    </div>
                </div>

                <div class="form-actions-bar">
                    <button type="reset" class="btn-clear">Reset Form</button>
                    <button type="submit" class="btn-submit-subj">Deploy Class</button>
                </div>
            </form>
        </div>

        <div class="sa-card table-panel">
            <div class="card-header-flex">
                <h3 class="card-title-list">Active Deployment Matrix</h3>
                <span class="badge-count">{{ isset($schedules) ? $schedules->count() : 0 }} Active Classes</span>
            </div>

            <div class="table-responsive">
                <table class="minimal-table">
                    <thead>
                        <tr>
                            <th>Subject Info</th>
                            <th>Section</th>
                            <th>Schedule Details</th>
                            <th>Assigned Faculty</th>
                            <th style="text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($schedules) && $schedules->count() > 0)
                            @foreach($schedules as $sched)
                                <tr>
                                    <td>
                                        <span class="subject-code-tag">{{ strtoupper($sched->subject->subject_code) }}</span>
                                        <div style="font-size: 12px; color: #64748b; margin-top: 4px; font-weight: 500;">
                                            {{ $sched->subject->subject_title }}
                                        </div>
                                    </td>
                                    
                                    <td><strong style="color: #0f172a;">{{ strtoupper($sched->section_name) }}</strong></td>
                                    
                                    <td>
                                        <span class="sched-info-tag">
                                            <i class="fas fa-calendar-day"></i> {{ $sched->class_days }}
                                        </span>
                                        <span class="sched-info-tag">
                                            <i class="fas fa-clock"></i> {{ date('g:i A', strtotime($sched->start_time)) }} - {{ date('g:i A', strtotime($sched->end_time)) }}
                                        </span>
                                        <div style="font-size: 11.5px; color: #475569; margin-top: 4px; font-weight: 600;">
                                            <i class="fas fa-door-open" style="color: #94a3b8;"></i> Room: {{ strtoupper($sched->room_assignment) }}
                                        </div>
                                    </td>
                                    
                                    <td>
                                        @if($sched->subject->professor)
                                            <span class="prof-assigned-tag">
                                                <i class="fas fa-user-tie"></i> {{ $sched->subject->professor->last_name }}, {{ substr($sched->subject->professor->first_name, 0, 1) }}.
                                            </span>
                                        @else
                                            <span class="prof-unassigned-tag">Vacant Pool</span>
                                        @endif
                                    </td>

                                    <td style="text-align: center;">
                                        <button class="btn-action-edit" title="Cancel or Modify Schedule">Edit</button>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="5" class="empty-table-state">
                                    No active class scheduling or section deployments documented yet.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<style>
    .sa-container { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #0f172a; max-width: 1300px; margin: 0 auto; padding: 20px; }
    
    /* Header Architecture */
    .sa-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 2px solid #e2e8f0; padding-bottom: 18px; }
    .header-titles h2 { margin: 0 0 4px 0; font-size: 28px; font-weight: 800; color: #0f172a; letter-spacing: -0.5px; }
    .header-titles p { margin: 0; font-size: 14.5px; color: #64748b; }
    .system-level { background-color: #dc2626; color: #ffffff; padding: 6px 14px; border-radius: 20px; font-weight: 700; font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.5px; }

    /* Layout Grids */
    .sa-workspace { display: flex; flex-direction: row; gap: 28px; align-items: flex-start; justify-content: space-between; width: 100%; }
    .form-panel { flex: 1; min-width: 390px; background: #ffffff; }
    .table-panel { flex: 2; background: #ffffff; }

    /* Component Cards */
    .sa-card { border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 10px -2px rgba(0, 0, 0, 0.03); overflow: hidden; }
    .card-bar { background: #f8fafc; padding: 16px 22px; font-weight: 700; font-size: 14.5px; color: #1e293b; border-bottom: 1px solid #e2e8f0; }
    .card-header-flex { background-color: #f8fafc; padding: 16px 22px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
    .card-title-list { font-size: 14.5px; font-weight: 700; color: #1e293b; margin: 0; }
    
    /* Form Mechanics */
    .pure-form { padding: 24px; display: flex; flex-direction: column; gap: 16px; }
    .form-group { display: flex; flex-direction: column; gap: 6px; }
    .form-group label { font-size: 12px; font-weight: 600; color: #475569; }
    .form-group input, .select-input { border: 1px solid #cbd5e1; border-radius: 6px; padding: 10px 14px; font-size: 14px; color: #0f172a; outline: none; background: #ffffff; box-sizing: border-box; width: 100%; }
    .form-group input:focus, .select-input:focus { border-color: #0033A0; box-shadow: 0 0 0 3px rgba(0, 51, 160, 0.15); }
    .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .uppercase-input { text-transform: uppercase; }
    .field-help { font-size: 11px; color: #94a3b8; margin-top: 2px; }
    
    /* Select input style */
    .select-input { background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e"); background-position: right 12px center; background-repeat: no-repeat; background-size: 20px; -webkit-appearance: none; -moz-appearance: none; appearance: none; padding-right: 40px; cursor: pointer; }

    /* Action Buttons */
    .form-actions-bar { display: flex; justify-content: flex-end; gap: 12px; margin-top: 12px; border-top: 1px solid #f1f5f9; padding-top: 16px; }
    .btn-submit-subj { background: #0033A0; color: #ffffff; border: none; padding: 10px 20px; font-size: 14px; font-weight: 700; border-radius: 6px; cursor: pointer; transition: background 0.2s; text-align: center; }
    .btn-submit-subj:hover { background: #002270; }
    .btn-clear { background: transparent; color: #64748b; border: 1px solid #cbd5e1; padding: 10px 16px; font-size: 14px; font-weight: 600; border-radius: 6px; cursor: pointer; }
    .btn-clear:hover { background: #f8fafc; color: #334155; }

    /* Database Table Layouts */
    .table-responsive { width: 100%; overflow-x: auto; }
    .minimal-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 13.5px; }
    .minimal-table th { background: #f8fafc; padding: 14px 20px; font-size: 11.5px; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; }
    .minimal-table td { padding: 15px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
    .minimal-table tbody tr:hover { background-color: #f8fafc; }
    
    .subject-code-tag { font-family: ui-monospace, SFMono-Regular, Consolas, monospace; font-weight: 700; color: #2563eb; background-color: #eff6ff; padding: 4px 8px; border-radius: 6px; font-size: 12px; display: inline-block; }
    
    /* Schedule specific labels */
    .sched-info-tag { font-size: 11.5px; font-weight: 600; color: #1e293b; background-color: #f1f5f9; padding: 3px 8px; border-radius: 4px; display: inline-inline-block; margin-right: 4px; border: 1px solid #e2e8f0; }
    .sched-info-tag i { color: #475569; margin-right: 3px; }

    /* Professor Assignment Badges */
    .prof-assigned-tag { font-size: 12.5px; font-weight: 600; color: #047857; background-color: #ecfdf5; border: 1px solid #a7f3d0; padding: 4px 10px; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px; }
    .prof-unassigned-tag { font-size: 12px; font-weight: 500; color: #64748b; background-color: #f1f5f9; padding: 4px 10px; border-radius: 6px; display: inline-block; font-style: italic; }

    .empty-table-state { text-align: center; color: #94a3b8; padding: 50px 0 !important; font-style: italic; font-size: 13.5px; }
    .btn-action-edit { background-color: transparent; border: 1px solid #cbd5e1; color: #475569; padding: 5px 12px; font-size: 12px; font-weight: 600; border-radius: 6px; cursor: pointer; transition: all 0.2s; }
    .btn-action-edit:hover { background-color: #f1f5f9; color: #1e293b; border-color: #94a3b8; }

    /* Alerts */
    .sa-alert-success { background: #ecfdf5; color: #065f46; padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; font-weight: 600; border-left: 5px solid #059669; font-size: 14px; }
    .badge-count { background: #e2e8f0; color: #334155; font-size: 11.5px; padding: 4px 12px; border-radius: 20px; font-weight: 700; }
</style>
@endsection