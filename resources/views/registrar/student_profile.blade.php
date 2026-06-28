@extends('layouts.app')

@section('content')
<div class="masterlist-container" style="max-width: 900px; margin: 0 auto; padding-bottom: 50px; font-family: 'Segoe UI', sans-serif;">
    
    <div class="masterlist-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 2px solid #e2e8f0; padding-bottom: 15px;">
        <div class="header-titles">
            <h2 style="margin: 0 0 5px 0; font-size: 26px; font-weight: 700; color: #0f172a;">Student Profile folder</h2>
            <p style="margin: 0; font-size: 14px; color: #64748b;">Academic Year File Registry Overview</p>
        </div>
        <div>
            <a href="{{ route('registrar.manage-student') }}" class="btn-action-view" style="display: inline-block; background-color: transparent; color: #0033A0; border: 1px solid #0033A0; padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; text-decoration: none;">
                <i class="fas fa-arrow-left"></i> Back to Ledger
            </a>
        </div>
    </div>

    <div class="masterlist-card" style="background: white; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); overflow: hidden;">
        <div class="card-top-bar" style="background-color: #0033A0; padding: 20px 24px; font-size: 16px; font-weight: 700; color: white;">
            <span><i class="fas fa-user-graduate"></i> Personal Information — {{ $student->student_id }}</span>
        </div>

        <div style="padding: 24px;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 12px; font-weight: 700; color: #475569; width: 30%; border-bottom: 1px solid #f1f5f9;">Full Name:</td>
                    <td style="padding: 12px; color: #0f172a; font-weight: 600; text-transform: uppercase; border-bottom: 1px solid #f1f5f9;">
                        {{ $student->last_name }}, {{ $student->first_name }} {{ $student->middle_name }}
                    </td>
                </tr>
                <tr>
                    <td style="padding: 12px; font-weight: 700; color: #475569; border-bottom: 1px solid #f1f5f9;">Course program:</td>
                    <td style="padding: 12px; color: #0f172a; font-weight: 600; border-bottom: 1px solid #f1f5f9;">{{ $student->course }}</td>
                </tr>
                <tr>
                    <td style="padding: 12px; font-weight: 700; color: #475569; border-bottom: 1px solid #f1f5f9;">Registration Date:</td>
                    <td style="padding: 12px; color: #0f172a; border-bottom: 1px solid #f1f5f9;">{{ $student->registration_date }}</td>
                </tr>
                <tr>
                    <td style="padding: 12px; font-weight: 700; color: #475569; border-bottom: 1px solid #f1f5f9;">Classification Entry:</td>
                    <td style="padding: 12px; color: #0f172a; border-bottom: 1px solid #f1f5f9;">
                        <span style="background-color: #e0f2fe; color: #0369a1; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700;">
                            {{ $student->student_type }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 12px; font-weight: 700; color: #475569; border-bottom: 1px solid #f1f5f9;">Gender:</td>
                    <td style="padding: 12px; color: #0f172a; border-bottom: 1px solid #f1f5f9;">{{ $student->sex }}</td>
                </tr>
                <tr>
                    <td style="padding: 12px; font-weight: 700; color: #475569; border-bottom: 1px solid #f1f5f9;">Civil Status:</td>
                    <td style="padding: 12px; color: #0f172a; border-bottom: 1px solid #f1f5f9;">{{ $student->civil_status }}</td>
                </tr>
                <tr>
                    <td style="padding: 12px; font-weight: 700; color: #475569; border-bottom: 1px solid #f1f5f9;">Home Address:</td>
                    <td style="padding: 12px; color: #0f172a; border-bottom: 1px solid #f1f5f9;">{{ $student->city_address }}</td>
                </tr>
            </table>
        </div>
    </div>
</div>
@endsection