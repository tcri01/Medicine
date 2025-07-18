<?php

namespace App\Services;


use App\Enums\Medicine\AppearanceKeyEnum;
use Generator;
use Illuminate\Support\Collection;

class GovService
{
    public function __construct(
        // 藥品資料外觀
        public string $drugVisualUrl = 'https://data.fda.gov.tw/opendata/exportDataList.do?method=ExportData&InfoId=42&logType=5',
        public string $drugAllLicense = 'https://data.fda.gov.tw/opendata/exportDataList.do?method=ExportData&InfoId=36&logType=2',
        // 未註銷藥品許可證資料集 csv
        // public string $drugEnableLicense = 'https://data.fda.gov.tw/opendata/exportDataList.do?method=ExportData&InfoId=37&logType=2',
    ) {}

    /**
     * 取得政府藥品外觀
     */
    public function getDrugVisualData(): Collection
    {
        $fileService = new FileService();

        $file = $fileService->downloadUrlFile($this->drugVisualUrl, 'tmp.zip');
        $data = $fileService->unzipFile($file)
            ->map(fn($i) => json_decode($i->getContent(), true))
            ->first();

        $appearanceMap = AppearanceKeyEnum::translationMap(); // 中文轉英文Map

        // 中文 key 換成 Enum 指定英文 key
        return collect($data)
            ->map(function ($i) use ($appearanceMap) {
                $result = [];
                foreach ($i as $k => $v) {
                    $result[$appearanceMap[$k] ?? $k] = $v;
                }
                return $result;
            });
    }

    /**
     * 取得全部許可證資料 csv
     * @return Generator 逐行提取
     */
    public function getDrugAllLicense(): Generator
    {
        $fileService = new FileService();

        $file = $fileService->downloadUrlFile($this->drugAllLicense, 'tmp.zip');

        $uploadedFile = $fileService->unzipFile($file)
            ->first();
        try {
            $fs = fopen($uploadedFile->getRealPath(), 'r');

            // csv header
            $header = fgetcsv($fs);
            $header = array_map(fn($value) => str_replace("\u{FEFF}", '', $value), $header); // 去除BOM

            // 整理資料
            while (($row = fgetcsv($fs)) !== false) {
                $row = array_combine($header, $row);
                yield [
                    'chinese_name' => $row['中文品名'],
                    'english_name' => $row['英文品名'],
                    'license_number' => $row['許可證字號'],
                    'drug_type' => $row['藥品類別'],
                    'status' => $row['註銷狀態'],
                ];
            }
        } finally {
            fclose($fs);
        }
    }
}
