<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class File extends Model
{
    use SoftDeletes;

    protected $table = 'files';

    protected $fillable = [
        'name',
        'basename',
        'disk',
        'mime_type',
        'size',
        'extension',
        'path',
        'created_uid',
        'meta',
    ];

    protected $casts = [
        'meta' => 'json',
    ];

    protected static function booted()
    {
        // 清除本地檔案
        static::deleting(function ($file) {
            // 清除壓縮檔案
            if (Storage::disk($file->disk)->exists($file->path . 'resize-' . $file->basename)) {
                Storage::disk($file->disk)->delete($file->path . 'resize-' . $file->basename);
            }
            if (Storage::disk($file->disk)->exists($file->path . $file->basename)) {
                Storage::disk($file->disk)->delete($file->path . $file->basename);
            }
        });
    }

    /**
     * 返回關聯對象
     */
    public function fileable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * 下載檔案 Storage:download
     */
    public function download()
    {
        /** @var Filesystem|\Illuminate\Filesystem\FilesystemAdapter */
        $storage = Storage::disk($this->disk);

        return $storage->download(
            "{$this->path}{$this->basename}",
            "{$this->name}.{$this->extension}"
        );
    }

    /**
     * 返回Storage:url()
     */
    public function url()
    {
        /** @var Filesystem|\Illuminate\Filesystem\FilesystemAdapter */
        $storage = Storage::disk($this->disk);

        return $storage->url("{$this->path}{$this->basename}");
    }

    /**
     * 取得檔案內容
     */
    public function getContent()
    {
        /** @var Filesystem|\Illuminate\Filesystem\FilesystemAdapter */
        $storage = Storage::disk($this->disk);

        return $storage->get("{$this->path}{$this->basename}");
    }

    /**
     * 配合前端使用，去除domain的圖片路徑
     */
    public function path()
    {
        return parse_url($this->url())['path'] ?? '';
    }

    /**
     * 取得檔案真實路徑
     */
    public function getRealPath()
    {
        /** @var Filesystem|\Illuminate\Filesystem\FilesystemAdapter */
        $storage = Storage::disk($this->disk);
        return $storage->path("{$this->path}{$this->basename}");
    }


    /**
     * 更新meta
     */
    public function updateMeta(array $meta)
    {
        $meta = array_merge($this->meta, $meta);
        $this->meta = $meta;
        $this->save();
    }

    public function toCreatedBy()
    {
        return $this->hasOne(User::class, 'id', 'created_uid');
    }
}
