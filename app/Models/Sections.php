<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Questions;
use App\Models\Categorias;
use Illuminate\Support\Facades\Auth;

class Sections extends Model
{
    use SoftDeletes;
    const EVALUACION = 'Evaluaciones';

    protected $guarded = [];

    protected $casts = [
        'investigacion' => 'boolean',
    ];

    public function categorias()

    {
        return $this->belongsTo(categorias::class, 'categoria_id');
    }

    public function questions()
    {
        return $this->hasMany(Questions::class, 'section_id')->orderBy('sort_order');
    }

    public function getUserEntriesAttribute()
    {
        return (AnswerFullView::where('user_id', Auth::user()->id)->where('section_title', $this->title)
            ->groupBy('entry_id')
            ->orderBy('fecha_creado')
            ->get());

        return \App\Models\Entry::query()
            ->where('user_id', Auth::id())
            ->whereHas('answers.question', function ($q) {
                $q->where('section_id', $this->id);
            })
            ->with('answers.question')
            ->get();
    }

    public static function idEvaluacion()
    {
        return self::select('id')->where('title', self::EVALUACION)->first()->id;
    }

    public function getAllEntriesAttribute()
    {
        return (AnswerFullView::where('section_title', $this->title)
            ->groupBy('entry_id')
            ->orderBy('fecha_creado')
            ->get());
    }

}
