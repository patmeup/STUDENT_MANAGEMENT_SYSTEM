@extends('layouts.app')

@section('content')
<div class="profile-container">

    {{-- ⚠️ SECURITY LOCK WARNING BANNER --}}
    @if($isLocked)
        <div class="alert-banner alert-warning">
            <strong style="display:block; margin-bottom: 4px;">⚠️ First-Time Login Action Required!</strong>
            Naka-lock ang lahat ng system tabs para sa iyong seguridad dahil gamit mo pa ang default plain-text password. 
            Mangyaring magpalit ng password sa ibaba upang ma-unlock ang buong features ng system.
        </div>
    @endif

    {{-- ALERT RESPONSES --}}
    @if(session('success'))
        <div class="alert-banner alert-success">
            🎉 {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert-banner alert-danger">
            @foreach($errors->all() as $error)
                <p style="margin: 4px 0;">❌ {{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- PERSONAL & CREDENTIALS DISPLAY CARD --}}
    <div class="profile-card">
        <div class="card-header">
            <h2 class="card-title">Personal & Employee Credentials</h2>
            <p class="card-subtitle">Ito ang iyong opisyal na impormasyon na nakatala sa system database.</p>
        </div>

        <div class="card-body grid-layout">
            <div>
                <label class="info-label">Employee ID / Login ID</label>
                <p class="info-value-mono">{{ $user->employee_id }}</p>
            </div>

            <div>
                <label class="info-label">Access Privilege / Role</label>
                <p class="info-value capitalize">{{ str_replace('_', ' ', $user->role) }}</p>
            </div>

            <div class="col-span-2 border-top-section">
                <label class="info-label">Account Holder Name</label>
                <p class="info-value" style="font-size: 1.25rem; font-weight: 700;">
                    {{ $user->last_name }}, {{ $user->first_name }} {{ $user->middle_name ?? '' }}
                </p>
            </div>

            <div class="border-top-section">
                <label class="info-label">Account Status</label>
                <div style="margin-top: 4px;">
                    <span class="status-badge">
                        ● {{ $user->status }}
                    </span>
                </div>
            </div>

            <div class="border-top-section">
                <label class="info-label">Security State</label>
                @if($isLocked)
                    <p class="info-value" style="color: #dc2626; margin-top: 4px;">
                        🔴 Unsecured (Plain-Text Default)
                    </p>
                @else
                    <p class="info-value" style="color: #16a34a; margin-top: 4px;">
                        ✅ Secured (Encrypted Hash)
                    </p>
                @endif
            </div>
        </div>
    </div>

    {{-- ACTIVE SECURITY FORM --}}
    <div class="profile-card">
        <div class="card-header">
            <h3 class="card-title">🔒 Update Security Password</h3>
            <p class="card-subtitle">I-update ang iyong login credentials para maging fully encrypted (hashed) sa system records.</p>
        </div>

        <form action="{{ route('account.update_password') }}" method="POST" class="card-body">
            @csrf
            
            <div class="form-group">
                <label class="form-label">Current Security Password</label>
                <input type="password" name="current_password" required placeholder="e.g., superadmin123" class="form-control">
            </div>

            <div class="grid-layout" style="margin-bottom: 16px;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_password" required placeholder="Gumawa ng bagong password" class="form-control">
                    <p class="input-hint">Dapat hindi bababa sa 8 characters.</p>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="new_password_confirmation" required placeholder="I-type muli ang bagong password" class="form-control">
                </div>
            </div>

            <div class="btn-container">
                <button type="submit" class="btn-submit">
                    Lock & Secure Account
                </button>
            </div>
        </form>
    </div>

</div>

<style>
    /* Custom Styling para sa User Details & Password Update Page */
    .profile-container {
        max-width: 850px;
        width: 100%;
        margin: 24px auto;
        font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        padding: 0 16px;
        box-sizing: border-box;
    }
    .profile-card {
        background-color: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        margin-bottom: 24px;
        overflow: hidden;
    }
    .card-header {
        background-color: #f8fafc;
        padding: 16px 24px;
        border-b: 1px solid #e2e8f0;
        border-bottom: 1px solid #e2e8f0;
    }
    .card-title {
        font-size: 1.125rem;
        font-weight: 700;
        color: #334155;
        margin: 0;
    }
    .card-subtitle {
        font-size: 0.75rem;
        color: #64748b;
        margin: 4px 0 0 0;
    }
    .card-body {
        padding: 24px;
    }
    .grid-layout {
        display: grid;
        grid-template-columns: 1fr;
        gap: 24px;
    }
    @media (min-width: 768px) {
        .grid-layout {
            grid-template-columns: 1fr 1fr;
        }
        .col-span-2 {
            grid-column: span 2 / span 2;
        }
    }
    .info-label {
        display: block;
        font-size: 0.75rem;
        font-weight: 600;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 4px;
    }
    .info-value {
        font-size: 1rem;
        font-weight: 600;
        color: #1e293b;
        margin: 0;
    }
    .info-value-mono {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 1.125rem;
        font-weight: 700;
        color: #2563eb;
        margin: 0;
    }
    .info-value.capitalize {
        text-transform: capitalize;
    }
    .border-top-section {
        border-top: 1px solid #f1f5f9;
        pt-4: 16px;
        padding-top: 16px;
    }
    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: 2px 10px;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
        background-color: #dcfce7;
        color: #166534;
    }
    .form-group {
        margin-bottom: 16px;
    }
    .form-label {
        display: block;
        font-size: 0.875rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 6px;
    }
    .form-control {
        width: 100%;
        padding: 8px 16px;
        font-size: 0.875rem;
        background-color: #ffffff;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        box-sizing: border-box;
        outline: none;
        transition: all 0.2s;
    }
    .form-control:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5);
    }
    .input-hint {
        font-size: 10px;
        color: #94a3b8;
        margin: 4px 0 0 0;
    }
    .btn-container {
        display: flex;
        justify-content: flex-end;
        padding-top: 8px;
    }
    .btn-submit {
        width: 100%;
        padding: 10px 24px;
        background-color: #0f172a;
        color: #ffffff;
        font-weight: 600;
        font-size: 0.875rem;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: background-color 0.2s;
    }
    .btn-submit:hover {
        background-color: #1e293b;
    }
    @media (min-width: 768px) {
        .btn-submit {
            width: auto;
        }
    }
    /* Banner Alerts */
    .alert-banner {
        padding: 16px;
        border-radius: 0 8px 8px 0;
        margin-bottom: 24px;
        font-size: 0.875rem;
    }
    .alert-warning { background-color: #fffbeb; border-left: 4px solid #f59e0b; color: #78350f; }
    .alert-success { background-color: #f0fdf4; border-left: 4px solid #22c55e; color: #166534; font-weight: 600; }
    .alert-danger { background-color: #fef2f2; border-left: 4px solid #ef4444; color: #991b1b; }
</style>
@endsection