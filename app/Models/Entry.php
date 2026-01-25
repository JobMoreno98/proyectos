<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entry extends Model
{
    protected $guarded = [];
    use SoftDeletes; // <--- Activa la magia

    protected $dates = ['deleted_at'];

    protected $casts = [
        'is_editable' => 'boolean',
    ];

    public function answers()
    {
        return $this->hasMany(Answer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
