@extends('layouts.app')

@section('content')
<div class="sa-container">
    
    <div class="sa-header">
        <div class="header-titles">
            <h2>List of Subjects</h2>
            <p>View course requirements and curriculum structure for your department.</p>
        </div>
    </div>

    <div class="sa-workspace-full">
        <div class="sa-card table-panel-full">
            <div class="card-header-flex" style="display: flex; flex-direction: column; gap: 14px; align-items: stretch;">
                <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                    <h3 class="card-title-list">Curriculum Structure</h3>
                    <span class="badge-count" id="visibleCount">{{ count($curriculumSubjects) }} Tracked Entries</span>
                </div>

                <div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center; width: 100%;">
                    <div style="flex: 2; min-width: 280px;">
                        <input type="text" id="catalogSearchInput" placeholder="Search subject code or title..." class="custom-search-input">
                    </div>

                    <div style="display: flex; gap: 6px; flex-wrap: wrap; align-items: center;">
                        <span style="font-size: 11.5px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-right: 2px;">Year:</span>
                        <button type="button" class="filter-pill active" data-filter-value="all">All</button>
                        <button type="button" class="filter-pill" data-filter-value="1st Year">1st</button>
                        <button type="button" class="filter-pill" data-filter-value="2nd Year">2nd</button>
                        <button type="button" class="filter-pill" data-filter-value="3rd Year">3rd</button>
                        <button type="button" class="filter-pill" data-filter-value="4th Year">4th</button>
                    </div>
                </div>
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
                        <tr id="catalogNoMatchRow" style="display: none;">
                            <td colspan="7" class="empty-table-state">
                                No subjects match your search or filter criteria.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('catalogSearchInput');
        const filterPills = document.querySelectorAll('.filter-pill');
        const tableRows = document.querySelectorAll('#catalogTable tbody tr:not(.no-data-row):not(#catalogNoMatchRow)');
        const noMatchRow = document.getElementById('catalogNoMatchRow');
        const visibleCountBadge = document.getElementById('visibleCount');
        const initialTotal = tableRows.length;
        
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

            if (noMatchRow) {
                if (visibleCount === 0 && initialTotal > 0) {
                    noMatchRow.style.display = '';
                } else {
                    noMatchRow.style.display = 'none';
                }
            }
        }

        if (searchInput) {
            searchInput.addEventListener('input', filterTable);
        }

        filterPills.forEach(pill => {
            pill.addEventListener('click', function () {
                filterPills.forEach(p => p.classList.remove('active'));
                this.classList.add('active');

                currentYearFilter = this.getAttribute('data-filter-value');
                filterTable();
            });
        });
    });
</script>

<style>
    .sa-container { font-family: 'Segoe UI', sans-serif; padding: 20px; max-width: 1400px; margin: 0 auto; }
    .sa-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 2px solid #e2e8f0; padding-bottom: 15px; }
    .header-titles h2 { margin: 0 0 4px 0; font-size: 26px; font-weight: 800; color: #0f172a; letter-spacing: -0.5px; }
    .header-titles p { margin: 0; font-size: 14px; color: #64748b; }
    .sec-level { background-color: #0f172a; color: white; padding: 6px 14px; border-radius: 20px; font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }

    .custom-search-input { width: 100%; padding: 9px 12px; font-size: 13.5px; color: #334155; border: 1px solid #cbd5e1; border-radius: 6px; outline: none; box-sizing: border-box; background: #fff; }
    .custom-search-input:focus { border-color: #cbd5e1 !important; box-shadow: none !important; outline: none !important; }

    .filter-pill { background: #f1f5f9; border: 1px solid #cbd5e1; color: #475569; padding: 5px 10px; font-size: 11.5px; font-weight: 600; border-radius: 20px; cursor: pointer; transition: all 0.15s ease; }
    .filter-pill:hover { background: #e2e8f0; color: #0f172a; }
    .filter-pill.active { background: #0f172a; color: #ffffff; border-color: #0f172a; box-shadow: 0 2px 4px rgba(15, 23, 42, 0.2); }

    .sa-workspace-full { width: 100%; }
    .sa-card { border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); overflow: hidden; background: #ffffff; }
    .card-header-flex { background-color: #f8fafc; padding: 18px 24px; border-bottom: 1px solid #e2e8f0; }
    .card-title-list { font-size: 14.5px; font-weight: 700; margin: 0; color: #1e293b; }
    
    .minimal-table { width: 100%; border-collapse: collapse; font-size: 14px; }
    .minimal-table th { background: #f8fafc; padding: 12px 24px; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; text-align: left; letter-spacing: 0.5px; }
    .minimal-table td { padding: 14px 24px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
    .minimal-table tbody tr:hover { background-color: #f8fafc; }
    
    .year-tag { font-weight: 700; color: #1e293b; background: #f1f5f9; padding: 4px 8px; border-radius: 6px; font-size: 11px; border: 1px solid #cbd5e1; }
    .sem-tag { font-weight: 600; color: #475569; background: #f8fafc; padding: 4px 8px; border-radius: 6px; font-size: 11px; border: 1px solid #e2e8f0; }
    .code-highlight { color: #2563eb; font-family: monospace; font-size: 13.5px; }
    
    .unit-badge { background: #eff6ff; color: #1e40af; font-weight: 700; padding: 4px 10px; border-radius: 20px; border: 1px solid #bfdbfe; font-size: 12px; }
    .badge-count { background: #0f172a; color: white; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 12px; }
    .empty-table-state { text-align: center; color: #94a3b8; padding: 60px 0 !important; font-style: italic; font-size: 13.5px; }
</style>
@endsection