@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="dashboard-wrapper py-4 px-3">
    
    @if(session('success'))
        <div class="alert-banner alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert-banner alert-danger">
            <i class="fa-solid fa-circle-exclamation"></i> Upload error! Please check the size or format of the image.
        </div>
    @endif

    <div class="announcement-carousel-wrapper">
        <div class="announcement-box">
            <span class="announcement-tag">ANNOUNCEMENT:</span>
            
            <div class="carousel-track" id="carouselTrack">
                @forelse($announcements as $index => $item)
                    <div class="carousel-slide {{ $index === 0 ? 'active' : '' }}">
                        <img src="{{ asset('storage/' . $item->image_path) }}" alt="{{ $item->title ?? 'Announcement Banner' }}">
                    </div>
                @empty
                    <div class="carousel-slide active">
                        <div class="no-banner-placeholder">
                            <i class="fa-regular fa-image fa-3x"></i>
                            <p>No active announcements. Upload banners below.</p>
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

    <div class="stats-dashboard-grid mb-5">
        
        <div class="stat-premium-card">
            <div class="stat-number-side text-blue">
                {{ $totalEmployees }}
            </div>
            <div class="stat-label-side">
                <span class="lbl-main">Total</span>
                <span class="lbl-sub">Employees</span>
            </div>
            <div class="stat-icon-watermark text-blue-icon">
                <i class="fa-solid fa-users-gear"></i>
            </div>
        </div>
        
        <div class="stat-premium-card">
            <div class="stat-number-side text-green">
                {{ $totalStudents }}
            </div>
            <div class="stat-label-side">
                <span class="lbl-main">Total</span>
                <span class="lbl-sub">Students</span>
            </div>
            <div class="stat-icon-watermark text-green-icon">
                <i class="fa-solid fa-graduation-cap"></i>
            </div>
        </div>

        <div class="stat-premium-card">
            <div class="stat-number-side text-orange">
                {{ $totalCourses }}
            </div>
            <div class="stat-label-side">
                <span class="lbl-main">Active</span>
                <span class="lbl-sub">Courses</span>
            </div>
            <div class="stat-icon-watermark text-orange-icon">
                <i class="fa-solid fa-book-bookmark"></i>
            </div>
        </div>

        <div class="stat-premium-card">
            <div class="stat-number-side text-purple">
                {{ $totalSubjects }}
            </div>
            <div class="stat-label-side">
                <span class="lbl-main">Total</span>
                <span class="lbl-sub">Subjects</span>
            </div>
            <div class="stat-icon-watermark text-purple-icon">
                <i class="fa-solid fa-folder-open"></i>
            </div>
        </div>

    </div>

    <hr class="my-5">

    <div class="management-panel">
        <h3 class="panel-title"><i class="fa-solid fa-sliders"></i> Carousel Image Management Panel</h3>
        
        <form action="{{ route('super_admin.announcements.store') }}" method="POST" enctype="multipart/form-data" class="upload-inline-form mb-4">
            @csrf
            <div class="form-group-custom">
                <input type="text" name="title" placeholder="Banner Title / Label (Optional)" class="form-control-custom">
                <input type="file" name="announcement_image" accept="image/*" required class="form-file-input">
                <button type="submit" class="btn-upload"><i class="fa-solid fa-plus"></i> Upload Banner</button>
            </div>
        </form>

        <div class="uploaded-banners-table">
            <h4>Current Active Banners (Max Recommended: 5)</h4>
            @if($announcements->count() > 0)
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Preview</th>
                            <th>Label/Title</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($announcements as $item)
                            <tr>
                                <td><img src="{{ asset('storage/' . $item->image_path) }}" class="table-preview-img"></td>
                                <td>{{ $item->title ?? 'Untitled Banner' }}</td>
                                <td>
                                    <form action="{{ route('super_admin.announcements.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this banner?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-delete-banner"><i class="fa-solid fa-trash-can"></i> Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-muted text-sm">No banners found in the system.</p>
            @endif
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

    .announcement-box {
        background: #e2e8f0; 
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
        background: rgba(255, 255, 255, 0.85);
        padding: 3px 8px;
        border-radius: 4px;
    }

    .carousel-track { 
        width: 100%; 
        height: 100%; 
        position: relative; 
        border: 3px solid #1e293b; 
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
    }
    
    .carousel-slide.active { opacity: 1; }

    .carousel-slide img { 
        width: 100%; 
        height: 100%; 
        object-fit: fill; 
    }
    
    .no-banner-placeholder { height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #64748b; }

    .carousel-dots { text-align: center; margin-top: 14px; }
    .dot { cursor: pointer; height: 14px; width: 14px; margin: 0 6px; background-color: #ffffff; border: 3px solid #1e293b; border-radius: 50%; display: inline-block; transition: 0.2s; }
    .dot.active { background-color: #f59e0b; }

    .stats-dashboard-grid { 
        display: grid; 
        grid-template-columns: repeat(4, 1fr); 
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

    .text-blue-icon { color: #2563eb; }
    .text-green-icon { color: #16a34a; }
    .text-orange-icon { color: #ea580c; }
    .text-purple-icon { color: #9333ea; }

    .management-panel { 
        background: #ffffff; 
        border: 3px solid #1e293b; 
        border-radius: 16px; 
        padding: 24px;
        margin-top: 25px;
    }

    .panel-title { font-size: 16px; font-weight: 800; margin-bottom: 18px; color: #1e293b; }
    .form-group-custom { display: flex; gap: 10px; flex-wrap: wrap; }
    .form-control-custom { flex: 1; min-width: 200px; padding: 8px 12px; border: 2px solid #cbd5e1; border-radius: 6px; }
    .form-file-input { padding: 6px; }
    .btn-upload { background: #1e293b; color: white; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 700; cursor: pointer; }
    
    .custom-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .custom-table th, .custom-table td { padding: 10px; text-align: left; border-bottom: 2px solid #e2e8f0; font-size: 14px; }
    .table-preview-img { width: 110px; height: 50px; object-fit: cover; border-radius: 4px; border: 2px solid #cbd5e1; }
    .btn-delete-banner { background: #ef4444; color: white; border: none; padding: 5px 10px; border-radius: 4px; font-size: 12px; cursor: pointer; font-weight: 600; }

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

    @media (max-width: 1024px) {
        .stats-dashboard-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 640px) {
        .stats-dashboard-grid { grid-template-columns: 1fr; }
    }
</style>
@endsection