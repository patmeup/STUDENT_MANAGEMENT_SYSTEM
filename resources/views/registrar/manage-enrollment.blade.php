@extends('layouts.app')

@section('content')
<div class="sis-container">

    <div class="sis-header">
        <div class="header-titles">
            <h2>Enrollment Processing Portal</h2>
            <p>Arellano University — Andres Bonifacio Campus (Digital SIS Registry)</p>
        </div>
        <div class="header-badge">
            Registrar Admission Section
        </div>
    </div>

    @if(session('success'))
        <div style="background-color: #d1e7dd; color: #0f5132; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; border-left: 5px solid #0f5132;">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div style="background-color: #f8d7da; color: #842029; padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; border-left: 5px solid #842029;">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom: 8px;">
                <i class="fas fa-triangle-exclamation"></i> There were some issues with your submission:
            </div>
            <ul style="margin: 0; padding-left: 20px; font-weight: 500; font-size: 13.5px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div id="enrollment-validation-error" style="display: none; background-color: #f8d7da; color: #842029; padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; border-left: 5px solid #842029;">
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 5px;">
            <i class="fas fa-triangle-exclamation"></i>
            Please complete all required fields before submitting the enrollment form.
        </div>
        <div id="validation-error-field" style="font-size: 13.5px; font-weight: 500;"></div>
    </div>

    <div class="sis-tabs-nav">
        <button class="tab-btn active" onclick="switchSisTab(event, 'tab-admission')">1. Admission Info</button>
        <button class="tab-btn" onclick="switchSisTab(event, 'tab-personal')">2. Personal Profile</button>
        <button class="tab-btn" onclick="switchSisTab(event, 'tab-education')">3. Educational Background</button>
        <button class="tab-btn" onclick="switchSisTab(event, 'tab-credentials')">4. Credentials Checklist</button>
    </div>

    <div class="sis-card">
        <div class="card-title-bar">
            Student Information Sheet (SIS) Digital Capture
        </div>

        <form action="{{ route('registrar.enroll.store') }}" method="POST" autocomplete="off" class="pure-form" enctype="multipart/form-data" novalidate>
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
            </div>

            <div id="tab-personal" class="sis-tab-content">
                <h3 class="section-divider">Demographic & Contact Coordinates</h3>
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

                <div class="form-grid-2" style="margin-top: 15px;">
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

                <h3 class="section-divider">Parental Reference Matrix</h3>
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
                <h3 class="section-divider">Academic Institutional History</h3>

                <div id="primary-schools-container">
                    <div class="school-row primary-school-row">
                        <div class="school-title">
                            Primary School
                            <button type="button" class="btn-remove-school" onclick="removePrimarySchool(this)" style="display: none;">
                                <i class="fas fa-times"></i> Remove
                            </button>
                        </div>
                        <div class="school-inputs">
                            <input type="text" name="primary_school_name[]" placeholder="School Name" required>
                            <input type="text" name="primary_school_address[]" placeholder="School Address" required>
                            <input type="text"
                                name="primary_school_year[]"
                                placeholder="Year Graduated (YYYY-YYYY)"
                                inputmode="text"
                                pattern="[0-9]{4}-[0-9]{4}"
                                maxlength="9"
                                oninput="this.value = this.value.replace(/[^0-9-]/g, '');"
                                required>
                        </div>
                    </div>
                </div>

                <button type="button" class="btn-add-school" onclick="addPrimarySchool()">
                    <i class="fas fa-plus"></i> Add Another Primary School
                </button>

                <div class="school-row">
                    <div class="school-title">Secondary School</div>
                    <div class="school-inputs">
                        <input type="text" name="secondary_school_name" placeholder="School Name" required>
                        <input type="text" name="secondary_school_address" placeholder="School Address" required>
                        <input type="text"
                            name="secondary_school_year"
                            placeholder="Year Graduated (YYYY-YYYY)"
                            inputmode="text"
                            pattern="[0-9]{4}-[0-9]{4}"
                            maxlength="9"
                            oninput="this.value = this.value.replace(/[^0-9-]/g, '');"
                            required>
                    </div>
                </div>

                <div class="school-row transferee-row">
                    <div class="school-title">College (Transferee)</div>
                    <div class="school-inputs">
                        <input type="text" name="college_school_name" placeholder="Previous Institution (If applicable)">
                        <input type="text" name="college_school_address" placeholder="Campus Location Address">
                        <input type="text"
                            name="college_school_year"
                            placeholder="Year Attended"
                            inputmode="text"
                            pattern="[0-9]{4}-[0-9]{4}"
                            maxlength="9"
                            oninput="this.value = this.value.replace(/[^0-9-]/g, '');"
                            required>
                    </div>
                </div>
            </div>

            <div id="tab-credentials" class="sis-tab-content">
                <h3 class="section-divider">Mandatory Enrollment Credentials Digital Capture</h3>
                <p style="color: #64748b; font-size: 13px; margin-bottom: 20px;">
                    Please upload or drag-and-drop the scanned documents submitted by the applicant for <strong id="selected-type-label" style="color: #0033A0;">Freshman (New Student)</strong>:
                </p>

                <div class="checkbox-grid" id="credentials-wrapper">
                    <div class="file-upload-card" data-types="New,Transferee,Cross-Enrollee">
                        <span class="file-label-text">PSA Birth Certificate</span>
                        <div class="drop-zone" onclick="triggerFileInput('file_psa')">
                            <i class="fas fa-cloud-upload-alt upload-icon"></i>
                            <span class="drop-zone-text">Click or Drag PDF/Image here</span>
                            <input type="file" name="doc_psa" id="file_psa" class="hidden-file-input" accept=".pdf,.jpg,.jpeg,.png" onchange="updateDropZoneStatus(this)">
                        </div>
                    </div>

                    <div class="file-upload-card" data-types="New,Transferee,Cross-Enrollee">
                        <span class="file-label-text">2x2 ID Picture w/ Name Tag</span>
                        <div class="drop-zone" onclick="triggerFileInput('file_2x2')">
                            <i class="fas fa-image upload-icon"></i>
                            <span class="drop-zone-text">Click or Drag Image here</span>
                            <input type="file" name="doc_2x2" id="file_2x2" class="hidden-file-input" accept=".jpg,.jpeg,.png" onchange="updateDropZoneStatus(this)">
                        </div>
                    </div>

                    <div class="file-upload-card" data-types="New">
                        <span class="file-label-text">Form 138 / SF9 (Report Card)</span>
                        <div class="drop-zone" tabindex="0" onclick="triggerFileInput('file_f138')">
                            <i class="fas fa-file-invoice upload-icon"></i>
                            <span class="drop-zone-text">Click or Drag PDF/Image here</span>
                            <input type="file" name="doc_f138" id="file_f138" class="hidden-file-input" accept=".pdf,.jpg,.jpeg,.png" onchange="updateDropZoneStatus(this)">
                        </div>
                    </div>

                    <div class="file-upload-card" data-types="New">
                        <span class="file-label-text">Good Moral Character Certificate</span>
                        <div class="drop-zone" tabindex="0" onclick="triggerFileInput('file_good_moral_new')">
                            <i class="fas fa-certificate upload-icon"></i>
                            <span class="drop-zone-text">Click or Drag PDF/Image here</span>
                            <input type="file" name="doc_good_moral_new" id="file_good_moral_new" class="hidden-file-input" accept=".pdf,.jpg,.jpeg,.png" onchange="updateDropZoneStatus(this)">
                        </div>
                    </div>

                    <div class="file-upload-card" data-types="New">
                        <span class="file-label-text">Certificate of Graduation / Diploma</span>
                        <div class="drop-zone" onclick="triggerFileInput('file_diploma')">
                            <i class="fas fa-graduation-cap upload-icon"></i>
                            <span class="drop-zone-text">Click or Drag PDF/Image here</span>
                            <input type="file" name="doc_diploma" id="file_diploma" class="hidden-file-input" accept=".pdf,.jpg,.jpeg,.png" onchange="updateDropZoneStatus(this)">
                        </div>
                    </div>

                    <div class="file-upload-card" data-types="Transferee">
                        <span class="file-label-text">Transcript of Records (TOR)</span>
                        <div class="drop-zone" onclick="triggerFileInput('file_tor')">
                            <i class="fas fa-file-alt upload-icon"></i>
                            <span class="drop-zone-text">Click or Drag PDF/Image here</span>
                            <input type="file" name="doc_tor" id="file_tor" class="hidden-file-input" accept=".pdf,.jpg,.jpeg,.png" onchange="updateDropZoneStatus(this)">
                        </div>
                    </div>

                    <div class="file-upload-card" data-types="Transferee">
                        <span class="file-label-text">Honorable Dismissal</span>
                        <div class="drop-zone" onclick="triggerFileInput('file_dismissal')">
                            <i class="fas fa-door-open upload-icon"></i>
                            <span class="drop-zone-text">Click or Drag PDF/Image here</span>
                            <input type="file" name="doc_dismissal" id="file_dismissal" class="hidden-file-input" accept=".pdf,.jpg,.jpeg,.png" onchange="updateDropZoneStatus(this)">
                        </div>
                    </div>

                    <div class="file-upload-card" data-types="Transferee">
                        <span class="file-label-text">Certificate of Good Moral</span>
                        <div class="drop-zone" onclick="triggerFileInput('file_good_moral_trans')">
                            <i class="fas fa-user-shield upload-icon"></i>
                            <span class="drop-zone-text">Click or Drag PDF/Image here</span>
                            <input type="file" name="doc_good_moral_trans" id="file_good_moral_trans" class="hidden-file-input" accept=".pdf,.jpg,.jpeg,.png" onchange="updateDropZoneStatus(this)">
                        </div>
                    </div>

                    <div class="file-upload-card" data-types="Cross-Enrollee">
                        <span class="file-label-text">Cross Enroll Permit</span>
                        <div class="drop-zone" onclick="triggerFileInput('file_permit')">
                            <i class="fas fa-clipboard-check upload-icon"></i>
                            <span class="drop-zone-text">Click or Drag PDF/Image here</span>
                            <input type="file" name="doc_permit" id="file_permit" class="hidden-file-input" accept=".pdf,.jpg,.jpeg,.png" onchange="updateDropZoneStatus(this)">
                        </div>
                    </div>
                </div>

                <div class="form-actions-bar">
                    <button type="reset" class="btn-secondary" onclick="resetDropZones()">Clear Entry Sheet</button>
                    <button type="submit" class="btn-primary">Compile & Secure ID Allocation</button>
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

    // Function para pindutin ang nakatagong input file kapag kinlik ang box
    function triggerFileInput(inputId) {
        document.getElementById(inputId).click();
    }

    // Baguhin ang UI ng box kapag may napiling file
    function updateDropZoneStatus(input) {
        var dropZone = input.parentElement;
        var zoneText = dropZone.querySelector('.drop-zone-text');
        var icon = dropZone.querySelector('.upload-icon');

        if (input.files && input.files.length > 0) {
            var fileName = input.files[0].name;
            dropZone.classList.add('has-file');
            zoneText.innerText = "Selected: " + fileName;
            icon.style.color = "#10b981"; // Magiging green ang icon kapag loaded
        } else {
            dropZone.classList.remove('has-file');
            zoneText.innerText = "Click or Drag PDF/Image here";
            icon.style.color = "#0033A0";
        }
    }

    // I-reset ang mga boxes kapag nag clear ng form
    function resetDropZones() {
        var dropZones = document.querySelectorAll('.drop-zone');
        dropZones.forEach(zone => {
            zone.classList.remove('has-file');
            zone.querySelector('.drop-zone-text').innerText = "Click or Drag PDF/Image here";
            zone.querySelector('.upload-icon').style.color = "#0033A0";
        });
    }

    // REALTIME ENGINE: Sinasala ang mga document slots depende sa Entry Type
    function filterCredentials() {
    var dropdown = document.getElementById("student_type");
    var selectedType = dropdown.value;
    var selectedText = dropdown.options[dropdown.selectedIndex].text;

    document.getElementById("selected-type-label").innerText = selectedText;

    // 1. Handle Document Upload Cards
    var cards = document.getElementById("credentials-wrapper").getElementsByClassName("file-upload-card");
    for (var i = 0; i < cards.length; i++) {
        var allowedTypes = cards[i].getAttribute("data-types").split(",");
        var fileInput = cards[i].querySelector('input[type="file"]');

        if (allowedTypes.includes(selectedType)) {
            cards[i].style.display = "block";
            if (fileInput) {
                fileInput.setAttribute('required', 'required');
            }
        } else {
            cards[i].style.display = "none";
            if (fileInput) {
                fileInput.removeAttribute('required');
                fileInput.value = "";
                updateDropZoneStatus(fileInput);
            }
        }
    }

    // 2. Handle College Input Section for Transferees dynamically
    var transfereeRow = document.querySelector('.transferee-row');
    var collegeInputs = transfereeRow.querySelectorAll('input');

    if (selectedType === 'Transferee') {
        transfereeRow.style.display = "block";
        collegeInputs.forEach(input => {
            if (input.name === 'college_school_year') {
                input.setAttribute('required', 'required');
            }
        });
    } else {
        transfereeRow.style.display = "none";
        collegeInputs.forEach(input => {
            input.removeAttribute('required');
            input.value = ""; // Clear values if toggled away
        });
    }
}

    // Add/Remove Primary School Functions
    function addPrimarySchool() {
        var container = document.getElementById('primary-schools-container');
        var newRow = document.createElement('div');
        newRow.className = 'school-row primary-school-row';
        newRow.innerHTML = `
            <div class="school-title">
                Primary School
                <button type="button" class="btn-remove-school" onclick="removePrimarySchool(this)">
                    <i class="fas fa-times"></i> Remove
                </button>
            </div>
            <div class="school-inputs">
                <input type="text" name="primary_school_name[]" placeholder="School Name" required>
                <input type="text" name="primary_school_address[]" placeholder="School Address" required>
                <input type="text"
                    name="primary_school_year[]"
                    placeholder="Year Graduated (YYYY-YYYY)"
                    inputmode="text"
                    maxlength="9"
                    oninput="this.value = this.value.replace(/[^0-9-]/g, '');"
                    required>
            </div>
        `;
        container.appendChild(newRow);
        updateRemoveButtons();
    }

    function removePrimarySchool(button) {
        var schoolRow = button.closest('.school-row');
        schoolRow.remove();
        updateRemoveButtons();
    }

    function updateRemoveButtons() {
        var schoolRows = document.querySelectorAll('.primary-school-row');
        schoolRows.forEach((row, index) => {
            var removeBtn = row.querySelector('.btn-remove-school');
            if (index === 0) {
                removeBtn.style.display = 'none';
            } else {
                removeBtn.style.display = 'inline-block';
            }
        });
    }

    // Drag and Drop Engine Setup para sa modern feel
    document.addEventListener("DOMContentLoaded", function() {
        filterCredentials();

        var dropZones = document.querySelectorAll('.drop-zone');

        dropZones.forEach(zone => {
            var input = zone.querySelector('input[type="file"]');

            zone.addEventListener('dragover', (e) => {
                e.preventDefault();
                zone.style.borderColor = "#10b981";
                zone.style.background = "#f0fdf4";
            });

            zone.addEventListener('dragleave', () => {
                zone.style.borderColor = "#cbd5e1";
                zone.style.background = "#f8fafc";
            });

            zone.addEventListener('drop', (e) => {
                e.preventDefault();
                zone.style.borderColor = "#cbd5e1";
                zone.style.background = "#f8fafc";

                if (e.dataTransfer.files.length > 0) {
                    // Gumamit ng DataTransfer object constructor para sa malinis na browser binding execution
                    var dt = new DataTransfer();
                    dt.items.add(e.dataTransfer.files[0]);
                    input.files = dt.files;

                    // I-trigger ang update status native listener
                    updateDropZoneStatus(input);
                }
            });
        });

        // FIX: Kapag may hidden na required field sa ibang tab na hindi napunan,
        // hindi maipapakita ng browser ang validation tooltip (dahil display:none ang tab),
        // kaya tahimik lang ito bino-block ang submit. Awtomatiko na lang tayong lilipat
        // sa tamang tab kung saan nagmula ang unang invalid field, bago i-report ang error.

        var enrollmentForm = document.querySelector('.pure-form');
        enrollmentForm.addEventListener('submit', function (e) {
            var tabContents = document.querySelectorAll('.sis-tab-content');
            var tabBtns = document.querySelectorAll('.tab-btn');
            var invalidTab = null;
            var invalidTabIndex = -1;
            var firstInvalidField = null;
            var errorBox = document.getElementById('enrollment-validation-error');
            var errorField = document.getElementById('validation-error-field');
            errorBox.style.display = 'none';
            errorField.innerText = '';

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
                errorBox.style.display = 'block';
                var fieldLabel = '';
                var parentGroup = firstInvalidField.closest('.form-group');
                if (parentGroup) {
                    var label = parentGroup.querySelector('label');
                    if (label) {
                        fieldLabel = label.innerText;
                    }
                }

                if (!fieldLabel && firstInvalidField.type === 'file') {
                    var fileCard = firstInvalidField.closest('.file-upload-card');
                    if (fileCard) {
                        var fileLabel = fileCard.querySelector('.file-label-text');
                        if (fileLabel) {
                            fieldLabel = fileLabel.innerText;
                        }
                    }
                }

                if (!fieldLabel) {
                    fieldLabel = firstInvalidField.name;
                }
                errorField.innerText = 'Missing required field: ' + fieldLabel;
                document.querySelectorAll('.validation-error-field').forEach(function(field) {
                    field.classList.remove('validation-error-field');
                });
                if (firstInvalidField.type === 'file') {

                    var invalidFileCard = firstInvalidField.closest('.file-upload-card');
                    var invalidDropZone = invalidFileCard
                        ? invalidFileCard.querySelector('.drop-zone')
                        : null;
                    if (invalidDropZone) {
                        invalidDropZone.classList.add('validation-error-field');
                    }
                } else {
                    firstInvalidField.classList.add('validation-error-field');
                }
                setTimeout(function () {
                    if (firstInvalidField.type === 'file') {
                        var fileCard = firstInvalidField.closest('.file-upload-card');
                        var dropZone = fileCard
                            ? fileCard.querySelector('.drop-zone')
                            : null;
                        if (dropZone) {
                            dropZone.focus();
                            dropZone.scrollIntoView({
                                behavior: 'smooth',
                                block: 'center'
                            });
                        }
                    } else {
                        firstInvalidField.focus();
                        firstInvalidField.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                        firstInvalidField.reportValidity();
                    }
                }, 50);
                errorBox.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
            }
        });

    });

</script>

<style>
    .validation-error-field {
        border-color: #dc2626 !important;
        box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.15) !important;
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
    .header-badge {
        background-color: #0033A0;
        color: white;
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 13px;
        box-shadow: 0 2px 5px rgba(0,51,160,0.2);
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
        transition: border-color 0.15s ease;
    }
    .form-group input:focus, .form-group select:focus {
        border-color: #0033A0;
        box-shadow: 0 0 0 3px rgba(0, 51, 160, 0.1);
    }
    .uppercase-input {
        text-transform: uppercase;
    }

    .school-row {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
    }
    .school-title {
        font-size: 13px;
        font-weight: 700;
        color: #475569;
        margin-bottom: 10px;
        text-transform: uppercase;
    }
    .transferee-row {
        border-left: 4px solid #eab308;
    }
    .school-inputs {
        display: grid;
        grid-template-columns: 2fr 2fr 1fr;
        gap: 15px;
    }
    .school-inputs input {
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 8px 12px;
        font-size: 13.5px;
        outline: none;
    }
    .school-inputs input:focus {
        border-color: #0033A0;
    }

    .btn-add-school, .btn-remove-school {
        background-color: #0033A0;
        color: white;
        border: none;
        padding: 8px 16px;
        font-size: 12px;
        font-weight: 600;
        border-radius: 6px;
        cursor: pointer;
        transition: background-color 0.15s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .btn-add-school {
        margin-bottom: 20px;
    }
    .btn-remove-school {
        background-color: #ef4444;
        margin-left: 15px;
        padding: 4px 10px;
        font-size: 11px;
    }
    .btn-add-school:hover {
        background-color: #002270;
    }
    .btn-remove-school:hover {
        background-color: #dc2626;
    }

    .checkbox-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 15px;
    }
    .check-box-card {
        display: flex;
        align-items: center;
        gap: 12px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        padding: 15px;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .check-box-card:hover {
        border-color: #0033A0;
        background-color: rgba(0, 51, 160, 0.01);
    }
    .check-box-card input[type="checkbox"] {
        width: 18px;
        height: 18px;
        cursor: pointer;
        accent-color: #0033A0;
    }
    .check-box-card span {
        font-size: 14px;
        font-weight: 600;
        color: #334155;
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
    .btn-primary:hover {
        background-color: #002270;
    }
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
    .btn-secondary:hover {
        background-color: #f8fafc;
        color: #334155;
    }

.file-upload-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        padding: 15px;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.01);
    }

    .file-label-text {
        display: block;
        font-size: 13.5px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 8px;
    }

    .drop-zone {
        border: 2px dashed #cbd5e1;
        background-color: #f8fafc;
        border-radius: 8px;
        padding: 20px 10px;
        text-align: center;
        cursor: pointer;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        transition: all 0.2s ease;
    }

    .drop-zone:hover {
        border-color: #0033A0;
        background-color: rgba(0, 51, 160, 0.02);
    }

    .drop-zone.has-file {
        border-color: #10b981;
        background-color: rgba(16, 185, 129, 0.03);
    }

    .upload-icon {
        font-size: 24px;
        color: #0033A0;
        transition: color 0.2s ease;
    }

    .drop-zone-text {
        font-size: 12px;
        font-weight: 600;
        color: #64748b;
    }

    .hidden-file-input {
        display: none;
    }

    /* Panatilihin ang grid system */
    .checkbox-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 15px;
    }
</style>
@endsection
