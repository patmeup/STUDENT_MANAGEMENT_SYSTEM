@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="dashboard-wrapper py-4 px-3">
    
    @if(session('success'))
        <div class="alert-banner alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert-banner alert-danger">
            <i class="fa-solid fa-circle-exclamation"></i> Upload error! Please check the size or format of the image.
        </div>
    @endif

    <div class="announcement-carousel-wrapper mb-4">
        <div class="announcement-box">
            <span class="announcement-tag">ANNOUNCEMENT:</span>
            
            <div class="carousel-track" id="carouselTrack">
                @forelse($announcements as $index => $item)
                    <div class="carousel-slide {{ $index === 0 ? 'active' : '' }}">
                        <!-- Blurred background for side fill -->
                        <div class="slide-blur-bg" style="background-image: url('{{ asset('storage/' . $item->image_path) }}');"></div>
                        <!-- Main Crisp Image -->
                        <img src="{{ asset('storage/' . $item->image_path) }}" alt="{{ $item->title ?? 'Announcement Banner' }}" class="main-slide-img">
                    </div>
                @empty
                    <div class="carousel-slide active">
                        <div class="no-banner-placeholder">
                            <i class="fa-regular fa-image fa-3x mb-2"></i>
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
            <div class="stat-number-side text-green">
                {{ $totalStudents }}
            </div>
            <div class="stat-label-side">
                <span class="lbl-main">Total</span>
                <span class="lbl-sub">Students</span>
            </div>
            <div class="stat-icon-right text-green">
                <i class="fa-solid fa-graduation-cap"></i>
            </div>
        </div>

        <div class="stat-premium-card">
            <div class="stat-number-side text-orange">
                {{ $pendingApprovals ?? 0 }}
            </div>
            <div class="stat-label-side">
                <span class="lbl-main">Pending</span>
                <span class="lbl-sub">Document Requests</span>
            </div>
            <div class="stat-icon-right text-orange">
                <i class="fa-solid fa-clock-rotate-left"></i>
            </div>
        </div>

        <div class="stat-premium-card">
            <div class="stat-number-side text-purple">
                {{ $academicRecords ?? 0 }}
            </div>
            <div class="stat-label-side">
                <span class="lbl-main">Academic</span>
                <span class="lbl-sub">Records</span>
            </div>
            <div class="stat-icon-right text-purple">
                <i class="fa-solid fa-file-invoice"></i>
            </div>
        </div>

    </div>

    <hr class="my-5">

    <div class="management-panel">
        <h3 class="panel-title"><i class="fa-solid fa-sliders"></i> Announcement Banner Management</h3>
        
        <form action="{{ route('registrar.announcements.store') }}" method="POST" enctype="multipart/form-data" class="upload-inline-form mb-4">
            @csrf
            <div class="form-group-custom">
                <input type="text" name="title" placeholder="Banner Title / Description (Optional)" class="form-control-custom">
                <input type="file" name="banner_image" accept="image/*" required class="form-file-input">
                <button type="submit" class="btn-upload"><i class="fa-solid fa-plus"></i> Upload Banner</button>
            </div>
        </form>

        <div class="uploaded-banners-table">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h4 style="font-size: 14px; font-weight: 700; color: #334155;">Active Banners (Drag to reorder priority)</h4>
                <span id="reorder-status" class="text-success font-weight-bold" style="display:none; font-size: 13px;"><i class="fa-solid fa-check"></i> Order Saved! Refreshing...</span>
            </div>

            @if($announcements->count() > 0)
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th style="width: 40px;"></th>
                            <th>Preview</th>
                            <th>Banner Title</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="draggable-banner-list">
                        @foreach($announcements as $item)
                            <tr draggable="true" data-id="{{ $item->id }}" class="drag-row">
                                <td class="drag-handle" title="Click and drag to reorder"><i class="fa-solid fa-grip-vertical"></i></td>
                                <td><img src="{{ asset('storage/' . $item->image_path) }}" class="table-preview-img" alt="Preview"></td>
                                <td>{{ $item->title ?? 'Untitled Banner' }}</td>
                                <td>
                                    <form action="{{ route('registrar.announcements.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this banner?');">
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
    // CAROUSEL AUTOMATION
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

    // DRAG AND DROP REORDER LOGIC
    const tbody = document.getElementById('draggable-banner-list');
    let draggedRow = null;

    if (tbody) {
        tbody.addEventListener('dragstart', (e) => {
            draggedRow = e.target.closest('tr');
            e.target.closest('tr').classList.add('dragging');
        });

        tbody.addEventListener('dragend', (e) => {
            e.target.closest('tr').classList.remove('dragging');
            saveNewOrder();
        });

        tbody.addEventListener('dragover', (e) => {
            e.preventDefault();
            const afterElement = getDragAfterElement(tbody, e.clientY);
            if (afterElement == null) {
                tbody.appendChild(draggedRow);
            } else {
                tbody.insertBefore(draggedRow, afterElement);
            }
        });
    }

    function getDragAfterElement(container, y) {
        const draggableElements = [...container.querySelectorAll('.drag-row:not(.dragging)')];

        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            } else {
                return closest;
            }
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }

    function saveNewOrder() {
        const rows = document.querySelectorAll('.drag-row');
        const order = Array.from(rows).map(row => row.getAttribute('data-id'));

        fetch("{{ route('registrar.announcements.reorder') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ order: order })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const statusEl = document.getElementById('reorder-status');
                statusEl.style.display = 'inline-block';
                setTimeout(() => {
                    location.reload();
                }, 800);
            }
        })
        .catch(error => console.error('Error reordering:', error));
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
        background: #0f172a;
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

    /* Blurred Backdrop Effect sa Gilid */
    .slide-blur-bg {
        position: absolute;
        top: -10%;
        left: -10%;
        width: 120%;
        height: 120%;
        background-size: cover;
        background-position: center;
        filter: blur(20px) brightness(0.4);
        z-index: 1;
        transform: scale(1.1);
    }

    /* Main Poster/Banner Image sa Gitna */
    .main-slide-img { 
        position: relative;
        z-index: 2;
        max-width: 100%; 
        max-height: 100%; 
        width: auto;
        height: auto;
        object-fit: contain; 
        box-shadow: 0 8px 25px rgba(0,0,0,0.5);
    }
    
    .no-banner-placeholder { height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #64748b; }

    .carousel-dots { text-align: center; margin-top: 14px; }
    .dot { cursor: pointer; height: 14px; width: 14px; margin: 0 6px; background-color: #ffffff; border: 3px solid #1e293b; border-radius: 50%; display: inline-block; transition: 0.2s; }
    .dot.active { background-color: #f59e0b; }

    .stats-dashboard-grid { 
        display: grid; 
        grid-template-columns: repeat(3, 1fr); 
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
        color: inherit;
    }

    .stat-number-side {
        font-size: 40px; 
        font-weight: 900;
        line-height: 1;
        letter-spacing: -1px;
        margin-right: 15px;
        min-width: 55px; 
        display: flex;
        align-items: center;
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
        font-size: 12px;
        font-weight: 600;
        color: #64748b;
    }

    .stat-icon-right {
        position: absolute;
        right: 24px;
        font-size: 28px;
        z-index: 2;
    }

    .text-green { color: #10b981; }
    .text-orange { color: #f59e0b; }
    .text-purple { color: #8b5cf6; }

    .management-panel {
        background: #ffffff;
        border: 3px solid #1e293b;
        border-radius: 16px;
        padding: 24px;
    }

    .panel-title {
        font-size: 18px;
        font-weight: 800;
        color: #1e293b;
        margin-bottom: 20px;
    }

    .upload-inline-form .form-group-custom {
        display: flex;
        gap: 12px;
        align-items: center;
    }

    .form-control-custom, .form-file-input {
        border: 2px solid #cbd5e1;
        border-radius: 8px;
        padding: 10px 14px;
        font-size: 14px;
        outline: none;
    }
    .form-control-custom { flex: 1; }
    .form-file-input { background: #f8fafc; }

    .btn-upload {
        background-color: #10b981;
        color: white;
        border: none;
        padding: 10px 20px;
        font-weight: 700;
        border-radius: 8px;
        cursor: pointer;
        transition: background 0.2s;
    }
    .btn-upload:hover { background-color: #059669; }

    .custom-table {
        width: 100%;
        border-collapse: collapse;
    }
    .custom-table th, .custom-table td {
        padding: 12px;
        border-bottom: 1px solid #e2e8f0;
        text-align: left;
        vertical-align: middle;
        font-size: 14px;
    }
    .custom-table th {
        background: #f8fafc;
        font-size: 12px;
        text-transform: uppercase;
        color: #64748b;
        font-weight: 700;
    }

    .table-preview-img {
        width: 110px;
        height: 50px;
        object-fit: cover;
        border-radius: 6px;
        border: 1px solid #cbd5e1;
    }

    .btn-delete-banner {
        background-color: #ef4444;
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
    }
    .btn-delete-banner:hover { background-color: #dc2626; }

    /* Drag and Drop CSS */
    .drag-row { cursor: move; transition: background 0.15s ease; }
    .drag-row.dragging { opacity: 0.4; background: #f1f5f9; }
    .drag-handle { color: #94a3b8; cursor: grab; font-size: 16px; text-align: center; }
    .drag-handle:active { cursor: grabbing; }

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
</style>
@endsection