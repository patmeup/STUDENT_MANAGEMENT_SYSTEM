<?php

namespace App\Support;

use Google\Client as GoogleClient;
use Google\Service\Classroom;
use Illuminate\Support\Facades\DB;

class GoogleClassroomService
{
    protected $client;
    protected $connection;

    public function __construct($connection)
    {
        $this->connection = $connection;

        $this->client = new GoogleClient();
        $this->client->setClientId(config('services.google.client_id'));
        $this->client->setClientSecret(config('services.google.client_secret'));
        $this->client->setAccessType('offline');

        $this->client->setAccessToken([
            'access_token' => $connection->access_token,
            'refresh_token' => $connection->refresh_token,
            'expires_in' => $connection->token_expires_at
                ? now()->diffInSeconds($connection->token_expires_at, false)
                : 0,
        ]);

        // I-refresh kung expired na
        if ($this->client->isAccessTokenExpired()) {
            $newToken = $this->client->fetchAccessTokenWithRefreshToken($connection->refresh_token);

            if (isset($newToken['access_token'])) {
                DB::table('google_classroom_connections')
                    ->where('id', $connection->id)
                    ->update([
                        'access_token' => $newToken['access_token'],
                        'token_expires_at' => now()->addSeconds($newToken['expires_in'] ?? 3600),
                        'updated_at' => now(),
                    ]);

                $this->client->setAccessToken($newToken);
            }
        }
    }

    public function getService()
    {
        return new Classroom($this->client);
    }

    public function syncCourses()
    {
        $service = $this->getService();
        $courses = $service->courses->listCourses(['teacherId' => 'me', 'courseStates' => ['ACTIVE']]);

        foreach ($courses->getCourses() ?? [] as $course) {
            $courseId = DB::table('google_classroom_courses')->updateOrInsert(
                ['google_course_id' => $course->getId()],
                [
                    'connection_id' => $this->connection->id,
                    'course_name' => $course->getName(),
                    'course_section' => $course->getSection(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            $localCourse = DB::table('google_classroom_courses')
                ->where('google_course_id', $course->getId())
                ->first();

            $this->syncAnnouncements($service, $course->getId(), $localCourse->id);
            $this->syncCourseWork($service, $course->getId(), $localCourse->id);
        }
    }

    protected function syncAnnouncements($service, $googleCourseId, $localCourseId)
    {
        $announcements = $service->courses_announcements->listCoursesAnnouncements($googleCourseId);

        foreach ($announcements->getAnnouncements() ?? [] as $a) {
            DB::table('google_classroom_announcements')->updateOrInsert(
                ['google_announcement_id' => $a->getId()],
                [
                    'course_id' => $localCourseId,
                    'text' => $a->getText(),
                    'posted_at' => $a->getCreationTime(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    protected function syncCourseWork($service, $googleCourseId, $localCourseId)
    {
        $courseWork = $service->courses_courseWork->listCoursesCourseWork($googleCourseId);

        foreach ($courseWork->getCourseWork() ?? [] as $cw) {
            $dueDate = null;
            if ($cw->getDueDate()) {
                $d = $cw->getDueDate();
                $dueDate = sprintf('%04d-%02d-%02d', $d->getYear(), $d->getMonth(), $d->getDay());
            }

            DB::table('google_classroom_coursework')->updateOrInsert(
                ['google_coursework_id' => $cw->getId()],
                [
                    'course_id' => $localCourseId,
                    'title' => $cw->getTitle(),
                    'description' => $cw->getDescription(),
                    'due_date' => $dueDate,
                    'alternate_link' => $cw->getAlternateLink(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}