@extends('layouts.app')

@section('content')
<div class="sa-container">
    <div class="sa-header">
        <div class="header-titles">
            <h2><i class="fas fa-user-clock"></i> Faculty Grade Submission Status</h2>
            <p>
                Track which classes have complete, partial, or missing grade submissions
                @if($activeAcademicPeriod)
                    for <strong>{{ $activeAcademicPeriod->school_year }} ({{ $activeAcademicPeriod->semester }})</strong>
                @endif
            </p>
        </div>
    </div>

    <div class="filter-bar-card">
        <form method="GET" action="{{ route('dean.faculty-grade-status') }}" class="term-form">
            <input type="hidden" name="search" value="{{ $search }}">
            <div class="term-select-wrapper">
                <i class="fa-regular fa-calendar-days"></i>
                <select name="term" class="term-select" onchange="this.form.submit()">
                    @foreach($termPeriods as $tp)
                        <option value="{{ $tp->term }}" {{ $termFilter == $tp->term ? 'selected' : '' }}>
                            {{ ucfirst($tp->term) }} Term ({{ \Carbon\Carbon::parse($tp->start_date)->format('M d') }} - {{ \Carbon\Carbon::parse($tp->end_date)->format('M d, Y') }})
                        </option>
                    @endforeach
                </select>
            </div>
        </form>

        <form method="GET" action="{{ route('dean.faculty-grade-status') }}" class="search-form">
            <input type="hidden" name="term" value="{{ $termFilter }}">
            <div class="search-input-wrapper">
                <i class="fas fa-search search-icon"></i>
                <input type="text" name="search" class="search-input" placeholder="Search by professor, subject, or section..." value="{{ $search }}">
            </div>
            <button type="submit" class="btn-filter-submit">
                <i class="fa-solid fa-filter"></i> Filter
            </button>
        </form>
    </div>

    <div class="status-legend">
        <span class="legend-chip legend-complete"><i class="fa-solid fa-circle-check"></i> Complete</span>
        <span class="legend-chip legend-partial"><i class="fa-solid fa-triangle-exclamation"></i> Partial</span>
        <span class="legend-chip legend-not-started"><i class="fa-solid fa-circle-xmark"></i> Not Started</span>
    </div>

    <div class="sa-card">
        <div class="table-responsive">
            <table class="minimal-table">
                <thead>
                    <tr>
                        <th>Professor</th>
                        <th>Subject / Section</th>
                        <th class="text-center">Enrolled</th>
                        <th class="text-center">Submitted</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($report as $class)
                        <tr>
                            <td>
                                @if($class->prof_first_name && $class->prof_last_name)
                                    <strong>{{ strtoupper($class->prof_last_name) }}</strong>, {{ $class->prof_first_name }}
                                @else
                                    <span class="text-muted-cell">TBA</span>
                                @endif
                            </td>
                            <td>
                                <span class="code-highlight">{{ strtoupper($class->subject_code) }}</span>
                                — {{ $class->subject_title }}
                                <span class="year-tag">{{ strtoupper($class->section_name) }}</span>
                            </td>
                            <td class="text-center fw-medium">{{ $class->enrolled_count }}</td>
                            <td class="text-center fw-medium">{{ $class->submitted_count }}</td>
                            <td class="text-center">
                                @if($class->status === 'complete')
                                    <span class="status-pill status-submitted"><i class="fa-solid fa-circle-check"></i> Complete</span>
                                @elseif($class->status === 'partial')
                                    <span class="status-pill status-partial"><i class="fa-solid fa-triangle-exclamation"></i> Partial ({{ $class->submitted_count }}/{{ $class->enrolled_count }})</span>
                                @elseif($class->status === 'not_started')
                                    <span class="status-pill status-pending"><i class="fa-solid fa-circle-xmark"></i> Not Started</span>
                                @else
                                    <span class="status-pill status-none"><i class="fa-solid fa-ban"></i> No Students</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn-breakdown" onclick="openBreakdownModal('{{ $class->schedule_id }}', '{{ strtoupper($class->subject_code) }} - {{ $class->subject_title }} ({{ strtoupper($class->section_name) }})', '{{ $termFilter }}')">
                                    <i class="fa-solid fa-eye"></i> Breakdown
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 50px; color: #64748b;">
                                <i class="fa-regular fa-folder-open" style="font-size: 30px; display: block; margin-bottom: 10px; color: #cbd5e1;"></i>
                                No class schedules found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- GRADE BREAKDOWN MODAL -->
<div id="breakdownModal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <div>
                <h3 id="modalSubjectTitle" style="margin: 0; font-size: 18px; color: #0f172a;">Grade Breakdown</h3>
                <p id="modalTermSub" style="margin: 4px 0 0 0; font-size: 12px; color: #64748b;"></p>
            </div>
            <button type="button" class="modal-close" onclick="closeBreakdownModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div id="modalLoading" style="text-align: center; padding: 30px; color: #64748b;">
                <i class="fa-solid fa-spinner fa-spin" style="font-size: 24px; margin-bottom: 8px;"></i>
                <p>Loading student grades...</p>
            </div>
            <div id="modalTableContainer" style="display: none; max-height: 400px; overflow-y: auto;">
                <table class="minimal-table">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th class="text-center">Class Standing</th>
                            <th class="text-center">Major Exam</th>
                            <th class="text-center">Equivalent</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody id="modalTableBody">
                        <!-- Dynamic content via JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    function openBreakdownModal(scheduleId, subjectInfo, term) {
        document.getElementById('breakdownModal').style.display = 'flex';
        document.getElementById('modalSubjectTitle').innerText = subjectInfo;
        document.getElementById('modalTermSub').innerText = 'Term: ' + term.toUpperCase();
        
        document.getElementById('modalLoading').style.display = 'block';
        document.getElementById('modalTableContainer').style.display = 'none';
        document.getElementById('modalTableBody').innerHTML = '';

        // Fetch grades summary via AJAX route (Gagawin mo itong route sa web.php)
        fetch(`/dean/grade-breakdown/${scheduleId}?term=${term}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('modalLoading').style.display = 'none';
                document.getElementById('modalTableContainer').style.display = 'block';
                
                let tbody = document.getElementById('modalTableBody');
                if (data.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="5" style="text-align: center; padding: 20px; color: #64748b;">No enrolled students found.</td></tr>`;
                    return;
                }

                data.forEach(row => {
                    let statusBadge = row.is_submitted == 1 
                        ? `<span class="status-pill status-submitted">Submitted</span>` 
                        : `<span class="status-pill status-pending">Pending</span>`;

                    tbody.innerHTML += `
                        <tr>
                            <td><strong>${row.last_name}, ${row.first_name}</strong></td>
                            <td class="text-center">${row.class_standing ?? '—'}</td>
                            <td class="text-center">${row.major_exam ?? '—'}</td>
                            <td class="text-center fw-bold text-primary">${row.equivalent ?? '—'}</td>
                            <td class="text-center">${statusBadge}</td>
                        </tr>
                    `;
                });
            })
            .catch(error => {
                document.getElementById('modalLoading').innerHTML = `<p style="color: #dc2626;">Failed to load grades. Please try again.</p>`;
            });
    }

    function closeBreakdownModal() {
        document.getElementById('breakdownModal').style.display = 'none';
    }
</script>

<style>
    .sa-container { font-family: 'Segoe UI', sans-serif; padding: 20px; max-width: 1300px; margin: 0 auto; }
    .sa-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #e2e8f0; padding-bottom: 12px; }
    .header-titles h2 { margin: 0; font-size: 24px; color: #0f172a; font-weight: 800; display: flex; align-items: center; gap: 10px; }
    .header-titles p { margin: 6px 0 0 0; font-size: 14px; color: #64748b; }
    .header-titles p strong { color: #1e293b; }

    .filter-bar-card {
        display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 12px;
        background: white; border: 1px solid #e2e8f0; border-radius: 12px;
        padding: 16px 20px; margin-bottom: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
    }
    .term-select-wrapper {
        display: flex; align-items: center; gap: 10px;
        border: 1px solid #cbd5e1; border-radius: 50px; padding: 8px 18px;
        background: #f8fafc; color: #64748b;
    }
    .term-select { border: none; background: transparent; font-weight: 600; color: #334155; font-size: 13.5px; outline: none; cursor: pointer; }

    .search-form { display: flex; gap: 10px; flex: 1; min-width: 280px; margin-top: 12px; }
    .search-input-wrapper { position: relative; flex: 1; }
    .search-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 13px; }
    .search-input {
        width: 100%; box-sizing: border-box; padding: 9px 12px 9px 36px;
        border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13.5px; color: #334155; outline: none;
    }
    .search-input:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.08); }
    .btn-filter-submit {
        background: #1e2a5a; color: white; border: none; padding: 9px 18px;
        border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer;
        display: inline-flex; align-items: center; gap: 6px;
    }
    .btn-filter-submit:hover { background: #141d40; }

    .status-legend { display: flex; gap: 14px; margin-bottom: 16px; padding: 0 4px; }
    .legend-chip { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 600; }
    .legend-complete { color: #15803d; }
    .legend-partial { color: #b45309; }
    .legend-not-started { color: #dc2626; }

    .sa-card { border-radius: 12px; border: 1px solid #e2e8f0; background: #ffffff; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }

    .minimal-table { width: 100%; border-collapse: collapse; font-size: 14px; }
    .minimal-table th { background: #f8fafc; padding: 14px 20px; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; text-align: left; }
    .minimal-table td { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
    .minimal-table tbody tr:hover { background-color: #f8fafc; }

    .code-highlight { color: #2563eb; font-family: monospace; font-size: 13.5px; font-weight: 700; }
    .year-tag { font-weight: 700; color: #0369a1; background: #e0f2fe; padding: 4px 8px; border-radius: 6px; font-size: 11px; border: 1px solid #bae6fd; margin-left: 6px; }
    .text-muted-cell { color: #94a3b8; }

    .status-pill { display: inline-flex; align-items: center; gap: 6px; font-size: 11.5px; font-weight: 700; padding: 6px 14px; border-radius: 20px; white-space: nowrap; }
    .status-submitted { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
    .status-partial { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
    .status-pending { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
    .status-none { background: #f1f5f9; color: #94a3b8; border: 1px solid #e2e8f0; }

    .btn-breakdown {
        background: #f1f5f9; color: #1e293b; border: 1px solid #cbd5e1; padding: 6px 12px;
        border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 5px;
    }
    .btn-breakdown:hover { background: #e2e8f0; color: #0f172a; }

    /* Modal Styles */
    .modal-overlay {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6);
        display: flex; justify-content: center; align-items: center; z-index: 1000;
    }
    .modal-content {
        background: white; width: 100%; max-width: 800px; border-radius: 12px; overflow: hidden;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); animation: modalFadeIn 0.2s ease-out;
    }
    .modal-header {
        padding: 16px 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0;
        display: flex; justify-content: space-between; align-items: center;
    }
    .modal-close { background: none; border: none; font-size: 24px; cursor: color: #64748b; cursor: pointer; }
    .modal-close:hover { color: #0f172a; }
    .modal-body { padding: 10px 0; }

    @keyframes modalFadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>
@endsection