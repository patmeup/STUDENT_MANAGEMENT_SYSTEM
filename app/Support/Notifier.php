<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Central in-app notification helper.
 * Every method is wrapped so a notification failure can NEVER break the real action.
 * Works without a logged-in session (Google sync, artisan commands).
 */
class Notifier
{
    /** Relative URL for a named route, or null if it doesn't exist. */
    public static function url(string $name, array $params = []): ?string
    {
        try {
            return route($name, $params, false);
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected static function insertRows(array $rows): void
    {
        if (empty($rows)) {
            return;
        }
        try {
            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('notifications')->insert($chunk);
            }
        } catch (\Throwable $e) {
            Log::warning('Notification insert failed: ' . $e->getMessage());
        }
    }

    protected static function row(string $rtype, $rid, string $type, string $title, string $message, ?string $url): array
    {
        return [
            'recipient_type' => $rtype,
            'recipient_id'   => (string) $rid,
            'type'           => mb_substr($type, 0, 60),
            'title'          => mb_substr($title, 0, 255),
            'message'        => $message,
            'url'            => $url,
            'read_at'        => null,
            'created_at'     => now(),
            'updated_at'     => now(),
        ];
    }

    public static function toEmployee($employeeId, string $type, string $title, string $message, ?string $url = null): void
    {
        if (!$employeeId) {
            return;
        }
        self::insertRows([self::row('employee', $employeeId, $type, $title, $message, $url)]);
    }

    public static function toStudent($studentId, string $type, string $title, string $message, ?string $url = null): void
    {
        if (!$studentId) {
            return;
        }
        self::insertRows([self::row('student', $studentId, $type, $title, $message, $url)]);
    }

    public static function toStudents($studentIds, string $type, string $title, string $message, ?string $url = null): void
    {
        try {
            $rows = [];
            foreach (collect($studentIds)->filter()->unique() as $sid) {
                $rows[] = self::row('student', $sid, $type, $title, $message, $url);
            }
            self::insertRows($rows);
        } catch (\Throwable $e) {
            Log::warning('Notifier::toStudents failed: ' . $e->getMessage());
        }
    }

    /**
     * Notify all ACTIVE employees with the given role(s).
     * $url may be a string, or an array keyed by role (['dept_sec' => '/x', 'super_admin' => '/y']).
     */
    public static function toRole($roles, string $type, string $title, string $message, $url = null, ?string $department = null): void
    {
        try {
            $roles = (array) $roles;
            $q = DB::table('employees')->whereIn('role', $roles)->where('status', 'Active');
            if ($department) {
                $q->whereRaw('LOWER(department) = ?', [strtolower($department)]);
            }
            $rows = [];
            foreach ($q->get(['id', 'role']) as $emp) {
                $u = is_array($url) ? ($url[$emp->role] ?? null) : $url;
                $rows[] = self::row('employee', $emp->id, $type, $title, $message, $u);
            }
            self::insertRows($rows);
        } catch (\Throwable $e) {
            Log::warning('Notifier::toRole failed: ' . $e->getMessage());
        }
    }

    /** Everyone: all active employees + all students. */
    public static function toAll(string $type, string $title, string $message, ?string $url = null): void
    {
        try {
            $rows = [];
            foreach (DB::table('employees')->where('status', 'Active')->pluck('id') as $id) {
                $rows[] = self::row('employee', $id, $type, $title, $message, $url);
            }
            foreach (DB::table('students')->pluck('student_id') as $sid) {
                $rows[] = self::row('student', $sid, $type, $title, $message, $url);
            }
            self::insertRows($rows);
        } catch (\Throwable $e) {
            Log::warning('Notifier::toAll failed: ' . $e->getMessage());
        }
    }

    /** Every student currently Enrolled in a class schedule. */
    public static function toScheduleStudents($scheduleId, string $type, string $title, string $message, ?string $url = null): void
    {
        try {
            $ids = DB::table('student_subjects')
                ->where('schedule_id', $scheduleId)
                ->where('status', 'Enrolled')
                ->pluck('student_number');
            self::toStudents($ids, $type, $title, $message, $url);
        } catch (\Throwable $e) {
            Log::warning('Notifier::toScheduleStudents failed: ' . $e->getMessage());
        }
    }

    /** class_schedules.professor_id, falling back to subjects.professor_id. */
    public static function scheduleProfessorId($scheduleId): ?int
    {
        try {
            $r = DB::table('class_schedules')
                ->join('subjects', 'class_schedules.subject_id', '=', 'subjects.id')
                ->where('class_schedules.id', $scheduleId)
                ->select('class_schedules.professor_id as sp', 'subjects.professor_id as subp')
                ->first();
            return $r ? ($r->sp ?: $r->subp ?: null) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Dean(s) of the professor's department (falls back to all deans). */
    public static function toDeanOfProfessor($professorId, string $type, string $title, string $message, ?string $url = null): void
    {
        try {
            $dept = $professorId ? DB::table('employees')->where('id', $professorId)->value('department') : null;
            $q = DB::table('employees')->where('role', 'dean')->where('status', 'Active');
            if ($dept) {
                $matched = (clone $q)->whereRaw('LOWER(department) = ?', [strtolower($dept)])->pluck('id');
                $ids = $matched->isNotEmpty() ? $matched : $q->pluck('id');
            } else {
                $ids = $q->pluck('id');
            }
            $rows = [];
            foreach ($ids as $id) {
                $rows[] = self::row('employee', $id, $type, $title, $message, $url);
            }
            self::insertRows($rows);
        } catch (\Throwable $e) {
            Log::warning('Notifier::toDeanOfProfessor failed: ' . $e->getMessage());
        }
    }

    /** "IT 111 LEC (BSIT1-A)" */
    public static function classLabel($scheduleId): string
    {
        try {
            $c = DB::table('class_schedules')
                ->join('subjects', 'class_schedules.subject_id', '=', 'subjects.id')
                ->where('class_schedules.id', $scheduleId)
                ->select('subjects.subject_code', 'class_schedules.section_name', 'class_schedules.schedule_type')
                ->first();
            return $c ? "{$c->subject_code} {$c->schedule_type} ({$c->section_name})" : "Class #{$scheduleId}";
        } catch (\Throwable $e) {
            return "Class #{$scheduleId}";
        }
    }

    /** document_name may be plain text or a JSON-encoded array. */
    public static function docLabel($raw): string
    {
        $d = is_string($raw) ? json_decode($raw, true) : $raw;
        return is_array($d) ? implode(', ', $d) : (string) $raw;
    }

    public static function roleLabel(?string $role): string
    {
        return [
            'super_admin' => 'Super Admin', 'system_admin' => 'System Admin', 'registrar' => 'Registrar',
            'dean' => 'Dean', 'dept_sec' => 'Department Secretary', 'professor' => 'Professor', 'student' => 'Student',
        ][$role] ?? (string) $role;
    }

    /** Subject / curriculum changes -> Dept. Secretary + Super Admin. */
    public static function catalogChange(string $entity, string $action, string $label, string $deptSecRoute): void
    {
        self::toRole(
            ['dept_sec', 'super_admin'],
            strtolower($entity) . '_' . $action,
            "{$entity} {$action}",
            "{$entity} \"{$label}\" was {$action} by " . (session('user_name') ?? 'System Admin') . '.',
            ['dept_sec' => self::url($deptSecRoute), 'super_admin' => self::url('super_admin.dashboard')]
        );
    }
}
