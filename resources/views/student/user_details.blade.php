@extends('layouts.app')

@section('content')
<div class="profile-container">

    @if($isLocked)
        <div class="alert-banner alert-warning">
            <strong style="display:block; margin-bottom: 4px;">First-Time Login Action Required!</strong>
            Please change your default password before continuing.
        </div>
    @endif

    @if(session('success'))
        <div class="alert-banner alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert-banner alert-danger">
            @foreach($errors->all() as $error)
                <p style="margin: 4px 0;">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="profile-card">
        <div class="card-header">
            <h2 class="card-title">Personal Credentials</h2>
        </div>

        <div class="card-body grid-layout">
            <div>
                <label class="info-label">Student Number</label>
                <p class="info-value-mono">{{ $user->student_id }}</p>
            </div>

            <div>
                <label class="info-label">Course / Program</label>
                <p class="info-value">{{ $user->course }}</p>
            </div>

            <div>
                <label class="info-label">Year Level</label>
                <p class="info-value">{{ $user->year_level }}</p>
            </div>

            <div>
                <label class="info-label">Semester</label>
                <p class="info-value">{{ $user->semester }}</p>
            </div>

            <div class="border-top-section col-span-2">
                <label class="info-label">Full Name</label>
                <p class="info-value" style="font-size: 1.25rem; font-weight: 700;">
                    {{ $user->last_name }}, {{ $user->first_name }} {{ $user->middle_name ?? '' }}
                </p>
            </div>
        </div>
    </div>

    <div class="profile-card">
        <div class="card-header">
            <h3 class="card-title">Update Security Password</h3>
        </div>

        <form action="{{ route('account.update_password') }}" method="POST" class="card-body">
            @csrf

            <div class="form-group">
                <label class="form-label">Current Security Password</label>
                <div class="password-wrapper">
                    <input type="password" name="current_password" required class="form-control" id="current_password">
                    <span class="toggle-password-text" onclick="togglePasswordVisibility('current_password', this)">Show</span>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">New Password</label>
                <div class="password-wrapper">
                    <input type="password" name="new_password" required class="form-control" id="new_password">
                    <span class="toggle-password-text" onclick="togglePasswordVisibility('new_password', this)">Show</span>
                </div>
                <p class="input-hint">Characters must be at least 8 characters long.</p>
            </div>

            <div class="form-group">
                <label class="form-label">Confirm New Password</label>
                <div class="password-wrapper">
                    <input type="password" name="new_password_confirmation" required class="form-control" id="new_password_confirmation">
                    <span class="toggle-password-text" onclick="togglePasswordVisibility('new_password_confirmation', this)">Show</span>
                </div>
            </div>

            <div class="btn-centered-container">
                <button type="submit" class="btn-submit">
                    Confirm
                </button>
            </div>

        </form>
    </div>

</div>

<style>
    .profile-container {
        max-width: 700px;
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
        border-bottom: 1px solid #e2e8f0;
    }
    .card-title {
        font-size: 1.125rem;
        font-weight: 700;
        color: #334155;
        margin: 0;
    }
    .card-body {
        padding: 24px;
    }
    .grid-layout {
        display: grid;
        grid-template-columns: 1fr;
        gap: 24px;
    }

    .password-wrapper {
        position: relative;
        display: flex;
        align-items: center;
        width: 80%;
        margin: 0 auto;
    }

    .toggle-password-text {
        position: absolute;
        right: 16px;
        font-size: 0.85rem;
        color: #64748b;
        cursor: pointer;
        user-select: none;
        font-weight: 500;
        transition: color 0.2s;
    }

    .toggle-password-text:hover {
        color: #1e293b;
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
    .border-top-section {
        border-top: 1px solid #f1f5f9;
        padding-top: 16px;
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
        width: 80%;
        margin-left: auto;
        margin-right: auto;
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
        text-align: left;
        width: 80%;
        margin: 4px auto 0 auto;
    }

    .btn-centered-container {
        width: 80%;
        margin: 20px auto 0 auto;
        display: flex;
        justify-content: center;
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

<script>
    function togglePasswordVisibility(inputId, toggleTextElement) {
        const passwordInput = document.getElementById(inputId);

        if (passwordInput.type === "password") {
            passwordInput.type = "text";
            toggleTextElement.textContent = "Hide";
        } else {
            passwordInput.type = "password";
            toggleTextElement.textContent = "Show";
        }
    }
</script>
@endsection