<?php

namespace App\Models\Medicine;

use App\Models\Trait\HasFiles;
use Illuminate\Database\Eloquent\Model;

/**
 * 藥品資料
 */
class Medicine extends Model
{
    use HasFiles;

    protected $table = 'medicine';

    protected $fillable = [
        'chinese_name',
        'english_name',
        'license_number',
    ];

    /**
     * to特徵
     */
    public function toAppearance()
    {
        return $this->belongsToMany(Appearance::class, 'medicine_appearance_medicine', 'medicine_id', 'medicine_appearance_id');
    }
}
