@extends('layouts.app')

@section('content')
<div class="sa-container">
    <div class="sa-header">
        <div class="header-titles">
            <h2>Department Student Registry</h2>
            <p>Department: <strong>{{ strtoupper($dept) }}</strong> — Academic Year Center</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert-success-custom">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert-danger-custom">
            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
        </div>
    @endif

    <div class="tabs-control-wrapper">
        <button class="tab-toggle-btn active" onclick="switchView('individualTab', this)">
            <i class="fas fa-user"></i> Individual List (Alphabetical)
        </button>
        <button class="tab-toggle-btn" onclick="switchView('sectionTab', this)">
            <i class="fas fa-layer-group"></i> Block Section List
        </button>
    </div>

    <div id="individualTab" class="tab-content-panel active-panel">
        <div class="sa-card">
            <div class="card-header-flex">
                <h3 class="card-title-list">All Enrolled Students (Alphabetical Order)</h3>
                <span class="badge-count">{{ count($students) }} Total Students</span>
            </div>

            <div class="table-responsive">
                <table class="minimal-table">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Full Name</th>
                            <th>Course</th>
                            <th>Year Level</th>
                            <th style="text-align: center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($students as $student)
                            <tr>
                                <td><strong class="code-highlight">{{ $student->student_id }}</strong></td>
                                <td><strong>{{ strtoupper($student->last_name) }}</strong>, {{ $student->first_name }}</td>
                                <td><span class="sem-tag">{{ $student->course }}</span></td>
                                <td><span class="year-tag">{{ $student->year_level }}</span></td>
                                <td style="text-align: center;">
                                    <a href="{{ route('department_secretary.plot_subjects', $student->student_id) }}" class="btn-action-plot">
                                        <i class="fas fa-edit"></i> Plot Subjects
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 40px; color: #64748b;">No individual student records found for this department.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="sectionTab" class="tab-content-panel">
        <div class="sa-card">
            <div class="card-header-flex">
                <h3 class="card-title-list">Active Dynamic Block Sections</h3>
                <span class="badge-count" style="background: #16a34a;">{{ count($sections) }} Active Blocks</span>
            </div>

            <div class="table-responsive">
                <table class="minimal-table">
                    <thead>
                        <tr>
                            <th>Block Section Name</th>
                            <th>Active Plotted Classes</th>
                            <th style="text-align: center;">Actions & Bulk Enrollment Controls</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sections as $sec)
                            <tr>
                                <td><span class="year-tag" style="font-size: 14px; padding: 6px 12px; background: #e0f2fe; color: #0369a1; border-color: #bae6fd;">{{ strtoupper($sec->section_name) }}</span></td>
                                <td><strong>{{ $sec->total_subjects }}</strong> Active Subjects</td>
                                <td style="text-align: center;">
                                    
                                    <a href="{{ route('department_secretary.view_section', $sec->section_name) }}" class="btn-action-view">
                                        <i class="fas fa-users"></i> View Students
                                    </a>

                                    <form action="{{ route('department_secretary.bulk_enroll') }}" method="POST" style="display:inline-block; margin-left: 15px;">
                                        @csrf
                                        <input type="hidden" name="current_section" value="{{ $sec->section_name }}">
                                        
                                        <select name="target_year" class="select-input-helper" style="width: 105px; padding: 4px 6px; font-size: 12px;" required>
                                            <option value="1st Year">1st Year</option>
                                            <option value="2nd Year">2nd Year</option>
                                            <option value="3rd Year">3rd Year</option>
                                            <option value="4th Year">4th Year</option>
                                        </select>

                                        <select name="target_semester" class="select-input-helper" style="width: 115px; padding: 4px 6px; font-size: 12px;" required>
                                            <option value="1st Semester">1st Sem</option>
                                            <option value="2nd Semester" selected>2nd Sem</option>
                                        </select>

                                        <button type="submit" class="btn-helper" style="background: #16a34a; padding: 5px 10px; font-size: 12px;" onclick="return confirm('Nais mo bang i-bulk enroll ang buong block na ito sa piniling Taon at Sem?')">
                                            ⚡ Bulk Enroll Block
                                        </button>
                                    </form>

                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" style="text-align: center; padding: 40px; color: #64748b;">No active block sections mapped in schedule matrix for your courses.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    function switchView(tabId, buttonElement) {
        document.querySelectorAll('.tab-content-panel').forEach(panel => {
            panel.classList.remove('active-panel');
        });
        
        document.querySelectorAll('.tab-toggle-btn').forEach(btn => {
            btn.classList.remove('active');
        });

        document.getElementById(tabId).classList.add('active-panel');
        buttonElement.classList.add('active');
    }
</script>

<style>
    .sa-container { font-family: 'Segoe UI', sans-serif; padding: 20px; max-width: 1300px; margin: 0 auto; }
    .sa-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #e2e8f0; padding-bottom: 12px; }
    .header-titles h2 { margin: 0; font-size: 24px; color: #0f172a; font-weight: 800; }
    .header-titles p { margin: 4px 0 0 0; font-size: 14px; color: #64748b; }

    /* Tabs Styling */
    .tabs-control-wrapper { display: flex; gap: 8px; margin-bottom: 20px; background: #f1f5f9; padding: 6px; border-radius: 8px; width: max-content; }
    .tab-toggle-btn { background: transparent; border: none; padding: 8px 18px; font-weight: 600; font-size: 13.5px; color: #475569; border-radius: 6px; cursor: pointer; transition: all 0.2s; }
    .tab-toggle-btn.active { background: #ffffff; color: #0f172a; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
    
    .tab-content-panel { display: none; }
    .tab-content-panel.active-panel { display: block; }

    /* Tables & Cards Component Layouts */
    .sa-card { border-radius: 12px; border: 1px solid #e2e8f0; background: #ffffff; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    .card-header-flex { background-color: #f8fafc; padding: 16px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
    .card-title-list { font-size: 14.5px; font-weight: 700; margin: 0; color: #1e293b; }
    .badge-count { background: #0f172a; color: white; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 12px; }

    .minimal-table { width: 100%; border-collapse: collapse; font-size: 14px; }
    .minimal-table th { background: #f8fafc; padding: 12px 24px; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; text-align: left; }
    .minimal-table td { padding: 14px 24px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
    .minimal-table tbody tr:hover { background-color: #f8fafc; }

    /* Action Buttons UI Styling */
    .btn-action-plot { text-decoration: none; background: #0f172a; color: white; padding: 6px 14px; border-radius: 6px; font-size: 12.5px; font-weight: 600; display: inline-block; }
    .btn-action-plot:hover { background: #1e293b; }
    .btn-action-view { text-decoration: none; background: #2563eb; color: white; padding: 6px 14px; border-radius: 6px; font-size: 12.5px; font-weight: 600; display: inline-block; }
    .btn-action-view:hover { background: #1d4ed8; }
    
    .select-input-helper { background-color: #ffffff; border: 1px solid #cbd5e1; border-radius: 5px; color: #334155; cursor: pointer; }
    .btn-helper { color: white; border: none; padding: 6px 12px; border-radius: 5px; font-weight: 600; cursor: pointer; }

    .year-tag { font-weight: 700; color: #1e293b; background: #f1f5f9; padding: 4px 8px; border-radius: 6px; font-size: 11px; border: 1px solid #cbd5e1; }
    .sem-tag { font-weight: 600; color: #475569; background: #f8fafc; padding: 4px 8px; border-radius: 6px; font-size: 11px; border: 1px solid #e2e8f0; }
    .code-highlight { color: #2563eb; font-family: monospace; font-size: 13.5px; }

    /* Alert Boxes */
    .alert-success-custom { background: #f0fdf4; color: #166534; padding: 12px 18px; border-radius: 8px; border-left: 5px solid #16a34a; margin-bottom: 15px; font-weight: 600; font-size: 13.5px; }
    .alert-danger-custom { background: #fef2f2; color: #991b1b; padding: 12px 18px; border-radius: 8px; border-left: 5px solid #dc2626; margin-bottom: 15px; font-weight: 600; font-size: 13.5px; }
</style>
@endsection