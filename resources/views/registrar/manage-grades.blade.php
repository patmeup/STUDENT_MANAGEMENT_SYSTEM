@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="rg-page">
    
    <header class="rg-header">
        <div>
            <h1 class="rg-title">Manage Student Grades</h1>
            <p class="rg-subtitle">
                Monitor and view student grade submission statuses
                @if(isset($activeAcademicPeriod))
                    for <strong style="color: var(--rg-ink);">{{ $activeAcademicPeriod->school_year }} ({{$activeAcademicPeriod->semester }})</strong>
                @endif
            </p>
        </div>
    </header>

    <form id="filterForm" method="GET" action="{{ route('registrar.manage-grades') }}" class="rg-filter-bar">
        <div style="flex: 0 0 auto;">
            <select id="termSelect" name="term" class="rg-select" onchange="document.getElementById('filterForm').submit();" style="min-width: 320px;">
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

        <div class="search-form" style="display: flex; flex: 1;">
            <div class="search-input-wrapper">
                <i class="fas fa-search search-icon"></i>
                <input type="text" name="search" id="searchInput" class="rg-input" placeholder="Search by Student ID, First Name, or Last Name..." value="{{ $search }}">
            </div>
        </div>
    </form>

    <div id="gradesResultContainer">
        <div class="rg-card">
            <div class="rg-table-wrap" style="border: none;">
                <table class="rg-table">
                    <thead>
                        <tr>
                            <th style="width: 13%;">Student ID</th>
                            <th style="width: 24%;">Full Name</th>
                            <th style="width: 9%;">Course</th>
                            <th style="width: 14%;">Year & Sem</th>
                            <th class="rg-text-center" style="width: 12%;">Term Average</th>
                            <th class="rg-text-center" style="width: 14%;">Status</th>
                            <th class="rg-text-center" style="width: 14%;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($students as $student)
                            <tr>
                                <td><span class="student-id-tag">{{ $student->student_id }}</span></td>
                                <td>
                                    <strong style="color: var(--rg-blue-dark); text-transform: uppercase;">{{ $student->last_name }}</strong>, {{$student->first_name }}
                                </td>
                                <td>
                                    <span class="rg-chip rg-chip-plain">{{ $student->course }}</span>
                                </td>
                                <td>
                                    <div style="font-weight: 600; font-size: 13px; color: var(--rg-ink);">{{ $student->year_level }}</div>
                                    <div style="font-size: 11.5px; color: var(--rg-muted); font-weight: 500; margin-top: 2px;">{{ $student->semester }}</div>
                                </td>

                                <td class="rg-text-center">
                                    @if($student->average_equivalent !== null)
                                        <strong style="color: var(--rg-ink);">{{ number_format($student->average_equivalent, 2) }}</strong>
                                    @else
                                        <span style="color: var(--rg-muted);">—</span>
                                    @endif
                                </td>

                                <td class="rg-text-center">
                                    @php
                                        $isSemestral = strtolower($termFilter) == 'semestral';
                                        $denominator =$student->total_required;
                                    @endphp

                                    @if($student->grade_status == 'Complete')
                                        <span class="status-chip status-ok">
                                            <i class="fa-solid fa-circle-check"></i> Complete ({{ $student->total_submitted }}/{{$denominator }})
                                        </span>
                                    @elseif($student->grade_status == 'Partial')
                                        <span class="status-chip status-info">
                                            <i class="fa-solid fa-spinner"></i> Partial ({{ $student->total_submitted }}/{{$denominator }})
                                        </span>
                                    @else
                                        <span class="status-chip status-warn">
                                            <i class="fa-solid fa-hourglass-half"></i> Pending (0/{{ $denominator }})
                                        </span>
                                    @endif
                                </td>

                                <td class="rg-text-center">
                                    <div style="display: flex; gap: 8px; justify-content: center; align-items: center;">
                                        <button type="button" class="rg-btn rg-btn-ghost" onclick="openGradesModal('{{ $student->student_id }}', '{{$student->first_name }} {{ $student->last_name }}', '{{$termFilter }}')" title="View Subject Grades">
                                            <i class="fa-solid fa-eye"></i> View
                                        </button>

                                        @if($student->grade_status == 'Complete')
                                            @if($student->is_fully_sent)
                                                <button type="button" class="rg-btn rg-btn-disabled" disabled title="Grades already released">
                                                    <i class="fa-solid fa-check"></i> Released
                                                </button>
                                            @else
                                                <form action="{{ route('registrar.release-grades', $student->student_id) }}" method="POST" style="display:inline;">
                                                    @csrf
                                                    <input type="hidden" name="term" value="{{ $termFilter }}">
                                                    <button type="submit" class="rg-btn rg-btn-primary" title="Release {{ $termFilter }} grades to student portal">
                                                        <i class="fa-solid fa-share"></i> Release
                                                    </button>
                                                </form>
                                            @endif
                                        @else
                                            <button type="button" class="rg-btn rg-btn-disabled" disabled title="Waiting for all enrolled subjects to be submitted for {{ $termFilter }} term">
                                                <i class="fa-solid fa-lock"></i> Locked
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 50px; color: var(--rg-muted);">
                                    <i class="fa-regular fa-folder-open" style="font-size: 30px; display: block; margin-bottom: 10px; color: #cbd5e1;"></i>
                                    No students found matching your criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($students->total() > 0)
                @php
                    $students->appends(['search' => $search, 'term' => $termFilter]);
                    $currentPage = $students->currentPage();
                    $lastPage = $students->lastPage();
                    $pageNumbers = collect([1, $lastPage])
                        ->merge(range(max(1, $currentPage - 1), min($lastPage, $currentPage + 1)))
                        ->unique()->sort()->values();
                @endphp
                <div class="rg-pagination-footer">
                    <p class="rg-pagination-info">
                        Showing <span>{{ $students->firstItem() }}</span> to <span>{{ $students->lastItem() }}</span> of <span>{{ $students->total() }}</span> results
                    </p>

                    @if($students->hasPages())
                        <nav class="rg-pagination" aria-label="Pagination">
                            {{-- Previous --}}
                            @if($students->onFirstPage())
                                <span class="rg-page-arrow disabled" aria-disabled="true">&lsaquo;</span>
                            @else
                                <a href="{{ $students->previousPageUrl() }}" class="rg-page-arrow" rel="prev" aria-label="Previous">&lsaquo;</a>
                            @endif

                            {{-- Page numbers --}}
                            @foreach($pageNumbers as $i => $page)
                                @if($i > 0 && $page - $pageNumbers[$i - 1] > 1)
                                    <span class="rg-page-dots">&hellip;</span>
                                @endif

                                @if($page == $currentPage)
                                    <span class="rg-page-num active" aria-current="page">{{ $page }}</span>
                                @else
                                    <a href="{{ $students->url($page) }}" class="rg-page-num">{{ $page }}</a>
                                @endif
                            @endforeach

                            {{-- Next --}}
                            @if($students->hasMorePages())
                                <a href="{{ $students->nextPageUrl() }}" class="rg-page-num rg-page-next" rel="next" aria-label="Next">&rsaquo;</a>
                            @else
                                <span class="rg-page-arrow disabled" aria-disabled="true">&rsaquo;</span>
                            @endif
                        </nav>
                    @endif
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
        <div class="custom-modal-body" style="padding-top: 15px;">
            <div id="modalTermLabel" style="display: none;"></div>
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
        
        const termLabelElem = document.getElementById('modalTermLabel');
        if (termLabelElem) {
            termLabelElem.innerText = term;
        }

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
                            <td class="text-center"><span class="sem-tag" style="background:#f8fafc; border:1px solid #e2e8f0; padding:4px 8px; border-radius:6px; font-size:11px;">${item.section_name || '—'}</span></td>
                            <td class="text-center">${fmt(item.prelim)}</td>
                            <td class="text-center">${fmt(item.midterm)}</td>
                            <td class="text-center">${fmt(item.semifinal)}</td>
                            <td class="text-center">${fmt(item.final)}</td>
                            <td class="text-center"><strong>${semestral}</strong></td>
                            <td class="text-center">
                                ${isSubmitted
                                    ? '<span style="background:#dcfce7; color:#15803d; border:1px solid #bbf7d0; padding:3px 8px; border-radius:20px; font-size:10px; font-weight:700;">Submitted</span>'
                                    : '<span style="background:#fef3c7; color:#b45309; border:1px solid #fde68a; padding:3px 8px; border-radius:20px; font-size:10px; font-weight:700;">Pending</span>'}
                            </td>
                        `;
                    } else {
                        let classStanding = (isSubmitted && item.class_standing !== null) ? item.class_standing : '—';
                        let majorExam = (isSubmitted && item.major_exam !== null) ? item.major_exam : '—';
                        let equivalent = (isSubmitted && item.equivalent !== null) ? parseFloat(item.equivalent).toFixed(2) : '—';

                        tr.innerHTML = `
                            <td><strong>${item.subject_code_display}</strong><br><small style="color:#64748b;">${item.subject_title_display}</small></td>
                            <td class="text-center"><span class="sem-tag" style="background:#f8fafc; border:1px solid #e2e8f0; padding:4px 8px; border-radius:6px; font-size:11px;">${item.section_name || '—'}</span></td>
                            <td class="text-center">${classStanding}</td>
                            <td class="text-center">${majorExam}</td>
                            <td class="text-center"><strong>${equivalent}</strong></td>
                            <td class="text-center">
                                ${isSubmitted
                                    ? '<span style="background:#dcfce7; color:#15803d; border:1px solid #bbf7d0; padding:3px 8px; border-radius:20px; font-size:10px; font-weight:700;">Submitted</span>'
                                    : '<span style="background:#fef3c7; color:#b45309; border:1px solid #fde68a; padding:3px 8px; border-radius:20px; font-size:10px; font-weight:700;">Pending</span>'}
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
    .rg-page {
        --rg-blue: #0033A0; --rg-blue-dark: #0A1F5C; --rg-blue-tint: #EAF0FF; --rg-gold: #F2B705;
        --rg-ink: #0B1B3A; --rg-muted: #5C6B8A; --rg-line: #E2E8F3; --rg-surface: #F7F9FD;
        --rg-green: #15803D; --rg-green-tint: #E8F7EE; --rg-red: #C62828;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: var(--rg-ink);
        max-width: 1300px; margin: 0 auto; padding: 24px 20px 40px;
    }

    .rg-header { display: flex; justify-content: flex-start; align-items: flex-end; gap: 20px; flex-wrap: wrap; padding-bottom: 18px; margin-bottom: 24px; border-bottom: 1px solid var(--rg-line); }
    .rg-title { margin: 0; font-size: 26px; font-weight: 800; letter-spacing: -0.2px; color: var(--rg-blue-dark); }
    .rg-subtitle { margin: 8px 0 0; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; font-size: 14px; color: var(--rg-muted); }

    .rg-filter-bar {
        display: flex; flex-wrap: wrap; gap: 14px; align-items: center;
        background: #fff; border: 1px solid var(--rg-line); border-radius: 10px;
        padding: 16px 20px; margin-bottom: 24px; box-shadow: 0 1px 2px rgba(10, 31, 92, 0.05);
    }

    .rg-select {
        width: 100%; box-sizing: border-box; height: 42px; padding: 0 40px 0 16px;
        font-size: 13.5px; font-weight: 600; color: var(--rg-ink); background-color: var(--rg-surface);
        border: 1px solid var(--rg-line); border-radius: 8px; cursor: pointer; outline: none;
        appearance: none; -webkit-appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='2' stroke='%235C6B8A'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7' /%3E%3C/svg%3E");
        background-repeat: no-repeat; background-position: right 14px center; background-size: 16px;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    .rg-select:focus { border-color: var(--rg-blue); box-shadow: 0 0 0 3px rgba(0, 51, 160, 0.14); background-color: #fff; }

    .search-input-wrapper { position: relative; width: 100%; }
    .search-icon { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--rg-muted); font-size: 14px; }
    .rg-input {
        width: 100%; box-sizing: border-box; height: 42px; padding: 0 16px 0 42px;
        font-size: 13.5px; color: var(--rg-ink); background: #fff;
        border: 1px solid var(--rg-line); border-radius: 8px; outline: none;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    .rg-input:focus { border-color: var(--rg-blue); box-shadow: 0 0 0 3px rgba(0, 51, 160, 0.14); }

    .rg-card { background: #fff; border: 1px solid var(--rg-line); border-radius: 10px; overflow: hidden; box-shadow: 0 1px 2px rgba(10, 31, 92, 0.05); }
    
    .rg-table-wrap { overflow-x: auto; }
    .rg-table { width: 100%; border-collapse: collapse; font-size: 14px; }
    .rg-table th { background: var(--rg-surface); padding: 12px 20px; font-size: 12px; font-weight: 700; color: var(--rg-muted); text-transform: uppercase; text-align: left; border-bottom: 1px solid var(--rg-line); white-space: nowrap; }
    .rg-table td { padding: 14px 20px; border-bottom: 1px solid #EEF2F9; color: #26345A; vertical-align: middle; font-weight: 500; }
    .rg-table tbody tr:last-child td { border-bottom: none; }
    .rg-table tbody tr:hover { background: #F5F8FF; }

    .rg-text-center { text-align: center !important; }

    .student-id-tag { font-family: ui-monospace, monospace; font-weight: 700; color: var(--rg-blue); background: var(--rg-blue-tint); padding: 3px 9px; border-radius: 6px; font-size: 12.5px; border: 1px solid #CFDBFB; }

    .rg-chip { display: inline-block; padding: 3px 10px; border-radius: 6px; font-size: 11.5px; font-weight: 700; white-space: nowrap; border: 1px solid transparent; }
    .rg-chip-plain { background: var(--rg-surface); color: #3B4A6B; border-color: var(--rg-line); }

    .status-chip { display: inline-flex; align-items: center; gap: 6px; padding: 4px 11px; border-radius: 16px; font-size: 11.5px; font-weight: 700; border: 1px solid transparent; white-space: nowrap; }
    .status-ok    { background: var(--rg-green-tint); color: var(--rg-green); border-color: #BFE8CE; }
    .status-warn  { background: #FEF3C7; color: #92400E; border-color: #FDE68A; }
    .status-info  { background: #E0F2FE; color: #0369A1; border-color: #BAE6FD; }

    .rg-btn { display: inline-flex; align-items: center; gap: 7px; padding: 7px 14px; border-radius: 7px; font-size: 12.5px; font-weight: 700; text-decoration: none; cursor: pointer; border: 1px solid transparent; transition: background .15s, color .15s, border-color .15s; white-space: nowrap; line-height: 1.2; }
    .rg-btn-primary { background: var(--rg-blue); color: #fff; }
    .rg-btn-primary:hover { background: var(--rg-blue-dark); color: #fff; }
    .rg-btn-ghost { background: #fff; color: var(--rg-blue); border-color: #B9C8F0; }
    .rg-btn-ghost:hover { background: var(--rg-blue-tint); color: var(--rg-blue-dark); }
    .rg-btn-disabled { background: var(--rg-surface); color: var(--rg-muted); border-color: var(--rg-line); cursor: not-allowed; }

    .rg-pagination-footer { padding: 18px 24px; border-top: 1px solid var(--rg-line); background: #fff; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
    .rg-pagination-info { margin: 0; font-size: 14px; color: var(--rg-muted); }
    .rg-pagination-info span { color: var(--rg-blue); font-weight: 500; }
    .rg-pagination { display: flex; align-items: center; gap: 6px; }
    .rg-page-num, .rg-page-arrow {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 32px; height: 32px; padding: 0 6px; box-sizing: border-box;
        font-size: 14px; font-weight: 600; line-height: 1; text-decoration: none;
        border-radius: 6px; border: 1px solid transparent; color: var(--rg-blue); background: #fff;
        transition: background 0.15s, border-color 0.15s, color 0.15s;
    }
    .rg-page-num { border-color: var(--rg-line); }
    .rg-page-num:hover { background: var(--rg-blue-tint); border-color: #B9C8F0; color: var(--rg-blue-dark); }
    .rg-page-num.active { background: var(--rg-blue); border-color: var(--rg-blue); color: #fff; cursor: default; }
    .rg-page-arrow { font-size: 18px; font-weight: 500; color: var(--rg-ink); }
    a.rg-page-arrow:hover { background: var(--rg-blue-tint); color: var(--rg-blue-dark); }
    .rg-page-arrow.disabled { color: var(--rg-ink); opacity: 0.55; cursor: default; }
    .rg-page-dots { color: var(--rg-muted); padding: 0 2px; user-select: none; }
    @media (max-width: 600px) { .rg-pagination-footer { justify-content: center; } }

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
    
    .table-responsive { overflow-x: auto; }
    .minimal-table { width: 100%; border-collapse: collapse; font-size: 14px; }
    .minimal-table th { background: #f8fafc; padding: 14px 20px; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; text-align: left; }
    .minimal-table td { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
    .minimal-table tbody tr:hover { background-color: #f8fafc; }
    .text-center { text-align: center; }
    
    @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
</style>
@endsection