@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="dashboard-wrapper py-4 px-3">

    {{-- Dynamic Academic Standing Alert Banner --}}
    @if(isset($student) && trim($student->status ?? 'Enrolled') !== 'Enrolled')
        @php
            $status = trim($student->status);
        @endphp

        <div style="margin-bottom: 25px; border-radius: 12px; padding: 18px 24px; display: flex; align-items: flex-start; gap: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            @if($status === 'On-Hold' || $status === 'On Hold') background: #fffbeb; border: 1.5px solid #fde68a; color: #92400e;
            @elseif($status === 'Purged') background: #faf5ff; border: 1.5px solid #e9d5ff; color: #6b21a8;
            @elseif($status === 'Dropped') background: #fef2f2; border: 1.5px solid #fca5a5; color: #991b1b;
            @else background: #f8fafc; border: 1.5px solid #cbd5e1; color: #334155;
            @endif">
            
            <div style="font-size: 22px; margin-top: 2px;">
                <i class="fas fa-exclamation-triangle"></i>
            </div>

            <div style="flex: 1;">
                <div style="font-size: 15px; font-weight: 800; letter-spacing: -0.2px; text-transform: uppercase; margin-bottom: 4px;">
                    @if($status === 'On-Hold' || $status === 'On Hold')
                        ACADEMIC ACCOUNT STATUS: ON-HOLD
                    @elseif($status === 'Purged')
                        ACADEMIC ACCOUNT STATUS: PURGED
                    @elseif($status === 'Dropped')
                        ACADEMIC ACCOUNT STATUS: DROPPED
                    @else
                        ACADEMIC ACCOUNT STATUS: {{ strtoupper($status) }}
                    @endif
                </div>

                <div style="font-size: 13.5px; line-height: 1.5; font-weight: 500; opacity: 0.95;">
                    @if($status === 'On-Hold' || $status === 'On Hold')
                        Your student record is currently marked as <strong>On-Hold</strong> due to pending document requirements or payment. Activity submissions and enrollment privileges are temporarily restricted. Please proceed to the Registrar's Office for resolution.
                    @elseif($status === 'Purged')
                        Your enrollment status for this academic term has been marked as <strong>Purged</strong>. Your access to active coursework is currently restricted. Please visit the Registrar's Office immediately.
                    @elseif($status === 'Dropped')
                        You have been officially <strong>Dropped</strong> from your subject assignments for this term. You are restricted from turning in new tasks or activities.
                    @endif
                </div>
            </div>
        </div>
    @endif
    
    @if(session('success'))
        <div class="alert-banner alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert-banner alert-danger"><i class="fa-solid fa-circle-exclamation"></i> {{ session('error') }}</div>
    @endif

    <div class="announcement-carousel-wrapper">
        <div class="announcement-box">
            <span class="announcement-tag">ANNOUNCEMENT:</span>
            
            <div class="carousel-track" id="carouselTrack">
                @forelse($announcements as $index => $item)
                    <div class="carousel-slide {{ $index === 0 ? 'active' : '' }}">
                        <!-- Blurred Background Layer -->
                        <div class="slide-bg-blur" style="background-image: url('{{ asset('storage/' . $item->image_path) }}');"></div>
                        <!-- Main Crisp Center Image -->
                        <img src="{{ asset('storage/' . $item->image_path) }}" alt="{{ $item->title ?? 'Announcement Banner' }}" class="slide-main-img">
                    </div>
                @empty
                    <div class="carousel-slide active">
                        <div class="no-banner-placeholder">
                            <i class="fa-regular fa-image fa-3x"></i>
                            <p>No active school announcements today.</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>

        @if($announcements->count() > 0)
            <div class="carousel-dots">
                @foreach($announcements as $index => $item)
                    <span class="dot {{ $index === 0 ? 'active' : '' }}" onclick="currentSlide({{ $index }})"></span>
                @endforeach
            </div>
        @endif
    </div>

    <div class="stats-dashboard-grid">
        
        <a href="{{ route('student.my_classes') }}" class="stat-premium-card" style="text-decoration: none; cursor: pointer;">
            <div class="stat-number-side text-blue">
                {{ $totalEnrolledClasses }}
            </div>
            <div class="stat-label-side">
                <span class="lbl-main">My Enrolled</span>
                <span class="lbl-sub">Classes</span>
            </div>
            <div class="stat-icon-watermark text-blue-icon">
                <i class="fa-solid fa-graduation-cap"></i>
            </div>
        </a>

        <div class="stat-premium-card">
            <div class="stat-number-side text-orange">
                {{ $pendingTasksCount }}
            </div>
            <div class="stat-label-side">
                <span class="lbl-main">Pending</span>
                <span class="lbl-sub">Tasks/Activities</span>
            </div>
            <div class="stat-icon-watermark text-orange-icon">
                <i class="fa-solid fa-clock-rotate-left"></i>
            </div>
        </div>
    </div>

    <!-- INSTITUTIONAL VISION, MISSION & CORE VALUES SECTION -->
    <div class="institutional-section">
        <div class="section-title-wrapper">
            <div class="section-title">
                <i class="fa-solid fa-graduation-cap text-blue-icon"></i> ABOUT ARELLANO UNIVERSITY
            </div>
            <!-- TAB TOGGLE BUTTONS -->
            <div class="tab-toggle-container">
                <button class="tab-btn active" onclick="switchInstitutionalTab('au', this)">
                    <i class="fa-solid fa-university"></i> Arellano University (AU)
                </button>
                <button class="tab-btn" onclick="switchInstitutionalTab('scs', this)">
                    <i class="fa-solid fa-laptop-code"></i> School of Computer Studies (SCS)
                </button>
            </div>
        </div>

        <!-- TAB 1: ARELLANO UNIVERSITY MAIN -->
        <div id="tab-au" class="institutional-tab-content active">
            <div class="institutional-grid">
                <!-- VISION CARD -->
                <div class="inst-card">
                    <div class="inst-card-header">
                        <i class="fa-solid fa-eye text-blue-icon"></i>
                        <h3>AU Vision</h3>
                    </div>
                    <div class="inst-card-body">
                        <p>To be a model institution of learning where relevant knowledge is acquired and skills are developed in response to the needs of the global community.</p>
                    </div>
                </div>

                <!-- MISSION CARD -->
                <div class="inst-card">
                    <div class="inst-card-header">
                        <i class="fa-solid fa-compass text-orange-icon"></i>
                        <h3>AU Mission</h3>
                    </div>
                    <div class="inst-card-body">
                        <p>To provide equitable access to learning through relevant, innovative, industry-sensitive and environment-conscious academic program and services.</p>
                    </div>
                </div>

                <!-- CORE VALUES CARD -->
                <div class="inst-card">
                    <div class="inst-card-header">
                        <i class="fa-solid fa-shield-halved text-blue-icon"></i>
                        <h3>Core Values (CHIEFS)</h3>
                    </div>
                    <div class="inst-card-body">
                        <ul class="values-list">
                            <li><strong class="val-letter">C</strong> — Competence</li>
                            <li><strong class="val-letter">H</strong> — Humility</li>
                            <li><strong class="val-letter">I</strong> — Integrity</li>
                            <li><strong class="val-letter">E</strong> — Equity</li>
                            <li><strong class="val-letter">F</strong> — Fortitude</li>
                            <li><strong class="val-letter">S</strong> — Stewardships</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 2: SCHOOL OF COMPUTER STUDIES (SCS) -->
        <div id="tab-scs" class="institutional-tab-content">
            <div class="institutional-grid">
                <!-- SCS VISION CARD -->
                <div class="inst-card">
                    <div class="inst-card-header">
                        <i class="fa-solid fa-eye text-blue-icon"></i>
                        <h3>SCS Vision</h3>
                    </div>
                    <div class="inst-card-body">
                        <p>Implementing active learning strategies and methods in the teaching and learning process, SCS mentors students to apply practical wisdom in life and to focus on reaching a goal.</p>
                    </div>
                </div>

                <!-- SCS MISSION CARD -->
                <div class="inst-card">
                    <div class="inst-card-header">
                        <i class="fa-solid fa-compass text-orange-icon"></i>
                        <h3>SCS Mission</h3>
                    </div>
                    <div class="inst-card-body">
                        <p>School of Computer Studies at Arellano University, Pasig Campus advocating progressive education will produce graduates who are leaders in their chosen professions and are preferred by any industry for their employer-desired work values.</p>
                    </div>
                </div>

                <!-- SCS DEPT CORE VALUES CARD -->
                <div class="inst-card">
                    <div class="inst-card-header">
                        <i class="fa-solid fa-shield-halved text-blue-icon"></i>
                        <h3>Core Values (CHIEFS)</h3>
                    </div>
                    <div class="inst-card-body">
                        <ul class="values-list">
                            <li><strong class="val-letter">C</strong> — Competence</li>
                            <li><strong class="val-letter">H</strong> — Humility</li>
                            <li><strong class="val-letter">I</strong> — Integrity</li>
                            <li><strong class="val-letter">E</strong> — Equity</li>
                            <li><strong class="val-letter">F</strong> — Fortitude</li>
                            <li><strong class="val-letter">S</strong> — Stewardships</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
    let slideIndex = 0;
    const slides = document.querySelectorAll('.carousel-slide');
    const dots = document.querySelectorAll('.dot');

    function showSlides(index) {
        if (slides.length === 0) return;
        if (index >= slides.length) slideIndex = 0;
        if (index < 0) slideIndex = slides.length - 1;
        
        slides.forEach(slide => slide.classList.remove('active'));
        dots.forEach(dot => dot.classList.remove('active'));
        
        if (slides[slideIndex]) slides[slideIndex].classList.add('active');
        if (dots[slideIndex]) dots[slideIndex].classList.add('active');
    }

    function currentSlide(index) {
        slideIndex = index;
        showSlides(slideIndex);
    }

    if (slides.length > 1) {
        setInterval(() => {
            slideIndex++;
            showSlides(slideIndex);
        }, 6000);
    }

    function switchInstitutionalTab(tabName, btnElement) {
        document.querySelectorAll('.institutional-tab-content').forEach(tab => tab.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));

        document.getElementById('tab-' + tabName).classList.add('active');
        btnElement.classList.add('active');
    }
</script>

<style>
    .dashboard-wrapper {
        max-width: 1140px; 
        margin: 0 auto; 
        font-family: 'Inter', 'Segoe UI', system-ui, sans-serif; 
    }

    .announcement-box {
        background: #ffffff; 
        border: 3px solid #1e293b;
        border-radius: 16px;
        padding: 12px;
        position: relative; 
        height: 340px; 
        display: flex;
        flex-direction: column;
    }

    .announcement-tag {
        position: absolute; 
        top: 22px; 
        left: 26px; 
        font-weight: 800;
        font-size: 12px; 
        color: #1e293b; 
        z-index: 10; 
        letter-spacing: 1px;
        text-transform: uppercase;
        background: rgba(255, 255, 255, 0.92);
        padding: 4px 10px;
        border-radius: 6px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .carousel-track { 
        width: 100%; 
        height: 100%; 
        position: relative; 
        border: 2px solid #1e293b; 
        border-radius: 12px;
        overflow: hidden;
        background: #ffffff;
    }
    
    .carousel-slide { 
        position: absolute; 
        top: 0; 
        left: 0; 
        width: 100%; 
        height: 100%; 
        opacity: 0; 
        transition: opacity 0.6s ease-in-out; 
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    
    .carousel-slide.active { opacity: 1; }

    /* Blurred Backdrop Effect */
    .slide-bg-blur {
        position: absolute;
        top: -10%;
        left: -10%;
        width: 120%;
        height: 120%;
        background-size: cover;
        background-position: center;
        filter: blur(14px) brightness(0.75);
        transform: scale(1.1);
        z-index: 1;
    }

    /* Main Crisp Banner Image */
    .slide-main-img { 
        position: relative;
        z-index: 2;
        max-width: 100%; 
        max-height: 100%; 
        object-fit: contain; 
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
    }
    
    .no-banner-placeholder { height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #64748b; }

    .carousel-dots { text-align: center; margin-top: 14px; }
    .dot { cursor: pointer; height: 14px; width: 14px; margin: 0 6px; background-color: #ffffff; border: 3px solid #1e293b; border-radius: 50%; display: inline-block; transition: 0.2s; }
    .dot.active { background-color: #f59e0b; }

    .stats-dashboard-grid { 
        display: grid; 
        grid-template-columns: repeat(2, 1fr); 
        gap: 20px; 
        width: 100%;
        margin-top: 25px;
        margin-bottom: 35px !important;
    }

    .stat-premium-card {
        background: #ffffff; 
        border: 3px solid #1e293b; 
        border-radius: 16px;
        height: 110px; 
        display: flex; 
        align-items: center; 
        padding: 0 24px;
        position: relative;
        overflow: hidden;
    }

    .stat-number-side {
        font-size: 40px; 
        font-weight: 900;
        line-height: 1;
        letter-spacing: -1px;
        margin-right: 15px;
        min-width: 55px; 
    }

    .stat-label-side {
        display: flex;
        flex-direction: column;
        justify-content: center;
        z-index: 2;
    }

    .stat-label-side .lbl-main {
        font-size: 13px;
        font-weight: 800;
        color: #1e293b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        line-height: 1.2;
    }

    .stat-label-side .lbl-sub {
        font-size: 15px;
        font-weight: 600;
        color: #475569;
        line-height: 1.2;
    }

    .stat-icon-watermark {
        position: absolute;
        right: 24px;
        font-size: 32px; 
        opacity: 1; 
        z-index: 1;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* INSTITUTIONAL SECTION & TAB STYLES */
    .institutional-section {
        margin-top: 20px !important;
        margin-bottom: 40px !important;
    }

    .section-title-wrapper {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 18px !important;
    }

    .section-title {
        font-size: 14px;
        font-weight: 800;
        color: #1e293b;
        letter-spacing: 0.8px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    /* TAB TOGGLE STYLES */
    .tab-toggle-container {
        display: flex;
        background: #e2e8f0;
        border: 2px solid #1e293b;
        border-radius: 30px;
        padding: 3px;
        gap: 4px;
    }

    .tab-btn {
        border: none;
        background: transparent;
        color: #475569;
        font-size: 12px;
        font-weight: 700;
        padding: 6px 14px;
        border-radius: 20px;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .tab-btn.active {
        background: #1e293b;
        color: #ffffff;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .tab-btn.active i {
        color: #f59e0b;
    }

    /* TAB CONTENT TOGGLING */
    .institutional-tab-content {
        display: none;
    }

    .institutional-tab-content.active {
        display: block;
    }

    .institutional-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
    }

    .inst-card {
        background: #ffffff;
        border: 3px solid #1e293b;
        border-radius: 16px;
        padding: 20px;
        display: flex;
        flex-direction: column;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }

    .inst-card-header {
        display: flex;
        align-items: center;
        gap: 10px;
        border-bottom: 2px solid #e2e8f0;
        padding-bottom: 10px;
        margin-bottom: 12px;
    }

    .inst-card-header i {
        font-size: 18px;
    }

    .inst-card-header h3 {
        font-size: 15px;
        font-weight: 800;
        color: #1e293b;
        margin: 0;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .inst-card-body p {
        font-size: 13px;
        color: #475569;
        line-height: 1.6;
        margin: 0;
        font-weight: 500;
    }

    .values-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 8px 12px;
    }

    .values-list li {
        font-size: 13px;
        color: #475569;
        font-weight: 600;
    }

    .val-letter {
        color: #1e3a8a;
        font-weight: 900;
        font-size: 14px;
    }

    .text-blue { color: #1e3a8a; }
    .text-orange { color: #c2410c; }

    .text-blue-icon { color: #2563eb; }
    .text-orange-icon { color: #ea580c; }

    .alert-banner { 
        padding: 14px 18px; 
        border-radius: 8px; 
        margin-bottom: 20px; 
        font-size: 14px; 
        font-weight: 600; 
        border: none;
        box-shadow: 0 2px 4px rgba(0,0,0,0.04);
    }
    .alert-success { background: #dcfce7; color: #14532d; }
    .alert-danger { background: #fee2e2; color: #991b1b; }

    @media (max-width: 900px) {
        .institutional-grid { grid-template-columns: 1fr; }
        .section-title-wrapper { flex-direction: column; align-items: flex-start; }
    }

    @media (max-width: 640px) {
        .stats-dashboard-grid { grid-template-columns: 1fr; }
    }
</style>
@endsection