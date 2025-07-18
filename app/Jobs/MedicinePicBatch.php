<?php

namespace App\Jobs;

use App\Models\Medicine\Medicine;
use App\Services\FileService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MedicinePicBatch implements ShouldQueue
{
    use Queueable, InteractsWithQueue, Dispatchable, SerializesModels;

    public FileService $fileService;
    public string $license_number;
    public string $url;

    /**
     * @param string $license_number
     * @param string $url
     * @param array $meta 增加註記
     */
    public function __construct(string $license_number, string $url, public array $meta = [])
    {
        $this->fileService = new FileService;
        $this->license_number = $license_number;
        $this->url = $url;
    }

    public function handle(): void
    {
        $name = md5(basename($this->url));
        // 該相片類別

        $entity = Medicine::where('license_number', $this->license_number)->firstOrFail();

        if (!$entity->toFiles()->where('name', $name)->exists()) {
            $file = $this->fileService->downloadUrlFile($this->url, $name);
            $this->fileService->putFiles($entity, $file);
            Log::info("MedicinePicBatch: {$this->license_number} {$this->url}");
        }
    }
}
