<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    use HasFactory;

    protected $fillable = ['kejuruan_id', 'nama', 'kode_program'];

    public function kejuruan()
    {
        return $this->belongsTo(Kejuruan::class);
    }
}
