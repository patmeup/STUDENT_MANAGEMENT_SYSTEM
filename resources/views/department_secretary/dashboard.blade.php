@extends('layouts.app')

@section('content')
<style>
    .dashboard-container {
        padding: 24px;
        background-color: #f8fafc;
        min-height: 100vh;
        width: 100%;
        font-family: system-ui, -apple-system, sans-serif;
        box-sizing: border-box;
    }
    
    .welcome-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background-color: #ffffff;
        padding: 24px;
        border-radius: 8px;
        border: 1px solid #f1f5f9;
        margin-bottom: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }

    .welcome-title {
        font-size: 24px;
        font-weight: 700;
        color: #1e293b;
        margin: 0 0 4px 0;
    }

    .welcome-subtitle {
        font-size: 14px;
        color: #64748b;
        margin: 0;
    }

    .btn-deploy {
        background-color: #0b44ad;
        color: #ffffff;
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        text-decoration: none;
        transition: background-color 0.2s;
    }
    .btn-deploy:hover {
        background-color: #1d4ed8;
    }

    /* Metric Grid Layout */
    .metric-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }

    .metric-card {
        background-color: #ffffff;
        padding: 20px;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        position: relative;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }

    .card-title {
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #94a3b8;
        margin: 0;
    }

    .card-value {
        font-size: 30px;
        font-weight: 700;
        color: #1e293b;
        margin: 8px 0 0 0;
    }

    .card-badge {
        position: absolute;
        top: 16px;
        right: 16px;
        font-size: 12px;
        font-weight: 700;
        padding: 4px 8px;
        border-radius: 4px;
    }
    .badge-blue { bg-color wrapper imitation */ background-color: #eff6ff; color: #0b44ad; }
    .badge-gray { background-color: #f1f5f9; color: #475569; }
    .badge-green { background-color: #ecfdf5; color: #059669; }

    /* Bottom Split Grid Layout */
    .split-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 24px;
    }
    @media (max-width: 992px) {
        .split-grid { grid-template-columns: 1fr; }
    }

    .panel-card {
        background-color: #ffffff;
        padding: 20px;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }

    .panel-title {
        font-size: 18px;
        font-weight: 700;
        color: #1e293b;
        margin: 0 0 16px 0;
    }

    .empty-text {
        color: #94a3b8;
        font-size: 14px;
        text-align: center;
        margin: 40px 0;
    }

    /* Block Items list */
    .block-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .block-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px;
        background-color: #f8fafc;
        border-radius: 8px;
        border: 1px solid #f1f5f9;
    }

    .block-badge {
        background-color: #eff6ff;
        color: #2563eb;
        font-weight: 700;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
    }

    .block-info {
        font-size: 14px;
        color: #475569;
    }
</style>

<div class="dashboard-container">
  
  <div class="welcome-row">
    <div>
      <h1 class="welcome-title">Department Dashboard</h1>
      <p class="welcome-subtitle">Department: {{ $dept ?? '—' }} Academic Year Center Overview</p>
    </div>
    <div>
      <a href="/department_secretary/manage-schedules" class="btn-deploy">+ Deploy Class</a>
    </div>
  </div>

  <div class="metric-grid">
    <div class="metric-card">
      <p class="card-title">Tracked Subjects</p>
      <p class="card-value">{{ $trackedSubjectsCount }}</p>
      <span class="card-badge badge-blue">Curriculum</span>
    </div>
    
    <div class="metric-card">
      <p class="card-title">Total Students</p>
      <p class="card-value">{{ $totalStudentsCount }}</p>
      <span class="card-badge badge-gray">Enrolled</span>
    </div>

    <div class="metric-card">
      <p class="card-title">Active Blocks</p>
      <p class="card-value">{{ $activeBlocksCount }}</p>
      <span class="card-badge badge-green">Live</span>
    </div>

    <div class="metric-card">
      <p class="card-title">Deployed Slots</p>
      <p class="card-value">{{ $deployedSlotsCount }}</p>
      <span class="card-badge badge-blue">Active Classes</span>
    </div>
  </div>

  <div class="split-grid">
    <div class="panel-card">
      <h3 class="panel-title" style="text-align: left;">Today's Deployment Highlights</h3>
      <p class="empty-text">No recent schedule modification alerts for today.</p>
    </div>

    <div class="panel-card">
      <h3 class="panel-title">Quick Block Summary</h3>
      <div class="block-list">
        <div class="block-item">
          <span class="block-badge">BSIT1-A</span>
          <span class="block-info">4 Active Subjects</span>
        </div>
        <div class="block-item">
          <span class="block-badge">BSIT1-B</span>
          <span class="block-info">4 Active Subjects</span>
        </div>
        <div class="block-item">
          <span class="block-badge">BSIT1-C</span>
          <span class="block-info">4 Active Subjects</span>
        </div>
      </div>
    </div>
  </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        console.log("Secretary Dashboard Script Ready!");
        // You can add your interactive dashboard alerts or click listeners here later!
    });
</script>
@endsection