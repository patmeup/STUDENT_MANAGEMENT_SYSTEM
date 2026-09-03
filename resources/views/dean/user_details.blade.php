@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<div class="profile-container">

    @if($isLocked)
        <div class="sa-alert-warning">
            <i class="fas fa-unlock-alt"></i> First-Time Login Action Required! Please update your password to unlock full system access.
        </div>
    @endif

    @if(session('success'))
        <div class="sa-alert-success">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif 

    @if($errors->any())
        <div class="sa-alert-error" style="align-items: flex-start;">
            <i class="fas fa-exclamation-circle" style="margin-top: 3px;"></i>
            <div>
                <strong>Unable to update password:</strong>
                <ul style="margin: 4px 0 0 0; padding-left: 20px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="profile-card">
        <div class="card-header">
            <h2 class="card-title">Personal Credentials</h2>
        </div>

        <div class="card-body">
            <div class="avatar-upload-wrapper">
                <form action="{{ route('dean.profile.picture.update') }}" method="POST" enctype="multipart/form-data" id="avatarForm">
                    @csrf
                    <div class="avatar-container">
                        @if(!empty($user->profile_picture))
                            <img src="{{ asset('storage/' . $user->profile_picture) }}"
                                alt="Profile Picture"
                                class="rounded-circle"
                                style="width: 120px; height: 120px; object-fit: cover; border-radius: 50%;">
                        @else
                            <img src="https://api.dicebear.com/7.x/initials/svg?seed={{ $user->first_name }}+{{ $user->last_name }}&backgroundType=solid&backgroundColor=cbd5e1"
                                alt="Default Profile Picture"
                                class="rounded-circle"
                                style="width: 120px; height: 120px; object-fit: cover; border-radius: 50%;">
                        @endif

                        <label for="profile_picture" class="avatar-edit-btn" title="Change Profile Picture">
                            <i class="fa-solid fa-camera"></i>
                        </label>
                        <input type="file" id="profile_picture" name="profile_picture" class="d-none" accept="image/*" onchange="document.getElementById('avatarForm').submit();">
                    </div>
                </form>
            </div>

            <div class="grid-layout">
                <div>
                    <label class="info-label">Employee ID</label>
                    <p class="info-value-mono">{{ $user->employee_id }}</p>
                </div>

                <div>
                    <label class="info-label">Role</label>
                    <p class="info-value capitalize">{{ str_replace('_', ' ', $user->role) }}</p>
                </div>

                <div class="border-top-section">
                    <label class="info-label">Full Name</label>
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
                <label class="form-label">Current Password</label>
                <div class="password-wrapper">
                    {{-- Automatic input of default temporary password if the account is locked/new --}}
                    <input type="password" 
                           name="current_password" 
                           required 
                           class="form-control" 
                           id="current_password" 
                           value="{{ $isLocked ? 'Welcome@AU2026' : '' }}" 
                           {{ $isLocked ? 'readonly' : '' }}
                           style="{{ $isLocked ? 'background-color: #f8fafc; color: #64748b; cursor: not-allowed;' : '' }}">
                    
                    @if(!$isLocked)
                        <span class="toggle-password-text" onclick="togglePasswordVisibility('current_password', this)">Show</span>
                    @else
                        <span style="position: absolute; right: 16px; font-size: 11px; color: #059669; font-weight: 700;">Default Applied</span>
                    @endif
                </div>
                @if($isLocked)
                    <p class="input-hint" style="color: #059669;">Temporary default password automatically applied for initial setup.</p>
                @endif
            </div>

            <div class="form-group">
                <label class="form-label">New Password</label>
                <div class="password-wrapper">
                    <input type="password" 
                           name="new_password" 
                           required 
                           class="form-control" 
                           id="new_password"
                           minlength="8">
                    <span class="toggle-password-text" onclick="togglePasswordVisibility('new_password', this)">Show</span>
                </div>
                <p class="input-hint">Must be at least 8 characters long.</p>
            </div>

            <div class="form-group">
                <label class="form-label">Confirm New Password</label>
                <div class="password-wrapper">
                    <input type="password" 
                           name="new_password_confirmation" 
                           required 
                           class="form-control" 
                           id="new_password_confirmation"
                           minlength="8">
                    <span class="toggle-password-text" onclick="togglePasswordVisibility('new_password_confirmation', this)">Show</span>
                </div>
            </div>

            <div class="btn-centered-container">
                <button type="submit" class="btn-submit">
                    Save Changes
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
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        padding: 0 16px;
        box-sizing: border-box;
    }

    .profile-card {
        background-color: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 4px 10px -2px rgba(0, 0, 0, 0.03);
        margin-bottom: 24px;
        overflow: hidden;
    }
    .card-header {
        background-color: #f8fafc;
        padding: 18px 24px;
        border-bottom: 1px solid #e2e8f0;
    }
    .card-title {
        font-size: 15px;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
    }
    .card-body {
        padding: 24px;
    }

    .grid-layout { display: grid; grid-template-columns: 1fr; gap: 24px; }
    @media (min-width: 768px) { .grid-layout { grid-template-columns: 1fr 1fr; } }
    
    .info-label { display: block; font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
    .info-value { font-size: 15px; font-weight: 600; color: #1e293b; margin: 0; }
    .info-value-mono { font-family: ui-monospace, SFMono-Regular, Consolas, monospace; font-size: 15px; font-weight: 700; color: #2563eb; margin: 0; }
    .capitalize { text-transform: capitalize; }
    .border-top-section { border-top: 1px solid #f1f5f9; padding-top: 16px; }
    .status-badge { display: inline-flex; align-items: center; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; background-color: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }

    .form-group { margin-bottom: 18px; }
    .form-label { display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px; width: 80%; margin-left: auto; margin-right: auto; }
    .form-control { width: 100%; padding: 10px 14px; font-size: 14px; color: #0f172a; background-color: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; outline: none; transition: all 0.2s; }
    .form-control:focus { border-color: #0f172a; box-shadow: 0 0 0 3px rgba(15, 23, 42, 0.1); }
    
    .password-wrapper { position: relative; display: flex; align-items: center; width: 80%; margin: 0 auto; }
    .toggle-password-text { position: absolute; right: 16px; font-size: 12px; color: #64748b; cursor: pointer; user-select: none; font-weight: 700; transition: color 0.2s; }
    .toggle-password-text:hover { color: #0f172a; }
    
    .input-hint { font-size: 11px; font-weight: 600; color: #94a3b8; text-align: left; width: 80%; margin: 6px auto 0 auto; }

    .btn-centered-container { width: 80%; margin: 25px auto 0 auto; display: flex; justify-content: center; }
    .btn-submit { width: 100%; padding: 12px 24px; background-color: #0f172a; color: #ffffff; font-weight: 700; font-size: 14px; border: none; border-radius: 6px; cursor: pointer; transition: background-color 0.2s; }
    .btn-submit:hover { background-color: #334155; }
    
    .sa-alert-warning { background: #fffbeb; color: #b45309; padding: 14px 20px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; border-left: 5px solid #f59e0b; font-size: 14px; display: flex; align-items: center; gap: 10px; }
    .sa-alert-success { background: #ecfdf5; color: #065f46; padding: 14px 20px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; border-left: 5px solid #059669; font-size: 14px; display: flex; align-items: center; gap: 10px; }
    .sa-alert-error { background: #fef2f2; color: #991b1b; padding: 14px 20px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; border-left: 5px solid #dc2626; font-size: 14px; display: flex; align-items: flex-start; gap: 10px; }

    .avatar-upload-wrapper { display: flex; justify-content: center; margin-bottom: 30px; }
    .avatar-container { position: relative; width: 110px; height: 110px; }
    .avatar-edit-btn { position: absolute; bottom: 2px; right: 2px; background-color: #0f172a; color: #ffffff; width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 14px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: all 0.2s ease; border: 3px solid #ffffff; }
    .avatar-edit-btn:hover { background-color: #3b82f6; transform: scale(1.05); }
    .d-none { display: none !important; }
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