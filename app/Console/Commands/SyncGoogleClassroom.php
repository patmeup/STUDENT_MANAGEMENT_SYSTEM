<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Support\GoogleClassroomService;

class SyncGoogleClassroom extends Command
{
    protected $signature = 'classroom:sync';
    protected $description = 'Sync Google Classroom announcements and coursework for all connected professors';

    public function handle()
    {
        $connections = DB::table('google_classroom_connections')->get();

        foreach ($connections as $connection) {
            try {
                $service = new GoogleClassroomService($connection);
                $service->syncCourses();

                DB::table('google_classroom_connections')
                    ->where('id', $connection->id)
                    ->update(['last_synced_at' => now()]);

                $this->info("Synced: {$connection->google_email}");
            } catch (\Exception $e) {
                $this->error("Failed for {$connection->google_email}: " . $e->getMessage());
            }
        }
    }
}