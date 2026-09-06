@extends('layouts.app')

@section('content')
<div class="sa-container">
    
    <div class="sa-header">
        <div class="header-titles">
            <h2>Subject Catalog</h2>
            <p>List of available subjects</p>
        </div>
    </div>

    <div class="filter-action-bar">
        <div class="search-box-wrapper">
            <input type="text" id="catalogSearchInput" placeholder="Search subject code or title..." class="select-input">
        </div>
        
        <div class="filter-tabs">
            <span class="filter-label">YEAR:</span>
            <button type="button" class="filter-pill active-tab" data-filter="all">All</button>
            <button type="button" class="filter-pill" data-filter="1st Year">1st</button>
            <button type="button" class="filter-pill" data-filter="2nd Year">2nd</button>
            <button type="button" class="filter-pill" data-filter="3rd Year">3rd</button>
            <button type="button" class="filter-pill" data-filter="4th Year">4th</button>
        </div>
    </div>

    <div class="sa-workspace-full">
        <div class="sa-card table-panel-full">
            <div class="card-header-flex">
                <h3 class="card-title-list">Curriculum Structure</h3>
                <span class="badge-count" id="visibleCount">{{ count($curriculumSubjects) }} Tracked Entries</span>
            </div>

            <div class="table-responsive">
                <table class="minimal-table" id="catalogTable">
                    <thead>
                        <tr>
                            <th>Year Level</th>
                            <th>Semester</th>
                            <th>Code</th>
                            <th>Subject Description</th>
                            <th style="text-align: center;">Lec</th>
                            <th style="text-align: center;">Lab</th>
                            <th style="text-align: center;">Credit Units</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($curriculumSubjects as $sub)
                            <tr data-year="{{ $sub->year_level }}">
                                <td><span class="year-tag">{{ $sub->year_level }}</span></td>
                                <td><span class="sem-tag">{{ $sub->semester }}</span></td>
                                <td><strong class="code-highlight">{{ $sub->subject_code }}</strong></td>
                                <td><span class="title-text">{{ $sub->subject_title }}</span></td>
                                <td style="text-align: center; color: #64748b;">{{ $sub->lecture_units }}</td>
                                <td style="text-align: center; color: #64748b;">{{ $sub->lab_units }}</td>
                                <td style="text-align: center;">
                                    <span class="unit-badge">{{ $sub->lecture_units + $sub->lab_units }} Units</span>
                                </td>
                            </tr>
                        @empty
                            <tr class="no-data-row">
                                <td colspan="7" class="empty-table-state">
                                    No subjects found matching the selected curriculum criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('catalogSearchInput');
        const filterButtons = document.querySelectorAll('.filter-tabs .filter-pill');
        const tableRows = document.querySelectorAll('#catalogTable tbody tr:not(.no-data-row)');
        const visibleCountBadge = document.getElementById('visibleCount');
        
        let currentYearFilter = 'all';

        function filterTable() {
            const searchTerm = searchInput.value.toLowerCase().trim();
            let visibleCount = 0;

            tableRows.forEach(row => {
                const rowYear = row.getAttribute('data-year') || '';
                const codeText = row.querySelector('.code-highlight')?.textContent.toLowerCase() || '';
                const titleText = row.querySelector('.title-text')?.textContent.toLowerCase() || '';

                const matchesYear = (currentYearFilter === 'all' || rowYear === currentYearFilter);
                const matchesSearch = (codeText.includes(searchTerm) || titleText.includes(searchTerm));

                if (matchesYear && matchesSearch) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (visibleCountBadge) {
                visibleCountBadge.textContent = `${visibleCount} Tracked Entries`;
            }
        }

        if (searchInput) {
            searchInput.addEventListener('keyup', filterTable);
        }

        filterButtons.forEach(button => {
            button.addEventListener('click', function () {
                filterButtons.forEach(btn => btn.classList.remove('active-tab'));
                this.classList.add('active-tab');

                currentYearFilter = this.getAttribute('data-filter');
                filterTable();
            });
        });
    });
</script>

<style>
    .sa-container { font-family: 'Segoe UI', sans-serif; padding: 24px; max-width: 1450px; margin: 0 auto; }
    .sa-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 2px solid #e2e8f0; padding-bottom: 16px; }
    .header-titles h2 { margin: 0 0 4px 0; font-size: 26px; color: #0f172a; font-weight: 800; }
    .header-titles p { margin: 0; font-size: 13.5px; color: #64748b; }
    .sec-level { background-color: #0284c7; color: white; padding: 6px 14px; border-radius: 20px; font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }

    .filter-action-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; gap: 16px; flex-wrap: nowrap; }
    .search-box-wrapper { flex-grow: 1; min-width: 0; }
    .search-box-wrapper input { width: 100%; padding-left: 12px !important; }

    .filter-tabs { display: flex; gap: 6px; align-items: center; flex-shrink: 0; flex-wrap: wrap; }
    .filter-label { font-size: 11.5px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; margin-right: 4px; }
    
    .filter-pill { background: #f1f5f9; border: 1px solid #cbd5e1; color: #475569; padding: 5px 12px; font-size: 11.5px; font-weight: 600; border-radius: 20px; cursor: pointer; transition: all 0.15s ease; outline: none; }
    .filter-pill:hover { background: #e2e8f0; color: #0f172a; }
    .active-tab { background: #0f172a !important; color: #ffffff !important; border-color: #0f172a !important; box-shadow: 0 2px 4px rgba(15, 23, 42, 0.2); }

    .sa-workspace-full { width: 100%; }
    .sa-card { border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); overflow: hidden; background: #ffffff; }
    .card-header-flex { background-color: #f8fafc; padding: 16px 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
    .card-title-list { font-size: 14px; font-weight: 700; margin: 0; color: #1e293b; }
    
    .select-input { border: 1px solid #cbd5e1; border-radius: 6px; padding: 9px 12px; font-size: 13.5px; color: #0f172a; outline: none; background: #ffffff; box-sizing: border-box; width: 100%; transition: all 0.15s; }
    .select-input:focus { border-color: #0033A0; box-shadow: 0 0 0 3px rgba(0, 51, 160, 0.1); }

    .minimal-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .minimal-table th { background: #f8fafc; padding: 12px 16px; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; text-align: left; letter-spacing: 0.5px; }
    .minimal-table td { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; color: #334155; }
    
    .year-tag { font-weight: 700; color: #1e293b; background: #f1f5f9; padding: 3px 7px; border-radius: 4px; font-size: 11.5px; }
    .sem-tag { font-weight: 600; color: #475569; background: #f8fafc; padding: 3px 7px; border-radius: 4px; font-size: 11.5px; border: 1px solid #e2e8f0; }
    .code-highlight { color: #2563eb; font-family: ui-monospace, monospace; font-size: 11.5px; }
    
    .unit-badge { background: #eff6ff; color: #1e40af; font-weight: 700; padding: 3px 8px; border-radius: 20px; border: 1px solid #bfdbfe; font-size: 11.5px; }
    .badge-count { background: #e2e8f0; color: #334155; font-size: 11px; padding: 3px 10px; border-radius: 20px; font-weight: 700; }
    .empty-table-state { text-align: center; color: #94a3b8; padding: 50px 0 !important; font-style: italic; }
</style>
@endsection