<?php

namespace App\Support;

use Google\Client as GoogleClient;
use Google\Service\Classroom;
use Google\Service\Drive;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

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
            DB::table('google_classroom_courses')->updateOrInsert(
                ['google_course_id' => $course->getId()],
                [
                    'connection_id' => $this->connection->id,
                    'course_name' => $course->getName(),
                    'course_section' => $course->getSection(),
                    'updated_at' => now(),
                    'created_at' => DB::raw("COALESCE(created_at, '" . now() . "')"),
                ]
            );

            $localCourse = DB::table('google_classroom_courses')
                ->where('google_course_id', $course->getId())
                ->first();

            $this->syncAnnouncements($service, $course->getId(), $localCourse->id);
            $this->syncCourseWork($service, $course->getId(), $localCourse->id);
            $this->syncCourseWorkMaterials($service, $course->getId(), $localCourse->id);
        }
    }

    protected function syncAnnouncements($service, $googleCourseId, $localCourseId)
    {
        $announcements = $service->courses_announcements->listCoursesAnnouncements($googleCourseId);
        $seenIds = [];

        foreach ($announcements->getAnnouncements() ?? [] as $a) {
            $seenIds[] = $a->getId();

            $postedAt = $a->getCreationTime() 
                ? Carbon::parse($a->getCreationTime())->format('Y-m-d H:i:s') 
                : null;

            DB::table('google_classroom_announcements')->updateOrInsert(
                ['google_announcement_id' => $a->getId()],
                [
                    'course_id' => $localCourseId,
                    'text' => $a->getText(),
                    'posted_at' => $postedAt,
                    'updated_at' => now(),
                    'created_at' => DB::raw("COALESCE(created_at, '" . now() . "')"),
                ]
            );
        }

        DB::table('google_classroom_announcements')
            ->where('course_id', $localCourseId)
            ->whereNotIn('google_announcement_id', $seenIds)
            ->delete();
    }

    protected function syncCourseWork($service, $googleCourseId, $localCourseId)
    {
        $courseWork = $service->courses_courseWork->listCoursesCourseWork($googleCourseId);
        $seenIds = [];

        foreach ($courseWork->getCourseWork() ?? [] as $cw) {
            $seenIds[] = $cw->getId();

            $dueDate = null;
            if ($cw->getDueDate()) {
                $d = $cw->getDueDate();
                $dueDate = sprintf('%04d-%02d-%02d', $d->getYear(), $d->getMonth(), $d->getDay());
            }

            $postedAt = $cw->getCreationTime() 
                ? Carbon::parse($cw->getCreationTime())->format('Y-m-d H:i:s') 
                : null;

            DB::table('google_classroom_coursework')->updateOrInsert(
                ['google_coursework_id' => $cw->getId()],
                [
                    'course_id' => $localCourseId,
                    'title' => $cw->getTitle(),
                    'description' => $cw->getDescription(),
                    'work_type' => $cw->getWorkType(),
                    'due_date' => $dueDate,
                    'posted_at' => $postedAt,
                    'alternate_link' => $cw->getAlternateLink(),
                    'updated_at' => now(),
                    'created_at' => DB::raw("COALESCE(created_at, '" . now() . "')"),
                ]
            );
        }

        DB::table('google_classroom_coursework')
            ->where('course_id', $localCourseId)
            ->whereNotIn('google_coursework_id', $seenIds)
            ->delete();
    }

    protected function syncCourseWorkMaterials($service, $googleCourseId, $localCourseId)
    {
        $materials = $service->courses_courseWorkMaterials->listCoursesCourseWorkMaterials($googleCourseId);
        $seenIds = [];

        foreach ($materials->getCourseWorkMaterial() ?? [] as $mat) {
            $seenIds[] = $mat->getId();

            $driveFileId = null;
            $driveFileName = null;

            foreach ($mat->getMaterials() ?? [] as $item) {
                $sharedDriveFile = $item->getDriveFile();
                if ($sharedDriveFile && $sharedDriveFile->getDriveFile()) {
                    $driveFileId = $sharedDriveFile->getDriveFile()->getId();
                    $driveFileName = $sharedDriveFile->getDriveFile()->getTitle();
                    break;
                }
            }

            $postedAt = $mat->getCreationTime() 
                ? Carbon::parse($mat->getCreationTime())->format('Y-m-d H:i:s') 
                : null;

            DB::table('google_classroom_materials')->updateOrInsert(
                ['google_material_id' => $mat->getId()],
                [
                    'course_id' => $localCourseId,
                    'title' => $mat->getTitle(),
                    'description' => $mat->getDescription(),
                    'posted_at' => $postedAt,
                    'alternate_link' => $mat->getAlternateLink(),
                    'drive_file_id' => $driveFileId,
                    'drive_file_name' => $driveFileName,
                    'updated_at' => now(),
                    'created_at' => DB::raw("COALESCE(created_at, '" . now() . "')"),
                ]
            );
        }

        DB::table('google_classroom_materials')
            ->where('course_id', $localCourseId)
            ->whereNotIn('google_material_id', $seenIds)
            ->delete();
    }

    public function downloadDriveFile(string $driveFileId): array
    {
        $drive = new Drive($this->client);

        $meta = $drive->files->get($driveFileId, ['fields' => 'name,mimeType']);
        $mimeType = $meta->getMimeType();
        $name = $meta->getName();

        $googleNativeExportMap = [
            'application/vnd.google-apps.document'     => ['application/pdf', '.pdf'],
            'application/vnd.google-apps.presentation'  => ['application/pdf', '.pdf'],
            'application/vnd.google-apps.spreadsheet'   => ['application/pdf', '.pdf'],
        ];

        if (isset($googleNativeExportMap[$mimeType])) {
            [$exportMime, $ext] = $googleNativeExportMap[$mimeType];
            $response = $drive->files->export($driveFileId, $exportMime, ['alt' => 'media']);

            if (!Str::endsWith(strtolower($name), $ext)) {
                $name .= $ext;
            }

            return [
                'content'  => $response->getBody()->getContents(),
                'name'     => $name,
                'mimeType' => $exportMime,
            ];
        }

        $response = $drive->files->get($driveFileId, ['alt' => 'media']);

        return [
            'content'  => $response->getBody()->getContents(),
            'name'     => $name,
            'mimeType' => $mimeType,
        ];
    }
}