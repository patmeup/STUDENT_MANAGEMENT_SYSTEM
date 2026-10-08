@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="rg-page">

    <header class="rg-header">
        <div>
            <h2 class="rg-title">Predictive Model Training</h2>
            <p class="rg-subtitle">Retrain system algorithms and monitor the accuracy of the grading prediction models.</p>
        </div>
    </header>

    @if(session('success'))
        <div class="rg-alert rg-alert-success" role="status">
            <i class="fas fa-check-circle"></i> <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="rg-alert rg-alert-error" role="alert">
            <i class="fas fa-exclamation-triangle"></i> <span>{{ session('error') }}</span>
        </div>
    @endif

    <div class="sa-workspace-vertical">
        
        <div class="rg-card" style="margin-bottom: 24px;">
            <div class="card-bar" style="display: flex; justify-content: space-between; align-items: center;">
                <span><i class="fas fa-brain" style="color: #8b5cf6; margin-right: 6px;"></i> Active Training Status</span>
                
                <form action="{{ route('super_admin.ml.train') }}" method="POST" onsubmit="return confirm('Start retraining ML models? This will process historical student grades.');" style="margin: 0;">
                    @csrf
                    <button type="submit" class="rg-btn rg-btn-primary" style="background-color: var(--rg-blue-dark);">
                        <i class="fas fa-arrows-rotate"></i> Retrain Model
                    </button>
                </form>
            </div>
            
            <div style="padding: 24px;">
                <p style="margin: 0 0 20px 0; font-size: 13.5px; text-align: center; color: var(--rg-muted);">
                    Currently targeting historical data up to: 
                    <strong style="color: var(--rg-blue-dark);">{{ $activePeriod ? $activePeriod->school_year . ' (' . $activePeriod->semester . ')' : 'No Academic Period Set' }}</strong>
                </p>

                <div class="stats-dashboard-grid">
                    <div class="stat-premium-card">
                        <div class="stat-number-side" style="color: var(--rg-green);">
                            {{ $avgAccuracy }}%
                        </div>
                        <div class="stat-label-side">
                            <span class="lbl-main">Average Accuracy</span>
                            <span class="lbl-sub">Overall R² Score</span>
                        </div>
                    </div>

                    <div class="stat-premium-card">
                        <div class="stat-number-side" style="font-size: 20px; color: var(--rg-blue-dark);">
                            {{ $lastTrainedAt ? \Carbon\Carbon::parse($lastTrainedAt)->format('M d, Y h:i A') : 'N/A' }}
                        </div>
                        <div class="stat-label-side">
                            <span class="lbl-main">Last Execution</span>
                            <span class="lbl-sub">Training Timestamp</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="rg-card">
            <div class="card-bar" style="display: flex; justify-content: space-between; align-items: center;">
                <span><i class="fas fa-database" style="color: var(--rg-blue); margin-right: 6px;"></i> Trained Models Registry</span>
                <span class="employee-count-badge" style="margin-left: 0; padding: 4px 10px; font-size: 11.5px; font-family: monospace;">table: ml_grade_models</span>
            </div>
            
            <div class="rg-table-wrap">
                @if($mlModels->isNotEmpty())
                    <table class="rg-table">
                        <thead>
                            <tr>
                                <th>Term Period</th>
                                <th>Sample Size</th>
                                <th>R-Squared (R²)</th>
                                <th>Accuracy</th>
                                <th>Coefficient Weights</th>
                                <th>Log Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($mlModels as $model)
                                <tr>
                                    @php
                                        $termNames = [
                                            1 => 'Prelim',
                                            2 => 'Midterm',
                                            3 => 'Semi-Final',
                                            4 => 'Finals'
                                        ];
                                    @endphp

                                    <td>
                                        <span class="rg-chip" style="background: var(--rg-surface); color: var(--rg-blue-dark); border-color: var(--rg-line);">
                                            {{ $termNames[$model->stage] ?? 'Term ' . $model->stage }}
                                        </span>
                                    </td>
                                    <td>
                                        <strong>{{ $model->sample_size }}</strong> <span style="font-size: 11px; color: var(--rg-muted);">records</span>
                                    </td>
                                    <td style="font-family: monospace; font-weight: 700; color: var(--rg-blue-dark); font-size: 14px;">
                                        {{ number_format((float)$model->r_squared, 4) }}
                                    </td>
                                    <td>
                                        <span class="rg-chip" style="background: var(--rg-green-tint); color: #14532D; border-color: #BFE8CE;">
                                            <i class="fas fa-check-circle" style="font-size: 11px;"></i>
                                            {{ round($model->r_squared * 100, 2) }}%
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $coefs = json_decode($model->coefficients, true);
                                        @endphp

                                        @if(is_array($coefs))
                                            <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                                                @foreach($coefs as $index => $weight)
                                                    <span style="background: #fff; border: 1px solid #CBD5E6; border-radius: 6px; padding: 3px 8px; font-size: 11.5px; display: inline-flex; align-items: center; gap: 5px; font-family: monospace;" title="Raw Value: {{ $weight }}">
                                                        <span style="color: var(--rg-blue); font-weight: 700;">&beta;<sub>{{ $index + 1 }}</sub></span>
                                                        <strong>{{ number_format((float)$weight, 4) }}</strong>
                                                    </span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span style="font-size: 11.5px; color: var(--rg-muted); font-family: monospace;">{{ $model->coefficients }}</span>
                                        @endif
                                    </td>
                                    <td style="font-size: 12px; color: var(--rg-muted); font-weight: 600;">
                                        {{ \Carbon\Carbon::parse($model->trained_at)->format('Y-m-d H:i') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="empty-table-state">
                        No trained model data found. Click <strong>"Retrain Model"</strong> to initialize processing.
                    </div>
                @endif
            </div>
        </div>

    </div>
</div>

<style>
    .rg-page {
        --rg-blue: #0033A0; --rg-blue-dark: #0A1F5C; --rg-blue-tint: #EAF0FF; --rg-gold: #F2B705;
        --rg-ink: #0B1B3A; --rg-muted: #5C6B8A; --rg-line: #E2E8F3; --rg-surface: #F7F9FD;
        --rg-green: #15803D; --rg-green-tint: #E8F7EE; --rg-red: #C62828;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: var(--rg-ink);
        max-width: 1500px; margin: 0 auto; padding: 24px 20px 40px;
    }

    .rg-header { display: flex; justify-content: space-between; align-items: flex-end; gap: 20px; flex-wrap: wrap; padding-bottom: 18px; margin-bottom: 24px; border-bottom: 1px solid var(--rg-line); }
    .rg-title { margin: 0; font-size: 26px; font-weight: 800; letter-spacing: -0.2px; color: var(--rg-blue-dark); }
    .rg-subtitle { margin: 8px 0 0; font-size: 14px; color: var(--rg-muted); }

    .rg-alert { display: flex; gap: 10px; align-items: flex-start; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 13.5px; font-weight: 600; line-height: 1.45; }
    .rg-alert i { margin-top: 2px; }
    .rg-alert-success { background: var(--rg-green-tint); color: #14532D; border: 1px solid #BFE8CE; border-left: 4px solid var(--rg-green); }
    .rg-alert-error { background: #FDECEC; color: #8E1B1B; border: 1px solid #F5C6C6; border-left: 4px solid var(--rg-red); }

    .rg-card { background: #fff; border: 1px solid var(--rg-line); border-radius: 10px; overflow: hidden; box-shadow: 0 1px 2px rgba(10, 31, 92, 0.05); }
    .card-bar { background: var(--rg-surface); padding: 16px 20px; font-weight: 700; font-size: 14.5px; color: var(--rg-blue-dark); border-bottom: 1px solid var(--rg-line); display: flex; align-items: center; gap: 8px; }

    .sa-workspace-vertical { display: flex; flex-direction: column; width: 100%; gap: 24px; }

    .stats-dashboard-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; width: 100%; }
    .stat-premium-card { background: #fff; border: 1px solid var(--rg-line); border-radius: 10px; height: 100px; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 0 24px; text-align: center; }
    .stat-number-side { font-size: 30px; font-weight: 900; line-height: 1; letter-spacing: -0.5px; margin-bottom: 6px; }
    .stat-label-side { display: flex; flex-direction: column; align-items: center; }
    .stat-label-side .lbl-main { font-size: 12px; font-weight: 700; color: var(--rg-blue-dark); text-transform: uppercase; letter-spacing: 0.5px; }
    .stat-label-side .lbl-sub { font-size: 13px; font-weight: 600; color: var(--rg-muted); }

    .rg-btn { display: inline-flex; align-items: center; gap: 7px; padding: 10px 18px; border-radius: 6px; font-size: 13.5px; font-weight: 700; text-decoration: none; cursor: pointer; border: 1px solid transparent; transition: background .15s, color .15s, border-color .15s; white-space: nowrap; line-height: 1.2; }
    .rg-btn-primary { background: var(--rg-blue); color: #fff; }
    .rg-btn-primary:hover { background: var(--rg-blue-dark); color: #fff; }

    .rg-table-wrap { overflow-x: auto; width: 100%; }
    .rg-table { width: 100%; min-width: 650px; border-collapse: collapse; font-size: 14px; table-layout: auto; }
    .rg-table th { background: var(--rg-surface); padding: 14px 18px; font-size: 11.5px; font-weight: 700; color: var(--rg-muted); text-transform: uppercase; text-align: left; border-bottom: 1px solid var(--rg-line); letter-spacing: 0.3px; }
    .rg-table td { padding: 16px 18px; border-bottom: 1px solid #EEF2F9; color: var(--rg-ink); vertical-align: middle; }
    .rg-table tbody tr:last-child td { border-bottom: none; }
    .rg-table tbody tr:hover { background: #F5F8FF; }

    .rg-chip { display: inline-block; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; white-space: nowrap; border: 1px solid transparent; }
    .employee-count-badge { background: var(--rg-blue-tint); border: 1px solid #CFDBFB; padding: 8px 14px; border-radius: 8px; color: var(--rg-blue-dark); font-weight: 600; font-size: 13.5px; }
    .empty-table-state { text-align: center; color: var(--rg-muted) !important; padding: 50px 0 !important; font-style: italic; }

    @media (max-width: 768px) {
        .stats-dashboard-grid { grid-template-columns: 1fr; }
    }
</style>
@endsection