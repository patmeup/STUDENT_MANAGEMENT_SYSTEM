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
                'employees.last_name as prof_last_name'
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

        $newSchedule = ClassSchedule::create([
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

        // NOTIFICATIONS: professor assigned + dean of the professor's department
        $__label = \App\Support\Notifier::classLabel($newSchedule->id);
        $__when  = $newSchedule->class_days . ' ' . date('h:i A', strtotime($request->input('start_time')))
                 . '-' . date('h:i A', strtotime($request->input('end_time'))) . ', ' . strtoupper($request->input('room_assignment'));
        \App\Support\Notifier::toEmployee($request->input('professor_id'), 'schedule_assigned', 'New class assigned',
            "You were assigned to {$__label}: {$__when}.", \App\Support\Notifier::url('professor.manage-classes'));
        \App\Support\Notifier::toDeanOfProfessor($request->input('professor_id'), 'schedule_created', 'New class schedule created',
            "{$__label} was scheduled ({$__when}).", \App\Support\Notifier::url('dean.schedule.list'));

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

        $__oldProf = \App\Support\Notifier::scheduleProfessorId($id); // before the update (class or subject professor)

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

        // NOTIFICATIONS: reassignment -> old + new professor; time/room/section change -> professor + students
        $__fmt     = fn ($t) => date('h:i A', strtotime($t));
        $__oldWhen = "{$schedule->class_days} " . $__fmt($schedule->start_time) . '-' . $__fmt($schedule->end_time) . ", {$schedule->room_assignment}";
        $__newWhen = $request->input('class_days') . ' ' . $__fmt($request->input('start_time')) . '-' . $__fmt($request->input('end_time')) . ", {$room}";
        $__changed = $__oldWhen !== $__newWhen
                  || $schedule->section_name !== $section
                  || strtoupper($schedule->schedule_type) !== $type;
        $__label   = \App\Support\Notifier::classLabel($id);
        $__newProf = (int) $request->input('professor_id');

        if ($__oldProf && (int) $__oldProf !== $__newProf) {
            \App\Support\Notifier::toEmployee($__oldProf, 'schedule_removed', 'Class reassigned',
                "You are no longer assigned to {$__label}.", \App\Support\Notifier::url('professor.manage-classes'));
            \App\Support\Notifier::toEmployee($__newProf, 'schedule_assigned', 'New class assigned',
                "You were assigned to {$__label}: {$__newWhen}.", \App\Support\Notifier::url('professor.manage-classes'));
        } elseif ($__changed) {
            \App\Support\Notifier::toEmployee($__newProf, 'schedule_updated', 'Class schedule updated',
                "{$__label} was changed. Now: {$__newWhen}.", \App\Support\Notifier::url('professor.manage-classes'));
        }
        if ($__changed) {
            \App\Support\Notifier::toScheduleStudents($id, 'schedule_updated', 'Class schedule updated',
                "{$__label} was changed. Now: {$__newWhen}.", \App\Support\Notifier::url('student.my_classes'));
        }

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
        // capture who is affected BEFORE the rows are deleted
        $__label    = \App\Support\Notifier::classLabel($id);
        $__profId   = \App\Support\Notifier::scheduleProfessorId($id);
        $__students = DB::table('student_subjects')->where('schedule_id', $id)->where('status', 'Enrolled')->pluck('student_number');

        DB::table('student_subjects')->where('schedule_id', $id)->delete();
        DB::table('class_schedules')->where('id', $id)->delete();

        // NOTIFICATIONS: professor + enrolled students
        \App\Support\Notifier::toEmployee($__profId, 'schedule_removed', 'Class schedule removed',
            "{$__label} was removed from your schedule.", \App\Support\Notifier::url('professor.manage-classes'));
        \App\Support\Notifier::toStudents($__students, 'schedule_removed', 'Class schedule removed',
            "{$__label} was removed from the schedule. Please check with the Department Secretary.", \App\Support\Notifier::url('student.my_classes'));

        return redirect()->back()->with('success', 'Class deployment record successfully deleted!');
    }
}
