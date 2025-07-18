<?php

namespace App\Models\Medicine;

use Illuminate\Database\Eloquent\Model;

class Appearance extends Model
{
    protected $table = 'medicine_appearances';

    public $timestamps  = false;

    protected $fillable = [
        'attr_key',
        'attr_value',
    ];


    /**
     * to藥品資料
     */
    public function toEntity()
    {
        return $this->belongsToMany(Medicine::class, 'medicine_appearance_medicine', 'medicine_appearance_id', 'medicine_id');
    }
}
