<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KuesionerGroup extends Model
{
    protected $fillable = ['nama_projek', 'semua_jawaban', 'status', 'hasil_akhir_json'];

    protected $casts = [
        'semua_jawaban' => 'array',
        'hasil_akhir_json' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}