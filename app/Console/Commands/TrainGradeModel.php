<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Support\PredictiveGradeHelper;

class TrainGradeModel extends Command
{
    protected $signature = 'grades:train-model';
    protected $description = 'I-train ang linear regression models para sa grade prediction';

    public function handle()
    {
        $activePeriod = DB::table('academic_periods')->where('is_active', 1)->first();

        if (!$activePeriod) {
            $this->error('Walang active academic period.');
            return;
        }

        $this->info("Nagsisimula ang training para sa: {$activePeriod->school_year} ({$activePeriod->semester})");

        $result = PredictiveGradeHelper::trainModels($activePeriod->id);

        if (!$result['success']) {
            $this->warn($result['message']);
            return;
        }

        foreach ($result['results'] as $stage => $info) {
            $this->info("Stage {$stage}: {$info['sample_size']} samples, R² = {$info['r_squared']}");
        }

        $this->info('Tapos na ang training!');
    }
}