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

    public function proyecto()
    {
        return $this->hasOne(Answer::class)
            ->whereHas('question')
            ->with('question.section'); // para que cargue también la pregunta
    }

    public function getCalificacionTotal()
    {
        $total = 0;
        
        foreach ($this->answers as $respuesta) {
            if (!blank($respuesta->value) && is_numeric($respuesta->value)) {
                $total += (float) $respuesta->value;
            }
        }

        $preguntasSubForm = Questions::where('type', 'sub_form')->pluck('id');

        $enlacesAHijos = $this->answers->whereIn('question_id', $preguntasSubForm);

        foreach ($enlacesAHijos as $enlace) {
            if (!blank($enlace->value) && is_numeric($enlace->value)) {
                $hijo = self::find($enlace->value);
                if ($hijo) {
                    
                    $total += $hijo->getCalificacionTotal();
                }
            }
        }

        return $total;
    }
}
