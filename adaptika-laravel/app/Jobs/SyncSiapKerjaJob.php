<?php

namespace App\Jobs;

use App\Services\SiapKerjaApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncSiapKerjaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // Allow up to 10 minutes for sync

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(SiapKerjaApiService $service): void
    {
        Log::info('SyncSiapKerjaJob: Memulai proses sinkronisasi massal dari API SIAP Kerja.');
        
        try {
            $result = $service->syncAll();
            Log::info('SyncSiapKerjaJob: Selesai.', $result);
        } catch (\Exception $e) {
            Log::error('SyncSiapKerjaJob: Gagal mengeksekusi sinkronisasi.', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
