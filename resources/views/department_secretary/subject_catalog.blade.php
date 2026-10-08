@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

@php
    $activePeriod = $activePeriod ?? \Illuminate\Support\Facades\DB::table('academic_periods')->where('is_active', true)->first();
@endphp

<div class="rg-page">

    <header class="rg-header">
        <div>
            <h1 class="rg-title">Subject Catalog</h1>
            <p class="rg-subtitle">List of available subjects in the curriculum.</p>
        </div>

        <div class="rg-period" title="Currently active academic period">
            <span class="rg-period-label">Active period</span>
            @if($activePeriod)
                <span class="rg-period-value">{{ $activePeriod->school_year }}</span>
                <span class="rg-period-sem">{{ $activePeriod->semester }}</span>
            @else
                <span class="rg-period-value rg-period-none">None set</span>
            @endif
        </div>
    </header>

    <div class="rg-card">

        <div class="rg-toolbar">
            <div class="rg-search">
                <i class="fas fa-search"></i>
                <input type="text" id="catalogSearchInput" placeholder="Search subject code, title, or faculty..." autocomplete="off">
            </div>

            <div class="rg-filter filter-tabs">
                <span class="rg-filter-label">Year:</span>
                <button type="button" class="filter-pill active" data-filter="all">All</button>
                <button type="button" class="filter-pill" data-filter="1st Year">1st</button>
                <button type="button" class="filter-pill" data-filter="2nd Year">2nd</button>
                <button type="button" class="filter-pill" data-filter="3rd Year">3rd</button>
                <button type="button" class="filter-pill" data-filter="4th Year">4th</button>
            </div>

            <span class="rg-result-count" id="visibleCount">{{ count($curriculumSubjects) }} subjects</span>
        </div>

        <div class="table-responsive">
            <table class="rg-table" id="catalogTable">
                <thead>
                    <tr>
                        <th>Year Level</th>
                        <th>Semester</th>
                        <th>Code</th>
                        <th>Subject Description</th>
                        <th>Assigned Faculty</th>
                        <th class="rg-text-center">Lec</th>
                        <th class="rg-text-center">Lab</th>
                        <th class="rg-text-center">Credit Units</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($curriculumSubjects as $sub)
                        <tr data-year="{{ $sub->year_level }}" data-faculty="{{ strtolower(trim(($sub->prof_last_name ?? '') . ' ' . ($sub->prof_first_name ?? ''))) }}">
                            <td class="rg-nowrap"><span class="rg-chip rg-chip-blue">{{ $sub->year_level }}</span></td>
                            <td class="rg-nowrap"><span class="rg-chip rg-chip-plain">{{ $sub->semester }}</span></td>
                            <td class="rg-nowrap"><span class="subject-code-tag code-highlight">{{ $sub->subject_code }}</span></td>
                            <td><span class="title-text">{{ $sub->subject_title }}</span></td>
                            <td class="col-faculty">
                                @if(!empty($sub->prof_last_name))
                                    <span class="prof-assigned-tag">
                                        <i class="fas fa-user-tie"></i>
                                        {{ $sub->prof_last_name }}, {{ mb_substr($sub->prof_first_name, 0, 1) }}.
                                    </span>
                                @else
                                    <span class="prof-unassigned-tag">Not assigned</span>
                                @endif
                            </td>
                            <td class="rg-text-center rg-muted-cell">{{ $sub->lecture_units }}</td>
                            <td class="rg-text-center rg-muted-cell">{{ $sub->lab_units }}</td>
                            <td class="rg-text-center rg-nowrap">
                                <span class="unit-badge">{{ $sub->lecture_units + $sub->lab_units }} Units</span>
                            </td>
                        </tr>
                    @empty
                        <tr class="no-data-row">
                            <td colspan="8" class="empty-table-state">
                                No subjects found matching the selected curriculum criteria.
                            </td>
                        </tr>
                    @endforelse

                    <tr id="noResultsRow" style="display: none;">
                        <td colspan="8" class="empty-table-state">
                            <i class="fas fa-search"></i> No subjects match your search or filter.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput       = document.getElementById('catalogSearchInput');
        const filterButtons     = document.querySelectorAll('.filter-tabs .filter-pill');
        const tableRows         = document.querySelectorAll('#catalogTable tbody tr[data-year]');
        const visibleCountBadge = document.getElementById('visibleCount');
        const noResultsRow      = document.getElementById('noResultsRow');

        let currentYearFilter = 'all';

        function filterTable() {
            const searchTerm = searchInput.value.toLowerCase().trim();
            let visibleCount = 0;

            tableRows.forEach(row => {
                const rowYear   = row.getAttribute('data-year') || '';
                const codeText  = row.querySelector('.code-highlight')?.textContent.toLowerCase() || '';
                const titleText = row.querySelector('.title-text')?.textContent.toLowerCase() || '';
                const facultyText = row.getAttribute('data-faculty') || '';

                const matchesYear   = (currentYearFilter === 'all' || rowYear === currentYearFilter);
                const matchesSearch = (codeText.includes(searchTerm) || titleText.includes(searchTerm) || facultyText.includes(searchTerm));

                if (matchesYear && matchesSearch) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (visibleCountBadge) {
                visibleCountBadge.textContent = visibleCount + (visibleCount === 1 ? ' subject' : ' subjects');
            }

            if (noResultsRow) {
                noResultsRow.style.display = (tableRows.length > 0 && visibleCount === 0) ? '' : 'none';
            }
        }

        if (searchInput) {
            searchInput.addEventListener('input', filterTable);
        }

        filterButtons.forEach(button => {
            button.addEventListener('click', function () {
                filterButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');

                currentYearFilter = this.getAttribute('data-filter');
                filterTable();
            });
        });
    });
</script>

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

    .rg-period { display: grid; grid-template-columns: auto auto; column-gap: 10px; align-items: baseline; background: #fff; border: 1px solid var(--rg-line); border-left: 4px solid var(--rg-gold); border-radius: 8px; padding: 9px 16px 9px 14px; }
    .rg-period-label { grid-column: 1 / -1; font-size: 11.5px; font-weight: 600; color: var(--rg-muted); margin-bottom: 2px; }
    .rg-period-value { font-size: 15px; font-weight: 800; color: var(--rg-blue-dark); }
    .rg-period-sem { font-size: 13px; font-weight: 600; color: var(--rg-blue); }
    .rg-period-none { color: var(--rg-red); }

    .rg-card { background: #fff; border: 1px solid var(--rg-line); border-radius: 10px; overflow: hidden; box-shadow: 0 1px 2px rgba(10, 31, 92, 0.05); }
    .rg-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 14px 18px; padding: 14px 20px; border-bottom: 1px solid var(--rg-line); background: var(--rg-surface); }

    .rg-search { position: relative; flex: 1 1 320px; max-width: 460px; }
    .rg-search i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #8A97B3; font-size: 13px; pointer-events: none; }
    .rg-search input { width: 100%; box-sizing: border-box; padding: 10px 12px 10px 34px; font-size: 13.5px; color: var(--rg-ink); background: #fff; border: 1px solid #CBD5E6; border-radius: 8px; }
    .rg-search input::placeholder { color: #8A97B3; }
    .rg-search input:focus { outline: none; border-color: var(--rg-blue); box-shadow: 0 0 0 3px rgba(0, 51, 160, 0.14); }

    .rg-filter { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
    .rg-filter-label { font-size: 12.5px; font-weight: 600; color: var(--rg-muted); margin-right: 2px; }
    .filter-pill { background: #fff; border: 1px solid #CBD5E6; color: var(--rg-muted); padding: 5px 12px; font-size: 12.5px; font-weight: 600; border-radius: 16px; cursor: pointer; transition: background .15s, color .15s, border-color .15s; outline: none; }
    .filter-pill:hover { border-color: var(--rg-blue); color: var(--rg-blue); }
    .filter-pill.active { background: var(--rg-blue); border-color: var(--rg-blue); color: #fff; }
    .filter-pill:focus-visible { box-shadow: 0 0 0 3px rgba(0, 51, 160, 0.2); }

    .rg-result-count { margin-left: auto; font-size: 12.5px; font-weight: 600; color: var(--rg-muted); }

    .table-responsive { width: 100%; overflow-x: auto; }
    .rg-table { width: 100%; min-width: 900px; border-collapse: collapse; font-size: 14px; }
    .rg-table th { background: #fff; padding: 11px 20px; font-size: 12px; font-weight: 700; color: var(--rg-muted); text-align: left; border-bottom: 1px solid var(--rg-line); white-space: nowrap; }
    .rg-table td { padding: 13px 20px; border-bottom: 1px solid #EEF2F9; color: #26345A; vertical-align: middle; }
    .rg-table tbody tr:last-child td { border-bottom: none; }
    .rg-table tbody tr:hover { background: #F5F8FF; }
    .rg-text-center { text-align: center !important; }
    .rg-nowrap { white-space: nowrap; }
    .rg-muted-cell { color: var(--rg-muted); font-variant-numeric: tabular-nums; }

    .title-text { font-weight: 500; color: var(--rg-ink); }

    .rg-chip { display: inline-block; padding: 3px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; white-space: nowrap; }
    .rg-chip-plain { background: var(--rg-surface); color: #3B4A6B; border: 1px solid var(--rg-line); }
    .rg-chip-blue { background: var(--rg-blue-tint); color: var(--rg-blue); border: 1px solid #CFDBFB; }

    .subject-code-tag { font-family: ui-monospace, monospace; font-weight: 700; color: var(--rg-blue); background-color: var(--rg-blue-tint); padding: 3px 8px; border-radius: 6px; font-size: 12px; border: 1px solid #CFDBFB; white-space: nowrap; }

    .unit-badge { display: inline-block; background: var(--rg-blue-tint); color: var(--rg-blue-dark); font-weight: 700; padding: 3px 10px; border-radius: 16px; border: 1px solid #CFDBFB; font-size: 12px; white-space: nowrap; }

    .col-faculty { white-space: nowrap; }
    .prof-assigned-tag { font-size: 12px; font-weight: 600; color: var(--rg-green); background-color: var(--rg-green-tint); border: 1px solid #BFE8CE; padding: 3px 8px; border-radius: 6px; display: inline-flex; align-items: center; gap: 5px; margin: 1px 3px 1px 0; cursor: default; }
    .prof-unassigned-tag { font-size: 11.5px; font-weight: 500; color: var(--rg-muted); background-color: var(--rg-surface); padding: 3px 8px; border-radius: 6px; font-style: italic; border: 1px solid var(--rg-line); }

    .empty-table-state { text-align: center; color: var(--rg-muted); padding: 40px 0 !important; font-style: italic; }

    @media (max-width: 720px) {
        .rg-header { align-items: flex-start; }
        .rg-result-count { margin-left: 0; }
        .rg-table th, .rg-table td { padding-left: 14px; padding-right: 14px; }
    }
</style>
@endsection