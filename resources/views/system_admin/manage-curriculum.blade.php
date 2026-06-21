@extends('layouts.app')

@section('content')
<div class="subj-container">
    
    <div class="subj-header">
        <div class="header-titles">
            <h2>Curriculum Subject Mapping</h2>
            <p>Admin Control Panel — Course Curriculum Core Management</p>
        </div>
        <div class="header-badge">Admin Mode</div>
    </div>

    @if(session('success'))
        <div class="alert-success-custom">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert-danger-custom" style="background: #fde8e8; color: #9b1c1c; padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; font-weight: 600; border-left: 5px solid #9b1c1c; font-size: 14px;">
            <i class="fas fa-exclamation-circle"></i> {{ $errors->first() }}
        </div>
    @endif

    <div class="subj-card">
        <div class="card-bar">
            <span><i class="fas fa-network-wired"></i> Map Subject to Academic Structure</span>
        </div>
        
        <form action="{{ route('system_admin.curriculum.store') }}" method="POST" autocomplete="off" class="pure-form">
            @csrf
            
            <div class="form-grid-3">
                <div class="form-group">
                    <label>Select Course Program</label>
                    <select name="course" required class="form-select-custom">
                        <option value="" disabled selected>-- Choose Course --</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->course_code }}">{{ $course->course_code }} - {{ $course->course_title }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label>Year Level</label>
                    <select name="year_level" required class="form-select-custom">
                        <option value="" disabled selected>-- Choose Year --</option>
                        <option value="1st Year">1st Year</option>
                        <option value="2nd Year">2nd Year</option>
                        <option value="3rd Year">3rd Year</option>
                        <option value="4th Year">4th Year</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Semester</label>
                    <select name="semester" required class="form-select-custom">
                        <option value="" disabled selected>-- Choose Semester --</option>
                        <option value="1st Semester">1st Semester</option>
                        <option value="2nd Semester">2nd Semester</option>
                        <option value="Summer">Summer</option>
                    </select>
                </div>
            </div>

            <div class="form-group" style="margin-top: 10px;">
                <label>Select Subject to Bind</label>
                <select name="subject_id" required class="form-select-custom">
                    <option value="" disabled selected>-- Choose Subject Catalog Entry --</option>
                    @foreach($subjects as $subject)
                        <option value="{{ $subject->id }}">[{{ $subject->subject_code }}] — {{ $subject->subject_title }} (Units: {{ $subject->total_units }})</option>
                    @endforeach
                </select>
            </div>

            <div class="form-actions-bar">
                <button type="reset" class="btn-clear">Reset Selection</button>
                <button type="submit" class="btn-submit-subj">Establish Curriculum Link</button>
            </div>
        </form>
    </div>

    <div class="subject-list-card">
        <div class="card-header-flex">
            <h3 class="card-title-list">Active Curriculum Structures</h3>
            <span class="badge-count">{{ count($mappings) }} Links Active</span>
        </div>

        <div class="table-responsive">
            <table class="minimal-table">
                <thead>
                    <tr>
                        <th>Course</th>
                        <th>Year Level</th>
                        <th>Semester</th>
                        <th>Subject Code</th>
                        <th>Descriptive Title</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($mappings as $map)
                        <tr>
                            <td><span class="subject-code-tag" style="color: #dc2626; background-color: #fef2f2;">{{ $map->course }}</span></td>
                            <td><strong>{{ $map->year_level }}</strong></td>
                            <td><span class="sem-badge">{{ $map->semester }}</span></td>
                            <td><span class="subject-code-tag">{{ $map->subject->subject_code ?? 'N/A' }}</span></td>
                            <td class="subject-title-text">{{ $map->subject->subject_title ?? 'Deleted Subject' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="empty-table-state">
                                ⚠️ Walang subject links na kasalukuyang nakalagay sa mga kurso.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<style>
    /* Pinapanatili ang disenyo para sa bawat masterlists */
    .subj-container { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #1e293b; max-width: 900px; margin: 0 auto; padding-bottom: 50px; }
    .subj-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 2px solid #e2e8f0; padding-bottom: 15px; }
    .header-titles h2 { margin: 0 0 5px 0; font-size: 26px; font-weight: 700; color: #0f172a; }
    .header-titles p { margin: 0; font-size: 14px; color: #64748b; }
    .header-badge { background-color: #dc2626; color: white; padding: 6px 14px; border-radius: 20px; font-weight: 600; font-size: 12px; }
    .subj-card { background: white; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); overflow: hidden; margin-bottom: 30px; }
    .card-bar { background: #ffffff; padding: 18px 24px; font-weight: 700; font-size: 15px; color: #0f172a; border-bottom: 1px solid #f1f5f9; }
    .pure-form { padding: 24px; display: flex; flex-direction: column; gap: 20px; }
    .form-group { display: flex; flex-direction: column; gap: 6px; }
    .form-group label { font-size: 13px; font-weight: 600; color: #475569; }
    
    .form-select-custom {
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 12px;
        font-size: 14px;
        outline: none;
        box-sizing: border-box;
        width: 100%;
        color: #0f172a;
        background: #fff;
    }
    .form-select-custom:focus { border-color: #0033A0; box-shadow: 0 0 0 3px rgba(0, 51, 160, 0.1); }
    
    .form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; }
    .form-actions-bar { display: flex; justify-content: flex-end; gap: 12px; margin-top: 15px; border-top: 1px solid #f1f5f9; padding-top: 20px; }
    .btn-submit-subj { background: #0033A0; color: white; border: none; padding: 12px 24px; font-size: 14px; font-weight: 700; border-radius: 6px; cursor: pointer; }
    .btn-submit-subj:hover { background: #002270; }
    .btn-clear { background: transparent; color: #64748b; border: 1px solid #cbd5e1; padding: 12px 20px; font-size: 14px; font-weight: 600; border-radius: 6px; cursor: pointer; }
    .btn-clear:hover { background: #f8fafc; color: #334155; }
    .alert-success-custom { background: #d1e7dd; color: #0f5132; padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; font-weight: 600; border-left: 5px solid #0f5132; font-size: 14px; }
    
    .subject-list-card { background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.02); overflow: hidden; }
    .card-header-flex { background-color: #f8fafc; padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
    .card-title-list { font-size: 15px; font-weight: 700; color: #0f172a; margin: 0; }
    .badge-count { background-color: #e2e8f0; color: #334155; font-size: 0.75rem; font-weight: 700; padding: 4px 12px; border-radius: 9999px; }
    .table-responsive { width: 100%; overflow-x: auto; }
    .minimal-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
    .minimal-table th { background-color: #f8fafc; color: #64748b; font-weight: 600; text-transform: uppercase; font-size: 0.75rem; padding: 14px 24px; border-bottom: 1px solid #e2e8f0; text-align: left; }
    .minimal-table td { padding: 14px 24px; border-bottom: 1px solid #f1f5f9; color: #334155; }
    .minimal-table tbody tr:hover { background-color: #f8fafc; }
    .subject-code-tag { font-family: ui-monospace, SFMono-Regular, Consolas, monospace; font-weight: 700; color: #2563eb; background-color: #eff6ff; padding: 4px 8px; border-radius: 6px; font-size: 0.85rem; }
    .subject-title-text { font-weight: 500; color: #1e293b; }
    .sem-badge { font-size: 0.8rem; background: #f1f5f9; color: #475569; padding: 3px 8px; border-radius: 4px; font-weight: 600; }
    .empty-table-state { text-align: center; color: #64748b; padding: 40px !important; font-style: italic; }
</style>
@endsection