<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ClassSchedule;
use App\Models\Subject;

class ScheduleController extends Controller
{
    public function index()
    {
        $schedules = DB::table('class_schedules')
            ->leftJoin('subjects', 'class_schedules.subject_id', '=', 'subjects.id')
            ->leftJoin('curricula', 'subjects.id', '=', 'curricula.subject_id')
            ->leftJoin('employees', 'class_schedules.professor_id', '=', 'employees.id')
            ->select(
                'class_schedules.*',
                'subjects.subject_code',
                'subjects.subject_title',
                'subjects.lecture_units',
                'subjects.lab_units',
                'curricula.year_level',
                'curricula.semester',
                'employees.first_name as prof_first_name',  
                'employees.last_name as prof_last_name',
                DB::raw("(SELECT COUNT(DISTINCT ss.student_number) FROM student_subjects ss
                          INNER JOIN students st ON st.student_id = ss.student_number
                          WHERE ss.schedule_id = class_schedules.id
                            AND ss.status = 'Enrolled') as enrolled_count")
            )
            ->orderBy('class_schedules.section_name', 'asc')
            ->get();

        $subjects = DB::table('subjects')
            ->join('curricula', 'subjects.id', '=', 'curricula.subject_id')
            ->select(
                'subjects.id',
                'subjects.subject_code',
                'subjects.subject_title',
                'curricula.year_level',
                'curricula.semester'
            )
            ->orderBy('curricula.year_level')
            ->orderBy('curricula.semester')
            ->get();

        $professors = DB::table('employees')
            ->whereIn('role', ['professor', 'dean'])
            ->where('status', 'Active')
            ->orderBy('last_name', 'asc')
            ->get();

        $activePeriod = DB::table('academic_periods')->where('is_active', true)->first();
        
        $sections = DB::table('class_schedules')
            ->whereNotNull('section_name')
            ->distinct()
            ->orderBy('section_name', 'asc')
            ->pluck('section_name');

        return view('department_secretary.manage-schedules', compact('schedules', 'subjects', 'professors', 'activePeriod', 'sections'));
    }

    public function enrolledStudents($id)
    {
        $schedule = DB::table('class_schedules')
            ->leftJoin('subjects', 'class_schedules.subject_id', '=', 'subjects.id')
            ->where('class_schedules.id', $id)
            ->select(
                'class_schedules.id',
                'class_schedules.section_name',
                'class_schedules.schedule_type',
                'subjects.subject_code',
                'subjects.subject_title'
            )
            ->first();

        if (!$schedule) {
            return response()->json(['message' => 'Schedule not found.'], 404);
        }

        $students = DB::table('student_subjects as ss')
            ->join('students as s', 's.student_id', '=', 'ss.student_number')
            ->where('ss.schedule_id', $id)
            ->where('ss.status', 'Enrolled')
            ->orderBy('s.last_name', 'asc')
            ->orderBy('s.first_name', 'asc')
            ->get([
                's.student_id',
                's.last_name',
                's.first_name',
                's.middle_name',
                's.course',
                's.year_level',
                's.section',
            ]);

        return response()->json([
            'schedule' => $schedule,
            'students' => $students,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject_id'      => 'required|exists:subjects,id',
            'schedule_type'   => 'required|in:LEC,LAB',
            'section_name'    => 'required|string|max:50',
            'class_days'      => 'required|string|max:20',
            'start_time'      => 'required',
            'end_time'        => 'required',
            'room_assignment' => 'required|string|max:50',
            'professor_id'    => 'required|exists:employees,id',
        ]);

        $activePeriod = DB::table('academic_periods')->where('is_active', true)->first();

        if (!$activePeriod) {
            return redirect()->back()->with('error', 'Cannot create schedule. No active academic period set in the system.');
        }

        $section = strtoupper(preg_replace('/\s+/', ' ', trim($request->input('section_name'))));
        $room    = strtoupper(preg_replace('/\s+/', ' ', trim($request->input('room_assignment'))));
        $type    = strtoupper($request->input('schedule_type'));

        $newStart = strtotime($request->input('start_time'));
        $newEnd   = strtotime($request->input('end_time'));

        if ($newEnd <= $newStart) {
            return redirect()->back()->withInput()->with('error', 'End time must be later than start time.');
        }

        $duplicate = DB::table('class_schedules')
            ->where('academic_period_id', $activePeriod->id)
            ->where('subject_id', $request->input('subject_id'))
            ->where('schedule_type', $type)
            ->where('section_name', $section)
            ->exists();

        if ($duplicate) {
            return redirect()->back()->withInput()->with('error', "A {$type} schedule for this subject already exists in section {$section}.");
        }

        $conflict = $this->findConflict(
            $activePeriod->id,
            $request->input('professor_id'),
            $room,
            $section,
            $request->input('class_days'),
            $newStart,
            $newEnd
        );

        if ($conflict) {
            return redirect()->back()->withInput()->with('error', 'Schedule conflict: ' . $conflict);
        }

        ClassSchedule::create([
            'academic_period_id' => $activePeriod->id,
            'subject_id'         => $request->input('subject_id'),
            'professor_id'       => $request->input('professor_id'),
            'schedule_type'      => $type,
            'section_name'       => $section,
            'class_days'         => $request->input('class_days'),
            'start_time'         => $request->input('start_time'),
            'end_time'           => $request->input('end_time'),
            'room_assignment'    => $room,
        ]);

        return redirect()->back()->with('success', 'Class deployment schedule successfully established!');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'schedule_type'   => 'required|in:LEC,LAB',
            'section_name'    => 'required|string|max:50',
            'class_days'      => 'required|string|max:20',
            'start_time'      => 'required',
            'end_time'        => 'required',
            'room_assignment' => 'required|string|max:50',
            'professor_id'    => 'required|exists:employees,id',
        ]);

        $schedule = DB::table('class_schedules')->where('id', $id)->first();

        if (!$schedule) {
            return redirect()->back()->with('error', 'Schedule not found. It may have been deleted.');
        }

        $section = strtoupper(preg_replace('/\s+/', ' ', trim($request->input('section_name'))));
        $room    = strtoupper(preg_replace('/\s+/', ' ', trim($request->input('room_assignment'))));
        $type    = strtoupper($request->input('schedule_type'));

        $newStart = strtotime($request->input('start_time'));
        $newEnd   = strtotime($request->input('end_time'));

        if ($newEnd <= $newStart) {
            return redirect()->back()->withInput()->with('error', 'End time must be later than start time.');
        }

        $duplicate = DB::table('class_schedules')
            ->where('id', '!=', $id)
            ->where('academic_period_id', $schedule->academic_period_id)
            ->where('subject_id', $schedule->subject_id)
            ->where('schedule_type', $type)
            ->where('section_name', $section)
            ->exists();

        if ($duplicate) {
            return redirect()->back()->withInput()->with('error', "A {$type} schedule for this subject already exists in section {$section}.");
        }

        $conflict = $this->findConflict(
            $schedule->academic_period_id,
            $request->input('professor_id'),
            $room,
            $section,
            $request->input('class_days'),
            $newStart,
            $newEnd,
            $id
        );

        if ($conflict) {
            return redirect()->back()->withInput()->with('error', 'Schedule conflict: ' . $conflict);
        }

        DB::table('class_schedules')->where('id', $id)->update([
            'professor_id'    => $request->input('professor_id'),
            'schedule_type'   => $type,
            'section_name'    => $section,
            'class_days'      => $request->input('class_days'),
            'start_time'      => $request->input('start_time'),
            'end_time'        => $request->input('end_time'),
            'room_assignment' => $room,
            'updated_at'      => now(),
        ]);

        return redirect()->back()->with('success', 'Class schedule successfully updated!');
    }

    private function findConflict($periodId, $professorId, $room, $section, $days, $newStart, $newEnd, $ignoreId = null)
    {
        $query = DB::table('class_schedules')
            ->where('academic_period_id', $periodId)
            ->where(function ($q) use ($professorId, $room, $section) {
                $q->where('professor_id', $professorId)
                  ->orWhereRaw('UPPER(room_assignment) = ?', [$room])
                  ->orWhere('section_name', $section);
            });

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        foreach ($query->get() as $c) {
            if (!$this->daysOverlap($c->class_days, $days)) {
                continue;
            }
            if (strtotime($c->start_time) < $newEnd && strtotime($c->end_time) > $newStart) {
                if ($c->professor_id == $professorId) {
                    return 'The selected faculty already has a class at this day/time.';
                }
                if (strtoupper($c->room_assignment) === $room) {
                    return "Room {$room} is already in use at this day/time.";
                }
                return "Section {$section} already has another class at this day/time.";
            }
        }

        return null;
    }

    private function daysOverlap($a, $b)
    {
        $split = function ($d) {
            return array_map('strtolower', array_map('trim', preg_split('/\s+and\s+/i', (string) $d)));
        };
        return count(array_intersect($split($a), $split($b))) > 0;
    }

    public function delete($id)
    {
        DB::table('student_subjects')->where('schedule_id', $id)->delete();
        DB::table('class_schedules')->where('id', $id)->delete();

        return redirect()->back()->with('success', 'Class deployment record successfully deleted!');
    }
}