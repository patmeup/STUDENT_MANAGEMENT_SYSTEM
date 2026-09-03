@extends('layouts.app')

@section('content')
<div class="sa-container">
    <div class="sa-header">
        <div class="header-titles">
            <h2>Block Section Roster</h2>
            <p>Section: <span class="section-badge-title">{{ strtoupper($section_name) }}</span></p>
        </div>
        <a href="{{ route('dean.masterlist') }}" class="btn-back">
            <i class="fas fa-arrow-left"></i> Back to Masterlist
        </a>
    </div>

    <div class="sa-card">
        <div class="card-header-flex">
            <h3 class="card-title-list">Official Class List</h3>
            <span class="badge-count">{{ count($students) }} Students Enrolled</span>
        </div>

        <div class="table-responsive">
            <table class="minimal-table">
                <thead>
                    <tr>
                        <th style="width: 80px; text-align: center;">#</th>
                        <th>Student ID</th>
                        <th>Full Name</th>
                        <th>Course</th>
                        <th>Year Level</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $index => $student)
                        <tr>
                            <td style="text-align: center; color: #94a3b8; font-weight: 600;">{{ $index + 1 }}</td>
                            <td><strong class="code-highlight">{{ $student->student_id }}</strong></td>
                            <td><strong>{{ strtoupper($student->last_name) }}</strong>, {{ $student->first_name }}</td>
                            <td><span class="sem-tag">{{ $student->course }}</span></td>
                            <td><span class="year-tag">{{ $student->year_level }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 50px; color: #64748b;">
                                <i class="fas fa-folder-open" style="font-size: 30px; display: block; margin-bottom: 10px; color: #cbd5e1;"></i>
                                No students are officially plotted/enrolled in this block section yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .sa-container { font-family: 'Segoe UI', sans-serif; padding: 20px; max-width: 1200px; margin: 0 auto; }
    .sa-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 2px solid #e2e8f0; padding-bottom: 18px; }
    .header-titles h2 { margin: 0 0 4px 0; font-size: 26px; font-weight: 800; color: #0f172a; letter-spacing: -0.5px; }
    .header-titles p { margin: 0; font-size: 14px; color: #64748b; }
    
    .section-badge-title { background: #0284c7; color: white; padding: 2px 10px; border-radius: 6px; font-weight: 700; font-size: 14px; }
    
    .btn-back { text-decoration: none; background: transparent; color: #475569; border: 1px solid #cbd5e1; padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; }
    .btn-back:hover { background: #f8fafc; color: #0f172a; border-color: #94a3b8; }

    .sa-card { border-radius: 12px; border: 1px solid #e2e8f0; background: #ffffff; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    .card-header-flex { background-color: #f8fafc; padding: 16px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
    .card-title-list { font-size: 14.5px; font-weight: 700; margin: 0; color: #1e293b; }
    .badge-count { background: #16a34a; color: white; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 12px; }

    .minimal-table { width: 100%; border-collapse: collapse; font-size: 14px; }
    .minimal-table th { background: #f8fafc; padding: 12px 24px; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; text-align: left; }
    .minimal-table td { padding: 14px 24px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
    .minimal-table tbody tr:hover { background-color: #f8fafc; }

    .code-highlight { color: #2563eb; font-family: monospace; font-size: 13.5px; }
    .year-tag { font-weight: 700; color: #1e293b; background: #f1f5f9; padding: 4px 8px; border-radius: 6px; font-size: 11px; border: 1px solid #cbd5e1; }
    .sem-tag { font-weight: 600; color: #475569; background: #f8fafc; padding: 4px 8px; border-radius: 6px; font-size: 11px; border: 1px solid #e2e8f0; }
</style>
@endsection