<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Support\GradeHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TrainPredictiveModels extends Command
{

    protected $signature = 'ml:train';

    protected $description = 'Train ML predictive models using historical student grade data';

    public function handle()
    {
        $activePeriod = DB::table('academic_periods')->where('is_active', 1)->first();

        if (!$activePeriod) {
            $this->error('No active academic period found in database!');
            return 1;
        }

        $this->info("Starting ML Model Training for Academic Period ID: {$activePeriod->id} ({$activePeriod->school_year} - {$activePeriod->semester})...");

        try {
            GradeHelper::trainModels($activePeriod->id);
            $this->info('ML Predictive Models trained and updated successfully!');
            return 0;
        } catch (\Exception $e) {
            $this->error('ML Training Failed: ' . $e->getMessage());
            Log::error('ML Command Error: ' . $e->getMessage());
            return 1;
        }
    }
}