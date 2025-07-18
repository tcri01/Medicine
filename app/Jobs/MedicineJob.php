<?php

namespace App\Jobs;

use App\Models\Medicine\Appearance;
use App\Models\Medicine\Medicine;
use App\Services\GovService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class MedicineJob implements ShouldQueue
{
    use Queueable;

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
    public function handle(): void
    {
        Log::debug('更新圖片資料 開始');
        // 藥品資料
        $govData = Cache::remember('gov_drug_batch_data', 60 * 60 * 3, function () {
            return (new GovService())->getDrugVisualData();
        });

        // 大資料先鍵入
        Medicine::upsert(
            $govData->map(fn($row) => [
                'license_number' => $row['license_number'],
                'chinese_name' => $row['chinese_name'],
                'english_name' => $row['english_name'],
            ])->toArray(),
            ['license_number'],
            ['chinese_name', 'english_name']
        );

        // 大資料鍵入 appearance
        Appearance::upsert(
            $govData->reduce(function ($carry, $row) {
                unset($row['chinese_name']);
                unset($row['english_name']);
                unset($row['license_number']);
                unset($row['image_link']);

                foreach ($row as $key => $value) {
                    // 分裂 ;;; 的資料
                    if (!empty($value)) {
                        foreach (explode(';;;', $value) as $splitValue) {
                            $checkKey = "$key|$splitValue";
                            if (!in_array($key, $carry)) {
                                $carry[$checkKey] = [
                                    'attr_key' => $key,
                                    'attr_value' => $splitValue,
                                ];
                            }
                        }
                    }
                }
                return $carry;
            }, []),
            ['attr_key', 'attr_value']
        );


        // 藥品 x 特徵 (syncWithoutDetaching)
        $infoList = Medicine::whereIn('license_number', $govData->pluck('license_number')->toArray())
            ->get()
            ->keyBy('license_number');

        $appearanceList = Appearance::get()
            ->keyBy(fn($appearance) => "{$appearance->attr_key}|{$appearance->attr_value}");

        $govData->map(function ($row) use ($infoList, $appearanceList) {

            // 儲存資料
            $info = $infoList->get($row['license_number']);
            $infoList->forget($row['license_number']);

            // 關聯圖片
            foreach (explode(';;;', $row['image_link']) as $imageUrl) {
                if (URL::isValidUrl($imageUrl)) {
                    MedicinePicBatch::dispatch($info->license_number, $imageUrl, ['type' => 'pill']);
                }
            }

            // 儲存特徵
            unset($row['chinese_name']);
            unset($row['english_name']);
            unset($row['license_number']);
            unset($row['image_link']);
            $row = array_filter($row, fn($value) => !empty($value));

            $appearanceSyncIds = [];

            foreach ($row as $key => $value) {
                // 分裂 ;;; 的資料
                foreach (explode(';;;', $value) as $splitValue) {

                    if ($appearance = $appearanceList->get("{$key}|{$splitValue}")) {
                        $appearanceSyncIds[] = $appearance->id;
                    }
                }
            }

            if (count($appearanceSyncIds) != 0) {
                $info->toAppearance()->syncWithoutDetaching($appearanceSyncIds);
            }
        });

        Log::debug('更新圖片資料 結束');
    }
}
