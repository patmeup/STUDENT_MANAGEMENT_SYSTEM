@extends('layouts.app')

@section('content')
<div class="subj-container">
    
    <div class="subj-header">
        <div class="header-titles">
            <h2>Institutional Degree Registry</h2>
            <p>Super Admin Master Control — University Programs Management</p>
        </div>
        <div class="header-badge" style="background-color: #000000;">Super Admin</div>
    </div>

    @if(session('success'))
        <div class="alert-success-custom">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    <div class="subj-card">
        <div class="card-bar">
            <span><i class="fas fa-plus-circle"></i> Register New College Course Program</span>
        </div>
        
        <form action="{{ route('super_admin.courses.store') }}" method="POST" autocomplete="off" class="pure-form">
            @csrf
            
            <div class="form-grid-2">
                {{-- 1. DROPDOWN PARA SA COURSE CODE --}}
                <div class="form-group">
                    <label>Course / Program Acronym Code</label>
                    <div class="custom-select-wrapper">
                        <select name="course_code" id="course-code-select" class="custom-select-dropdown" required>
                            <option value="" disabled selected hidden>Select Program</option>
                            <option value="BSIT">BSIT</option>
                            <option value="BSCS">BSCS</option>
                        </select>
                    </div>
                    <small class="field-help">Capstone Scope: Limited to IT Department baseline.</small>
                </div>

                {{-- 2. AUTOMATED DEPARTMENT FIELD (Naka-lock para sa IT Dept) --}}
                <div class="form-group">
                    <label>Assigned College Department</label>
                    <div class="custom-select-wrapper">
                        <select name="department" class="custom-select-dropdown" required>
                            <option value="College of Computer Studies" selected>College of Computer Studies</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- 3. FULL TITLE FIELD (Kusa nating papapandayin gamit ang JS para iwas typo) --}}
            <div class="form-group">
                <label>Full Descriptive Degree Title</label>
                <input type="text" name="course_title" id="course-title-input" placeholder="Select a program above to auto-populate" readonly required style="background-color: #f8fafc; cursor: not-allowed;">
            </div>

            <div class="form-actions-bar">
                <button type="reset" class="btn-clear" id="btn-reset-form">Clear Form</button>
                <button type="submit" class="btn-submit-subj" style="background-color: #000000;">Authorize & Save Course</button>
            </div>
        </form>
    </div>

    <div class="subject-list-card">
        <div class="card-header-flex">
            <h3 class="card-title-list">University Active Program Registry</h3>
            <span class="badge-count">{{ count($courses) }} Registered</span>
        </div>

        <div class="table-responsive">
            <table class="minimal-table">
                <thead>
                    <tr>
                        <th>Course Code</th>
                        <th>Department</th>
                        <th>Full Program Title</th>
                        <th style="text-align: center; width: 100px;">Actions</th> </tr>
                </thead>
                <tbody>
                    @forelse($courses as $course)
                        <tr>
                            <td><span class="subject-code-tag" style="color: #10b981; background-color: #ecfdf5;">{{ $course->course_code }}</span></td>
                            <td><strong>{{ $course->department }}</strong></td>
                            <td class="subject-title-text">{{ $course->course_title }}</td>
                            <td style="text-align: center;">
                                <button type="button" class="btn-edit-action" onclick="openEditCourseModal({{ json_encode($course) }})">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="empty-table-state"> No institutional course programs recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- JAVASCRIPT MECHANICS PARA AUTOMATIC ANG FULL TITLE --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const courseSelect = document.getElementById('course-code-select');
        const titleInput = document.getElementById('course-title-input');
        const resetBtn = document.getElementById('btn-reset-form');

        const programTitles = {
            'BSIT': 'Bachelor of Science in Information Technology',
            'BSCS': 'Bachelor of Science in Computer Science'
        };

        courseSelect.addEventListener('change', function() {
            if (programTitles[this.value]) {
                titleInput.value = programTitles[this.value];
            } else {
                titleInput.value = '';
            }
        });

        resetBtn.addEventListener('click', function() {
            titleInput.value = '';
        });
    });
>>>>>>> 5d78ce17e26018a3291e1f6fd3d1da3d86ac7f37
</script>

<style>
    .subj-container { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #1e293b; max-width: 900px; margin: 0 auto; padding-bottom: 50px; }
    .subj-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 2px solid #e2e8f0; padding-bottom: 15px; }
    .header-titles h2 { margin: 0 0 5px 0; font-size: 26px; font-weight: 700; color: #0f172a; }
    .header-titles p { margin: 0; font-size: 14px; color: #64748b; }
    .header-badge { color: white; padding: 6px 14px; border-radius: 20px; font-weight: 600; font-size: 12px; }
    .subj-card { background: white; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); overflow: hidden; margin-bottom: 30px; }
    .card-bar { background: #ffffff; padding: 18px 24px; font-weight: 700; font-size: 15px; color: #0f172a; border-bottom: 1px solid #f1f5f9; }
    .pure-form { padding: 24px; display: flex; flex-direction: column; gap: 20px; }
    .form-group { display: flex; flex-direction: column; gap: 6px; }
    .form-group label { font-size: 13px; font-weight: 600; color: #475569; }
    .form-group input { border: 1px solid #cbd5e1; border-radius: 6px; padding: 12px; font-size: 14px; outline: none; box-sizing: border-box; width: 100%; color: #0f172a; }
    .form-group input:focus { border-color: #000000; box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.05); }
    .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .uppercase-input { text-transform: uppercase; }
    .field-help { font-size: 11px; color: #94a3b8; margin-top: 2px; }
    .form-actions-bar { display: flex; justify-content: flex-end; gap: 12px; margin-top: 15px; border-top: 1px solid #f1f5f9; padding-top: 20px; }
    .btn-submit-subj { color: white; border: none; padding: 12px 24px; font-size: 14px; font-weight: 700; border-radius: 6px; cursor: pointer; }
    .btn-clear { background: transparent; color: #64748b; border: 1px solid #cbd5e1; padding: 12px 20px; font-size: 14px; font-weight: 600; border-radius: 6px; cursor: pointer; }
    .alert-success-custom { background: #d1e7dd; color: #0f5132; padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; font-weight: 600; border-left: 5px solid #0f5132; font-size: 14px; }
    .subject-list-card { background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.02); overflow: hidden; }
    .card-header-flex { background-color: #f8fafc; padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
    .card-title-list { font-size: 15px; font-weight: 700; color: #0f172a; margin: 0; }
    .badge-count { background-color: #e2e8f0; color: #334155; font-size: 0.75rem; font-weight: 700; padding: 4px 12px; border-radius: 9999px; }
    .table-responsive { width: 100%; overflow-x: auto; }
    .minimal-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
    .minimal-table th { background-color: #f8fafc; color: #64748b; font-weight: 600; text-transform: uppercase; font-size: 0.75rem; padding: 14px 24px; border-bottom: 1px solid #e2e8f0; text-align: left; }
    .minimal-table td { padding: 14px 24px; border-bottom: 1px solid #f1f5f9; color: #334155; }
    .subject-code-tag { font-family: ui-monospace, SFMono-Regular, Consolas, monospace; font-weight: 700; padding: 4px 8px; border-radius: 6px; font-size: 0.85rem; }
    .subject-title-text { font-weight: 500; color: #1e293b; }
    .empty-table-state { text-align: center; color: #64748b; padding: 40px !important; font-style: italic; }
    .custom-select-wrapper { position: relative; width: 100%; }
    .custom-select-dropdown { width: 100%; padding: 12px 40px 12px 14px; font-size: 14px; color: #0f172a; background-color: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; appearance: none; -webkit-appearance: none; cursor: pointer; outline: none; }
    .custom-select-dropdown:focus { border-color: #000000; box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.05); }
    .custom-select-wrapper::after { content: "▼"; font-size: 0.65rem; color: #64748b; position: absolute; right: 14px; top: 50%; transform: translateY(-50%); pointer-events: none; }
</style>
@endsection