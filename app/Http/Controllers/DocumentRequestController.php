<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DocumentRequestController extends Controller
{
    public function showDocumentReqList()
    {
        $studentId = session('student_id') ?? Auth::user()->student_id;

        $user = DB::table('students')->where('student_id', $studentId)->first();

        $requests = DB::table('document_requests')
            ->where('student_id', $studentId)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('student.document_request', compact('user', 'requests'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'document_name' => 'required',
            'purpose'       => 'required|string|max:500',
        ]);

        $studentId = session('student_id') ?? Auth::user()->student_id;

        $documentName = is_array($request->document_name) 
            ? json_encode($request->document_name) 
            : $request->document_name;

        DB::table('document_requests')->insert([
            'student_id'    => $studentId,
            'document_name' => $documentName,
            'purpose'       => $request->purpose,
            'status'        => 'Pending',
            'file_path'     => null,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        return redirect()->back()->with('success', 'Document request submitted successfully!');
    }

    public function manageDocumentRequests()
    {
        $requests = DB::table('document_requests')
            ->join('students', 'document_requests.student_id', '=', 'students.student_id')
            ->select(
                'document_requests.id as request_id',
                'document_requests.document_name',
                'document_requests.purpose',
                'document_requests.status',
                'document_requests.created_at',
                'students.id as student_pk',
                'students.student_id',
                'students.first_name',
                'students.last_name',
                'students.email'
            )
            ->orderBy('document_requests.created_at', 'desc')
            ->get();

        return view('registrar.document_requests', compact('requests'));
    }

    public function updateStatus(Request $request, $requestId)
    {
        $request->validate([
            'status'  => 'required|string',
        ]);

        DB::table('document_requests')
            ->where('id', $requestId)
            ->update([
                'status'     => $request->status,
                'updated_at' => now(),
            ]);

        return redirect()->back()->with('success', 'Document request status updated successfully!');
    }
}