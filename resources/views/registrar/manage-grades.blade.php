@extends('layouts.app')

@section('content')
<div class="sa-container">
    
    <div class="sa-header" style="display: flex; gap: 15px; align-items: flex-start; margin-bottom: 25px; border-bottom: 2px solid #e2e8f0; padding-bottom: 15px;">
        <div style="font-size: 32px; color: #1e2a5a; margin-top: 2px;">
            <i class="fas fa-graduation-cap"></i>
        </div>
        <div class="header-titles">
            <h2 style="margin: 0; font-size: 24px; color: #0f172a; font-weight: 800; line-height: 1.2;">Manage Student Grades</h2>
            <p style="margin: 6px 0 0 0; font-size: 14px; color: #64748b;">
                Monitor and view student grade submission statuses
                @if(isset($activeAcademicPeriod))
                    for <strong style="color: #1e293b;">{{ $activeAcademicPeriod->school_year }} ({{ $activeAcademicPeriod->semester }})</strong>
                @endif
            </p>
        </div>
    </div>

    <form id="filterForm" method="GET" action="{{ route('registrar.manage-grades') }}" class="filter-bar-card">
        <div style="flex: 0 0 auto;">
            <select id="termSelect" name="term" class="custom-solid-select" onchange="document.getElementById('filterForm').submit();">
                @foreach($termPeriods->sortBy('start_date') as $tp)
                    <option value="{{ strtolower($tp->term) }}" {{ strtolower($termFilter) == strtolower($tp->term) ? 'selected' : '' }}>
                        {{ ucfirst($tp->term) }} Term ({{ \Carbon\Carbon::parse($tp->start_date)->format('M d') }} - {{ \Carbon\Carbon::parse($tp->end_date)->format('M d, Y') }})
                    </option>
                @endforeach
                <option value="semestral" {{ strtolower($termFilter) == 'semestral' ? 'selected' : '' }}>
                    Semestral Grade (Final Average)
                </option>
            </select>
        </div>

        <div class="search-form" style="display: flex; gap: 10px; flex: 1;">
            <div class="search-input-wrapper" style="position: relative; flex: 1;">
                <i class="fas fa-search search-icon"></i>
                <input type="text" name="search" id="searchInput" class="search-input" placeholder="Search by Student ID, First Name, or Last Name..." value="{{ $search }}">
            </div>
        </div>
    </form>

    <div id="gradesResultContainer">
        <div class="sa-card">
            <div class="table-responsive">
                <table class="minimal-table">
                    <thead>
                        <tr>
                            <th style="width: 14%;">Student ID</th>
                            <th style="width: 26%;">Full Name</th>
                            <th style="width: 16%;">Course / Year</th>
                            <th class="text-center" style="width: 14%;">Term Average</th>
                            <th class="text-center" style="width: 15%;">Status</th>
                            <th class="text-center" style="width: 15%;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($students as $student)
                            <tr>
                                <td><strong class="code-highlight">{{ $student->student_id }}</strong></td>
                                <td><strong>{{ strtoupper($student->last_name) }}</strong>, {{ $student->first_name }}</td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 6px;">
                                        <span class="sem-tag">{{ $student->course }}</span>
                                        <span class="year-tag">Yr {{ $student->year_level }}</span>
                                    </div>
                                </td>

                                <td class="text-center">
                                    @if($student->average_equivalent !== null)
                                        <span class="equivalent-badge">{{ number_format($student->average_equivalent, 2) }}</span>
                                    @else
                                        <span class="text-muted-cell">—</span>
                                    @endif
                                </td>

                                <td class="text-center">
                                    @if($student->grade_status == 'Complete')
                                        <span class="status-pill status-submitted">
                                            <i class="fa-solid fa-circle-check"></i> Complete ({{ $student->total_submitted }}/{{ $student->total_enrolled }})
                                        </span>
                                    @elseif($student->grade_status == 'Partial')
                                        <span class="status-pill status-partial">
                                            <i class="fa-solid fa-spinner"></i> Partial ({{ $student->total_submitted }}/{{ $student->total_enrolled }})
                                        </span>
                                    @else
                                        <span class="status-pill status-pending">
                                            <i class="fa-solid fa-hourglass-half"></i> Pending (0/{{ $student->total_enrolled }})
                                        </span>
                                    @endif
                                </td>

                                <td class="text-center">
                                    <div class="action-buttons-wrapper">
                                        <button type="button" class="btn-action btn-view" onclick="openGradesModal('{{ $student->student_id }}', '{{ $student->first_name }} {{ $student->last_name }}', '{{ $termFilter }}')" title="View Subject Grades">
                                            <i class="fa-solid fa-eye"></i> View
                                        </button>

                                        @if($student->grade_status == 'Complete')
                                            @if($student->is_fully_sent)
                                                <span class="status-pill status-submitted" style="padding: 6px 12px; font-size: 11px;">
                                                    <i class="fa-solid fa-check-double"></i> Released
                                                </span>
                                            @else
                                                <form action="{{ route('registrar.release-grades', $student->student_id) }}" method="POST" style="display:inline;">
                                                    @csrf
                                                    <input type="hidden" name="term" value="{{ $termFilter }}">
                                                    <button type="submit" class="btn-action btn-release" title="Release {{ $termFilter }} grades to student portal">
                                                        <i class="fa-solid fa-share"></i> Release
                                                    </button>
                                                </form>
                                            @endif
                                        @else
                                            <button type="button" class="btn-action btn-locked" disabled title="Waiting for all enrolled subjects to be submitted for {{ $termFilter }} term">
                                                <i class="fa-solid fa-lock"></i> Locked
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 50px; color: #64748b;">
                                    <i class="fa-regular fa-folder-open" style="font-size: 30px; display: block; margin-bottom: 10px; color: #cbd5e1;"></i>
                                    No students found matching your criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($students->hasPages())
                <div class="pagination-footer">
                    {{ $students->appends(['search' => $search, 'term' => $termFilter])->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<div id="gradesModal" class="custom-modal-overlay" style="display: none;">
    <div class="custom-modal-card">
        <div class="custom-modal-header">
            <h3 id="modalStudentName">Student Grades</h3>
            <button type="button" onclick="closeGradesModal()" class="modal-close-btn">&times;</button>
        </div>
        <div class="custom-modal-body">
            <p style="font-size: 13px; color: #64748b; margin-top: 0;">Term: <strong id="modalTermLabel" style="text-transform: uppercase;"></strong></p>
            <div class="table-responsive">
                <table class="minimal-table" style="font-size: 13px;">
                    <thead>
                        <tr id="modalHeaderRow">
                            <th>Subject Code & Title</th>
                            <th class="text-center">Section</th>
                            <th class="text-center">Class Standing</th>
                            <th class="text-center">Major Exam</th>
                            <th class="text-center">Equivalent</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody id="modalGradesTableBody">
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const filterForm = document.getElementById('filterForm');
        let debounceTimer;

        if (searchInput && filterForm) {
            searchInput.addEventListener('input', function() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function() {
                    filterForm.submit();
                }, 500);
            });
        }
    });

    function openGradesModal(studentId, studentName, term) {
        document.getElementById('modalStudentName').innerText = studentName + ' (' + studentId + ')';
        document.getElementById('modalTermLabel').innerText = term;

        const isSemestral = term.toLowerCase() === 'semestral';
        const headerRow = document.getElementById('modalHeaderRow');
        const colCount = isSemestral ? 8 : 6;

        headerRow.innerHTML = isSemestral
            ? `<th>Subject Code & Title</th>
            <th class="text-center">Section</th>
            <th class="text-center">Prelim</th>
            <th class="text-center">Midterm</th>
            <th class="text-center">Semi-Final</th>
            <th class="text-center">Final</th>
            <th class="text-center">Semestral</th>
            <th class="text-center">Status</th>`
            : `<th>Subject Code & Title</th>
            <th class="text-center">Section</th>
            <th class="text-center">Class Standing</th>
            <th class="text-center">Major Exam</th>
            <th class="text-center">Equivalent</th>
            <th class="text-center">Status</th>`;

        const tbody = document.getElementById('modalGradesTableBody');
        tbody.innerHTML = `<tr><td colspan="${colCount}" style="text-align: center; padding: 25px;"><i class="fa-solid fa-spinner fa-spin" style="color: #1e2a5a; font-size: 18px;"></i><div style="margin-top: 8px; color: #64748b; font-size: 13px;">Loading grades...</div></td></tr>`;

        document.getElementById('gradesModal').style.display = 'flex';

        fetch(`/registrar/student-grades-json/${studentId}?term=${term}`)
            .then(response => response.json())
            .then(data => {
                tbody.innerHTML = '';
                if (!data || data.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="${colCount}" style="text-align: center; padding: 20px; color: #64748b;">No enrolled subjects found.</td></tr>`;
                    return;
                }

                data.forEach(item => {
                    let isSubmitted = item.is_submitted == 1;
                    let tr = document.createElement('tr');

                    if (isSemestral) {
                        const fmt = v => (v !== null && v !== undefined) ? parseFloat(v).toFixed(2) : '—';
                        let semestral = (isSubmitted && item.equivalent !== null) ? parseFloat(item.equivalent).toFixed(2) : '—';

                        tr.innerHTML = `
                            <td><strong>${item.subject_code_display}</strong><br><small style="color:#64748b;">${item.subject_title_display}</small></td>
                            <td class="text-center"><span class="sem-tag">${item.section_name || '—'}</span></td>
                            <td class="text-center">${fmt(item.prelim)}</td>
                            <td class="text-center">${fmt(item.midterm)}</td>
                            <td class="text-center">${fmt(item.semifinal)}</td>
                            <td class="text-center">${fmt(item.final)}</td>
                            <td class="text-center"><strong>${semestral}</strong></td>
                            <td class="text-center">
                                ${isSubmitted
                                    ? '<span class="status-pill status-submitted" style="font-size:10px; padding:3px 8px;">Submitted</span>'
                                    : '<span class="status-pill status-pending" style="font-size:10px; padding:3px 8px;">Pending</span>'}
                            </td>
                        `;
                    } else {
                        let classStanding = (isSubmitted && item.class_standing !== null) ? item.class_standing : '—';
                        let majorExam = (isSubmitted && item.major_exam !== null) ? item.major_exam : '—';
                        let equivalent = (isSubmitted && item.equivalent !== null) ? parseFloat(item.equivalent).toFixed(2) : '—';

                        tr.innerHTML = `
                            <td><strong>${item.subject_code_display}</strong><br><small style="color:#64748b;">${item.subject_title_display}</small></td>
                            <td class="text-center"><span class="sem-tag">${item.section_name || '—'}</span></td>
                            <td class="text-center">${classStanding}</td>
                            <td class="text-center">${majorExam}</td>
                            <td class="text-center"><strong>${equivalent}</strong></td>
                            <td class="text-center">
                                ${isSubmitted
                                    ? '<span class="status-pill status-submitted" style="font-size:10px; padding:3px 8px;">Submitted</span>'
                                    : '<span class="status-pill status-pending" style="font-size:10px; padding:3px 8px;">Pending</span>'}
                            </td>
                        `;
                    }

                    tbody.appendChild(tr);
                });
            })
            .catch(error => {
                console.error('Error fetching modal grades:', error);
                tbody.innerHTML = `<tr><td colspan="${colCount}" style="text-align: center; color: red; padding: 20px;">Failed to load grades.</td></tr>`;
            });
    }

    function closeGradesModal() {
        document.getElementById('gradesModal').style.display = 'none';
    }
</script>

<style>
    .sa-container { font-family: 'Segoe UI', sans-serif; padding: 20px 0; max-width: 1300px; margin: 0 auto; }

    .filter-bar-card {
        display: flex; flex-wrap: wrap; gap: 14px; align-items: center;
        background: white; border: 1px solid #e2e8f0; border-radius: 12px;
        padding: 16px 20px; margin-bottom: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
    }

    .custom-solid-select {
        appearance: none;
        -webkit-appearance: none;
        width: 100%;
        min-width: 320px;
        height: 42px;
        padding: 0 40px 0 16px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background-color: #f8fafc;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='2' stroke='%2364748b'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7' /%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 14px center;
        background-size: 16px;
        font-size: 13.5px;
        font-weight: 600;
        color: #334155;
        cursor: pointer;
        outline: none;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    .custom-solid-select:focus {
        border-color: #1e2a5a;
        box-shadow: 0 0 0 3px rgba(30,42,90,0.1);
        background-color: #ffffff;
    }

    .search-input-wrapper { position: relative; flex: 1; height: 42px; display: flex; align-items: center; }
    .search-icon { position: absolute; left: 16px; color: #94a3b8; font-size: 14px; }
    .search-input {
        width: 100%; height: 100%; box-sizing: border-box; padding: 0 16px 0 42px;
        border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; color: #334155; outline: none; background: #fff;
    }
    .search-input:focus { border-color: #1e2a5a; box-shadow: 0 0 0 3px rgba(30,42,90,0.08); }

    .sa-card { border-radius: 12px; border: 1px solid #e2e8f0; background: #ffffff; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }

    .minimal-table { width: 100%; border-collapse: collapse; font-size: 14px; }
    .minimal-table th { background: #f8fafc; padding: 14px 20px; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; text-align: left; }
    .minimal-table td { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
    .minimal-table tbody tr:hover { background-color: #f8fafc; }

    .code-highlight { color: #1e2a5a; font-family: monospace; font-size: 13.5px; }
    .year-tag { font-weight: 700; color: #0369a1; background: #e0f2fe; padding: 4px 8px; border-radius: 6px; font-size: 11px; border: 1px solid #bae6fd; }
    .sem-tag { font-weight: 600; color: #475569; background: #f8fafc; padding: 4px 8px; border-radius: 6px; font-size: 11px; border: 1px solid #e2e8f0; }
    .text-muted-cell { color: #94a3b8; }

    .equivalent-badge {
        font-weight: 800; color: #1e2a5a; background: #eef1fb;
        padding: 5px 14px; border-radius: 20px; font-size: 13px;
        border: 1px solid #c7d2fe; display: inline-block;
    }

    .status-pill {
        display: inline-flex; align-items: center; gap: 6px;
        font-size: 11.5px; font-weight: 700; padding: 5px 12px; border-radius: 20px;
        white-space: nowrap;
    }
    .status-submitted { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
    .status-partial { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
    .status-pending { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }

    .action-buttons-wrapper { display: flex; gap: 8px; justify-content: center; align-items: center; }
    .btn-action {
        padding: 6px 14px; border-radius: 6px; font-size: 12px; font-weight: 600;
        cursor: pointer; display: inline-flex; align-items: center; gap: 5px;
        border: 1px solid transparent; transition: all 0.15s ease; white-space: nowrap;
    }
    .btn-view { background: #f1f5f9; color: #334155; border-color: #cbd5e1; }
    .btn-view:hover { background: #e2e8f0; color: #0f172a; }
    .btn-release { background: #1e2a5a; color: white; border-color: #1e2a5a; }
    .btn-release:hover { background: #141d40; }
    .btn-locked { background: #f1f5f9; color: #94a3b8; border-color: #e2e8f0; cursor: not-allowed; }

    .custom-modal-overlay {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0, 0, 0, 0.5); z-index: 1000;
        display: flex; justify-content: center; align-items: center;
    }
    .custom-modal-card {
        background: white; width: 95%; max-width: 850px; border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1); overflow: hidden; animation: fadeIn 0.2s ease-in-out;
    }
    .custom-modal-header {
        display: flex; justify-content: space-between; align-items: center;
        padding: 16px 20px; background: #1e2a5a; color: white;
    }
    .custom-modal-header h3 { margin: 0; font-size: 16px; font-weight: 700; }
    .modal-close-btn { background: transparent; border: none; color: white; font-size: 22px; cursor: pointer; }
    .custom-modal-body { padding: 20px; max-height: 75vh; overflow-y: auto; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

    .pagination-footer {
        padding: 24px;
        border-top: 1px solid #e2e8f0;
        background: #ffffff;
    }

    .pagination-footer nav > div:first-of-type {
        display: none !important;
    }

    .pagination-footer nav > div:last-of-type {
        display: flex !important;
        width: 100% !important;
        justify-content: space-between !important;
        align-items: center !important;
    }

    .pagination-footer p {
        font-size: 15px !important;
        color: #64748b !important;
        margin: 0 !important;
    }

    .pagination-footer span.relative.z-0.inline-flex {
        display: flex !important;
        gap: 8px !important;
        box-shadow: none !important;
        align-items: center !important;
    }

    .pagination-footer span.relative.z-0.inline-flex > a,
    .pagination-footer span.relative.z-0.inline-flex > span {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 45px !important;
        height: 45px !important;
        border: 2px solid #cbd5e1 !important;
        border-radius: 8px !important;
        font-size: 16px !important;
        font-weight: 700 !important;
        color: #1e293b !important;
        background: #ffffff !important;
        text-decoration: none !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .pagination-footer span.relative.z-0.inline-flex > a:hover {
        background: #f1f5f9 !important;
        border-color: #1e2a5a !important;
        color: #1e2a5a !important;
    }

    .pagination-footer span.relative.z-0.inline-flex > span[aria-current="page"] > span {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 100% !important;
        height: 100% !important;
        background: #1e2a5a !important;
        color: #ffffff !important;
        border-radius: 5px !important; 
    }

    .pagination-footer span.relative.z-0.inline-flex > span[aria-current="page"] {
        border-color: #1e2a5a !important;
        padding: 0 !important;
    }

    .pagination-footer svg {
        width: 22px !important;
        height: 22px !important;
    }
</style>
@endsection