@extends('layouts.app')

@section('content')
<div class="sis-container">

    <div class="sis-header">
        <div class="header-titles">
            <h2>Student Enrollment Form</h2>
            <p>Please fill out the student's information and requirements completely.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success d-flex align-items-center justify-content-between p-3 mb-4 rounded-3 shadow-sm border-start border-success border-4" role="alert">
            <div class="d-flex align-items-center gap-3">
                <i class="bi bi-check-circle-fill fs-2 text-success"></i>
                <div>
                    <span class="text-uppercase fw-bold text-success d-block small tracking-wide">
                        Enrollment Successful
                    </span>
                    <div class="fs-5 text-dark mt-1">
                        Assigned Student ID: 
                        <span class="badge bg-success fs-6 px-3 py-2 ms-1 font-monospace shadow-sm">
                            {{ session('student_id') }}
                        </span>
                    </div>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div id="enrollment-validation-error" class="sis-alert sis-alert-error" style="display: none;">
        <i class="fas fa-exclamation-circle sis-alert-icon"></i>
        <div style="width: 100%;">
            <div class="sis-alert-title">ACTION REQUIRED: MISSING OR INVALID INFORMATION</div>
            <div class="sis-alert-body" style="margin-top: 4px;">
                Please complete all required fields and check all required credentials before submitting.
            </div>
            <div id="validation-error-field" class="missing-field-container"></div>
        </div>
    </div>

    <div class="sis-tabs-nav">
        <button class="tab-btn active" onclick="switchSisTab(event, 'tab-admission')">1. Admission & Personal Info</button>
        <button class="tab-btn" onclick="switchSisTab(event, 'tab-education')">2. Educational Background</button>
        <button class="tab-btn" onclick="switchSisTab(event, 'tab-credentials')">3. Credentials Checklist</button>
    </div>

    <div class="sis-card">
        <div class="card-title-bar">
            Student Information Sheet (SIS) Entry Form
        </div>

        <form action="{{ route('registrar.enroll.store') }}" method="POST" autocomplete="off" class="pure-form" novalidate>
            @csrf

            <div id="tab-admission" class="sis-tab-content active">                
                
                <div class="form-grid-3">
                    <div class="form-group">
                        <label>Registration Date</label>
                        <input type="date" name="registration_date" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Student Entry Type</label>
                        <select name="student_type" id="student_type" onchange="filterCredentials()" required>
                            <option value="New">Freshman (New Student)</option>
                            <option value="Transferee">Transferee</option>
                            <option value="Cross-Enrollee">Cross Enrollee</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Course / Program</label>
                        <select name="course" required>
                            <option value="">-- Select Course / Program --</option>
                            <option value="BSIT">BS Information Technology</option>
                            <option value="BSCS">BS Computer Science</option>
                        </select>
                    </div>
                </div>

                <div class="form-grid-2" style="margin-top: 15px;">
                    <div class="form-group">
                        <label>Year Level</label>
                        <select name="year_level" required>
                            <option value="1st Year">1st Year</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option>
                            <option value="4th Year">4th Year</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Semester</label>
                        <select name="semester" required>
                            <option value="1st Semester">1st Semester</option>
                            <option value="2nd Semester">2nd Semester</option>
                        </select>
                    </div>
                </div>

                <h3 class="section-divider">Student Name Profile</h3>
                <div class="form-grid-3">
                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" name="last_name" placeholder="DELA CRUZ" class="uppercase-input" required>
                    </div>
                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" name="first_name" placeholder="JUAN" class="uppercase-input" required>
                    </div>
                    <div class="form-group">
                        <label>Middle Name</label>
                        <input type="text" name="middle_name" placeholder="SANTOS" class="uppercase-input">
                    </div>
                </div>

                <h3 class="section-divider">Personal Information & Contact Details</h3>
                <div class="form-grid-4">
                    <div class="form-group">
                        <label>Sex</label>
                        <select name="sex" required>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Civil Status</label>
                        <select name="civil_status">
                            <option value="Single">Single</option>
                            <option value="Married">Married</option>
                            <option value="Separated">Separated</option>
                            <option value="Widowed">Widowed</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input type="date" name="date_of_birth" required>
                    </div>
                    <div class="form-group">
                        <label>Nationality</label>
                        <input type="text" name="nationality" value="FILIPINO" required>
                    </div>
                </div>

                <div class="form-grid-3" style="margin-top: 15px;">
                    <div class="form-group" style="grid-column: span 2;">
                        <label>City Address</label>
                        <input type="text" name="city_address" placeholder="Current Permanent Address" required>
                    </div>
                    <div class="form-group">
                        <label>Zip Code</label>
                        <input type="text"
                            name="zip_code"
                            placeholder="xxxx"
                            inputmode="numeric"
                            pattern="[0-9]*"
                            maxlength="4"
                            oninput="this.value = this.value.replace(/[^0-9]/g, '');"
                            required>
                    </div>
                </div>
                
                <div class="form-grid-3" style="margin-top: 15px;">
                    <div class="form-group">
                        <label>Place of Birth</label>
                        <input type="text" name="place_of_birth" placeholder="City or Province">
                    </div>
                    <div class="form-group">
                        <label>Mobile Number / Telephone</label>
                        <input type="text"
                            name="contact_no"
                            placeholder="09xxxxxxxxx"
                            inputmode="numeric"
                            pattern="[0-9]*"
                            maxlength="11"
                            oninput="this.value = this.value.replace(/[^0-9]/g, '');"
                            required>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" placeholder="student@example.com" required>
                    </div>
                </div>

                <h3 class="section-divider">Parent Information</h3>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Father's Full Name</label>
                        <input type="text" name="fathers_name" placeholder="Last Name, First Name Middle Name">
                    </div>
                    <div class="form-group">
                        <label>Father's Occupation</label>
                        <input type="text" name="fathers_occupation" placeholder="Profession">
                    </div>
                    <div class="form-group">
                        <label>Mother's Maiden Name</label>
                        <input type="text" name="mothers_name" placeholder="Last Name, First Name Middle Name">
                    </div>
                    <div class="form-group">
                        <label>Mother's Occupation</label>
                        <input type="text" name="mothers_occupation" placeholder="Profession">
                    </div>
                </div>
            </div>

            <div id="tab-education" class="sis-tab-content">
                <h3 class="section-divider">Educational History</h3>
                
                <div class="school-section-header">
                    <div class="section-header-title">Elementary School</div>
                    <button type="button" class="btn-add-school" onclick="addElementarySchool()">
                        <i class="fas fa-plus"></i> Add Elementary
                    </button>
                </div>
                <div id="elementary-schools-container">
                    <div class="school-row elementary-school-row">
                        <div class="school-inputs">
                            <input type="text" name="elem_school_name[]" placeholder="School Name" required>
                            <input type="text" name="elem_school_address[]" placeholder="School Address" required>
                            <input type="text" name="elem_school_year[]" placeholder="Year Graduated (YYYY-YYYY)" maxlength="9" oninput="this.value = this.value.replace(/[^0-9-]/g, '');" required>
                        </div>
                        <button type="button" class="btn-remove-school" onclick="removeRow(this, 'elementary-school-row')" style="display: none;"><i class="fas fa-times"></i> Remove</button>
                    </div>
                </div>

                <div class="school-section-header" style="margin-top: 25px;">
                    <div class="section-header-title">Junior High School</div>
                    <button type="button" class="btn-add-school" onclick="addJuniorHS()">
                        <i class="fas fa-plus"></i> Add Junior HS
                    </button>
                </div>
                <div id="junior-hs-container">
                    <div class="school-row junior-hs-row">
                        <div class="school-inputs">
                            <input type="text" name="jhs_school_name[]" placeholder="School Name" required>
                            <input type="text" name="jhs_school_address[]" placeholder="School Address" required>
                            <input type="text" name="jhs_school_year[]" placeholder="Year Graduated (YYYY-YYYY)" maxlength="9" oninput="this.value = this.value.replace(/[^0-9-]/g, '');" required>
                        </div>
                        <button type="button" class="btn-remove-school" onclick="removeRow(this, 'junior-hs-row')" style="display: none;"><i class="fas fa-times"></i> Remove</button>
                    </div>
                </div>

                <div class="school-section-header" style="margin-top: 25px;">
                    <div class="section-header-title">Senior High School</div>
                    <button type="button" class="btn-add-school" onclick="addSeniorHS()">
                        <i class="fas fa-plus"></i> Add Senior HS
                    </button>
                </div>
                <div id="senior-hs-container">
                    <div class="school-row senior-hs-row">
                        <div class="school-inputs">
                            <input type="text" name="shs_school_name[]" placeholder="School Name" required>
                            <input type="text" name="shs_school_address[]" placeholder="School Address" required>
                            <input type="text" name="shs_school_year[]" placeholder="Year Graduated (YYYY-YYYY)" maxlength="9" oninput="this.value = this.value.replace(/[^0-9-]/g, '');" required>
                        </div>
                        <button type="button" class="btn-remove-school" onclick="removeRow(this, 'senior-hs-row')" style="display: none;"><i class="fas fa-times"></i> Remove</button>
                    </div>
                </div>

                <div class="school-section-header" style="margin-top: 25px;">
                    <div class="section-header-title">Vocational / Trade Course <span style="font-weight: 400; color: #94a3b8;">(Optional)</span></div>
                    <button type="button" class="btn-add-school" onclick="addVocational()">
                        <i class="fas fa-plus"></i> Add Vocational
                    </button>
                </div>
                <div id="vocational-container">
                    <!-- Optional rows added dynamically -->
                </div>

                <div id="transferee-section" style="display: none;">
                    <div class="school-section-header" style="margin-top: 25px;">
                        <div class="section-header-title">College (Transferee Institution)</div>
                    </div>
                    <div class="school-row transferee-row" style="margin-bottom: 0;">
                        <div class="school-inputs">
                            <input type="text" name="college_school_name" placeholder="Previous Institution">
                            <input type="text" name="college_school_address" placeholder="Campus Location Address">
                            <input type="text" name="college_school_year" placeholder="Year Attended" maxlength="9" oninput="this.value = this.value.replace(/[^0-9-]/g, '');">
                        </div>
                    </div>
                </div>
            </div>

            <div id="tab-credentials" class="sis-tab-content">
                <h3 class="section-divider">Required Enrollment Credentials</h3>
                <p class="credentials-instruction">
                    Please verify the physical submission of the following documents for <strong id="selected-type-label" style="color: #0033A0;">Freshman (New Student)</strong>:
                </p>

                <div class="credentials-checklist-container" id="credentials-wrapper">
                    <div class="credential-item-row" data-types="New,Transferee,Cross-Enrollee">
                        <label class="credential-checkbox-label">
                            <input type="checkbox" name="check_psa" class="credential-checkbox" required>
                            <span class="credential-text">PSA Birth Certificate</span>
                        </label>
                    </div>

                    <div class="credential-item-row" data-types="New,Transferee,Cross-Enrollee">
                        <label class="credential-checkbox-label">
                            <input type="checkbox" name="check_2x2" class="credential-checkbox" required>
                            <span class="credential-text">2x2 ID Picture w/ Name Tag</span>
                        </label>
                    </div>

                    <div class="credential-item-row" data-types="New">
                        <label class="credential-checkbox-label">
                            <input type="checkbox" name="check_f138" class="credential-checkbox" required>
                            <span class="credential-text">Form 138 / SF9 (Report Card)</span>
                        </label>
                    </div>

                    <div class="credential-item-row" data-types="New">
                        <label class="credential-checkbox-label">
                            <input type="checkbox" name="check_good_moral_new" class="credential-checkbox" required>
                            <span class="credential-text">Good Moral Character Certificate</span>
                        </label>
                    </div>

                    <div class="credential-item-row" data-types="New">
                        <label class="credential-checkbox-label">
                            <input type="checkbox" name="check_diploma" class="credential-checkbox" required>
                            <span class="credential-text">Certificate of Graduation / Diploma</span>
                        </label>
                    </div>

                    <div class="credential-item-row" data-types="Transferee">
                        <label class="credential-checkbox-label">
                            <input type="checkbox" name="check_tor" class="credential-checkbox" required>
                            <span class="credential-text">Transcript of Records (TOR)</span>
                        </label>
                    </div>

                    <div class="credential-item-row" data-types="Transferee">
                        <label class="credential-checkbox-label">
                            <input type="checkbox" name="check_dismissal" class="credential-checkbox" required>
                            <span class="credential-text">Honorable Dismissal</span>
                        </label>
                    </div>

                    <div class="credential-item-row" data-types="Transferee">
                        <label class="credential-checkbox-label">
                            <input type="checkbox" name="check_good_moral_trans" class="credential-checkbox" required>
                            <span class="credential-text">Certificate of Good Moral (Transferee)</span>
                        </label>
                    </div>

                    <div class="credential-item-row" data-types="Transferee">
                        <label class="credential-checkbox-label">
                            <input type="checkbox" name="check_curriculum" class="credential-checkbox" required>
                            <span class="credential-text">Old Curriculum / Prospectus</span>
                        </label>
                    </div>

                    <div class="credential-item-row" data-types="Cross-Enrollee">
                        <label class="credential-checkbox-label">
                            <input type="checkbox" name="check_permit" class="credential-checkbox" required>
                            <span class="credential-text">Cross Enroll Permit</span>
                        </label>
                    </div>
                </div>

                <div class="form-actions-bar">
                    <button type="reset" class="btn-secondary" onclick="resetCheckboxes()">Clear Checklist</button>
                    <button type="submit" class="btn-primary">Submit & Generate Student ID</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    function switchSisTab(evt, tabId) {
        evt.preventDefault();

        var tabContents = document.getElementsByClassName("sis-tab-content");
        for (var i = 0; i < tabContents.length; i++) {
            tabContents[i].classList.remove("active");
        }

        var tabLinks = document.getElementsByClassName("tab-btn");
        for (var i = 0; i < tabLinks.length; i++) {
            tabLinks[i].classList.remove("active");
        }

        document.getElementById(tabId).classList.add("active");
        evt.currentTarget.classList.add("active");
    }

    function resetCheckboxes() {
        var checkboxes = document.querySelectorAll('.credential-checkbox');
        checkboxes.forEach(chk => {
            chk.checked = false;
        });
        clearInputHighlights();
    }

    function clearInputHighlights() {
        var highlighted = document.querySelectorAll('.input-field-error');
        highlighted.forEach(el => el.classList.remove('input-field-error'));
    }

    function filterCredentials() {
        var dropdown = document.getElementById("student_type");
        var selectedType = dropdown.value;
        var selectedText = dropdown.options[dropdown.selectedIndex].text;
        
        document.getElementById("selected-type-label").innerText = selectedText;

        var rows = document.querySelectorAll(".credential-item-row");
        rows.forEach(row => {
            var allowedTypes = row.getAttribute("data-types").split(",");
            var chkInput = row.querySelector('.credential-checkbox');
            
            if (allowedTypes.includes(selectedType)) {
                row.style.display = "flex"; 
                if (chkInput) chkInput.required = true;
            } else {
                row.style.display = "none";  
                if (chkInput) {
                    chkInput.checked = false;
                    chkInput.required = false;
                }
            }
        });

        var transfereeSection = document.getElementById('transferee-section');
        var collegeInputs = transfereeSection.querySelectorAll('input');
        
        if (selectedType === 'Transferee') {
            transfereeSection.style.display = "block";
            collegeInputs.forEach(input => {
                if (input.name === 'college_school_year') {
                    input.setAttribute('required', 'required');
                }
            });
        } else {
            transfereeSection.style.display = "none";
            collegeInputs.forEach(input => {
                input.removeAttribute('required');
                input.value = "";
            });
        }
    }

    function addElementarySchool() {
        let container = document.getElementById('elementary-schools-container');
        let div = document.createElement('div');
        div.className = 'school-row elementary-school-row';
        div.innerHTML = `<div class="school-inputs">
            <input type="text" name="elem_school_name[]" placeholder="School Name" required>
            <input type="text" name="elem_school_address[]" placeholder="School Address" required>
            <input type="text" name="elem_school_year[]" placeholder="Year Graduated (YYYY-YYYY)" maxlength="9" oninput="this.value = this.value.replace(/[^0-9-]/g, '');" required>
        </div><button type="button" class="btn-remove-school" onclick="removeRow(this, 'elementary-school-row')"><i class="fas fa-times"></i> Remove</button>`;
        container.appendChild(div);
        updateRemoveButtons('elementary-school-row');
    }

    function addJuniorHS() {
        let container = document.getElementById('junior-hs-container');
        let div = document.createElement('div');
        div.className = 'school-row junior-hs-row';
        div.innerHTML = `<div class="school-inputs">
            <input type="text" name="jhs_school_name[]" placeholder="School Name" required>
            <input type="text" name="jhs_school_address[]" placeholder="School Address" required>
            <input type="text" name="jhs_school_year[]" placeholder="Year Graduated (YYYY-YYYY)" maxlength="9" oninput="this.value = this.value.replace(/[^0-9-]/g, '');" required>
        </div><button type="button" class="btn-remove-school" onclick="removeRow(this, 'junior-hs-row')"><i class="fas fa-times"></i> Remove</button>`;
        container.appendChild(div);
        updateRemoveButtons('junior-hs-row');
    }

    function addSeniorHS() {
        let container = document.getElementById('senior-hs-container');
        let div = document.createElement('div');
        div.className = 'school-row senior-hs-row';
        div.innerHTML = `<div class="school-inputs">
            <input type="text" name="shs_school_name[]" placeholder="School Name" required>
            <input type="text" name="shs_school_address[]" placeholder="School Address" required>
            <input type="text" name="shs_school_year[]" placeholder="Year Graduated (YYYY-YYYY)" maxlength="9" oninput="this.value = this.value.replace(/[^0-9-]/g, '');" required>
        </div><button type="button" class="btn-remove-school" onclick="removeRow(this, 'senior-hs-row')"><i class="fas fa-times"></i> Remove</button>`;
        container.appendChild(div);
        updateRemoveButtons('senior-hs-row');
    }

    function addVocational() {
        let container = document.getElementById('vocational-container');
        let div = document.createElement('div');
        div.className = 'school-row vocational-row';
        div.innerHTML = `<div class="school-inputs">
            <input type="text" name="vocational_school_name[]" placeholder="Vocational / Trade School Name">
            <input type="text" name="vocational_school_address[]" placeholder="School Address">
            <input type="text" name="vocational_school_year[]" placeholder="Year Attended" maxlength="9" oninput="this.value = this.value.replace(/[^0-9-]/g, '');">
        </div><button type="button" class="btn-remove-school" onclick="removeRow(this, 'vocational-row')"><i class="fas fa-times"></i> Remove</button>`;
        container.appendChild(div);
        updateRemoveButtons('vocational-row');
    }

    function removeRow(btn, rowClass) {
        let row = btn.closest('.school-row');
        row.remove();
        updateRemoveButtons(rowClass);
    }

    function updateRemoveButtons(rowClass) {
        let rows = document.querySelectorAll('.' + rowClass);
        rows.forEach((row, index) => {
            let btn = row.querySelector('.btn-remove-school');
            if (btn) {
                if (index === 0 && rowClass !== 'vocational-row') {
                    btn.style.display = 'none';
                } else {
                    btn.style.display = 'inline-flex';
                }
            }
        });
    }

    document.addEventListener("DOMContentLoaded", function() {
        filterCredentials();
        updateRemoveButtons('elementary-school-row');
        updateRemoveButtons('junior-hs-row');
        updateRemoveButtons('senior-hs-row');

        var enrollmentForm = document.querySelector('.pure-form');
        enrollmentForm.addEventListener('submit', function (e) {
            clearInputHighlights();

            var tabContents = document.querySelectorAll('.sis-tab-content');
            var tabBtns = document.querySelectorAll('.tab-btn');
            var invalidTab = null;
            var invalidTabIndex = -1;
            var firstInvalidField = null;
            var errorBox = document.getElementById('enrollment-validation-error');
            var errorFieldContainer = document.getElementById('validation-error-field');
            
            errorBox.style.display = 'none';
            errorFieldContainer.innerHTML = '';

            for (var i = 0; i < tabContents.length; i++) {
                var invalidField = tabContents[i].querySelector(':invalid');
                if (invalidField) {
                    invalidTab = tabContents[i];
                    invalidTabIndex = i;
                    firstInvalidField = invalidField;
                    break;
                }
            }

            if (firstInvalidField) {
                e.preventDefault();

                for (var j = 0; j < tabContents.length; j++) {
                    tabContents[j].classList.remove('active');
                }
                for (var k = 0; k < tabBtns.length; k++) {
                    tabBtns[k].classList.remove('active');
                }
                invalidTab.classList.add('active');
                if (tabBtns[invalidTabIndex]) {
                    tabBtns[invalidTabIndex].classList.add('active');
                }

                var fieldLabel = '';
                if (firstInvalidField.classList.contains('credential-checkbox')) {
                    var itemRow = firstInvalidField.closest('.credential-item-row');
                    var textSpan = itemRow ? itemRow.querySelector('.credential-text') : null;
                    fieldLabel = textSpan ? textSpan.innerText.trim() : 'Credential Checkbox';
                    
                    errorFieldContainer.innerHTML = `
                        <div class="missing-field-badge-row">
                            <span>Unchecked Requirement:</span>
                            <span class="missing-field-badge">${fieldLabel}</span>
                        </div>
                    `;

                    if (itemRow) itemRow.classList.add('input-field-error');
                } else {
                    var parentGroup = firstInvalidField.closest('.form-group');
                    if (parentGroup) {
                        var label = parentGroup.querySelector('label');
                        if (label) fieldLabel = label.innerText;
                    } else if (firstInvalidField.placeholder) {
                        fieldLabel = firstInvalidField.placeholder;
                    }

                    errorFieldContainer.innerHTML = `
                        <div class="missing-field-badge-row">
                            <span>Required Field Missing:</span>
                            <span class="missing-field-badge">${fieldLabel || 'Input Field'}</span>
                        </div>
                    `;

                    firstInvalidField.classList.add('input-field-error');
                }

                errorBox.style.display = 'flex';

                setTimeout(function () {
                    firstInvalidField.focus();
                    firstInvalidField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 100);

                var clearErr = function() {
                    firstInvalidField.classList.remove('input-field-error');
                    var parentRow = firstInvalidField.closest('.credential-item-row');
                    if(parentRow) parentRow.classList.remove('input-field-error');
                    firstInvalidField.removeEventListener('input', clearErr);
                    firstInvalidField.removeEventListener('change', clearErr);
                };
                firstInvalidField.addEventListener('input', clearErr);
                firstInvalidField.addEventListener('change', clearErr);
            }
        });

    });

</script>

<style>
    .sis-alert {
        display: flex;
        align-items: flex-start;
        gap: 16px;
        padding: 18px 24px;
        border-radius: 12px;
        margin-bottom: 25px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
        border-left: 8px solid;
        animation: fadeIn 0.3s ease-in-out;
    }

    .sis-alert-error {
        background-color: #fef2f2;
        border-color: #dc2626;
        color: #7f1d1d;
    }

    .sis-alert-success {
        background-color: #f0fdf4;
        border-color: #16a34a;
        color: #14532d;
    }

    .sis-alert-icon {
        font-size: 28px;
        margin-top: 2px;
        flex-shrink: 0;
    }

    .sis-alert-error .sis-alert-icon {
        color: #dc2626;
    }

    .sis-alert-success .sis-alert-icon {
        color: #16a34a;
    }

    .sis-alert-title {
        font-size: 16px;
        font-weight: 800;
        letter-spacing: 0.3px;
        text-transform: uppercase;
    }

    .sis-alert-body {
        font-size: 14.5px;
        font-weight: 600;
        margin-top: 2px;
    }

    .missing-field-container {
        margin-top: 10px;
    }

    .missing-field-badge-row {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background-color: rgba(220, 38, 38, 0.1);
        padding: 6px 14px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 700;
        color: #991b1b;
        border: 1px solid rgba(220, 38, 38, 0.2);
    }

    .missing-field-badge {
        background-color: #dc2626;
        color: #ffffff;
        padding: 3px 10px;
        border-radius: 6px;
        font-size: 13.5px;
        font-weight: 800;
        letter-spacing: 0.5px;
        box-shadow: 0 2px 4px rgba(220, 38, 38, 0.3);
    }

    .input-field-error {
        border: 2px solid #dc2626 !important;
        background-color: #fff5f5 !important;
        box-shadow: 0 0 10px rgba(220, 38, 38, 0.35) !important;
        outline: none;
        animation: shake 0.3s ease-in-out;
    }

    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        75% { transform: translateX(5px); }
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-8px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .sis-container {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: #1e293b;
        max-width: 1200px;
        margin: 0 auto;
        padding-bottom: 50px;
    }

    .sis-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        border-bottom: 2px solid #e2e8f0;
        padding-bottom: 15px;
    }
    .header-titles h2 {
        margin: 0 0 5px 0;
        font-size: 26px;
        font-weight: 700;
        color: #0f172a;
    }
    .header-titles p {
        margin: 0;
        font-size: 14px;
        color: #64748b;
    }

    .sis-tabs-nav {
        display: flex;
        gap: 10px;
        background: white;
        padding: 8px;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        margin-bottom: 25px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }
    .tab-btn {
        flex: 1;
        background: transparent;
        border: none;
        padding: 12px 10px;
        font-size: 14px;
        font-weight: 600;
        color: #64748b;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
        text-align: center;
    }
    .tab-btn:hover {
        background-color: #f1f5f9;
        color: #0033A0;
    }
    .tab-btn.active {
        background-color: #0033A0;
        color: white;
    }

    .sis-card {
        background: white;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        overflow: hidden;
    }
    .card-title-bar {
        background-color: #ffffff;
        padding: 20px 24px;
        font-size: 16px;
        font-weight: 700;
        color: #0033A0;
        border-bottom: 1px solid #f1f5f9;
    }
    .pure-form {
        padding: 24px;
    }

    .sis-tab-content {
        display: none;
    }
    .sis-tab-content.active {
        display: block;
    }

    .section-divider {
        font-size: 15px;
        font-weight: 700;
        color: #334155;
        border-bottom: 1px solid #e2e8f0;
        padding-bottom: 8px;
        margin: 30px 0 20px 0;
    }

    .form-grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
    .form-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    .form-grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .form-group label {
        font-size: 12.5px;
        font-weight: 600;
        color: #475569;
    }
    .form-group input, .form-group select {
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 10px 12px;
        font-size: 14px;
        color: #0f172a;
        background-color: #fff;
        outline: none;
        box-sizing: border-box;
        width: 100%;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .form-group input:focus, .form-group select:focus {
        border-color: #0033A0;
        box-shadow: 0 0 0 3px rgba(0, 51, 160, 0.1);
    }
    .uppercase-input {
        text-transform: uppercase;
    }

    .school-section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }
    .section-header-title {
        font-size: 13.5px;
        font-weight: 700;
        color: #334155;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .school-row {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 18px 20px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 16px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.01);
    }

    .transferee-row {
        border-left: 4px solid #eab308;
    }

    .school-inputs {
        flex: 1;
        display: grid;
        grid-template-columns: 2fr 2fr 1.2fr;
        gap: 16px;
    }
    .school-inputs input {
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 10px 14px;
        font-size: 13.5px;
        outline: none;
        background-color: #fff;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .btn-add-school {
        background-color: #0033A0;
        color: white;
        border: none;
        padding: 8px 16px;
        font-size: 12.5px;
        font-weight: 600;
        border-radius: 6px;
        cursor: pointer;
        transition: background-color 0.15s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    
    .btn-remove-school {
        background-color: #ef4444;
        color: white;
        border: none;
        padding: 10px 16px;
        font-size: 12.5px;
        font-weight: 600;
        border-radius: 6px;
        cursor: pointer;
        transition: background-color 0.15s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
        height: 40px;
    }

    .btn-add-school:hover { background-color: #002270; }
    .btn-remove-school:hover { background-color: #dc2626; }

    .credentials-instruction {
        color: #475569;
        font-size: 14px;
        margin-bottom: 25px;
        line-height: 1.5;
        background-color: #f8fafc;
        padding: 12px 18px;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
    }

    .form-actions-bar {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        margin-top: 35px;
        border-top: 1px solid #e2e8f0;
        padding-top: 20px;
    }
    .btn-primary {
        background-color: #0033A0;
        color: white;
        border: none;
        padding: 12px 30px;
        font-size: 14px;
        font-weight: 700;
        border-radius: 6px;
        cursor: pointer;
        transition: background-color 0.15s ease;
    }
    .btn-primary:hover { background-color: #002270; }
    .btn-secondary {
        background-color: transparent;
        color: #64748b;
        border: 1px solid #cbd5e1;
        padding: 12px 24px;
        font-size: 14px;
        font-weight: 600;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .btn-secondary:hover { background-color: #f8fafc; color: #334155; }

    .credentials-checklist-container {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .credential-item-row {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        padding: 14px 20px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        transition: all 0.2s ease;
    }

    .credential-item-row:hover {
        border-color: #cbd5e1;
        background-color: #f8fafc;
    }
    
    .credential-checkbox-label {
        display: flex;
        align-items: center;
        gap: 12px;
        cursor: pointer;
        flex: 1;
        margin: 0;
    }
    .credential-checkbox {
        width: 18px;
        height: 18px;
        accent-color: #0033A0;
        cursor: pointer;
    }

    .credential-text {
        font-size: 14px;
        font-weight: 600;
        color: #1e293b;
    }
</style>
@endsection