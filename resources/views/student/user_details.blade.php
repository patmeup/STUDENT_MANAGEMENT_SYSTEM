@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

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

    <!-- PERSONAL CREDENTIALS CARD -->
    <div class="profile-card">
        <div class="card-header d-flex-between">
            <h2 class="card-title">Personal Credentials</h2>
            <button type="button" class="btn-edit-credentials" onclick="openEditModal()" title="Edit Contact Information">
                <i class="fa-solid fa-pen-to-square"></i>
            </button>
        </div>

        <div class="card-body">
            <!-- PROFILE PICTURE UPLOAD AVATAR -->
            <div class="avatar-upload-wrapper">
                <form action="{{ route('student.profile.picture.update') }}" method="POST" enctype="multipart/form-data" id="avatarForm">
                    @csrf
                    <div class="avatar-container">
                        @if(!empty($user->profile_picture))
                            <img src="{{ asset('storage/' . $user->profile_picture) }}" 
                                alt="Profile Picture" 
                                class="rounded-circle" 
                                style="width: 120px; height: 120px; object-fit: cover; border-radius: 50%;">
                        @else
                            {{-- DiceBear Initials Avatar kung walang pfp --}}
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

            <!-- DETAILS GRID -->
            <div class="grid-layout">
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

                <div>
                    <label class="info-label">Section</label>
                    <p class="info-value">{{ $user->section ?? 'N/A' }}</p>
                </div>

                <div class="border-top-section col-span-2" style="border-top: 1px solid #f1f5f9; padding-top: 16px; margin-top: 0;">
                    <div>
                        <label class="info-label">Full Name</label>
                        <p class="info-value" style="font-size: 1.25rem; font-weight: 700;">
                            {{ $user->last_name }}, {{ $user->first_name }} {{ $user->middle_name ?? '' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- UPDATE SECURITY PASSWORD CARD -->
    <div class="profile-card">
        <div class="card-header">
            <h3 class="card-title">Update Security Password</h3>
        </div>

        <form action="{{ route('account.update_password') }}" method="POST" class="card-body" onsubmit="return validatePasswordForm(event)">
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
                    <input type="password" name="new_password" id="new_password" required minlength="8" class="form-control">
                    <span class="toggle-password-text" onclick="togglePasswordVisibility('new_password', this)">Show</span>
                </div>
                <p class="input-hint">Must be at least 8 characters long. (Any combination of characters allowed)</p>
            </div>

            <div class="form-group">
                <label class="form-label">Confirm New Password</label>
                <div class="password-wrapper">
                    <input type="password" name="new_password_confirmation" id="new_password_confirmation" required minlength="8" class="form-control">
                    <span class="toggle-password-text" onclick="togglePasswordVisibility('new_password_confirmation', this)">Show</span>
                </div>
            </div>

            <div id="passwordError" style="display: none; width: 80%; margin: 8px auto 0 auto; color: #ef4444; font-size: 0.85rem; font-weight: 600;"></div>

            <div class="btn-centered-container">
                <button type="submit" class="btn-submit">
                    Confirm
                </button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT CREDENTIALS MODAL -->
<div class="modal-overlay" id="editCredentialsModal">
    <div class="modal-container">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fa-solid fa-user-gear"></i> Edit Personal Info</h3>
            <button type="button" class="modal-close-btn" onclick="closeEditModal()">&times;</button>
        </div>
        
        <form action="{{ route('account.update_credentials') }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="modal-body">
                <p class="modal-subtitle-hint">Academic records below are locked and cannot be edited by students.</p>
                
                <div class="modal-grid-2">
                    <div class="form-group-modal">
                        <label class="modal-label">Student Number</label>
                        <input type="text" class="form-control-modal disabled-input" value="{{ $user->student_id }}" disabled readonly>
                    </div>
                    <div class="form-group-modal">
                        <label class="modal-label">Course / Program</label>
                        <input type="text" class="form-control-modal disabled-input" value="{{ $user->course }}" disabled readonly>
                    </div>
                    <div class="form-group-modal">
                        <label class="modal-label">Year Level</label>
                        <input type="text" class="form-control-modal disabled-input" value="{{ $user->year_level }}" disabled readonly>
                    </div>
                    <div class="form-group-modal">
                        <label class="modal-label">Semester</label>
                        <input type="text" class="form-control-modal disabled-input" value="{{ $user->semester }}" disabled readonly>
                    </div>
                    <div class="form-group-modal">
                        <label class="modal-label">Section</label>
                        <input type="text" class="form-control-modal disabled-input" value="{{ $user->section ?? 'N/A' }}" disabled readonly>
                    </div>
                </div>

                <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 16px 0;">
                <p class="modal-subtitle-hint editable-hint">Editable contact details:</p>

                <div class="form-group-modal">
                    <label class="modal-label">Email Address <span class="required-star">*</span></label>
                    <input type="email" name="email" required class="form-control-modal" value="{{ old('email', $user->email) }}">
                </div>

                <div class="modal-grid-2">
                    <div class="form-group-modal">
                        <label class="modal-label">Mobile Number <span class="required-star">*</span></label>
                        <input 
                            type="tel" 
                            name="contact_no" 
                            required 
                            class="form-control-modal" 
                            inputmode="numeric"
                            pattern="[0-9]*"
                            maxlength="11"
                            oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                            value="{{ old('contact_no', $user->contact_no) }}"
                        >
                    </div>

                    <div class="form-group-modal">
                        <label class="modal-label">Civil Status <span class="required-star">*</span></label>
                        @php 
                            $currentStatus = old('civil_status', $user->civil_status ?? 'Single');
                        @endphp
                        <select name="civil_status" required class="form-control-modal">
                            <option value="Single" {{ $currentStatus == 'Single' ? 'selected' : '' }}>Single</option>
                            <option value="Married" {{ $currentStatus == 'Married' ? 'selected' : '' }}>Married</option>
                            <option value="Divorced" {{ $currentStatus == 'Divorced' ? 'selected' : '' }}>Divorced</option>
                            <option value="Widowed" {{ $currentStatus == 'Widowed' ? 'selected' : '' }}>Widowed</option>
                        </select>
                    </div>
                </div>

                <div class="form-group-modal">
                    <label class="modal-label">Home Address <span class="required-star">*</span></label>
                    <textarea name="city_address" required class="form-control-modal" rows="2" style="resize: vertical;">{{ old('city_address', $user->city_address) }}</textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-modal-cancel" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="btn-modal-save">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<style>
    .avatar-upload-wrapper {
        display: flex;
        justify-content: center;
        margin-bottom: 20px;
    }
    .avatar-container {
        position: relative;
        width: 110px;
        height: 110px;
    }
    .avatar-img {
        width: 110px;
        height: 110px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #ffffff;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }
    .avatar-placeholder {
        width: 110px;
        height: 110px;
        border-radius: 50%;
        background-color: #2563eb;
        color: #ffffff;
        font-size: 2.2rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 3px solid #ffffff;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }
    .avatar-edit-btn {
        position: absolute;
        bottom: 2px;
        right: 2px;
        background-color: #0f172a;
        color: #ffffff;
        width: 34px;
        height: 34px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 0.9rem;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        transition: all 0.2s ease;
    }
    .avatar-edit-btn:hover {
        background-color: #2563eb;
        transform: scale(1.08);
    }
    .d-none {
        display: none !important;
    }

    /* EXISTING STYLES */
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
    .d-flex-between {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .card-title {
        font-size: 1.125rem;
        font-weight: 700;
        color: #334155;
        margin: 0;
    }
    .btn-edit-credentials {
        background: none;
        border: none;
        color: #64748b;
        font-size: 1.25rem;
        cursor: pointer;
        padding: 6px;
        border-radius: 6px;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .btn-edit-credentials:hover {
        color: #2563eb;
        background-color: #f1f5f9;
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

    .modal-overlay {
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        background-color: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(4px);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        padding: 16px;
        box-sizing: border-box;
    }
    .modal-overlay.active {
        display: flex;
    }
    .modal-container {
        background: #ffffff;
        border-radius: 14px;
        width: 100%;
        max-width: 550px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        overflow: hidden;
        animation: modalScaleUp 0.25s ease-out;
    }
    @keyframes modalScaleUp {
        from { transform: scale(0.95); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }
    .modal-header {
        background-color: #f8fafc;
        padding: 16px 20px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .modal-title {
        font-size: 1.05rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .modal-title i { color: #2563eb; }
    .modal-close-btn {
        background: none; border: none; color: #94a3b8;
        font-size: 24px; cursor: pointer; padding: 0; line-height: 1;
    }
    .modal-close-btn:hover { color: #475569; }
    .modal-body { padding: 20px; }
    .modal-subtitle-hint {
        font-size: 11px; font-weight: 600; color: #94a3b8;
        text-transform: uppercase; margin: 0 0 10px 0; letter-spacing: 0.3px;
    }
    .editable-hint { color: #2563eb; margin-top: 4px; }
    .modal-grid-2 {
        display: grid; grid-template-columns: 1fr; gap: 12px;
    }
    @media(min-width: 480px) {
        .modal-grid-2 { grid-template-columns: 1fr 1fr; }
    }
    .form-group-modal { margin-bottom: 12px; }
    .modal-label {
        display: block; font-size: 0.8rem; font-weight: 600; color: #475569; margin-bottom: 4px;
    }
    .required-star { color: #ef4444; }
    .form-control-modal {
        width: 100%; padding: 8px 12px; font-size: 0.875rem;
        border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;
        outline: none; background-color: #fff; font-family: inherit;
    }
    .form-control-modal:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15); }
    .disabled-input {
        background-color: #f1f5f9; color: #64748b; cursor: not-allowed; border-color: #e2e8f0; font-weight: 500;
    }
    .modal-footer {
        padding: 12px 20px; background-color: #f8fafc;
        border-top: 1px solid #e2e8f0; text-align: right;
        display: flex; justify-content: flex-end; gap: 10px;
    }
    .btn-modal-cancel {
        background: #fff; border: 1px solid #cbd5e1; padding: 8px 16px;
        font-size: 0.875rem; font-weight: 600; color: #475569; border-radius: 6px;
        cursor: pointer; transition: 0.15s;
    }
    .btn-modal-cancel:hover { background: #f8fafc; color: #1e293b; }
    .btn-modal-save {
        background: #2563eb; border: none; padding: 8px 18px;
        font-size: 0.875rem; font-weight: 600; color: #fff; border-radius: 6px;
        cursor: pointer; transition: 0.15s;
    }
    .btn-modal-save:hover { background: #1d4ed8; }
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

    function openEditModal() {
        document.getElementById('editCredentialsModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeEditModal() {
        document.getElementById('editCredentialsModal').classList.remove('active');
        document.body.style.overflow = '';
    }

    function validatePasswordForm(event) {
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('new_password_confirmation').value;
        const errorDiv = document.getElementById('passwordError');

        if (newPassword.length < 8) {
            event.preventDefault();
            errorDiv.textContent = 'Password must be at least 8 characters long.';
            errorDiv.style.display = 'block';
            return false;
        }

        if (newPassword !== confirmPassword) {
            event.preventDefault();
            errorDiv.textContent = 'New password and confirmation password do not match.';
            errorDiv.style.display = 'block';
            return false;
        }

        errorDiv.style.display = 'none';
        return true;
    }

    window.onclick = function(event) {
        const editModal = document.getElementById('editCredentialsModal');
        if (event.target == editModal) {
            closeEditModal();
        }
    }
</script>
@endsection