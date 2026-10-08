@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

@php
    $selectedType   = strtolower((string) request('student_type'));
    $hasActiveFilter = request()->filled('search') || request()->filled('course') || request()->filled('student_type');

    $typeClasses = [
        'new'            => 'new',
        'transferee'     => 'transferee',
        'cross-enrollee' => 'cross',
        'old'            => 'old',
    ];
@endphp

<div class="rg-page">

    <header class="rg-header">
        <div>
            <h1 class="rg-title">Enrolled Student List</h1>
            <p class="rg-subtitle">View, search, and manage all active student records.</p>
        </div>
    </header>

    @if(session('success'))
        <div class="rg-alert rg-alert-success" role="status">
            <i class="fas fa-check-circle"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="rg-alert rg-alert-error" role="alert">
            <i class="fas fa-exclamation-circle"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <div class="rg-card">

        <form id="liveFilterForm" action="{{ route('registrar.manage-students') }}" method="GET" class="rg-toolbar">

            <div class="rg-search">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" name="search" value="{{ request('search') }}"
                       placeholder="Search ID, first name, or last name..." autocomplete="off">
            </div>

            <div class="rg-field">
                <label for="courseFilter">Course</label>
                <select id="courseFilter" name="course" class="auto-submit-select">
                    <option value="">All Courses</option>
                    <option value="BSIT" {{ request('course') == 'BSIT' ? 'selected' : '' }}>BSIT</option>
                    <option value="BSCS" {{ request('course') == 'BSCS' ? 'selected' : '' }}>BSCS</option>
                </select>
            </div>

            <div class="rg-field">
                <label for="typeFilter">Entry Type</label>
                <select id="typeFilter" name="student_type" class="auto-submit-select">
                    <option value="">All Types</option>
                    <option value="New"            {{ $selectedType === 'new' ? 'selected' : '' }}>New</option>
                    <option value="Transferee"     {{ $selectedType === 'transferee' ? 'selected' : '' }}>Transferee</option>
                    <option value="Cross-Enrollee" {{ $selectedType === 'cross-enrollee' ? 'selected' : '' }}>Cross Enrollee</option>
                    <option value="Old"            {{ $selectedType === 'old' ? 'selected' : '' }}>Old</option>
                </select>
            </div>

            @if($hasActiveFilter)
                <a href="{{ route('registrar.manage-students') }}" class="rg-btn rg-btn-ghost rg-clear">
                    <i class="fas fa-times"></i> Clear filters
                </a>
            @endif

            <span class="rg-result-count">{{ $students->count() }} {{ $students->count() === 1 ? 'student' : 'students' }}</span>
        </form>

        <div class="table-responsive">
            <table class="rg-table">
                <thead>
                    <tr>
                        <th>Student Number</th>
                        <th>Full Name</th>
                        <th>Course</th>
                        <th>Year &amp; Sem</th>
                        <th>Entry Type</th>
                        <th class="rg-text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $student)
                        @php
                            $typeKey   = strtolower(trim((string) $student->student_type));
                            $typeClass = $typeClasses[$typeKey] ?? 'plain';
                        @endphp
                        <tr>
                            <td class="rg-nowrap">
                                <span class="student-id-tag">{{ $student->student_id }}</span>
                            </td>

                            <td class="student-name">
                                {{ $student->last_name }}, {{ $student->first_name }} {{ $student->middle_name }}
                            </td>

                            <td class="rg-nowrap">
                                <span class="rg-chip rg-chip-plain">{{ $student->course }}</span>
                            </td>

                            <td class="rg-nowrap">
                                <div class="yr-line">{{ $student->year_level }}</div>
                                <div class="sem-line">{{ $student->semester }}</div>
                            </td>

                            <td class="rg-nowrap">
                                <span class="type-badge type-{{ $typeClass }}">{{ $student->student_type }}</span>
                            </td>

                            <td class="rg-text-center rg-nowrap">
                                <div class="rg-actions">
                                    <a href="{{ route('registrar.students.profile', ['student_id' => $student->student_id]) }}"
                                       class="rg-btn rg-btn-outline-navy" title="View/Edit Full Profile">
                                        <i class="fas fa-folder-open"></i> View Profile
                                    </a>

                                    <form action="{{ route('registrar.students.destroy', ['student_id' => $student->student_id]) }}"
                                          method="POST"
                                          data-name="{{ $student->last_name }}, {{ $student->first_name }}"
                                          data-id="{{ $student->student_id }}"
                                          onsubmit="return confirmDelete(this);">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rg-btn rg-btn-danger-outline" title="Delete Student Record">
                                            <i class="fas fa-trash-alt"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-table-state">
                                @if($hasActiveFilter)
                                    <strong>No students match your search or filters.</strong>
                                    <small>Try a different keyword or clear the filters.</small>
                                @else
                                    <strong>No student records found in the database.</strong>
                                    <small>Go to "Manage Enrollment" to register a new student.</small>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .rg-page {
        --rg-blue: #0033A0;
        --rg-blue-dark: #0A1F5C;
        --rg-blue-tint: #EAF0FF;
        --rg-gold: #F2B705;
        --rg-ink: #0B1B3A;
        --rg-muted: #5C6B8A;
        --rg-line: #E2E8F3;
        --rg-surface: #F7F9FD;
        --rg-green: #15803D;
        --rg-green-tint: #E8F7EE;
        --rg-red: #C62828;

        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: var(--rg-ink);
        max-width: 1300px;
        margin: 0 auto;
        padding: 24px 20px 40px;
    }

    .rg-header { display: flex; justify-content: space-between; align-items: flex-end; gap: 20px; flex-wrap: wrap; padding-bottom: 18px; margin-bottom: 20px; border-bottom: 1px solid var(--rg-line); }
    .rg-title { margin: 0; font-size: 26px; font-weight: 800; letter-spacing: -0.2px; color: var(--rg-blue-dark); }
    .rg-subtitle { margin: 5px 0 0; font-size: 14px; color: var(--rg-muted); }

    .rg-alert { display: flex; gap: 10px; align-items: flex-start; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 13.5px; font-weight: 600; line-height: 1.45; }
    .rg-alert i { margin-top: 2px; }
    .rg-alert-success { background: var(--rg-green-tint); color: #14532D; border: 1px solid #BFE8CE; border-left: 4px solid var(--rg-green); }
    .rg-alert-error { background: #FDECEC; color: #8E1B1B; border: 1px solid #F5C6C6; border-left: 4px solid var(--rg-red); }

    .rg-card { background: #fff; border: 1px solid var(--rg-line); border-radius: 10px; overflow: hidden; box-shadow: 0 1px 2px rgba(10, 31, 92, 0.05); }
    .rg-toolbar { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 14px 18px; padding: 14px 20px; border-bottom: 1px solid var(--rg-line); background: var(--rg-surface); margin: 0; }

    .rg-search { position: relative; flex: 1 1 320px; max-width: 460px; }
    .rg-search i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #8A97B3; font-size: 13px; pointer-events: none; }
    .rg-search input { width: 100%; box-sizing: border-box; padding: 10px 12px 10px 34px; font-size: 13.5px; color: var(--rg-ink); background: #fff; border: 1px solid #CBD5E6; border-radius: 8px; height: 40px; }
    .rg-search input::placeholder { color: #8A97B3; }
    .rg-search input:focus, .rg-field select:focus { outline: none; border-color: var(--rg-blue); box-shadow: 0 0 0 3px rgba(0, 51, 160, 0.14); }

    .rg-field { display: flex; flex-direction: column; gap: 4px; }
    .rg-field label { font-size: 11.5px; font-weight: 700; color: var(--rg-muted); text-transform: uppercase; letter-spacing: 0.3px; }
    .rg-field select { min-width: 160px; height: 40px; padding: 0 12px; font-size: 13.5px; color: var(--rg-ink); background: #fff; border: 1px solid #CBD5E6; border-radius: 8px; cursor: pointer; }

    .rg-clear { height: 40px; box-sizing: border-box; }
    .rg-result-count { margin-left: auto; align-self: center; font-size: 12.5px; font-weight: 600; color: var(--rg-muted); }

    .rg-btn { display: inline-flex; align-items: center; gap: 7px; padding: 7px 14px; border-radius: 7px; font-size: 12.5px; font-weight: 700; text-decoration: none; cursor: pointer; border: 1px solid transparent; transition: background .15s, color .15s, border-color .15s; white-space: nowrap; line-height: 1.2; background: #fff; }
    .rg-btn-ghost { color: var(--rg-blue); border-color: #B9C8F0; }
    .rg-btn-ghost:hover { background: var(--rg-blue-tint); color: var(--rg-blue-dark); }
    .rg-btn-outline-navy { color: var(--rg-blue-dark); border-color: var(--rg-blue-dark); }
    .rg-btn-outline-navy:hover { background: var(--rg-blue-dark); color: #fff; }
    .rg-btn-danger-outline { color: var(--rg-red); border-color: #F0B4B4; }
    .rg-btn-danger-outline:hover { background: var(--rg-red); border-color: var(--rg-red); color: #fff; }

    .table-responsive { width: 100%; overflow-x: auto; }
    .rg-table { width: 100%; min-width: 820px; border-collapse: collapse; font-size: 14px; }
    .rg-table th { background: #fff; padding: 11px 20px; font-size: 12px; font-weight: 700; color: var(--rg-muted); text-align: left; border-bottom: 1px solid var(--rg-line); white-space: nowrap; text-transform: uppercase; letter-spacing: 0.3px; }
    .rg-table td { padding: 13px 20px; border-bottom: 1px solid #EEF2F9; color: #26345A; vertical-align: middle; }
    .rg-table tbody tr:last-child td { border-bottom: none; }
    .rg-table tbody tr:hover { background: #F5F8FF; }
    .rg-text-center { text-align: center !important; }
    .rg-nowrap { white-space: nowrap; }
    .rg-actions { display: flex; justify-content: center; align-items: center; gap: 8px; }
    .rg-actions form { margin: 0; }

    .student-id-tag { font-family: ui-monospace, monospace; font-weight: 700; color: var(--rg-blue); background: var(--rg-blue-tint); padding: 4px 9px; border-radius: 6px; font-size: 12.5px; border: 1px solid #CFDBFB; }
    .student-name { font-weight: 600; color: var(--rg-ink); text-transform: uppercase; }

    .rg-chip { display: inline-block; padding: 3px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; white-space: nowrap; }
    .rg-chip-plain { background: var(--rg-surface); color: #3B4A6B; border: 1px solid var(--rg-line); }

    .yr-line { font-size: 13px; font-weight: 700; color: var(--rg-ink); }
    .sem-line { font-size: 12px; font-weight: 500; color: var(--rg-muted); margin-top: 1px; }

    .type-badge { display: inline-block; padding: 3px 11px; border-radius: 16px; font-size: 11.5px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.3px; border: 1px solid transparent; }
    .type-new         { background: var(--rg-green-tint); color: var(--rg-green); border-color: #BFE8CE; }
    .type-transferee  { background: #FEF3C7; color: #92400E; border-color: #FDE68A; }
    .type-cross       { background: #E0F2FE; color: #0369A1; border-color: #BAE6FD; }
    .type-old         { background: #EEF2F9; color: #3B4A6B; border-color: #D5DCEB; }
    .type-plain       { background: var(--rg-surface); color: var(--rg-muted); border-color: var(--rg-line); }

    .empty-table-state { text-align: center; color: var(--rg-muted); padding: 44px 0 !important; }
    .empty-table-state strong { display: block; font-size: 15px; color: var(--rg-ink); margin-bottom: 4px; }
    .empty-table-state small { font-size: 13px; }

    @media (max-width: 720px) {
        .rg-header { align-items: flex-start; }
        .rg-result-count { margin-left: 0; }
        .rg-table th, .rg-table td { padding-left: 14px; padding-right: 14px; }
    }
</style>

<script>
function confirmDelete(form) {
    var name = form.dataset.name;
    var id   = form.dataset.id;
    return confirm('⚠ DELETE RECORD:\n\n' + name + ' (' + id + ')\n\nAre you sure you want to permanently remove this student record from the system? This action cannot be undone.');
}

document.addEventListener('DOMContentLoaded', function () {
    const filterForm = document.getElementById('liveFilterForm');
    const searchInput = document.getElementById('searchInput');
    const selectDropdowns = document.querySelectorAll('.auto-submit-select');

    selectDropdowns.forEach(dropdown => {
        dropdown.addEventListener('change', () => {
            filterForm.submit();
        });
    });

    let typingDebounceTimer;
    searchInput.addEventListener('input', () => {
        clearTimeout(typingDebounceTimer);
        typingDebounceTimer = setTimeout(() => {
            filterForm.submit();
        }, 500);
    });

    if (searchInput.value !== '') {
        const textVal = searchInput.value;
        searchInput.value = '';
        searchInput.focus();
        searchInput.value = textVal;
    }
});
</script>
@endsection