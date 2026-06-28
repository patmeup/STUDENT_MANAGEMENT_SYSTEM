@extends('layouts.app')

@section('content')
<div class="sa-container">
    
    <div class="sa-header">
        <div class="header-titles">
            <h2>Global Subject Encoder</h2>
            <p>Admin Control Panel — Curriculum Core Masterlist</p>
        </div>
        <div class="header-badge system-level">Admin Mode</div>
    </div>

    @if(session('success'))
        <div class="sa-alert-success">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    <div class="sa-workspace">

        <div class="sa-card form-panel">
            <div class="card-bar">
                <span><i class="fas fa-book-medical"></i> Subject Specifications Form</span>
            </div>
            
            {{-- Binigyan natin ng ID ang form at dynamically babaguhin ang action path kapag Edit mode na --}}
            <form id="subjectForm" action="{{ route('system_admin.subjects.store') }}" method="POST" autocomplete="off" class="pure-form">
                @csrf
                
                {{-- Mahalagang Hidden Input: Dito itatago ang ID ng isasalbuhang subject kapag mag-aupdate --}}
                <input type="hidden" name="subject_id" id="form_subject_id">
                
                <div class="form-group">
                    <label>Subject Code / Acronym</label>
                    <input type="text" name="subject_code" id="form_subject_code" placeholder="e.g., COPRO1, GEC-RPH, NET101" class="uppercase-input" required>
                    <small class="field-help">This field must be completely unique inside the global database system.</small>
                </div>

                <div class="form-group">
                    <label>Subject Descriptive Title</label>
                    <input type="text" name="subject_title" id="form_subject_title" placeholder="e.g., Computer Programming 1 (Contextualized)" required>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Lecture Units Credit</label>
                        <input type="number" name="lecture_units" id="form_lecture_units" min="0" max="9" value="3" required>
                    </div>
                    <div class="form-group">
                        <label>Laboratory Units Credit</label>
                        <input type="number" name="lab_units" id="form_lab_units" min="0" max="9" value="0" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Assigned Instructor / Professor</label>
                    <select name="professor_id" id="form_professor_id" class="select-input">
                        <option value="">-- Unassigned / Pool Assignment --</option>
                        @if(isset($professors) && $professors->count() > 0)
                            @foreach($professors as $prof)
                                <option value="{{ $prof->id }}">
                                    {{ strtoupper($prof->last_name) }}, {{ $prof->first_name }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                    <small class="field-help">Select an active instructor to handle this class section deployment.</small>
                </div>

                <div class="form-actions-bar">
                    <button type="button" class="btn-clear" onclick="resetFormState()">Reset Form</button>
                    <button type="submit" id="btn-submit-action" class="btn-submit-subj">Compile & Save</button>
                </div>
            </form>
        </div>

        <div class="sa-card table-panel">
            <div class="card-header-flex">
                <h3 class="card-title-list">Global Subject Catalog</h3>
                <span class="badge-count">{{ isset($subjects) ? $subjects->count() : 0 }} Total Subjects</span>
            </div>

            <div class="table-responsive">
                <table class="minimal-table">
                    <thead>
                        <tr>
                            <th>Subject Code</th>
                            <th>Descriptive Title</th>
                            <th>Assigned Faculty</th>
                            <th style="text-align: center;">Lect</th>
                            <th style="text-align: center;">Lab</th>
                            <th style="text-align: center;">Total</th>
                            <th style="text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($subjects) && $subjects->count() > 0)
                            @foreach($subjects as $subject)
                                <tr>
                                    <td><span class="subject-code-tag">{{ strtoupper($subject->subject_code) }}</span></td>
                                    <td class="subject-title-text">{{ $subject->subject_title }}</td>
                                    
                                    <td>
                                        @if($subject->professor)
                                            <span class="prof-assigned-tag">
                                                <i class="fas fa-user-tie"></i> {{ strtoupper($subject->professor->last_name) }}, {{ $subject->professor->first_name }}
                                            </span>
                                        @else
                                            <span class="prof-unassigned-tag">Vacant Pool</span>
                                        @endif
                                    </td>

                                    <td style="text-align: center;">{{ $subject->lecture_units }}</td>
                                    <td style="text-align: center;">{{ $subject->lab_units }}</td>
                                    <td style="text-align: center;">
                                        <strong class="total-units-text">{{ $subject->total_units ?? ($subject->lecture_units + $subject->lab_units) }}</strong>
                                    </td>
                                    <td style="text-align: center;">
                                        {{-- Nilagyan natin ng data-id para makuha ng JavaScript kung aling hilera ang pinindot --}}
                                        <button type="button" class="btn-action-edit btn-trigger-edit" data-id="{{ $subject->id }}" title="Edit Subject">Edit</button>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="7" class="empty-table-state">
                                    There's no registered subject found in the system records.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<style>
    .sa-container { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #0f172a; max-width: 1300px; margin: 0 auto; padding: 20px; }
    
    /* Header Architecture */
    .sa-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 2px solid #e2e8f0; padding-bottom: 18px; }
    .header-titles h2 { margin: 0 0 4px 0; font-size: 28px; font-weight: 800; color: #0f172a; letter-spacing: -0.5px; }
    .header-titles p { margin: 0; font-size: 14.5px; color: #64748b; }
    .system-level { background-color: #dc2626; color: #ffffff; padding: 6px 14px; border-radius: 20px; font-weight: 700; font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.5px; }

    /* Layout Grids */
    .sa-workspace { display: flex; flex-direction: row; gap: 28px; align-items: flex-start; justify-content: space-between; width: 100%; }
    .form-panel { flex: 1; min-width: 380px; background: #ffffff; }
    .table-panel { flex: 1.8; background: #ffffff; }

    /* Component Cards */
    .sa-card { border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 10px -2px rgba(0, 0, 0, 0.03); overflow: hidden; }
    .card-bar { background: #f8fafc; padding: 16px 22px; font-weight: 700; font-size: 14.5px; color: #1e293b; border-bottom: 1px solid #e2e8f0; }
    .card-header-flex { background-color: #f8fafc; padding: 16px 22px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
    .card-title-list { font-size: 14.5px; font-weight: 700; color: #1e293b; margin: 0; }
    
    /* Form Mechanics */
    .pure-form { padding: 24px; display: flex; flex-direction: column; gap: 16px; }
    .form-group { display: flex; flex-direction: column; gap: 6px; }
    .form-group label { font-size: 12px; font-weight: 600; color: #475569; }
    .form-group input, .select-input { border: 1px solid #cbd5e1; border-radius: 6px; padding: 10px 14px; font-size: 14px; color: #0f172a; outline: none; background: #ffffff; box-sizing: border-box; width: 100%; }
    .form-group input:focus, .select-input:focus { border-color: #0033A0; box-shadow: 0 0 0 3px rgba(0, 51, 160, 0.15); }
    .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .uppercase-input { text-transform: uppercase; }
    .field-help { font-size: 11px; color: #94a3b8; margin-top: 2px; }
    
    /* Custom Dropdown Styling */
    .select-input { background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e"); background-position: right 12px center; background-repeat: no-repeat; background-size: 20px; -webkit-appearance: none; -moz-appearance: none; appearance: none; padding-right: 40px; cursor: pointer; }

    /* Action Buttons */
    .form-actions-bar { display: flex; justify-content: flex-end; gap: 12px; margin-top: 12px; border-top: 1px solid #f1f5f9; padding-top: 16px; }
    .btn-submit-subj { background: #0033A0; color: #ffffff; border: none; padding: 10px 20px; font-size: 14px; font-weight: 700; border-radius: 6px; cursor: pointer; transition: background 0.2s; text-align: center; }
    .btn-submit-subj:hover { background: #002270; }
    .btn-clear { background: transparent; color: #64748b; border: 1px solid #cbd5e1; padding: 10px 16px; font-size: 14px; font-weight: 600; border-radius: 6px; cursor: pointer; }
    .btn-clear:hover { background: #f8fafc; color: #334155; }

    /* Database Table Layouts */
    .table-responsive { width: 100%; overflow-x: auto; }
    .minimal-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 13.5px; }
    .minimal-table th { background: #f8fafc; padding: 14px 20px; font-size: 11.5px; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; }
    .minimal-table td { padding: 15px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
    .minimal-table tbody tr:hover { background-color: #f8fafc; }
    
    .subject-code-tag { font-family: ui-monospace, SFMono-Regular, Consolas, monospace; font-weight: 700; color: #2563eb; background-color: #eff6ff; padding: 4px 8px; border-radius: 6px; font-size: 12px; display: inline-block; }
    .subject-title-text { font-weight: 600; color: #1e293b; }
    
    /* Professor Assignment Badges */
    .prof-assigned-tag { font-size: 12.5px; font-weight: 600; color: #047857; background-color: #ecfdf5; border: 1px solid #a7f3d0; padding: 4px 10px; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px; }
    .prof-unassigned-tag { font-size: 12px; font-weight: 500; color: #64748b; background-color: #f1f5f9; padding: 4px 10px; border-radius: 6px; display: inline-block; font-style: italic; }

    .total-units-text { color: #0f172a; font-weight: 700; }
    .empty-table-state { text-align: center; color: #94a3b8; padding: 50px 0 !important; font-style: italic; font-size: 13.5px; }

    .btn-action-edit { background-color: transparent; border: 1px solid #cbd5e1; color: #475569; padding: 5px 12px; font-size: 12px; font-weight: 600; border-radius: 6px; cursor: pointer; transition: all 0.2s; }
    .btn-action-edit:hover { background-color: #f1f5f9; color: #1e293b; border-color: #94a3b8; }

    /* Alerts */
    .sa-alert-success { background: #ecfdf5; color: #065f46; padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; font-weight: 600; border-left: 5px solid #059669; font-size: 14px; }
    .badge-count { background: #e2e8f0; color: #334155; font-size: 11.5px; padding: 4px 12px; border-radius: 20px; font-weight: 700; }
</style>

{{-- JavaScript Section --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const editButtons = document.querySelectorAll('.btn-trigger-edit');
    const formElement = document.getElementById('subjectForm');
    const submitBtn = document.getElementById('btn-submit-action');

    editButtons.forEach(button => {
        button.addEventListener('click', function () {
            const id = this.getAttribute('data-id');

            // Gagamit tayo ng dynamic string replacement para i-inject ang ID sa tamang Laravel Route
            let editUrl = "{{ route('admin.subject.edit', ':id') }}";
            editUrl = editUrl.replace(':id', id);

            fetch(editUrl)
                .then(response => {
                    if (!response.ok) throw new Error('Network query error');
                    return response.json();
                })
                .then(data => {
                    // Punuin ang input fields gamit ang natanggap na data
                    document.getElementById('form_subject_id').value = data.id;
                    document.getElementById('form_subject_code').value = data.subject_code;
                    document.getElementById('form_subject_title').value = data.subject_title;
                    document.getElementById('form_lecture_units').value = data.lecture_units;
                    document.getElementById('form_lab_units').value = data.lab_units;
                    document.getElementById('form_professor_id').value = data.professor_id || '';

                    // Palitan ang Form Action at Button Design para maging malinaw na UPDATE operation
                    formElement.action = "{{ route('admin.subject.update') }}"; 
                    submitBtn.innerText = "Update Record Entry";
                    submitBtn.style.backgroundColor = "#dc2626"; // Papalitan ng kulay admin-red
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    alert('Hindi makuha ang detalye ng subject. Pakisigurado na ang route name na "admin.subject.edit" ay rehistrado sa iyong web.php at gumagana ang Controller method nito.');
                });
        });
    });
});

function resetFormState() {
    const formElement = document.getElementById('subjectForm');
    const submitBtn = document.getElementById('btn-submit-action');
    
    formElement.reset();
    document.getElementById('form_subject_id').value = '';
    formElement.action = "{{ route('system_admin.subjects.store') }}";
    
    submitBtn.innerText = "Compile & Save";
    submitBtn.style.backgroundColor = "#0033A0";
}
</script>
@endsection