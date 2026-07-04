@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="classes-container py-4">
    <div class="page-header">
        <div class="header-text">
            <h1>My Enrolled Classes</h1>
            <p>1st Semester | Academic Year 2026-2027</p>
        </div>
        <div class="header-stats">
            @if(!$classes->isEmpty())
                <span class="stat-badge">Total Units: <strong>{{ $classes->sum('total_units') }}</strong></span>
            @else
                <span class="stat-badge">Total Units: <strong>0</strong></span>
            @endif
        </div>
    </div>

    @if($classes->isEmpty())
        <div class="add-more-placeholder">
            <i class="fa-solid fa-circle-plus fs-1 mb-2" style="color: #ccc;"></i>
            <p class="fw-medium mb-1" style="color: #666;">No classes plotted at the moment</p>
            <p class="small text-muted mb-0">Wait for Registrar to add more subjects</p>
        </div>
    @else
        <div class="classes-grid">
            @foreach($classes as $index => $class)
                <div class="class-card">
                    <div class="card-accent" style="background: {{ $index % 2 == 0 ? '#1e2a5a' : '#facc15' }};"></div>
                    
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="subject-code">{{ strtoupper($class->subject_code) }}</div>
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill d-flex align-items-center gap-1" style="font-size: 0.7rem; font-weight: 600;">
                                <span class="status-pulse-dot"></span>
                                {{ $class->status }}
                            </span>
                        </div>

                        <h3 class="subject-title" title="{{ $class->subject_title }}">
                            {{ $class->subject_title }}
                        </h3>
                        
                        <p class="instructor">
                            <i class="fa-regular fa-user"></i> 
                            <span>
                                @if($class->prof_first_name && $class->prof_last_name)
                                    Prof. {{ $class->prof_first_name }} {{ $class->prof_last_name }}
                                @else
                                    TBA (To Be Assigned)
                                @endif
                            </span>
                        </p>
                        
                        <div class="class-meta">
                            <span class="meta-item">
                                <i class="fa-regular fa-clock"></i> 
                                <strong>{{ $class->class_days }}:</strong> {{ \Carbon\Carbon::parse($class->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($class->end_time)->format('h:i A') }}
                            </span>
                            <span class="meta-item">
                                <i class="fa-solid fa-location-dot"></i> 
                                <span>Room: {{ $class->room_assignment }}</span>
                            </span>
                            <span class="meta-item">
                                <i class="fa-solid fa-users"></i> 
                                <span>Enrolled Students: <strong>{{ $class->enrolled_students_count ?? 0 }}</strong></span>
                            </span>
                        </div>
                    </div>
                    
                    <div class="card-footer">
                        <a href="{{ route('student.class_details', $class->schedule_id) }}" class="btn-view text-center d-block text-decoration-none">
                            View Materials
                        </a>
                    </div>
                </div>
            @endforeach

            <div class="add-more-placeholder">
                <i class="fa-solid fa-circle-plus fs-2 mb-2" style="opacity: 0.6;"></i>
                <p class="small mb-0">Wait for Registrar to add more subjects</p>
            </div>
        </div>
    @endif
</div>

<style>
    .classes-container {
        max-width: 1100px;
        margin: 0 auto;
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        border-bottom: 2px solid #f1f3f9;
        padding-bottom: 20px;
    }

    .header-text h1 {
        font-size: 26px;
        color: #1e2a5a;
        font-weight: 800;
        margin-bottom: 5px;
    }

    .header-text p {
        color: #666;
        font-size: 14px;
        margin-bottom: 0;
    }

    .stat-badge {
        background: #1e2a5a;
        color: white;
        padding: 8px 16px;
        border-radius: 50px;
        font-size: 13px;
    }

    /* Grid Layout */
    .classes-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 25px;
    }

    /* Card Styling */
    .class-card {
        background: white;
        border-radius: 15px;
        overflow: hidden;
        border: 1px solid #e0e0e0;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        display: flex;
        flex-direction: column;
    }

    .class-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.08);
    }

    .card-accent {
        height: 6px;
        width: 100%;
    }

    .card-body {
        padding: 20px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }

    .subject-code {
        font-size: 12px;
        font-weight: 700;
        color: #0284c7;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .subject-title {
        font-size: 17px;
        color: #1e293b;
        font-weight: 700;
        margin-top: 5px;
        margin-bottom: 12px;
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;  
        overflow: hidden;
        min-height: 48px;
    }

    .instructor {
        font-size: 14px;
        color: #475569;
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 15px;
    }

    .instructor i {
        width: 16px;
        color: #64748b;
    }

    .class-meta {
        display: flex;
        flex-direction: column;
        gap: 8px;
        border-top: 1px solid #f1f5f9;
        padding-top: 15px;
        margin-top: auto;
    }

    .meta-item {
        font-size: 13px;
        color: #475569;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .meta-item i {
        width: 14px;
        color: #1e2a5a;
        text-align: center;
    }

    .card-footer {
        padding: 15px 20px;
        background: #fcfcfc;
        border-top: 1px solid #f1f3f9;
    }

    .btn-view {
    width: 100%;
    padding: 10px;
    border-radius: 8px;
    border: 1px solid #1e2a5a;
    background: transparent;
    color: #1e2a5a;
    font-weight: 600;
    cursor: pointer;
    transition: 0.3s;
    display: inline-block;
    text-align: center;
    text-decoration: none;
}

    .btn-view:hover {
        background: #1e2a5a;
        color: white;
    }

    .add-more-placeholder {
        border: 2px dashed #cbd5e1;
        border-radius: 15px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 30px;
        color: #94a3b8;
        text-align: center;
        background: #f8fafc;
        min-height: 250px;
    }

    .bg-success-subtle { background-color: #d1fae5 !important; color: #065f46 !important; }
    
    .status-pulse-dot {
        width: 6px;
        height: 6px;
        background-color: #10b981;
        border-radius: 50%;
        display: inline-block;
        animation: pulseEffect 2s infinite;
    }

    @keyframes pulseEffect {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 4px rgba(16, 185, 129, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
    }
</style>
@endsection