@extends('layouts.app')

@section('content')
@php
    $activePeriod = $activePeriod ?? \Illuminate\Support\Facades\DB::table('academic_periods')->where('is_active', true)->first();
@endphp
<div class="sa-container">
    <div class="sa-header">
        <div class="header-titles">
            <h2>Department Student Registry</h2>
            <p>View, search, and manage student records and block sections.</p>
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
            <div class="card-header-flex" style="display: flex; flex-direction: column; gap: 14px; align-items: stretch;">
                <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                    <h3 class="card-title-list">All Enrolled Students (Alphabetical Order)</h3>
                    <span class="badge-count" id="studentTotalCount">{{ count($students) }} Total Students</span>
                </div>

                <div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center; width: 100%;">
                    <div style="flex: 2; min-width: 280px;">
                        <input type="text" id="studentSearchInput" placeholder="Search by Student ID, Name, or Course..." class="custom-search-input">
                    </div>

                    <div style="display: flex; gap: 6px; flex-wrap: wrap; align-items: center;">
                        <span style="font-size: 11.5px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-right: 2px;">Year:</span>
                        <button type="button" class="filter-pill active" data-filter-value="">All</button>
                        <button type="button" class="filter-pill" data-filter-value="1st Year">1st</button>
                        <button type="button" class="filter-pill" data-filter-value="2nd Year">2nd</button>
                        <button type="button" class="filter-pill" data-filter-value="3rd Year">3rd</button>
                        <button type="button" class="filter-pill" data-filter-value="4th Year">4th</button>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="minimal-table">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Full Name</th>
                            <th>Course</th>
                            <th>Section</th>
                            <th>Academic Level</th>
                            <th style="text-align: center;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="studentTableBody">
                        @forelse($students as $student)
                            <tr class="student-row" data-year="{{ $student->year_level }}">
                                <td><strong class="code-highlight">{{ $student->student_id }}</strong></td>
                                <td><strong>{{ strtoupper($student->last_name) }}</strong>, {{ $student->first_name }}</td>
                                <td><span class="sem-tag">{{ $student->course }}</span></td>
                                <td>
                                    @if($student->section)
                                        <span class="year-tag" style="background: #e0f2fe; color: #0369a1; border-color: #bae6fd;">
                                            {{ $student->section }}
                                        </span>
                                    @else
                                        <span style="color: #94a3b8; font-size: 12px; font-style: italic;">None</span>
                                    @endif
                                </td>
                                <td><span class="year-tag">{{ $student->year_level }}</span></td>
                                <td style="text-align: center;">
                                    <div style="display: flex; gap: 6px; justify-content: center; align-items: center;">
                                        <a href="{{ route('department_secretary.students.profile', $student->student_id) }}" class="btn-action-view" title="View Student Profile">
                                            <i class="fas fa-folder-open"></i> View Profile
                                        </a>

                                        <a href="{{ route('department_secretary.plot_subjects', $student->student_id) }}" class="btn-action-plot">
                                            Plot
                                        </a>

                                        <form action="{{ route('department_secretary.delete_student', $student->student_id) }}" method="POST" style="display: inline-block;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-action-delete" onclick="return confirm('Are you sure you want to delete student {{ $student->student_id }}? This will permanently remove their record from the database.')">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr id="studentEmptyRow">
                                <td colspan="6" style="text-align: center; padding: 40px; color: #64748b;">No individual student records found for this department.</td>
                            </tr>
                        @endforelse
                        <tr id="studentNoMatchRow" style="display: none;">
                            <td colspan="6" style="text-align: center; padding: 40px; color: #64748b;">No students match your filter or search query.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="sectionTab" class="tab-content-panel">
        <div class="sa-card">
            <div class="card-header-flex">
                <h3 class="card-title-list">Active Dynamic Block Sections</h3>
                <span class="badge-count" style="background: #16a34a;">
                    {{ $sections->count() }} Active Blocks
                </span>
            </div>

            <div class="search-bar-container" style="padding: 14px 24px; background: #ffffff; border-bottom: 1px solid #f1f5f9;">
                <input type="text" id="blockSearchInput" placeholder="Search by Block Section Name..." class="custom-search-input" style="max-width: 450px;">
            </div>

            <div class="table-responsive">
                <table class="minimal-table">
                    <thead>
                        <tr>
                            <th>Block Section Name</th>
                            <th>Students in Block</th>
                            <th>Active Subjects</th>
                            <th style="text-align: center;">Actions & Bulk Enrollment Controls</th>
                        </tr>
                    </thead>
                    <tbody id="blockTableBody">
                        @forelse($sections as $block)
                            @php
                                $sectionName = $block->section_name;
                                $slug        = Str::slug($sectionName . '-' . ($block->block_year_level ?? 'na'));
                                $enrolled    = $block->enrolled_count ?? 0;
                                $pending     = $block->pending_count ?? 0;
                            @endphp
                            <tr class="block-row">
                                <td>
                                    <span class="year-tag" style="font-size: 14px; padding: 6px 12px; background: #e0f2fe; color: #0369a1; border-color: #bae6fd;">
                                        {{ strtoupper($sectionName) }}
                                    </span>
                                </td>

                                <td>
                                    <span class="enrolled-count-badge">
                                        <i class="fas fa-users"></i> {{ $enrolled }} {{ Str::plural('Student', $enrolled) }}
                                    </span>
                                </td>

                                <td>
                                    <span id="display-count-{{ $slug }}">
                                        <strong>{{ $block->total_subjects ?? 0 }}</strong> Active Subjects
                                    </span>
                                </td>

                                <td style="text-align: center;">
                                    <a href="{{ route('department_secretary.view_section', $sectionName) }}" class="btn-action-view">
                                        <i class="fas fa-users"></i> View Students
                                    </a>

                                    <form action="{{ route('department_secretary.bulk_enroll') }}" method="POST" style="display:inline-block; margin-left: 15px;">
                                        @csrf
                                        <input type="hidden" name="current_section" value="{{ $sectionName }}">

                                        <span style="font-size: 12px; color: #64748b; margin-right: 6px;">
                                            {{ $activePeriod ? $activePeriod->school_year . ' · ' . $activePeriod->semester : 'No active period' }}
                                        </span>

                                        <button type="submit" class="btn-helper"
                                                style="background: {{ $pending > 0 ? '#16a34a' : '#94a3b8' }}; padding: 5px 10px; font-size: 12px;"
                                                {{ $pending > 0 ? '' : 'disabled' }}
                                                onclick="return confirm('Bulk enroll all students of this block into its schedules for the active semester?')">
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

    document.addEventListener('DOMContentLoaded', function () {
        const studentSearch = document.getElementById('studentSearchInput');
        const filterPills = document.querySelectorAll('.filter-pill');
        const studentRows = document.querySelectorAll('.student-row');
        const noMatchRow = document.getElementById('studentNoMatchRow');
        const totalCountBadge = document.getElementById('studentTotalCount');
        const initialTotal = studentRows.length;

        let activeYear = '';

        function filterStudents() {
            const query = studentSearch ? studentSearch.value.toLowerCase().trim() : '';
            let visibleCount = 0;

            studentRows.forEach(row => {
                const content = row.textContent.toLowerCase();
                const rowYear = row.getAttribute('data-year');

                const matchesSearch = content.includes(query);
                const matchesYear = activeYear === '' || rowYear === activeYear;

                if (matchesSearch && matchesYear) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (totalCountBadge) {
                totalCountBadge.textContent = `${visibleCount} Students Found`;
            }

            if (noMatchRow) {
                if (visibleCount === 0 && initialTotal > 0) {
                    noMatchRow.style.display = '';
                } else {
                    noMatchRow.style.display = 'none';
                }
            }
        }

        filterPills.forEach(pill => {
            pill.addEventListener('click', function () {
                filterPills.forEach(p => p.classList.remove('active'));
                this.classList.add('active');
                activeYear = this.getAttribute('data-filter-value');
                filterStudents();
            });
        });

        if (studentSearch) {
            studentSearch.addEventListener('input', filterStudents);
        }

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

        function updateRowSemesterOptions(row) {
            const yearSelect = row.querySelector('.year-selector-js');
            const semSelect = row.querySelector('.sem-selector-js');
            const optSummer = row.querySelector('.opt-summer-js');

            if (!yearSelect || !semSelect || !optSummer) return;

            if (yearSelect.value === '3rd Year') {
                optSummer.style.display = '';
            } else {
                optSummer.style.display = 'none';
                if (semSelect.value === 'Summer') {
                    semSelect.value = '1st Semester';
                }
            }
        }

        function updateSubjectCounter(slug, rowElement) {
            const yearSelect = rowElement.querySelector('.year-selector-js');
            const semSelect = rowElement.querySelector('.sem-selector-js');
            
            if (!yearSelect || !semSelect) return;

            const selectedYear = yearSelect.value;
            const selectedSem = semSelect.value;
            
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

        document.querySelectorAll('.block-row').forEach(row => {
            const yearSelect = row.querySelector('.year-selector-js');
            const semSelect = row.querySelector('.sem-selector-js');
            const targetSlug = yearSelect ? yearSelect.getAttribute('data-target') : null;

            if (yearSelect) {
                yearSelect.addEventListener('change', function() {
                    updateRowSemesterOptions(row);
                    updateSubjectCounter(targetSlug, row);
                });
            }

            if (semSelect) {
                semSelect.addEventListener('change', function() {
                    updateSubjectCounter(targetSlug, row);
                });
            }

            updateRowSemesterOptions(row);
            updateSubjectCounter(targetSlug, row);
        });
    });
</script>

<style>
    .sa-container { font-family: 'Segoe UI', sans-serif; padding: 20px; max-width: 1300px; margin: 0 auto; }
    .sa-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #e2e8f0; padding-bottom: 12px; }
    .header-titles h2 { margin: 0; font-size: 24px; color: #0f172a; font-weight: 800; }
    .header-titles p { margin: 4px 0 0 0; font-size: 14px; color: #64748b; }

    .custom-search-input { width: 100%; padding: 9px 12px; font-size: 13.5px; color: #334155; border: 1px solid #cbd5e1; border-radius: 6px; outline: none; box-sizing: border-box; background: #fff; }
    .custom-search-input:focus { border-color: #cbd5e1 !important; box-shadow: none !important; outline: none !important; }

    .filter-pill { background: #f1f5f9; border: 1px solid #cbd5e1; color: #475569; padding: 5px 10px; font-size: 11.5px; font-weight: 600; border-radius: 20px; cursor: pointer; transition: all 0.15s ease; }
    .filter-pill:hover { background: #e2e8f0; color: #0f172a; }
    .filter-pill.active { background: #0f172a; color: #ffffff; border-color: #0f172a; box-shadow: 0 2px 4px rgba(15, 23, 42, 0.2); }

    .tabs-control-wrapper { display: flex; gap: 8px; margin-bottom: 20px; background: #f1f5f9; padding: 6px; border-radius: 8px; width: max-content; }
    .tab-toggle-btn { background: transparent; border: none; padding: 8px 18px; font-weight: 600; font-size: 13.5px; color: #475569; border-radius: 6px; cursor: pointer; transition: all 0.2s; }
    .tab-toggle-btn.active { background: #ffffff; color: #0f172a; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
    
    .tab-content-panel { display: none; }
    .tab-content-panel.active-panel { display: block; }

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

    /* Registrar-style Action Buttons */
    .btn-action-plot {
        display: inline-block;
        background-color: transparent;
        color: #0f172a;
        border: 1px solid #0f172a;
        padding: 6px 14px;
        border-radius: 6px;
        font-size: 12.5px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.15s ease;
    }
    .btn-action-plot:hover {
        background-color: #0f172a;
        color: white;
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.2);
    }

    .btn-action-view {
        display: inline-block;
        background-color: transparent;
        color: #2563eb;
        border: 1px solid #2563eb;
        padding: 6px 14px;
        border-radius: 6px;
        font-size: 12.5px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.15s ease;
    }
    .btn-action-view:hover {
        background-color: #2563eb;
        color: white;
    }
    
    .btn-action-delete {
        display: inline-block;
        background-color: transparent;
        color: #dc2626;
        border: 1px solid #dc2626;
        padding: 6px 14px;
        border-radius: 6px;
        font-size: 12.5px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .btn-action-delete:hover {
        background-color: #dc2626;
        color: white;
        box-shadow: 0 2px 6px rgba(220, 38, 38, 0.25);
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