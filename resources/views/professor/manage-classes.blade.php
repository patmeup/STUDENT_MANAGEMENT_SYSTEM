@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="prof-container py-4">
    
    <div class="prof-header">
        <div class="header-titles">
            <h2>Academic Class Management</h2>
            <p>View your active teaching loads, assigned student rosters, and Google Classroom integration status.</p>
        </div>
    </div>

    @if(session('error'))
        <div class="prof-alert-danger">
            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
        </div>
    @endif

    @if(session('success'))
        <div class="prof-alert-success">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    <div class="card-header-flex mb-4">
        <div class="header-left-group">
            <h3 class="card-title-list">My Assigned Teaching Load</h3>
            <span class="badge-count">{{ count($classes) }} Active Class Sections</span>
        </div>

        <div class="teaching-load-actions">
            @if($gConnection)
                <div class="google-connected-pill">
                    <i class="fa-brands fa-google-classroom"></i>
                    <div class="google-connection-info">
                        <span class="connected-label">Google Classroom Connected</span>
                        <span class="connected-email">{{ $gConnection->google_email }}</span>
                        <span class="connected-synced" style="font-size: 15px; color: #374151; font-weight: 500; margin-top: 3px; display: block;">
                            Last synced: {{ $gConnection->last_synced_at ? \Carbon\Carbon::parse($gConnection->last_synced_at)->diffForHumans() : 'Never' }}
                        </span>
                    </div>
                    <div class="pill-divider"></div>
                    <form action="{{ route('professor.google-classroom.sync') }}" method="POST" class="m-0">
                        @csrf
                        <button type="submit" class="btn-sync-icon" title="Sync Now">
                            <i class="fa-solid fa-arrows-rotate"></i>
                        </button>
                    </form>
                </div>
            @else
                <a href="{{ route('professor.google-classroom.connect') }}" class="btn-google-classroom-connect">
                    <i class="fa-brands fa-google-classroom"></i> Connect Google Classroom
                </a>
            @endif
        </div>
    </div>

    <div class="filter-tab-group mb-4">
        <button type="button" class="filter-btn active" data-filter="LEC">
            <i class="fa-solid fa-chalkboard-user me-1"></i> Lecture
        </button>
        <button type="button" class="filter-btn" data-filter="LAB">
            <i class="fa-solid fa-flask me-1"></i> Laboratory
        </button>
    </div>

    @if($classes->isEmpty())
        <div class="empty-placeholder-card">
            <i class="fa-solid fa-chalkboard-user fs-1 mb-2" style="color: #ccc;"></i>
            <p class="fw-medium mb-1" style="color: #666;">No teaching subjects assigned yet</p>
            <p class="small text-muted mb-0">Wait for Registrar or Dean to load your schedule active items.</p>
        </div>
    @else
        <div class="prof-classes-grid">
            @foreach($classes as $class)
                @php
                    $isEven = $loop->index % 2 == 0;
                    $accentColor = $isEven ? '#1e2a5a' : '#facc15';
                    $schedType = strtoupper($class->schedule_type ?? 'LEC');
                @endphp
                
                <a href="{{ route('professor.class_details', $class->schedule_id) }}" 
                   class="prof-class-card clickable-card class-card-item" 
                   data-type="{{ $schedType }}">
                    
                    <div class="card-accent" style="background: {{ $accentColor }};"></div>
                    
                    <div class="card-body">
                        <div class="card-top-meta mb-2">
                            <span class="subject-code">{{ strtoupper($class->subject_code) }}</span>
                            <span class="section-divider">•</span>
                            <span class="section-display-text">{{ strtoupper($class->section_name) }}</span>

                            <span class="badge-sched-type {{ strtolower($schedType) }} ms-auto">
                                {{ $schedType }}
                            </span>
                        </div>

                        <h3 class="subject-title" title="{{ $class->subject_title }}">
                            {{ $class->subject_title }}
                        </h3>
                        
                        <div class="card-info-specs">
                            <p class="units-display mb-2">
                                <i class="fa-solid fa-book-bookmark"></i>
                                <span>Academic Credits: <strong>{{ $class->lecture_units + $class->lab_units }} Units</strong></span>
                            </p>

                            <p class="roster-count mb-3">
                                <i class="fa-solid fa-users"></i> 
                                <span>Enrolled Students: <strong>{{ $class->students_count ?? 0 }}</strong></span>
                            </p>
                        </div>
                        
                        <div class="class-meta">
                            <span class="meta-item">
                                <i class="fa-regular fa-clock"></i> 
                                <strong>{{ $class->class_days }}:</strong> {{ date('h:i A', strtotime($class->start_time)) }} - {{ date('h:i A', strtotime($class->end_time)) }}
                            </span>
                            <span class="meta-item">
                                <i class="fa-solid fa-location-dot"></i> 
                                <span>Room: {{ strtoupper($class->room_assignment) }}</span>
                            </span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>

<style>
    .prof-container { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; max-width: 1200px; margin: 0 auto; }
    .prof-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 2px solid #f1f3f9; padding-bottom: 20px; }
    .header-titles h2 { font-size: 26px; color: #1e2a5a; font-weight: 800; margin-bottom: 5px; }
    .header-titles p { color: #666; font-size: 14px; margin-bottom: 0; }
    .prof-level { background: #1e2a5a; color: white; padding: 8px 16px; border-radius: 50px; font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }

    .card-header-flex { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; flex-wrap: wrap; gap: 14px; }
    .header-left-group { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }
    .card-title-list { font-size: 16px; font-weight: 700; margin: 0; color: #1e293b; }
    .badge-count { background: #1e2a5a; color: white; font-size: 12px; padding: 6px 14px; border-radius: 20px; font-weight: 700; display: inline-flex; align-items: center; }

    .teaching-load-actions { display: flex; align-items: center; gap: 12px; }

    .filter-tab-group {
        display: flex;
        gap: 8px;
        background: #f1f5f9;
        padding: 5px;
        border-radius: 10px;
        width: fit-content;
    }

    .filter-btn {
        border: none;
        background: transparent;
        padding: 8px 20px;
        border-radius: 7px;
        font-size: 13px;
        font-weight: 700;
        color: #64748b;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .filter-btn:hover {
        color: #1e2a5a;
    }

    .filter-btn.active {
        background: #1e2a5a;
        color: #ffffff;
        box-shadow: 0 2px 6px rgba(30, 42, 90, 0.2);
    }

    .badge-sched-type {
        font-size: 10px;
        font-weight: 800;
        padding: 3px 8px;
        border-radius: 4px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .badge-sched-type.lec {
        background-color: #e0f2fe;
        color: #0369a1;
    }

    .badge-sched-type.lab {
        background-color: #fef3c7;
        color: #b45309;
    }

    .prof-classes-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 25px; }

    .clickable-card { text-decoration: none !important; color: inherit !important; display: block; }
    
    .prof-class-card { background: white; border-radius: 15px; overflow: hidden; border: 1px solid #e0e0e0; transition: transform 0.25s ease, box-shadow 0.25s ease; display: flex; flex-direction: column; }
    .prof-class-card:hover { transform: translateY(-5px); box-shadow: 0 12px 24px rgba(30, 42, 90, 0.12); border-color: #cbd5e1; }
    
    .card-accent { height: 6px; width: 100%; }
    .card-body { padding: 22px; flex-grow: 1; display: flex; flex-direction: column; }

    .card-top-meta { display: flex; align-items: center; gap: 6px; font-size: 12.5px; font-weight: 700; color: #0284c7; letter-spacing: 0.5px; }
    .section-divider { color: #94a3b8; font-size: 10px; }
    .section-display-text { text-transform: uppercase; color: #0284c7; }

    .subject-title { font-size: 17px; color: #1e293b; font-weight: 700; margin-top: 4px; margin-bottom: 16px; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; min-height: 48px; }

    .card-info-specs { border-bottom: 1px dashed #e2e8f0; padding-bottom: 12px; margin-bottom: 14px; }
    .units-display, .roster-count { font-size: 13.5px; color: #475569; display: flex; align-items: center; gap: 8px; margin: 0; }
    .units-display i, .roster-count i { width: 16px; color: #64748b; text-align: center; }

    .class-meta { display: flex; flex-direction: column; gap: 8px; margin-top: auto; }
    .meta-item { font-size: 13px; color: #475569; display: flex; align-items: center; gap: 8px; }
    .meta-item i { width: 14px; color: #1e2a5a; text-align: center; }

    .empty-placeholder-card { border: 2px dashed #cbd5e1; border-radius: 15px; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 30px; color: #94a3b8; text-align: center; background: #f8fafc; min-height: 250px; }
    .prof-alert-danger { background: #fef2f2; color: #991b1b; padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; border-left: 5px solid #dc2626; font-size: 14px; }
    .prof-alert-success { background: #f0fdf4; color: #166534; padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; border-left: 5px solid #16a34a; font-size: 14px; }

    .btn-google-classroom-connect {
        background-color: #ffffff;
        color: #15803d;
        border: 1px solid #bbf7d0;
        font-weight: 600;
        font-size: 13px;
        padding: 7px 14px;
        border-radius: 6px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s ease;
        white-space: nowrap;
    }
    
    .btn-google-classroom-connect:hover { background-color: #f0fdf4; border-color: #86efac; color: #166534; }

    .google-connected-pill {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        background-color: #f0fdf4;
        border: 1px solid #bbf7d0;
        padding: 6px 8px 6px 12px;
        border-radius: 30px;
    }
    .google-connected-pill > i { font-size: 18px; color: #15803d; }
    .google-connection-info { display: flex; flex-direction: column; line-height: 1.2; }
    .connected-label { font-weight: 700; color: #166534; font-size: 15px; }
    .connected-email { font-size: 13px; color: #64748b; }

    .pill-divider { width: 1px; height: 26px; background: #bbf7d0; }

    .btn-sync-icon {
        width: 30px; height: 30px;
        border-radius: 50%;
        border: none;
        background: white;
        color: #15803d;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        cursor: pointer;
        transition: 0.2s;
        box-shadow: 0 1px 2px rgba(0,0,0,0.06);
    }
    .btn-sync-icon:hover {
        background: #15803d;
        color: white;
        transform: rotate(90deg);
    }

    .btn-sm-outline { background: white; border: 1px solid #cbd5e1; color: #475569; font-size: 12px; font-weight: 600; padding: 7px 12px; border-radius: 6px; cursor: pointer; transition: 0.15s; display: inline-flex; align-items: center; gap: 5px; text-decoration: none; height: 100%; }
    .btn-sm-outline:hover { border-color: #1e2a5a; color: #1e2a5a; background: #eef1fb; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const filterBtns = document.querySelectorAll('.filter-btn');
    const classCards = document.querySelectorAll('.class-card-item');

    function filterCards(targetType) {
        classCards.forEach(card => {
            const cardType = card.getAttribute('data-type');
            if (cardType === targetType) {
                card.style.display = 'flex';
            } else {
                card.style.display = 'none';
            }
        });
    }

    const activeBtn = document.querySelector('.filter-btn.active');
    if (activeBtn) {
        filterCards(activeBtn.getAttribute('data-filter'));
    }

    filterBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            filterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            const filterValue = this.getAttribute('data-filter');
            filterCards(filterValue);
        });
    });
});
</script>
@endsection