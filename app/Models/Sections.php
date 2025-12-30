<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Question;
use App\Models\Categorias;
use App\Models\Entry;
use Illuminate\Support\Facades\Auth;

class Sections extends Model
{
    use SoftDeletes;

    protected $guarded = [];
    
    public function categorias()

    {
        return $this->belongsTo(categorias::class, 'categoria_id');
    }

    public function questions()
    {
        return $this->hasMany(Question::class, 'section_id')->orderBy('sort_order');
    }
    public function getUserEntriesAttribute()
    {
        return Entry::query()
            ->where('user_id', Auth::id())
            ->whereHas('answers.question', function ($q) {
                $q->where('section_id', $this->id);
            })
            ->with('answers.question') // Eager loading para optimizar
            ->get();
    }
}
