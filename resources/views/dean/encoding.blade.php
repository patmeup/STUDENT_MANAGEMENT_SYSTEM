@extends('layouts.app')

@section('content')
<div class="dean-container">
    
    <div class="dean-header">
        <div class="header-titles">
            <h2>Curriculum Encoding & Mapping</h2>
            <p>Dean's Portal — Subject-to-Course Assignment Matrix</p>
        </div>
        <div class="header-badge dean">Dean Access</div>
    </div>

    @if(session('success'))
        <div class="alert-success-custom">
            <i class="fas fa-link"></i> {{ session('success') }}
        </div>
    @endif

    <div class="dean-workspace">
        
        <div class="dean-card form-panel">
            <div class="card-bar">Curriculum Assignment Form</div>
            <form action="{{ route('dean.curriculum.store') }}" method="POST" class="pure-form">
                @csrf
                
                <div class="form-group">
                    <label>Select Global Subject</label>
                    <select name="subject_id" class="select-search" required>
                        <option value="">-- Choose Global Subject Code --</option>
                        @foreach($globalSubjects as $subject)
                            <option value="{{ $subject->id }}">
                                {{ $subject->subject_code }} — {{ $subject->subject_title }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label>Assign to Course / Program</label>
                    <select name="course" required>
                        <option value="">-- Select Target Course --</option>
                        <option value="BSIT">BS Information Technology</option>
                        <option value="BSN">BS Nursing</option>
                        <option value="BSA">BS Accountancy</option>
                        <option value="BSED">Bachelor of Secondary Education</option>
                        <option value="Crim">BS Criminology</option>
                    </select>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Academic Year Level</label>
                        <select name="year_level" required>
                            <option value="1st Year">1st Year</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option>
                            <option value="4th Year">4th Year</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Target Semester</label>
                        <select name="semester" required>
                            <option value="1st Semester">1st Semester</option>
                            <option value="2nd Semester">2nd Semester</option>
                            <option value="Summer">Summer Term</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn-map">Confirm Subject Assignment</button>
            </form>
        </div>

        <div class="dean-card table-panel">
            <div class="card-bar d-flex-jsb">
                <span>Recent Curriculum Mappings</span>
                <span class="count-tag">Mapped: {{ $mappings->count() }}</span>
            </div>
            
            <div class="table-wrap">
                <table class="clean-table">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Target Course</th>
                            <th>Year & Sem</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($mappings as $map)
                            <tr>
                                <td>
                                    <strong class="code-text">{{ $map->subject->subject_code }}</strong>
                                    <br><small class="title-subtext">{{ $map->subject->subject_title }}</small>
                                </td>
                                <td><span class="course-pill">{{ $map->course }}</span></td>
                                <td>
                                    <span class="schedule-text">{{ $map->year_level }}</span>
                                    <br><small class="sem-text">{{ $map->semester }}</small>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="empty-state">No assignments made yet. Use the form on the left to begin mapping.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<style>
    .dean-container { font-family: 'Segoe UI', sans-serif; color: #1e293b; max-width: 1200px; margin: 0 auto; padding-bottom: 50px; }
    .dean-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 2px solid #e2e8f0; padding-bottom: 15px; }
    .header-titles h2 { margin: 0 0 5px 0; font-size: 26px; font-weight: 700; color: #0f172a; }
    .header-titles p { margin: 0; font-size: 14px; color: #64748b; }
    .header-badge.dean { background-color: #0d9488; color: white; padding: 6px 14px; border-radius: 20px; font-weight: 600; font-size: 12px; }

    .dean-workspace { display: flex; gap: 25px; align-items: flex-start; }
    .form-panel { flex: 1; min-width: 400px; }
    .table-panel { flex: 1.5; }

    .dean-card { background: white; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); overflow: hidden; }
    .card-bar { background: #fafafa; padding: 15px 20px; font-weight: 700; font-size: 15px; color: #1e293b; border-bottom: 1px solid #e2e8f0; }
    
    .pure-form { padding: 24px; display: flex; flex-direction: column; gap: 18px; }
    .form-group { display: flex; flex-direction: column; gap: 6px; }
    .form-group label { font-size: 12.5px; font-weight: 600; color: #475569; }
    .form-group select { border: 1px solid #cbd5e1; border-radius: 6px; padding: 12px; font-size: 14px; background: #fff; outline: none; cursor: pointer; }
    .form-group select:focus { border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13,148,136,0.1); }
    
    .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
    
    .btn-map { background: #0d9488; color: white; border: none; padding: 14px; font-size: 14px; font-weight: 700; border-radius: 6px; cursor: pointer; margin-top: 10px; transition: 0.2s; }
    .btn-map:hover { background: #0f766e; box-shadow: 0 4px 12px rgba(13,148,136,0.2); }

    /* Table Styling */
    .table-wrap { width: 100%; overflow-x: auto; }
    .clean-table { width: 100%; border-collapse: collapse; }
    .clean-table th { background: #f8fafc; padding: 14px 18px; font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; }
    .clean-table td { padding: 14px 18px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
    
    .code-text { color: #0f172a; font-family: monospace; }
    .title-subtext { color: #64748b; font-size: 12px; }
    .course-pill { background: #f0fdfa; color: #0d9488; padding: 3px 10px; border-radius: 4px; font-size: 11px; font-weight: 700; border: 1px solid #ccfbf1; }
    .schedule-text { font-weight: 600; color: #334155; }
    
    .alert-success-custom { background: #f0fdfa; color: #0f766e; padding: 15px; border-radius: 8px; margin-bottom: 25px; font-weight: 600; border-left: 5px solid #0d9488; font-size: 14px; }
    .d-flex-jsb { display: flex; justify-content: space-between; align-items: center; }
    .count-tag { background: #f1f5f9; color: #475569; font-size: 12px; padding: 2px 10px; border-radius: 5px; }
    .empty-state { text-align: center; color: #94a3b8; padding: 40px 0; font-style: italic; }
</style>
@endsection