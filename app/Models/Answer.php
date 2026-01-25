<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    protected $guarded = [];

    public function entry()
    {
        return $this->belongsTo(Entry::class);
    }

    public function question()
    {
        return $this->belongsTo(Questions::class);
    }
}
