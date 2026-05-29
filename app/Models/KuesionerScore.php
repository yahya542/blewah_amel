<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KuesionerScore extends Model
{
    protected $fillable = ['kuesioner_id', 'varietas', 'kriteria', 'score'];

    public function kuesioner()
    {
        return $this->belongsTo(Kuesioner::class);
    }
}