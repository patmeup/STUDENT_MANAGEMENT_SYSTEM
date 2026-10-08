@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="dashboard-wrapper py-4 px-3">

    {{-- Alert Notifications --}}
    @if(session('success'))
        <div class="alert-banner alert-success">
            <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert-banner alert-danger">
            <i class="fa-solid fa-circle-exclamation"></i>
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="announcement-carousel-wrapper mb-4">
        <div class="announcement-box">
            <span class="announcement-tag">ANNOUNCEMENT:</span>

            <div class="carousel-track" id="carouselTrack">
                @forelse($announcements as $index => $item)
                    <div class="carousel-slide {{ $index === 0 ? 'active' : '' }}">
                        <!-- Blurred background image -->
                        <img src="{{ asset('storage/' . $item->image_path) }}" class="slide-bg-blur" alt="Background Blur">
                        <!-- Main centered image -->
                        <img src="{{ asset('storage/' . $item->image_path) }}" class="slide-main-img" alt="{{ $item->title ?? 'Announcement Banner' }}">
                    </div>
                @empty
                    <div class="carousel-slide active">
                        <div class="no-banner-placeholder">
                            <i class="fa-regular fa-image fa-2x"></i>
                            <p style="margin-top: 6px; font-size: 13px;">No active announcements.</p>
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

        <div class="stat-premium-card">
            <div class="stat-number-side text-blue">
                {{ $trackedSubjectsCount }}
            </div>
            <div class="stat-label-side">
                <span class="lbl-main">Tracked</span>
                <span class="lbl-sub">Subjects</span>
            </div>
            <div class="stat-icon-watermark text-blue-icon">
                <i class="fa-solid fa-bookmark"></i>
            </div>
        </div>

        <div class="stat-premium-card">
            <div class="stat-number-side text-green">
                {{ $totalStudentsCount }}
            </div>
            <div class="stat-label-side">
                <span class="lbl-main">Total</span>
                <span class="lbl-sub">Students</span>
            </div>
            <div class="stat-icon-watermark text-green-icon">
                <i class="fa-solid fa-user-graduate"></i>
            </div>
        </div>

        <div class="stat-premium-card">
            <div class="stat-number-side text-orange">
                {{ $activeBlocksCount }}
            </div>
            <div class="stat-label-side">
                <span class="lbl-main">Total Block</span>
                <span class="lbl-sub">Sections</span>
            </div>
            <div class="stat-icon-watermark text-orange-icon">
                <i class="fa-solid fa-users-rectangle"></i>
            </div>
        </div>

        <div class="stat-premium-card">
            <div class="stat-number-side text-purple">
                {{ $deployedSlotsCount }}
            </div>
            <div class="stat-label-side">
                <span class="lbl-main">Scheduled</span>
                <span class="lbl-sub">Classes</span>
            </div>
            <div class="stat-icon-watermark text-purple-icon">
                <i class="fa-solid fa-calendar-days"></i>
            </div>
        </div>

        <div class="stat-premium-card card-centered">
            <div class="stat-number-side text-teal">
                {{ $totalFacultyCount }}
            </div>
            <div class="stat-label-side">
                <span class="lbl-main">Department</span>
                <span class="lbl-sub">Faculty</span>
            </div>
            <div class="stat-icon-watermark text-teal-icon">
                <i class="fa-solid fa-chalkboard-user"></i>
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
</script>

<style>
    .dashboard-wrapper {
        max-width: 1140px;
        margin: 0 auto;
        font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
    }

    .announcement-carousel-wrapper {
        width: 100%;
        margin: 0 auto 24px auto;
    }

    /* OUTER BOX CONTAINER */
    .announcement-box {
        background: #f1f5f9;
        border: 3px solid #1e293b;
        border-radius: 16px;
        padding: 10px;
        position: relative;
        height: 280px;
        display: flex;
        flex-direction: column;
    }

    .announcement-tag {
        position: absolute;
        top: 20px;
        left: 24px;
        font-weight: 800;
        font-size: 11px;
        color: #1e293b;
        z-index: 10;
        letter-spacing: 1px;
        text-transform: uppercase;
        background: rgba(255, 255, 255, 0.95);
        padding: 3px 8px;
        border-radius: 4px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.15);
    }

    .carousel-track {
        width: 100%;
        height: 100%;
        position: relative;
        border: 2px solid #1e293b;
        border-radius: 10px;
        overflow: hidden;
        background: #000000;
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

    /* BLURRED BACKGROUND IMAGE EFFECT */
    .slide-bg-blur {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        filter: blur(12px) brightness(0.65);
        transform: scale(1.1);
        z-index: 1;
    }

    /* CENTERED MAIN BANNER IMAGE */
    .slide-main-img {
        position: relative;
        z-index: 2;
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
        box-shadow: 0 4px 15px rgba(0,0,0,0.3);
    }

    .no-banner-placeholder {
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: #64748b;
        background: #ffffff;
        width: 100%;
    }

    .carousel-dots { text-align: center; margin-top: 10px; }
    .dot { cursor: pointer; height: 12px; width: 12px; margin: 0 5px; background-color: #ffffff; border: 2px solid #1e293b; border-radius: 50%; display: inline-block; transition: 0.2s; }
    .dot.active { background-color: #f59e0b; }

    /* STATS GRID */
    .stats-dashboard-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
        width: 100%;
        margin-top: 25px;
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

    .stat-premium-card.card-centered {
        grid-column: 1 / -1;
        justify-self: center;
        width: calc(50% - 10px);
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

    .text-blue { color: #1e3a8a; }
    .text-green { color: #15803d; }
    .text-orange { color: #c2410c; }
    .text-purple { color: #6b21a8; }
    .text-teal { color: #0f766e; }

    .text-blue-icon { color: #2563eb; }
    .text-green-icon { color: #16a34a; }
    .text-orange-icon { color: #ea580c; }
    .text-purple-icon { color: #9333ea; }
    .text-teal-icon { color: #14b8a6; }

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

    @media (max-width: 768px) {
        .announcement-box { height: 200px; }
        .stats-dashboard-grid {
            grid-template-columns: 1fr;
        }
        .stat-premium-card.card-centered {
            width: 100%;
        }
    }
</style>
@endsection
