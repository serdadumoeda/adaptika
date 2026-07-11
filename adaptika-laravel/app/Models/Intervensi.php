<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Intervensi extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'peserta_id', 'role', 'catatan'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function peserta()
    {
        return $this->belongsTo(Peserta::class);
    }
}
