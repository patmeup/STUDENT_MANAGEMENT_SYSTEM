@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="dashboard-wrapper py-4 px-3">

    @if(session('success'))
        <div class="alert-banner alert-success">
            <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert-banner alert-danger">
            <i class="fa-solid fa-circle-exclamation"></i> {{ session('error') }}
        </div>
    @endif

    <!-- TOP HEADER & RETRAIN BUTTON -->
    <div class="management-panel mt-0 mb-4" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
        <div>
            <h3 class="panel-title" style="margin: 0; display: flex; align-items: center; gap: 10px;">
                <i class="fa-solid fa-brain text-blue-icon"></i> Machine Learning Predictor Status
            </h3>
            <p class="text-muted text-sm" style="margin: 4px 0 0 0; font-size: 13px; color: #64748b;">
                Active Academic Period: 
                <strong>{{ $activePeriod ? $activePeriod->school_year . ' (' . $activePeriod->semester . ')' : 'None Set' }}</strong>
            </p>
        </div>

        <!-- RUN ML:TRAIN COMMAND BUTTON -->
        <form action="{{ route('super_admin.ml.train') }}" method="POST" onsubmit="return confirm('Start retraining ML models? This will process historical student grades.');">
            @csrf
            <button type="submit" class="btn-retrain">
                <i class="fa-solid fa-arrows-rotate"></i> Retrain ML Model
            </button>
        </form>
    </div>

    <!-- METRICS OVERVIEW CARDS (2-COLUMN GRID) -->
    <div class="stats-dashboard-grid mb-4">
        
        <!-- OVERALL ACCURACY CARD -->
        <div class="stat-premium-card">
            <div class="stat-number-side text-green">
                {{ $avgAccuracy }}%
            </div>
            <div class="stat-label-side">
                <span class="lbl-main">Overall Model</span>
                <span class="lbl-sub">Average R² Accuracy</span>
            </div>
            <div class="stat-icon-watermark text-green-icon">
                <i class="fa-solid fa-chart-line"></i>
            </div>
        </div>

        <!-- LAST TRAINED TIMESTAMP CARD -->
        <div class="stat-premium-card">
            <div class="stat-number-side text-blue" style="font-size: 22px;">
                {{ $lastTrainedAt ? \Carbon\Carbon::parse($lastTrainedAt)->format('M d, Y h:i A') : 'N/A' }}
            </div>
            <div class="stat-label-side">
                <span class="lbl-main">Last Training</span>
                <span class="lbl-sub">Execution Timestamp</span>
            </div>
            <div class="stat-icon-watermark text-blue-icon">
                <i class="fa-solid fa-clock-rotate-left"></i>
            </div>
        </div>
    </div>

    <!-- ML MODEL DATA TABLE -->
    <div class="management-panel">
        <h4 style="font-size: 15px; font-weight: 800; color: #1e293b; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-database text-purple-icon"></i> Trained Models Registry 
            <span class="code-tag">ml_grade_models</span>
        </h4>

        @if($mlModels->isNotEmpty())
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Term / Period</th>
                            <th>Sample Size</th>
                            <th>R-Squared (R²)</th>
                            <th>Accuracy</th>
                            <th>Model Weights / Coefficients</th>
                            <th>Last Trained Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($mlModels as $model)
                            <tr>
                                @php
                                    $termNames = [
                                        1 => 'Prelim',
                                        2 => 'Midterm',
                                        3 => 'Semi-Finals / Finals'
                                    ];
                                @endphp

                                <td>
                                    <span class="stage-badge">
                                        {{ $termNames[$model->stage] ?? 'Term ' . $model->stage }}
                                    </span>
                                </td>
                                <td>
                                    <strong>{{ $model->sample_size }}</strong> <span class="text-muted" style="font-size: 12px;">records</span>
                                </td>
                                <td style="font-family: monospace; font-weight: 700; color: #334155;">
                                    {{ number_format((float)$model->r_squared, 4) }}
                                </td>
                                <td>
                                    <span class="accuracy-badge">
                                        <i class="fa-solid fa-circle-check" style="font-size: 10px;"></i>
                                        {{ round($model->r_squared * 100, 2) }}%
                                    </span>
                                </td>
                                <td>
                                    <!-- CONVERTED JSON TO READABLE WEIGHT BADGES -->
                                    @php
                                        $coefs = json_decode($model->coefficients, true);
                                    @endphp

                                    @if(is_array($coefs))
                                        <div class="coef-container">
                                            @foreach($coefs as $index => $weight)
                                                <span class="coef-pill" title="Raw Value: {{ $weight }}">
                                                    <small class="coef-label">&beta;<sub>{{ $index + 1 }}</sub></small>
                                                    <strong>{{ number_format((float)$weight, 4) }}</strong>
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-muted" style="font-size: 11px;">{{ $model->coefficients }}</span>
                                    @endif
                                </td>
                                <td style="font-size: 12px; color: #475569; font-weight: 600;">
                                    <i class="fa-regular fa-calendar-check" style="margin-right: 4px; color: #64748b;"></i>
                                    {{ \Carbon\Carbon::parse($model->trained_at)->format('Y-m-d H:i:s') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p style="color: #64748b; font-size: 14px; margin-top: 10px;">
                No trained model data found. Click the <strong>"Retrain ML Model"</strong> button above to run the initial training command.
            </p>
        @endif
    </div>

</div>

<style>
    .dashboard-wrapper { 
        max-width: 1140px; 
        margin: 0 auto; 
        font-family: 'Inter', 'Segoe UI', system-ui, sans-serif; 
    }

    .management-panel { 
        background: #ffffff; 
        border: 3px solid #1e293b; 
        border-radius: 16px; 
        padding: 24px; 
    }

    .panel-title { 
        font-size: 16px; 
        font-weight: 800; 
        color: #1e293b; 
    }

    /* GRID FOR STAT CARDS */
    .stats-dashboard-grid { 
        display: grid; 
        grid-template-columns: repeat(2, 1fr); 
        gap: 20px; 
        width: 100%; 
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
        font-size: 36px; 
        font-weight: 900; 
        line-height: 1; 
        letter-spacing: -1px; 
        margin-right: 15px; 
        min-width: 55px; 
        z-index: 2;
    }

    .stat-label-side { 
        display: flex; 
        flex-direction: column; 
        justify-content: center; 
        z-index: 2; 
    }

    .stat-label-side .lbl-main { 
        font-size: 12px; 
        font-weight: 800; 
        color: #1e293b; 
        text-transform: uppercase; 
        letter-spacing: 0.5px; 
    }

    .stat-label-side .lbl-sub { 
        font-size: 14px; 
        font-weight: 600; 
        color: #475569; 
    }

    .stat-icon-watermark { 
        position: absolute; 
        right: 24px; 
        font-size: 32px; 
        opacity: 0.2; 
        z-index: 1; 
        pointer-events: none;
    }

    /* COLOR THEMES */
    .text-green { color: #15803d; } 
    .text-green-icon { color: #16a34a; }
    .text-blue { color: #1e3a8a; } 
    .text-blue-icon { color: #2563eb; }
    .text-purple-icon { color: #9333ea; }

    /* RETRAIN BUTTON */
    .btn-retrain {
        background: #2563eb; 
        color: white; 
        border: 2px solid #1e293b;
        padding: 10px 20px; 
        border-radius: 8px; 
        font-weight: 800; 
        font-size: 13px;
        cursor: pointer; 
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 2px 0 #1e293b;
    }

    .btn-retrain:hover {
        background: #1d4ed8;
        transform: translateY(-1px);
        box-shadow: 0 4px 0 #1e293b;
    }

    /* BADGES & CODE FORMATTING */
    .code-tag {
        background: #f1f5f9;
        color: #475569;
        font-family: monospace;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 12px;
        border: 1px solid #cbd5e1;
    }

    .stage-badge {
        background: #f8fafc;
        border: 2px solid #1e293b;
        color: #1e293b;
        font-weight: 800;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 12px;
        display: inline-block;
    }

    .accuracy-badge {
        background: #dcfce7; 
        color: #14532d; 
        border: 1px solid #86efac;
        font-weight: 800; 
        padding: 4px 10px; 
        border-radius: 20px; 
        font-size: 12px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    /* READABLE COEFFICIENTS PILLS */
    .coef-container {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }

    .coef-pill {
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 3px 8px;
        font-size: 12px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-family: monospace;
    }

    .coef-label {
        color: #2563eb;
        font-weight: 800;
        font-size: 12px;
    }

    /* TABLE DESIGN */
    .table-responsive { overflow-x: auto; }
    
    .custom-table { 
        width: 100%; 
        border-collapse: collapse; 
        margin-top: 5px; 
    }

    .custom-table th {
        background: #f8fafc;
        color: #1e293b;
        font-weight: 800;
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 0.5px;
        padding: 12px 10px;
        border-bottom: 2px solid #1e293b;
    }

    .custom-table td { 
        padding: 12px 10px; 
        text-align: left; 
        border-bottom: 1px solid #e2e8f0; 
        font-size: 13px; 
        vertical-align: middle;
    }

    .custom-table tbody tr:hover {
        background-color: #f8fafc;
    }

    /* ALERTS */
    .alert-banner { 
        padding: 14px 18px; 
        border-radius: 8px; 
        margin-bottom: 20px; 
        font-size: 14px; 
        font-weight: 600; 
    }
    .alert-success { background: #dcfce7; color: #14532d; border: 1px solid #86efac; }
    .alert-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }

    @media (max-width: 640px) {
        .stats-dashboard-grid { grid-template-columns: 1fr; }
    }
</style>
@endsection