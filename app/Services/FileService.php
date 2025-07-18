<?php

namespace App\Services;

use App\Jobs\File\CompressionFileJob;
use App\Jobs\File\ResizeFileJob;
use App\Models\File;
use Illuminate\Http\{
    JsonResponse,
    Request,
    UploadedFile
};
use FilesystemIterator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\{
    DB,
    Log,
    Storage,
    URL
};
use Greattree\Response\Laravel\Support\Facades\Response;
use Pion\Laravel\ChunkUpload\Handler\HandlerFactory;
use Pion\Laravel\ChunkUpload\Receiver\FileReceiver;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Intervention\Image\ImageManager;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;
use ZipArchive;

/**
 * 檔案 service
 */
class FileService
{
    protected int $maxFileInDir; // 資料夾可容許之最大量
    protected array $tmpfileArray; // 暫存 tmpfile 檔案 使其不消失

    public int $createdUid;
    protected string $filePath;
    protected string $fileDisk;
    protected $meta;

    public function __construct()
    {
        $this->fileDisk = env('FILESERVICE_DEFAULT_FILE', 'public');
        $this->maxFileInDir = env('FILESERVICE_MAX_FILE_IN_DIR', 1000);
        $this->tmpfileArray = [];
        $this->createdUid = request()->user()->id ?? 0;
    }

    public function isExist(File $file)
    {
        return Storage::disk($file->disk)->exists($file->path . $file->basename);
    }

    public function delete(File $file)
    {
        Storage::disk($file->disk)->delete($file->path . $file->basename);
    }

    /**
     * model 關聯檔案(單檔或多檔)
     * @param Model $model 有繼承Trait hasFiles
     * @param array<UploadedFile>|UploadedFile $files|$file
     * @param integer $createdUid 預設0
     * @return Collection
     */
    public function putFiles($model, array|UploadedFile $files): Collection
    {
        // 皆存入陣列處理
        if ($files instanceof UploadedFile) {
            $files = [$files];
        } elseif (is_array($files)) {
            foreach ($files as $file) {
                if (! $file instanceof UploadedFile) {
                    throw new InvalidParameterException('array content must be uploadedFile');
                }
            }
        }

        $files = $this->setFileDetail($files);

        return DB::transaction(function () use ($model, $files) {

            try {
                $result = collect();
                $files->each(function ($file) use ($model, $result) {
                    Storage::disk($file['disk'])->putFile(
                        $file['path'],
                        $file['uploadedFile'],
                    );
                    // 繼承 hasFiles 的model物件開始關聯
                    $file = $model->putFile($file);
                    $result->add($file);
                });


                return $result;
            } catch (\Exception $e) {
                $files->map(function ($file) {
                    if ($this->isExist($file)) {
                        $this->delete($file);
                    }
                });
                // 拋出失敗交由外層處理
                throw $e;
            }
        });
    }

    /**
     * 將網址檔案下載至暫存供後續處理(service消滅暫存會消滅)
     * @param string $url
     * @param string filename = null
     * @return UploadedFile 暫存檔
     */
    public function downloadUrlFile(string $url, $filename = null): UploadedFile
    {
        if (!URL::isValidUrl($url)) {
            throw new \InvalidArgumentException('not valid url');
        }
        $filename = $filename ?? basename($url);
        $data = file_get_contents($url);
        $tmpfile = tmpfile();
        fwrite($tmpfile, $data);

        $this->tmpfileArray[] = $tmpfile; // 暫時儲存

        return new UploadedFile(
            stream_get_meta_data($tmpfile)['uri'],
            $filename,
            finfo_file(finfo_open(FILEINFO_MIME_TYPE), stream_get_meta_data($tmpfile)['uri'])
        );
    }

    /**
     * 返回解壓檔案清單
     * @param UploadedFile $uploadedFile 目標檔案
     * @param string $targetPath = null
     * @return Collection<UploadedFile> 解壓暫存檔清單
     */
    public function unzipFile(UploadedFile $uploadedFile): Collection
    {
        $dir = sys_get_temp_dir() . '/' .  uniqid('tmp_') . '/';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true); // 創建目錄
        }

        // 結束時清除暫存資料夾及其檔案
        register_shutdown_function(function () use ($dir) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($files as $fileInfo) {
                $todo = $fileInfo->isDir() ? 'rmdir' : 'unlink';
                $todo($fileInfo->getRealPath());
            }
            rmdir($dir);
        });

        // 解壓縮
        $zip = new ZipArchive;
        if ($zip->open($uploadedFile->getPathname()) === TRUE) {
            $zip->extractTo($dir); // 解壓到指定目錄

            // 取得解壓後的文件列表
            $extractedFiles = collect();
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $extractedFiles->add(new UploadedFile(
                    $dir . $zip->getNameIndex($i),
                    $zip->getNameIndex($i),
                    finfo_file(finfo_open(FILEINFO_MIME_TYPE), $dir . $zip->getNameIndex($i))
                ));
            }

            $zip->close();

            return $extractedFiles;
        } else {
            throw new \Exception('無法打開壓縮檔');
        }
    }

    /**
     * 取得 service 設定 disk
     * @return string $fileDisk
     */
    public function getFileDisk()
    {
        return $this->fileDisk;
    }

    /**
     * 設定 disk
     * @param string $fileDisk
     */
    public function setFileDisk(string $fileDisk)
    {
        if (!array_key_exists($fileDisk, config('filesystems.disks'))) {
            throw new \InvalidArgumentException('filesystems not this disk. please check param');
        }

        $this->fileDisk = $fileDisk;
    }

    /**
     * 取得檔案basepath 存入數字資料夾中，滿了就換下一個
     * @return string path
     */
    public function getFilePath(): string
    {
        $basepath = $this->filePath ?? '';
        // 有數字資料夾嗎? 沒有就新增 : 有就找最大的那個
        $numberDir = array_filter(Storage::disk($this->getFileDisk())->directories($basepath), function ($dir) {
            $dirname = basename($dir);
            return is_numeric($dirname) && ctype_digit($dirname);
        });

        if (empty($numberDir)) {
            // 如果沒有數字資料夾，創建第一個（1）
            $newDirNumber = 1;
        } else {
            // 取得最新的資料夾（最大數字）
            $currentDir = max(array_map('basename', $numberDir));
            // 檢查當前資料夾的檔案數量
            $filesCount = count(Storage::disk($this->getFileDisk())->files($basepath . '/' . $currentDir));

            if ($filesCount >= $this->maxFileInDir) {
                // 如果資料夾滿了，建立新的資料夾
                $newDirNumber = $currentDir + 1;
            } else {
                // 未滿，繼續使用當前資料夾
                $newDirNumber = $currentDir;
            }
        }

        return rtrim($basepath, '/') . '/' . $newDirNumber . '/';
    }

    /**
     * 設定檔案前路徑
     * @param string $filePath
     */
    public function setFilePath(string $filePath)
    {
        $this->filePath = rtrim($filePath, '/') . '/';
    }

    /**
     * 設定檔案 meta
     * @param array $meta
     */
    public function setMeta(array $meta)
    {
        $this->meta = array_merge($this->meta, $meta);
    }

    /**
     * @return array
     */
    public function getMeta()
    {
        return $this->meta ?? [];
    }

    /**
     * 取得檔案基本設定的array
     * @param array<UploadedFile> $uploadedFiles
     * @return Collection 檔案資料
     */
    public function setFileDetail(array $uploadedFiles): Collection
    {
        return collect($uploadedFiles)
            ->map(function ($uploadFile) {
                return [
                    'name' => pathinfo($uploadFile->getClientOriginalName(), PATHINFO_FILENAME),
                    'basename' => $uploadFile->hashName(),
                    'disk' => $this->getFileDisk(),
                    'extension' => $uploadFile->clientExtension(),
                    'mime_type' => $uploadFile->getClientMimeType(),
                    'size' => $uploadFile->getSize(),
                    'path' => $this->getFilePath(),
                    'created_uid' => $this->createdUid,
                    'uploadedFile' => $uploadFile,
                    'meta' => $this->getMeta(),
                ];
            });
    }

    public function setCreatedUid(int $createdUid)
    {
        $this->createdUid = $createdUid;
    }

    public function uploadPublicFile($model, array|UploadedFile $files)
    {
        $this->setFileDisk('public');
        $this->putFiles($model, $files);
    }

    public function putUrlFiles($model, array $urls, string $disk = 'public')
    {
        $this->setFileDisk($disk);

        $files = [];
        foreach ($urls as $url) {
            $files[] = $this->downloadUrlFile($url);
        }

        $this->putFiles($model, $files);
    }
}
