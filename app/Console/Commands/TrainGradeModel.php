<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Support\GradeHelper;

class TrainGradeModel extends Command
{
    protected $signature = 'grades:train-model';
    protected $description = 'Train the linear regression models for grade prediction';

    public function handle()
    {
        $activePeriod = DB::table('academic_periods')->where('is_active', 1)->first();

        if (!$activePeriod) {
            $this->error('No active academic period found.');
            return;
        }

        $this->info("The Model is being trained for {$activePeriod->school_year} ({$activePeriod->semester})");

        $result = GradeHelper::trainModels($activePeriod->id);

        if (!$result['success']) {
            $this->warn($result['message']);
            return;
        }

        foreach ($result['results'] as $stage => $info) {
            $this->info("Stage {$stage}: {$info['sample_size']} samples, R² = {$info['r_squared']}");
        }

        $this->info('The training is complete!');
    }
}