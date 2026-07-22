@extends('layouts.app')

@section('content')
<div class="sa-container">
    
    <div class="sa-header">
        <div class="header-titles">
            <h2>Subject Catalog</h2>
            <p>{{ $dept }} — Course Specification Matrix</p>
        </div>
        <div class="header-badge sec-level">Academic Reference</div>
    </div>

    <div class="filter-action-bar">
        <div class="search-box-wrapper">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="catalogSearchInput" placeholder="Search subject code or title...">
        </div>
        
        <div class="filter-tabs">
            <button type="button" class="tab-link active-tab" data-filter="all">All Years</button>
            <button type="button" class="tab-link" data-filter="1st Year">1st Year</button>
            <button type="button" class="tab-link" data-filter="2nd Year">2nd Year</button>
            <button type="button" class="tab-link" data-filter="3rd Year">3rd Year</button>
            <button type="button" class="tab-link" data-filter="4th Year">4th Year</button>
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
        const filterButtons = document.querySelectorAll('.filter-tabs .tab-link');
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
            searchInput.addEventListener('input', filterTable);
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
    .sa-container { font-family: 'Segoe UI', sans-serif; padding: 20px; max-width: 1400px; margin: 0 auto; }
    .sa-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 2px solid #e2e8f0; padding-bottom: 15px; }
    .header-titles h2 { margin: 0; font-size: 24px; color: #0f172a; font-weight: 800; }
    .header-titles p { margin: 4px 0 0 0; font-size: 14px; color: #64748b; }
    .sec-level { background-color: #1e3a8a; color: white; padding: 6px 14px; border-radius: 20px; font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }

    .filter-action-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; gap: 20px; flex-wrap: wrap; }
    .search-box-wrapper { position: relative; flex: 1; max-width: 400px; }
    .search-box-wrapper input { width: 100%; padding: 10px 16px 10px 40px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 14px; outline: none; transition: border 0.2s; }
    .search-box-wrapper input:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
    .search-icon { position: absolute; left: 14px; top: 13px; color: #94a3b8; font-size: 14px; }

    .filter-tabs { display: flex; gap: 8px; background: #f1f5f9; padding: 4px; border-radius: 8px; }
    .tab-link { background: none; border: none; cursor: pointer; color: #475569; font-size: 13px; font-weight: 600; padding: 6px 14px; border-radius: 6px; transition: all 0.2s; outline: none; }
    .tab-link:hover { color: #0f172a; }
    .active-tab { background: white !important; color: #0f172a !important; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }

    .sa-workspace-full { width: 100%; }
    .sa-card { border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); overflow: hidden; background: #ffffff; }
    .card-header-flex { background-color: #f8fafc; padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
    .card-title-list { font-size: 15px; font-weight: 700; margin: 0; color: #1e293b; }
    
    .minimal-table { width: 100%; border-collapse: collapse; font-size: 14px; }
    .minimal-table th { background: #f8fafc; padding: 14px 24px; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; text-align: left; letter-spacing: 0.5px; }
    .minimal-table td { padding: 16px 24px; border-bottom: 1px solid #f1f5f9; color: #334155; }
    .minimal-table tbody tr:hover { background-color: #f8fafc; }
    
    .year-tag { font-weight: 700; color: #1e293b; background: #f1f5f9; padding: 4px 8px; border-radius: 6px; font-size: 12px; }
    .sem-tag { font-weight: 600; color: #475569; background: #f8fafc; padding: 4px 8px; border-radius: 6px; font-size: 12px; border: 1px solid #e2e8f0; }
    .code-highlight { color: #2563eb; font-family: monospace; font-size: 14px; }
    
    .unit-badge { background: #eff6ff; color: #1e40af; font-weight: 700; padding: 4px 10px; border-radius: 20px; border: 1px solid #bfdbfe; font-size: 12px; }
    .badge-count { background: #0f172a; color: white; font-size: 12px; padding: 4px 12px; border-radius: 20px; font-weight: 700; }
    .empty-table-state { text-align: center; color: #94a3b8; padding: 80px 0 !important; font-style: italic; }
</style>
@endsection