@extends('layouts.app')

@section('content')
<!-- Siguraduhing may Font Awesome para sa Pen at Close logos -->
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
            <!-- PEN LOGO BUTTON FOR EDITING -->
            <button type="button" class="btn-edit-credentials" onclick="openEditModal()" title="Edit Contact Information">
                <i class="fa-solid fa-pen-to-square"></i>
            </button>
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

    <div class="profile-card">
        <div class="card-header">
            <h3 class="card-title">Document Requests & Availability</h3>
        </div>

        <form action="{{ route('student.submit_request') }}" method="POST">
            @csrf
            <div class="card-body">
                <p style="font-size: 0.875rem; color: #64748b; margin-top: 0; margin-bottom: 16px;">
                    You can choose and request your documents if you've submitted it to the Registrar Office.
                </p>

                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.875rem;">
                        <thead>
                            <tr style="border-bottom: 2px solid #e2e8f0; color: #475569;">
                                <th style="padding: 10px; width: 40px; text-align: center;">Choose</th>
                                <th style="padding: 10px;">Document Name</th>
                                <th style="padding: 10px;">Status / Availability</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $studentType = $user->student_type;

                                $allowedCodes = [];
                                if ($studentType === 'New') {
                                    $allowedCodes = ['doc_psa', 'doc_2x2', 'doc_f138', 'doc_good_moral_new', 'doc_diploma'];
                                } elseif ($studentType === 'Transferee') {
                                    $allowedCodes = ['doc_psa', 'doc_2x2', 'doc_tor', 'doc_dismissal', 'doc_good_moral_trans'];
                                } elseif ($studentType === 'Cross-Enrollee') {
                                    $allowedCodes = ['doc_psa', 'doc_2x2', 'doc_permit'];
                                }

                                $allDocuments = [
                                    ['name' => 'PSA Birth Certificate', 'path' => $user->path_psa, 'code' => 'doc_psa'],
                                    ['name' => '2x2 Picture', 'path' => $user->path_2x2, 'code' => 'doc_2x2'],
                                    ['name' => 'Form 138 (F138)', 'path' => $user->path_f138, 'code' => 'doc_f138'],
                                    ['name' => 'Good Moral Character', 'path' => $user->path_good_moral_new, 'code' => 'doc_good_moral_new'],
                                    ['name' => 'Diploma', 'path' => $user->path_diploma, 'code' => 'doc_diploma'],
                                    ['name' => 'Transcript of Records (TOR)', 'path' => $user->path_tor, 'code' => 'doc_tor'],
                                    ['name' => 'Transfer Credential / Honorable Dismissal', 'path' => $user->path_dismissal, 'code' => 'doc_dismissal'],
                                    ['name' => 'Good Moral Character (Transferee)', 'path' => $user->path_good_moral_trans, 'code' => 'doc_good_moral_trans'],
                                    ['name' => 'Permit', 'path' => $user->path_permit, 'code' => 'doc_permit'],
                                ];

                                $filteredDocuments = array_filter($allDocuments, function($doc) use ($allowedCodes) {
                                    return in_array($doc['code'], $allowedCodes);
                                });
                            @endphp

                            @forelse($filteredDocuments as $doc)
                                @php
                                    $hasFileRecord = !empty($doc['path']);
                                @endphp

                                <tr style="border-bottom: 1px solid #f1f5f9;">
                                    <td style="padding: 12px 10px; text-align: center;">
                                        @if($hasFileRecord)
                                            <input type="checkbox" name="documents[]" value="{{ $doc['name'] }}" style="width: 16px; height: 16px; cursor: pointer;">
                                        @else
                                            <input type="checkbox" disabled title="Wala pa sa iyong record" style="width: 16px; height: 16px; cursor: not-allowed;">
                                        @endif
                                    </td>
                                    <td style="padding: 12px 10px; font-weight: 600; color: #1e293b;">
                                        {{ $doc['name'] }}
                                    </td>
                                    <td style="padding: 12px 10px;">
                                        @if($hasFileRecord)
                                            <span style="background-color: #dcfce7; color: #166534; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600;">
                                                Available on Records
                                            </span>
                                        @else
                                            <span style="background-color: #f1f5f9; color: #64748b; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600;">
                                                There is no document or you haven't submitted to the Registrar Office.
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" style="padding: 16px; text-align: center; color: #64748b;">
                                        There are no record of documents that you've submitted.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- PURPOSE FIELD & SUBMIT BUTTON -->
                <div style="margin-top: 20px;">
                    <label class="form-label" style="width: 100%; margin-bottom: 6px;">Purpose on requesting the documents<span style="color: #ef4444;">*</span></label>
                    <textarea name="purpose" required class="form-control" rows="3" style="width: 100%; resize: vertical;"></textarea>
                </div>

                <div style="margin-top: 16px; display: flex; justify-content: flex-end;">
                    <button type="submit" class="btn-submit" style="width: auto; padding: 10px 24px;">
                        Submit all requested Documents
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- UPDATE SECURITY PASSWORD CARD -->
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

<!-- EDIT CREDENTIALS MODAL OVERLAY -->
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
                
                <!-- DISABLED / ACADEMIC FIELDS -->
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
                </div>

                <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 16px 0;">
                <p class="modal-subtitle-hint editable-hint">Editable contact details:</p>

                <!-- EDITABLE FIELDS WITH EXISTING DETAILS -->
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

<!-- REQUEST DOCUMENT MODAL -->
<div class="modal-overlay" id="documentRequestModal">
    <div class="modal-container">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fa-solid fa-file-arrow-down"></i> Mag-request ng Dokumento</h3>
            <button type="button" class="modal-close-btn" onclick="closeRequestModal()">&times;</button>
        </div>
        
        <form action="{{ route('student.submit_request') }}" method="POST">
            @csrf
            <div class="modal-body">
                <input type="hidden" name="document_code" id="modalDocCode">
                
                <div class="form-group-modal">
                    <label class="modal-label">Nire-request na Dokumento</label>
                    <input type="text" id="modalDocName" class="form-control-modal disabled-input" readonly>
                </div>

                <div class="form-group-modal" style="margin-top: 12px;">
                    <label class="modal-label">Layunin / Purpose <span class="required-star">*</span></label>
                    <textarea name="purpose" required class="form-control-modal" rows="3" placeholder="Ilagay kung para saan gagamitin ang dokumento (Hal. Employment, Board Exam, Scholarship)"></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-modal-cancel" onclick="closeRequestModal()">Kanselahin</button>
                <button type="submit" class="btn-modal-save">Ipadala ang Request</button>
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

    /* MODAL WINDOW CSS STYLES */
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

    // EDIT CREDENTIALS MODAL LOGIC
    function openEditModal() {
        document.getElementById('editCredentialsModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeEditModal() {
        document.getElementById('editCredentialsModal').classList.remove('active');
        document.body.style.overflow = '';
    }

    // DOCUMENT REQUEST MODAL LOGIC
    function openRequestModal(docName, docCode) {
        document.getElementById('modalDocName').value = docName;
        document.getElementById('modalDocCode').value = docCode;
        document.getElementById('documentRequestModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeRequestModal() {
        document.getElementById('documentRequestModal').classList.remove('active');
        document.body.style.overflow = '';
    }

    // Isara ang modals kapag kinlik sa labas ng box
    window.onclick = function(event) {
        const editModal = document.getElementById('editCredentialsModal');
        const requestModal = document.getElementById('documentRequestModal');
        if (event.target == editModal) {
            closeEditModal();
        }
        if (event.target == requestModal) {
            closeRequestModal();
        }
    }
</script>
@endsection