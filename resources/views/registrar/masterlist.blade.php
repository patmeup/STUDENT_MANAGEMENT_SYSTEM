@extends('layouts.app')

@section('content')
<div class="masterlist-container">
    
    <div class="masterlist-header">
        <div class="header-titles">
            <h2>Student Masterlist Ledger</h2>
            <p>Arellano University — Andres Bonifacio Campus (Active Academic Registry)</p>
        </div>
        <div class="header-count">
            Total Records: <strong>{{ $students->count() }}</strong>
        </div>
    </div>

    <div class="masterlist-card">
        <div class="card-top-bar">
            <span><i class="fas fa-list-ul"></i> Enrolled Student Roster</span>
        </div>

        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th style="width: 15%;">Student Number</th>
                        <th style="width: 35%;">Full Name</th>
                        <th style="width: 15%;">Course</th>
                        <th style="width: 15%;">Entry Type</th>
                        <th style="width: 20%; text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $student)
                        <tr>
                            <td>
                                <span class="student-id-badge">{{ $student->student_id }}</span>
                            </td>
                            
                            <td class="student-name">
                                {{ $student->last_name }}, {{ $student->first_name }} {{ $student->middle_name }}
                            </td>
                            
                            <td class="student-course">
                                <span class="course-tag">{{ $student->course }}</span>
                            </td>
                            
                            <td>
                                <span class="type-badge {{ strtolower($student->student_type) }}">
                                    {{ $student->student_type }}
                                </span>
                            </td>
                            
                            <td style="text-align: center;">
                                {{-- Papalitan natin ang '#' ng dynamic route parameter --}}
                                <a href="{{ route('registrar.student.profile', ['student_id' => $student->student_id]) }}" class="btn-action-view" title="View/Edit Full Profile">
                                    <i class="fas fa-folder-open"></i> View Profile
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="empty-table-state">
                                <div class="empty-msg">
                                    <p>No student records captured yet inside the database.</p>
                                    <small>Go to "Manage Enrollment" to register your first student folder entry.</small>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .masterlist-container {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: #1e293b;
        max-width: 1200px;
        margin: 0 auto;
        padding-bottom: 50px;
    }

    /* Header Summary elements styling */
    .masterlist-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        border-bottom: 2px solid #e2e8f0;
        padding-bottom: 15px;
    }
    .header-titles h2 {
        margin: 0 0 5px 0;
        font-size: 26px;
        font-weight: 700;
        color: #0f172a;
    }
    .header-titles p {
        margin: 0;
        font-size: 14px;
        color: #64748b;
    }
    .header-count {
        background-color: rgba(0, 51, 160, 0.05);
        color: #0033A0;
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        border: 1px solid rgba(0, 51, 160, 0.1);
    }

    /* Card Box container structural styling */
    .masterlist-card {
        background: white;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        overflow: hidden;
    }
    .card-top-bar {
        background-color: #ffffff;
        padding: 20px 24px;
        font-size: 16px;
        font-weight: 700;
        color: #0033A0;
        border-bottom: 1px solid #f1f5f9;
    }

    /* Pure Responsive Table Layouts Engine */
    .table-responsive {
        width: 100%;
        overflow-x: auto;
    }
    .custom-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }
    .custom-table th {
        background-color: #f8fafc;
        color: #475569;
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
        padding: 16px 24px;
        border-bottom: 1px solid #e2e8f0;
        letter-spacing: 0.5px;
    }
    .custom-table td {
        padding: 16px 24px;
        border-bottom: 1px solid #f1f5f9;
        font-size: 14px;
        color: #334155;
        vertical-align: middle;
    }
    .custom-table tbody tr:hover {
        background-color: #f8fafc;
    }

    /* Text components styling decorators */
    .student-id-badge {
        font-family: monospace;
        background-color: #0f172a;
        color: #f8fafc;
        padding: 4px 10px;
        border-radius: 5px;
        font-weight: 700;
        font-size: 13px;
    }
    .student-name {
        font-weight: 600;
        color: #0f172a;
        text-transform: uppercase;
    }
    .course-tag {
        font-weight: 700;
        color: #475569;
    }

    /* Flexible Type Badges */
    .type-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
    }
    .type-badge.new {
        background-color: #dcfce7;
        color: #15803d;
    }
    .type-badge.transferee {
        background-color: #fef9c3;
        color: #a16207;
    }
    .type-badge.cross-enroll {
        background-color: #e0f2fe;
        color: #0369a1;
    }

    /* Compact View Action Trigger Control Buttons */
    .btn-action-view {
        display: inline-block;
        background-color: transparent;
        color: #0033A0;
        border: 1px solid #0033A0;
        padding: 6px 14px;
        border-radius: 6px;
        font-size: 12.5px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.15s ease;
    }
    .btn-action-view:hover {
        background-color: #0033A0;
        color: white;
        box-shadow: 0 2px 6px rgba(0, 51, 160, 0.2);
    }

    /* Empty table states handlers */
    .empty-table-state {
        text-align: center !important;
        padding: 50px 0 !important;
        background-color: #fafafa;
    }
    .empty-msg p {
        margin: 0 0 5px 0;
        font-size: 16px;
        font-weight: 600;
        color: #64748b;
    }
    .empty-msg small {
        color: #94a3b8;
        font-size: 13px;
    }
</style>
@endsection