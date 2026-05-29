<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kuesioner extends Model
{
    protected $fillable = ['nama_responden', 'usia', 'status', 'hasil_json'];

    protected $casts = [
        'hasil_json' => 'array',
    ];

    public function comparisons()
    {
        return $this->hasMany(KuesionerComparison::class);
    }

    public function scores()
    {
        return $this->hasMany(KuesionerScore::class);
    }
}