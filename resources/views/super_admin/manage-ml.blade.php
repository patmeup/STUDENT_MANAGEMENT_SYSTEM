@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="sa-container">

    <div class="sa-header">
        <div class="header-titles">
            <h2>Predictive Model Training</h2>
            <p>Retrain system algorithms and monitor the accuracy of the grading prediction models.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="sa-alert-success">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="sa-alert-error">
            <i class="fas fa-exclamation-triangle"></i> {{ session('error') }}
        </div>
    @endif

    <div class="sa-workspace-vertical">
        
        <div class="sa-card form-panel">
            <div class="card-bar" style="display: flex; justify-content: space-between; align-items: center;">
                <span><i class="fas fa-brain text-purple-icon"></i> Active Training Status</span>
                
                <form action="{{ route('super_admin.ml.train') }}" method="POST" onsubmit="return confirm('Start retraining ML models? This will process historical student grades.');" style="margin: 0;">
                    @csrf
                    <button type="submit" class="btn-retrain">
                        <i class="fas fa-arrows-rotate"></i> Retrain Model
                    </button>
                </form>
            </div>
            
            <div class="pure-form" style="padding: 24px;">
                <p class="text-muted" style="margin: 0 0 20px 0; font-size: 13.5px; text-align: center;">
                    Currently targeting historical data up to: 
                    <strong style="color: #0f172a;">{{ $activePeriod ? $activePeriod->school_year . ' (' . $activePeriod->semester . ')' : 'No Academic Period Set' }}</strong>
                </p>

                <div class="stats-dashboard-grid">
                    <div class="stat-premium-card">
                        <div class="stat-number-side text-green">
                            {{ $avgAccuracy }}%
                        </div>
                        <div class="stat-label-side">
                            <span class="lbl-main">Average Accuracy</span>
                            <span class="lbl-sub">Overall R² Score</span>
                        </div>
                    </div>

                    <div class="stat-premium-card">
                        <div class="stat-number-side text-blue" style="font-size: 20px;">
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

        <div class="sa-card table-panel" style="margin-top: 25px;">
            <div class="card-bar d-flex-jsb table-header-flex">
                <span><i class="fas fa-database text-blue-icon"></i> Trained Models Registry</span>
                <span class="count-tag" style="font-family: monospace;">table: ml_grade_models</span>
            </div>
            
            <div class="table-wrap">
                @if($mlModels->isNotEmpty())
                    <table class="sa-table">
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
                                        <span class="stage-badge">
                                            {{ $termNames[$model->stage] ?? 'Term ' . $model->stage }}
                                        </span>
                                    </td>
                                    <td>
                                        <strong>{{ $model->sample_size }}</strong> <span style="font-size: 11px; color: #64748b;">records</span>
                                    </td>
                                    <td style="font-family: monospace; font-weight: 700; color: #334155; font-size: 14px;">
                                        {{ number_format((float)$model->r_squared, 4) }}
                                    </td>
                                    <td>
                                        <span class="accuracy-badge">
                                            <i class="fas fa-check-circle" style="font-size: 11px;"></i>
                                            {{ round($model->r_squared * 100, 2) }}%
                                        </span>
                                    </td>
                                    <td>
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
                                            <span style="font-size: 11.5px; color: #64748b; font-family: monospace;">{{ $model->coefficients }}</span>
                                        @endif
                                    </td>
                                    <td style="font-size: 12px; color: #475569; font-weight: 600;">
                                        {{ \Carbon\Carbon::parse($model->trained_at)->format('Y-m-d H:i') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div style="text-align: center; color: #94a3b8; padding: 50px 0; font-style: italic; font-size: 13.5px;">
                        No trained model data found. Click <strong>"Retrain Model"</strong> to initialize processing.
                    </div>
                @endif
            </div>
        </div>

    </div>
</div>

<style>
    .sa-container { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #0f172a; max-width: 1300px; margin: 0 auto; padding: 20px; }
    
    .sa-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 2px solid #e2e8f0; padding-bottom: 18px; }
    .header-titles h2 { margin: 0 0 4px 0; font-size: 28px; font-weight: 800; color: #0f172a; letter-spacing: -0.5px; }
    .header-titles p { margin: 0; font-size: 14.5px; color: #64748b; }
    .root-level { background-color: #475569; color: #ffffff; padding: 6px 14px; border-radius: 20px; font-weight: 700; font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.5px; }

    .sa-workspace-vertical { display: flex; flex-direction: column; width: 100%; }

    .sa-card { background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 10px -2px rgba(0, 0, 0, 0.03); overflow: hidden; }
    .card-bar { background: #f8fafc; padding: 16px 22px; font-weight: 700; font-size: 14.5px; color: #1e293b; border-bottom: 1px solid #e2e8f0; }
    .d-flex-jsb { display: flex; justify-content: space-between; align-items: center; }
    .count-tag { background: #e2e8f0; color: #334155; font-size: 11.5px; padding: 4px 10px; border-radius: 5px; font-weight: 700; }

    .sa-alert-success { background: #ecfdf5; color: #065f46; padding: 14px 20px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; border-left: 5px solid #059669; font-size: 14px; display: flex; align-items: center; gap: 10px; }
    .sa-alert-error { background: #fef2f2; color: #991b1b; padding: 14px 20px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; border-left: 5px solid #dc2626; font-size: 14px; display: flex; align-items: center; gap: 10px; }

    .stats-dashboard-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; width: 100%; }
    
    .stat-premium-card { background: #ffffff; border: 2px solid #e2e8f0; border-radius: 12px; height: 100px; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 0 24px; text-align: center; }
    
    .stat-number-side { font-size: 30px; font-weight: 900; line-height: 1; letter-spacing: -0.5px; margin-bottom: 6px; }
    
    .stat-label-side { display: flex; flex-direction: column; align-items: center; }
    .stat-label-side .lbl-main { font-size: 12px; font-weight: 700; color: #1e293b; text-transform: uppercase; letter-spacing: 0.5px; }
    .stat-label-side .lbl-sub { font-size: 13px; font-weight: 600; color: #64748b; }

    .btn-retrain { background: #0f172a; color: white; border: none; padding: 8px 18px; border-radius: 6px; font-weight: 700; font-size: 12.5px; cursor: pointer; transition: all 0.2s ease; display: flex; align-items: center; gap: 8px; }
    .btn-retrain:hover { background: #334155; }

    .table-wrap { width: 100%; overflow-x: auto; }
    .sa-table { width: 100%; border-collapse: collapse; text-align: left; }
    .sa-table th { background: #f8fafc; padding: 14px 20px; font-size: 11.5px; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; }
    .sa-table td { padding: 15px 20px; border-bottom: 1px solid #f1f5f9; font-size: 13.5px; vertical-align: middle; }

    .text-green { color: #059669; } 
    .text-green-icon { color: #10b981; }
    .text-blue { color: #0f172a; } 
    .text-blue-icon { color: #3b82f6; }
    .text-purple-icon { color: #8b5cf6; }

    .stage-badge { background: #f1f5f9; border: 1px solid #cbd5e1; color: #334155; font-weight: 700; padding: 4px 10px; border-radius: 6px; font-size: 11.5px; display: inline-block; }
    
    .accuracy-badge { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; font-weight: 700; padding: 4px 10px; border-radius: 20px; font-size: 11.5px; display: inline-flex; align-items: center; gap: 5px; }

    .coef-container { display: flex; flex-wrap: wrap; gap: 6px; }
    .coef-pill { background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; padding: 3px 8px; font-size: 11.5px; display: inline-flex; align-items: center; gap: 5px; font-family: monospace; }
    .coef-label { color: #3b82f6; font-weight: 700; font-size: 11.5px; }

    @media (max-width: 768px) {
        .stats-dashboard-grid { grid-template-columns: 1fr; }
    }
</style>
@endsection