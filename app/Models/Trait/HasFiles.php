<?php

namespace App\Models\Trait;

use App\Models\File;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasFiles
{
    /**
     * 當關聯檔案刪除時，一併刪除儲存檔案
     */
    protected static function bootHasFiles()
    {
        static::deleting(function ($model) {
            // 獲取所有關聯檔案
            $model->toFiles->each(function ($file) {
                // model實例化刪除 確保觸發 File boot::deleting
                $file->delete();
            });
        });
    }

    public function toFiles(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable')->withTrashed()->orderBy('created_at', 'desc');
    }

    /**
     * 上傳檔案關聯至model
     * @param array $file
     * @return File
     */
    public function putFile(array $file): File
    {
        return $this->toFiles()->create([
            'name' => $file['name'],
            'basename' => $file['basename'],
            'disk' => $file['disk'],
            'extension' => $file['extension'],
            'mime_type' => $file['mime_type'],
            'size' => $file['size'],
            'path' => $file['path'],
            'created_uid' => $file['created_uid'],
            'meta' => $file['meta'],
        ]);
    }
}
