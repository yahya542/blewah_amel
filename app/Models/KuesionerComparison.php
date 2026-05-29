<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KuesionerComparison extends Model
{
    protected $fillable = ['kuesioner_id', 'kriteria_from', 'kriteria_to', 'value'];

    public function kuesioner()
    {
        return $this->belongsTo(Kuesioner::class);
    }
}