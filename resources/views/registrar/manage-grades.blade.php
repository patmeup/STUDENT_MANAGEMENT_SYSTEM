@extends('layouts.app')

@section('content')
<div class="sa-container">
    <div class="sa-header">
        <div class="header-titles">
            <h2><i class="fas fa-graduation-cap"></i> Manage Student Grades</h2>
            <p>
                Monitor and view student grade submission statuses
                @if(isset($activeAcademicPeriod))
                    for <strong>{{ $activeAcademicPeriod->school_year }} ({{ $activeAcademicPeriod->semester }})</strong>
                @endif
            </p>
        </div>
    </div>

    <div class="filter-bar-card">
        <div class="term-select-wrapper">
            <i class="fa-regular fa-calendar-days"></i>
            <select id="termSelect" class="term-select">
                @foreach($termPeriods->sortBy('start_date') as $tp)
                    <option value="{{ $tp->term }}" {{ $termFilter == $tp->term ? 'selected' : '' }}>
                        {{ ucfirst($tp->term) }} Term ({{ \Carbon\Carbon::parse($tp->start_date)->format('M d') }} - {{ \Carbon\Carbon::parse($tp->end_date)->format('M d, Y') }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="search-form" style="display: flex; gap: 10px; flex: 1;">
            <div class="search-input-wrapper" style="position: relative; flex: 1;">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="searchInput" class="search-input" placeholder="Search by Student ID, First Name, or Last Name..." value="{{ request('search') }}">
            </div>
        </div>
    </div>

    <div id="gradesResultContainer">
        <div class="sa-card">
            <div class="table-responsive">
                <table class="minimal-table">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Full Name</th>
                            <th>Course / Year</th>
                            <th class="text-center">Term Average (GWA)</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($students as $student)
                            <tr>
                                <td><strong class="code-highlight">{{ $student->student_id }}</strong></td>
                                <td><strong>{{ strtoupper($student->last_name) }}</strong>, {{ $student->first_name }}</td>
                                <td>
                                    <span class="sem-tag">{{ $student->course }}</span>
                                    <span class="year-tag">Yr {{ $student->year_level }}</span>
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
                                        <span class="status-pill status-partial" style="background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd;">
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
                                                <span class="status-pill status-submitted" style="padding: 5px 10px; font-size: 11px;">
                                                    <i class="fa-solid fa-check-double"></i> Sent
                                                </span>
                                            @else
                                                <form action="{{ route('registrar.release-grades', $student->student_id) }}" method="POST" style="display:inline;">
                                                    @csrf
                                                    <input type="hidden" name="term" value="{{ $termFilter }}">
                                                    <button type="submit" class="btn-action btn-release" title="Release grades to student portal">
                                                        <i class="fa-solid fa-share"></i> Release
                                                    </button>
                                                </form>
                                            @endif
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
                    {{ $students->appends(['search' => request('search'), 'term' => $termFilter])->links() }}
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
                        <tr>
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
        const termSelect = document.getElementById('termSelect');
        const resultContainer = document.getElementById('gradesResultContainer');
        let debounceTimer;

        function fetchFilteredResults() {
            const searchValue = searchInput.value;
            const termValue = termSelect.value;
            const url = `{{ route('registrar.manage-grades') }}?search=${encodeURIComponent(searchValue)}&term=${encodeURIComponent(termValue)}`;

            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newContent = doc.getElementById('gradesResultContainer');
                if (newContent) {
                    resultContainer.innerHTML = newContent.innerHTML;
                }
            })
            .catch(error => console.error('Error updating grades list:', error));
        }

        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(fetchFilteredResults, 300);
        });

        termSelect.addEventListener('change', function() {
            fetchFilteredResults();
        });
    });

    function openGradesModal(studentId, studentName, term) {
        document.getElementById('modalStudentName').innerText = studentName + ' (' + studentId + ')';
        document.getElementById('modalTermLabel').innerText = term;
        
        const tbody = document.getElementById('modalGradesTableBody');
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 20px;">Loading grades...</td></tr>';
        
        document.getElementById('gradesModal').style.display = 'flex';

        fetch(`/registrar/student-grades-json/${studentId}?term=${term}`)
            .then(response => response.json())
            .then(data => {
                tbody.innerHTML = '';
                if (data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 20px; color: #64748b;">No enrolled subjects found.</td></tr>';
                    return;
                }

                data.forEach(item => {
                    let isSubmitted = item.is_submitted == 1;
                    let tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td><strong>${item.subject_code}</strong><br><small style="color:#64748b;">${item.subject_title}</small></td>
                        <td class="text-center"><span class="sem-tag">${item.section_name || '—'}</span></td>
                        <td class="text-center">${isSubmitted ? item.class_standing : '—'}</td>
                        <td class="text-center">${isSubmitted ? item.major_exam : '—'}</td>
                        <td class="text-center"><strong>${isSubmitted && item.equivalent !== null ? parseFloat(item.equivalent).toFixed(2) : '—'}</strong></td>
                        <td class="text-center">
                            ${isSubmitted 
                                ? '<span class="status-pill status-submitted" style="font-size:10px; padding:3px 8px;">Submitted</span>' 
                                : '<span class="status-pill status-pending" style="font-size:10px; padding:3px 8px;">Pending</span>'}
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            })
            .catch(error => {
                console.error('Error:', error);
                tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: red; padding: 20px;">Failed to load grades.</td></tr>';
            });
    }

    function closeGradesModal() {
        document.getElementById('gradesModal').style.display = 'none';
    }
</script>

<style>
    .sa-container { font-family: 'Segoe UI', sans-serif; padding: 20px; max-width: 1300px; margin: 0 auto; }
    .sa-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #e2e8f0; padding-bottom: 12px; }
    .header-titles h2 { margin: 0; font-size: 24px; color: #0f172a; font-weight: 800; display: flex; align-items: center; gap: 10px; }
    .header-titles p { margin: 6px 0 0 0; font-size: 14px; color: #64748b; }
    .header-titles p strong { color: #1e293b; }

    .filter-bar-card {
        display: flex; flex-wrap: wrap; gap: 12px; align-items: center;
        background: white; border: 1px solid #e2e8f0; border-radius: 12px;
        padding: 16px 20px; margin-bottom: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
    }
    .term-select-wrapper {
        display: flex; align-items: center; gap: 10px;
        border: 1px solid #cbd5e1; border-radius: 50px; padding: 8px 18px;
        background: #f8fafc; color: #64748b;
    }
    .term-select { border: none; background: transparent; font-weight: 600; color: #334155; font-size: 13.5px; outline: none; cursor: pointer; }

    .search-input-wrapper { position: relative; flex: 1; }
    .search-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 13px; }
    .search-input {
        width: 100%; box-sizing: border-box; padding: 9px 12px 9px 36px;
        border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; color: #334155; outline: none;
    }
    .search-input:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.08); }

    .sa-card { border-radius: 12px; border: 1px solid #e2e8f0; background: #ffffff; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }

    .minimal-table { width: 100%; border-collapse: collapse; font-size: 14px; }
    .minimal-table th { background: #f8fafc; padding: 14px 20px; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; text-align: left; }
    .minimal-table td { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
    .minimal-table tbody tr:hover { background-color: #f8fafc; }

    .code-highlight { color: #2563eb; font-family: monospace; font-size: 13.5px; }
    .year-tag { font-weight: 700; color: #0369a1; background: #e0f2fe; padding: 4px 8px; border-radius: 6px; font-size: 11px; border: 1px solid #bae6fd; margin-left: 6px; }
    .sem-tag { font-weight: 600; color: #475569; background: #f8fafc; padding: 4px 8px; border-radius: 6px; font-size: 11px; border: 1px solid #e2e8f0; }
    .text-muted-cell { color: #94a3b8; }

    .equivalent-badge {
        font-weight: 800; color: #1e2a5a; background: #eef1fb;
        padding: 5px 14px; border-radius: 20px; font-size: 13px;
        border: 1px solid #c7d2fe;
    }

    .status-pill {
        display: inline-flex; align-items: center; gap: 6px;
        font-size: 11.5px; font-weight: 700; padding: 6px 14px; border-radius: 20px;
    }

    .status-submitted { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
    .status-pending { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }

    .action-buttons-wrapper {
        display: flex;
        gap: 6px;
        justify-content: center;
        align-items: center;
    }

    .btn-action {
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 11.5px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        border: 1px solid transparent;
        transition: background 0.15s ease;
        text-decoration: none;
    }

    .btn-view {
        background: #f1f5f9;
        color: #334155;
        border-color: #cbd5e1;
    }
    .btn-view:hover {
        background: #e2e8f0;
        color: #0f172a;
    }

    .btn-release {
        background: #1e2a5a;
        color: white;
        border-color: #1e2a5a;
    }
    .btn-release:hover {
        background: #141d40;
    }

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

    .custom-modal-body .minimal-table th, 
    .custom-modal-body .minimal-table td {
        padding: 12px 16px;
    }

    .custom-modal-body .sem-tag {
        white-space: nowrap;
        display: inline-block;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .pagination-footer {
        display: flex;
        justify-content: flex-end;
        padding: 16px 20px;
        border-top: 1px solid #f1f5f9;
        background: white;
    }
    .pagination-footer nav {
        display: flex;
        align-items: center;
    }
    .pagination-footer svg {
        width: 16px;
        height: 16px;
    }
    .pagination-footer nav div:first-child {
        display: none;
    }
    .pagination-footer nav ul {
        display: flex;
        gap: 4px;
        list-style: none;
        padding: 0;
        margin: 0;
        align-items: center;
    }
    .pagination-footer nav ul li a, 
    .pagination-footer nav ul li span {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 32px;
        height: 32px;
        padding: 0 10px;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        color: #475569;
        font-size: 13px;
        text-decoration: none;
        transition: 0.15s;
        background: #ffffff;
    }
    .pagination-footer nav ul li a:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
        color: #1e2a5a;
    }
    .pagination-footer nav ul li.active span {
        background: #1e2a5a;
        color: white;
        border-color: #1e2a5a;
    }
    .pagination-footer nav ul li.disabled span {
        color: #94a3b8;
        background: #f8fafc;
        border-color: #e2e8f0;
    }
</style>
@endsection