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

    @if($errors->any())
        <div class="alert-danger-custom">
            <i class="fas fa-exclamation-circle"></i>
            <ul style="margin: 0; padding-left: 20px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
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

            <div class="search-bar-container">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="studentSearchInput" placeholder="Search by Student ID, Name, or Course..." class="custom-search-input">
            </div>

            <div class="table-responsive">
                <table class="minimal-table">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Full Name</th>
                            <th>Course</th>
                            <th>Academic Level</th>
                            <th style="text-align: center;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="studentTableBody">
                        @forelse($students as $student)
                            <tr class="student-row">
                                <td><strong class="code-highlight">{{ $student->student_id }}</strong></td>
                                <td><strong>{{ strtoupper($student->last_name) }}</strong>, {{ $student->first_name }}</td>
                                <td><span class="sem-tag">{{ $student->course }}</span></td>
                                <td><span class="year-tag">{{ $student->year_level }}</span></td>
                                <td style="text-align: center;">
                                    <div style="display: flex; gap: 6px; justify-content: center; align-items: center;">
                                        <a href="{{ route('department_secretary.plot_subjects', $student->student_id) }}" class="btn-action-plot">
                                            <i class="fas fa-edit"></i> Plot
                                        </a>

                                        <form action="{{ route('department_secretary.delete_student', $student->student_id) }}" method="POST" style="display: inline-block;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-action-delete" onclick="return confirm('Are you sure you want to delete student {{ $student->student_id }}? This will permanently remove their record from the database.')">
                                                <i class="fas fa-trash-alt"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr id="studentEmptyRow">
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
                {{-- INAYOS: Binibilang na ang unique grouped blocks sa halip na raw un-grouped count --}}
                <span class="badge-count" style="background: #16a34a;">
                    {{ $sections->groupBy('section_name')->count() }} Active Blocks
                </span>
            </div>

            <div class="search-bar-container">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="blockSearchInput" placeholder="Search by Block Section Name..." class="custom-search-input">
            </div>

            <div class="table-responsive">
                <table class="minimal-table">
                    <thead>
                        <tr>
                            <th>Block Section Name</th>
                            <th>Enrolled Students</th>
                            <th>Academic Level</th>
                            <th style="text-align: center;">Actions & Bulk Enrollment Controls</th>
                        </tr>
                    </thead>
                    <tbody id="blockTableBody">
                        @forelse($sections->groupBy('section_name') as $sectionName => $records)
                            @php 
                                $slug = Str::slug($sectionName); 
                                $defaultYear = '1st Year';
                                if (str_contains($sectionName, '1')) $defaultYear = '1st Year';
                                elseif (str_contains($sectionName, '2')) $defaultYear = '2nd Year';
                                elseif (str_contains($sectionName, '3')) $defaultYear = '3rd Year';
                                elseif (str_contains($sectionName, '4')) $defaultYear = '4th Year';
                            @endphp
                            <tr class="block-row">
                                <td>
                                    <span class="year-tag" style="font-size: 14px; padding: 6px 12px; background: #e0f2fe; color: #0369a1; border-color: #bae6fd;">
                                        {{ strtoupper($sectionName) }}
                                    </span>
                                </td>

                                <td>
                                    {{-- DYNAMIC STUDENT ENROLLED COUNT --}}
                                    <span class="enrolled-count-badge">
                                        <i class="fas fa-users"></i> {{ $records->first()->enrolled_count ?? 0 }} Students
                                    </span>
                                </td>

                                <td>
                                    <span id="display-count-{{ $slug }}">
                                        <strong>0</strong> Active Subjects
                                    </span>

                                    <div class="block-meta-dataset" style="display: none;" data-block-id="{{ $slug }}">
                                        @foreach($records as $rec)
                                            <span data-year="{{ $rec->year_level }}" data-sem="{{ $rec->semester }}" data-count="{{ $rec->total_subjects }}"></span>
                                        @endforeach
                                    </div>
                                </td>

                                <td style="text-align: center;">
                                    <a href="{{ route('department_secretary.view_section', $sectionName) }}" class="btn-action-view">
                                        <i class="fas fa-users"></i> View Students
                                    </a>

                                    <form action="{{ route('department_secretary.bulk_enroll') }}" method="POST" style="display:inline-block; margin-left: 15px;">
                                        @csrf
                                        <input type="hidden" name="current_section" value="{{ $sectionName }}">
                                        
                                        <select name="target_year" class="select-input-helper year-selector-js" data-target="{{ $slug }}" style="width: 105px; padding: 4px 6px; font-size: 12px;" required>
                                            <option value="1st Year" {{ $defaultYear == '1st Year' ? 'selected' : '' }}>1st Year</option>
                                            <option value="2nd Year" {{ $defaultYear == '2nd Year' ? 'selected' : '' }}>2nd Year</option>
                                            <option value="3rd Year" {{ $defaultYear == '3rd Year' ? 'selected' : '' }}>3rd Year</option>
                                            <option value="4th Year" {{ $defaultYear == '4th Year' ? 'selected' : '' }}>4th Year</option>
                                        </select>

                                        <select name="target_semester" class="select-input-helper sem-selector-js" data-target="{{ $slug }}" style="width: 115px; padding: 4px 6px; font-size: 12px;" required>
                                            <option value="1st Semester" selected>1st Sem</option>
                                            <option value="2nd Semester">2nd Sem</option>
                                        </select>

                                        <button type="submit" class="btn-helper" style="background: #16a34a; padding: 5px 10px; font-size: 12px;" onclick="return confirm('Do you want to bulk enroll this entire block into the selected Year and Semester?')">
                                            Bulk Enroll Block
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr id="blockEmptyRow">
                                <td colspan="4" style="text-align: center; padding: 40px; color: #64748b;">No active block sections mapped in schedule matrix for your courses.</td>
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

    // Client-Side Searching Feature Engine
    document.addEventListener('DOMContentLoaded', function () {
        // --- 1. Student Masterlist Filter Engine ---
        const studentSearch = document.getElementById('studentSearchInput');
        const studentTableBody = document.getElementById('studentTableBody');
        
        if (studentSearch && studentTableBody) {
            const rows = studentTableBody.getElementsByClassName('student-row');
            
            studentSearch.addEventListener('input', function () {
                const searchVal = studentSearch.value.toLowerCase().trim();
                
                for (let i = 0; i < rows.length; i++) {
                    const content = rows[i].textContent.toLowerCase();
                    if (content.includes(searchVal)) {
                        rows[i].style.display = '';
                    } else {
                        rows[i].style.display = 'none';
                    }
                }
            });
        }

        // --- 2. Block Section Filter Engine ---
        const blockSearch = document.getElementById('blockSearchInput');
        const blockTableBody = document.getElementById('blockTableBody');
        
        if (blockSearch && blockTableBody) {
            const rows = blockTableBody.getElementsByClassName('block-row');
            
            blockSearch.addEventListener('input', function () {
                const searchVal = blockSearch.value.toLowerCase().trim();
                
                for (let i = 0; i < rows.length; i++) {
                    const content = rows[i].textContent.toLowerCase();
                    if (content.includes(searchVal)) {
                        rows[i].style.display = '';
                    } else {
                        rows[i].style.display = 'none';
                    }
                }
            });
        }
    });

    function updateSubjectCounter(slug, rowElement) {
        const selectedYear = rowElement.querySelector('.year-selector-js').value;
        const selectedSem = rowElement.querySelector('.sem-selector-js').value;
        
        const datasetSpans = rowElement.querySelectorAll('.block-meta-dataset span');
        let matchedCount = 0;

        datasetSpans.forEach(span => {
            const itemYear = span.getAttribute('data-year');
            const itemSem = span.getAttribute('data-sem');

            if (itemYear === selectedYear && itemSem === selectedSem) {
                matchedCount = parseInt(span.getAttribute('data-count')) || 0;
            }
        });

        const targetDisplay = document.getElementById(`display-count-${slug}`);
        if (targetDisplay) {
            targetDisplay.innerHTML = `<strong>${matchedCount}</strong> Active Subjects`;
        }
    }

    document.querySelectorAll('.year-selector-js, .sem-selector-js').forEach(dropdown => {
        const row = dropdown.closest('tr');
        const targetSlug = dropdown.getAttribute('data-target');

        dropdown.addEventListener('change', function() {
            updateSubjectCounter(targetSlug, row);
        });

        // Patakbuhin agad para makuha ang default active subjects pagkaload ng page
        updateSubjectCounter(targetSlug, row);
    });
</script>

<style>
    .sa-container { font-family: 'Segoe UI', sans-serif; padding: 20px; max-width: 1300px; margin: 0 auto; }
    .sa-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #e2e8f0; padding-bottom: 12px; }
    .header-titles h2 { margin: 0; font-size: 24px; color: #0f172a; font-weight: 800; }
    .header-titles p { margin: 4px 0 0 0; font-size: 14px; color: #64748b; }

    /* Search Bar Design Styling */
    .search-bar-container {
        padding: 14px 24px;
        background: #ffffff;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        position: relative;
    }
    .search-icon {
        position: absolute;
        left: 38px;
        color: #94a3b8;
        font-size: 14px;
    }
    .custom-search-input {
        width: 100%;
        max-width: 450px;
        padding: 9px 12px 9px 36px;
        font-size: 13.5px;
        color: #334155;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        outline: none;
        transition: border-color 0.15s ease;
    }
    .custom-search-input:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.08);
    }

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

    .enrolled-count-badge {
        display: inline-flex; align-items: center; gap: 6px;
        background: #f0fdf4; color: #166534; font-weight: 700;
        padding: 5px 12px; border-radius: 20px; font-size: 12.5px;
        border: 1px solid #bbf7d0;
    }

    .minimal-table { width: 100%; border-collapse: collapse; font-size: 14px; }
    .minimal-table th { background: #f8fafc; padding: 12px 24px; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; text-align: left; }
    .minimal-table td { padding: 14px 24px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
    .minimal-table tbody tr:hover { background-color: #f8fafc; }

    .btn-action-plot { text-decoration: none; background: #0f172a; color: white; padding: 6px 14px; border-radius: 6px; font-size: 12.5px; font-weight: 600; display: inline-block; }
    .btn-action-plot:hover { background: #1e293b; }
    .btn-action-view { text-decoration: none; background: #2563eb; color: white; padding: 6px 14px; border-radius: 6px; font-size: 12.5px; font-weight: 600; display: inline-block; }
    .btn-action-view:hover { background: #1d4ed8; }
    
    .btn-action-delete {
        background: #dc2626;
        color: #ffffff;
        border: none;
        padding: 6px 14px;
        border-radius: 6px;
        font-size: 12.5px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        transition: background 0.15s ease;
    }

    .btn-action-delete:hover {
        background: #b91c1c;
    }

    .select-input-helper { background-color: #ffffff; border: 1px solid #cbd5e1; border-radius: 5px; color: #334155; cursor: pointer; }
    .btn-helper { color: white; border: none; padding: 6px 12px; border-radius: 5px; font-weight: 600; cursor: pointer; }

    .year-tag { font-weight: 700; color: #1e293b; background: #f1f5f9; padding: 4px 8px; border-radius: 6px; font-size: 11px; border: 1px solid #cbd5e1; }
    .sem-tag { font-weight: 600; color: #475569; background: #f8fafc; padding: 4px 8px; border-radius: 6px; font-size: 11px; border: 1px solid #e2e8f0; }
    .code-highlight { color: #2563eb; font-family: monospace; font-size: 13.5px; }

    .alert-success-custom { background: #f0fdf4; color: #166534; padding: 12px 18px; border-radius: 8px; border-left: 5px solid #16a34a; margin-bottom: 15px; font-weight: 600; font-size: 13.5px; }
    .alert-danger-custom { background: #fef2f2; color: #991b1b; padding: 12px 18px; border-radius: 8px; border-left: 5px solid #dc2626; margin-bottom: 15px; font-weight: 600; font-size: 13.5px; }
</style>
@endsection