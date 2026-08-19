@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="prof-container py-4">

    <div class="prof-header">
        <div class="header-titles">
            <h2>My Enrolled Classes</h2>
            <p>1st Semester — Academic Year 2026-2027</p>
        </div>
        <div class="header-badge student-level">Total Units: {{ $totalUnits ?? 0 }}</div>
    </div>

    <div class="card-header-flex mb-4">
        <h3 class="card-title-list">Currently Enrolled Subjects</h3>

        <div class="filter-tab-group">
            <button type="button" class="filter-tab-btn active" onclick="filterClasses('LEC', this)">
                <i class="fa-solid fa-book-open"></i> Lecture
            </button>
            <button type="button" class="filter-tab-btn" onclick="filterClasses('LAB', this)">
                <i class="fa-solid fa-flask"></i> Laboratory
            </button>
        </div>
    </div>

    @if($classes->isEmpty())
        <div class="empty-placeholder-card">
            <i class="fa-solid fa-circle-plus fs-1 mb-2" style="color: #ccc;"></i>
            <p class="fw-medium mb-1" style="color: #666;">No classes plotted at the moment</p>
            <p class="small text-muted mb-0">Wait for Registrar to add more subjects</p>
        </div>
    @else
        <div class="prof-classes-grid">
            @foreach($classes as $index => $class)
                @php
                    $type = strtoupper($class->component_type ?? 'LEC');
                    $isLab = $type === 'LAB';
                    $accentColor = $isLab ? '#eab308' : '#1e2a5a';
                @endphp

                <a href="{{ route('student.class_details', $class->schedule_id) }}" 
                   class="prof-class-card clickable-card class-card-item" 
                   data-type="{{ $isLab ? 'LAB' : 'LEC' }}"
                   style="{{ $isLab ? 'display: none !important;' : '' }}">
                    
                    <div class="card-accent" style="background: {{ $accentColor }};"></div>

                    <div class="card-body">
                        <div class="card-top-row">
                            <div class="card-top-meta">
                                <span class="subject-code">{{ strtoupper($class->subject_code) }}</span>
                                
                                <span class="badge-component {{ $isLab ? 'component-lab' : 'component-lec' }}">
                                    <i class="{{ $isLab ? 'fa-solid fa-flask' : 'fa-solid fa-book-open' }}"></i>
                                    {{ $isLab ? 'LAB' : 'LEC' }}
                                </span>
                            </div>
                            <span class="badge-enrolled-status">
                                <span class="status-pulse-dot"></span> {{ $class->status }}
                            </span>
                        </div>

                        <h3 class="subject-title" title="{{ $class->subject_title }}">
                            {{ $class->subject_title }}
                        </h3>

                        <div class="card-info-specs">
                            <p class="units-display mb-2">
                                <i class="fa-regular fa-user"></i>
                                <span>
                                    @if($class->prof_first_name && $class->prof_last_name)
                                        Prof. {{ $class->prof_first_name }} {{ $class->prof_last_name }}
                                    @else
                                        TBA (To Be Assigned)
                                    @endif
                                </span>
                            </p>

                            <p class="roster-count mb-0">
                                <i class="fa-solid fa-users"></i>
                                <span>Enrolled Students: <strong>{{ $class->enrolled_students_count ?? 0 }}</strong></span>
                            </p>
                        </div>

                        <div class="class-meta">
                            <span class="meta-item">
                                <i class="fa-regular fa-clock"></i>
                                <strong>{{ $class->class_days }}:</strong> {{ \Carbon\Carbon::parse($class->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($class->end_time)->format('h:i A') }}
                            </span>
                            <span class="meta-item">
                                <i class="fa-solid fa-location-dot"></i>
                                <span>Room: {{ $class->room_assignment }}</span>
                            </span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        <div id="no-classes-found" class="empty-placeholder-card" style="display: none;">
            <i class="fa-solid fa-folder-open fs-1 mb-2" style="color: #cbd5e1;"></i>
            <p class="fw-medium mb-1" style="color: #64748b;">No enrolled classes found for this component</p>
        </div>
    @endif
</div>

<script>
    function filterClasses(type, btnElement) {
        document.querySelectorAll('.filter-tab-btn').forEach(btn => btn.classList.remove('active'));

        btnElement.classList.add('active');

        const cards = document.querySelectorAll('.class-card-item');
        let visibleCount = 0;

        cards.forEach(card => {
            if (card.getAttribute('data-type') === type) {
                card.style.setProperty('display', 'flex', 'important');
                visibleCount++;
            } else {
                card.style.setProperty('display', 'none', 'important');
            }
        });

        const noClassesCard = document.getElementById('no-classes-found');
        if (noClassesCard) {
            noClassesCard.style.display = (visibleCount === 0) ? 'flex' : 'none';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const activeBtn = document.querySelector('.filter-tab-btn.active');
        if (activeBtn) {
            filterClasses('LEC', activeBtn);
        }
    });
</script>

<style>
    .prof-container { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; max-width: 1200px; margin: 0 auto; }
    .prof-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 2px solid #f1f3f9; padding-bottom: 20px; }
    .header-titles h2 { font-size: 26px; color: #1e2a5a; font-weight: 800; margin-bottom: 5px; }
    .header-titles p { color: #666; font-size: 14px; margin-bottom: 0; }
    .student-level { background: #1e2a5a; color: white; padding: 8px 16px; border-radius: 50px; font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }

    .card-header-flex { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; }
    .card-title-list { font-size: 16px; font-weight: 700; margin: 0; color: #1e293b; }

    .filter-tab-group {
        display: flex;
        gap: 6px;
        background: #f1f5f9;
        padding: 4px;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
    }
    .filter-tab-btn {
        border: none;
        background: transparent;
        padding: 6px 16px;
        border-radius: 7px;
        font-size: 13px;
        font-weight: 700;
        color: #64748b;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .filter-tab-btn:hover {
        color: #1e2a5a;
    }
    .filter-tab-btn.active {
        background: #1e2a5a;
        color: #ffffff;
        box-shadow: 0 2px 6px rgba(30, 42, 90, 0.2);
    }

    .prof-classes-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 25px; }

    .clickable-card { text-decoration: none !important; color: inherit !important; display: flex; }

    .prof-class-card { background: white; border-radius: 15px; overflow: hidden; border: 1px solid #e0e0e0; transition: transform 0.25s ease, box-shadow 0.25s ease; flex-direction: column; width: 100%; }
    .prof-class-card:hover { transform: translateY(-5px); box-shadow: 0 12px 24px rgba(30, 42, 90, 0.12); border-color: #cbd5e1; }

    .card-accent { height: 6px; width: 100%; }
    .card-body { padding: 22px; flex-grow: 1; display: flex; flex-direction: column; }

    .card-top-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
    .card-top-meta { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 700; color: #0284c7; letter-spacing: 0.5px; }

    .badge-component {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 10.5px;
        font-weight: 800;
        padding: 2px 8px;
        border-radius: 6px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .component-lec {
        background-color: #e0f2fe;
        color: #0369a1;
        border: 1px solid #bae6fd;
    }
    .component-lab {
        background-color: #fef9c3;
        color: #a16207;
        border: 1px solid #fef08a;
    }

    .badge-enrolled-status {
        display: inline-flex; align-items: center; gap: 6px;
        background: #dcfce7; color: #15803d; font-size: 11px; font-weight: 700;
        padding: 3px 10px; border-radius: 20px; text-transform: capitalize;
    }
    .status-pulse-dot {
        width: 6px; height: 6px; background-color: #22c55e; border-radius: 50%;
        display: inline-block; animation: pulseEffect 2s infinite;
    }
    @keyframes pulseEffect {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.6); }
        70% { transform: scale(1); box-shadow: 0 0 0 4px rgba(34, 197, 94, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
    }

    .subject-title { font-size: 17px; color: #1e293b; font-weight: 700; margin-top: 4px; margin-bottom: 16px; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; min-height: 48px; }

    .card-info-specs { border-bottom: 1px dashed #e2e8f0; padding-bottom: 12px; margin-bottom: 14px; }
    .units-display, .roster-count { font-size: 13.5px; color: #475569; display: flex; align-items: center; gap: 8px; margin: 0; }
    .units-display i, .roster-count i { width: 16px; color: #64748b; text-align: center; }

    .class-meta { display: flex; flex-direction: column; gap: 8px; margin-top: auto; }
    .meta-item { font-size: 13px; color: #475569; display: flex; align-items: center; gap: 8px; }
    .meta-item i { width: 14px; color: #1e2a5a; text-align: center; }

    .empty-placeholder-card { border: 2px dashed #cbd5e1; border-radius: 15px; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 30px; color: #94a3b8; text-align: center; background: #f8fafc; min-height: 250px; width: 100%; }
</style>
@endsection