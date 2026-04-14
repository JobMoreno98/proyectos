<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;
    use HasRoles;
    protected $guard_name = 'web';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'datos_json' => 'array',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    // App/Models/User.php

    public function datosGenerales()
    {
        return $this->hasOne(ViewDatosGenerales::class, 'user_id');
    }

    public function hasUpdatedProfileThisYear(): bool
    {
        return $this->datosGenerales && $this->datosGenerales->fecha_registro->isCurrentYear();
    }
    public function getNombreAttribute()
    {
        $datos = $this->datosGenerales->datos_json;

        $datos = array_map(function ($valor) {
            // Intenta decodificar el valor como JSON
            $decoded = json_decode($valor, true);

            // Si se pudo decodificar y no es null, usa ese valor
            if ($decoded !== null) {
                return $decoded;
            }

            // Si no, elimina comillas sobrantes y devuelve el valor original
            return trim($valor, '"');
        }, $datos);


        $keys =  array_map(['App\Models\User', 'slugify'], array_keys($datos));

        $datos_slug = array_combine($keys, $datos);
        //dd($datos_slug);

        return (trim($datos_slug['nombres'], '"') ?? '') . ' ' . (trim($datos_slug['apellido-paterno'], '"') ?? '') . ' ' . (trim($datos_slug['apellido-materno'], '"') ?? '');
    }

    private static function slugify($text)
    {
        $text = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ', 'Á', 'É', 'Í', 'Ó', 'Ú', 'Ü', 'Ñ'],
            ['a', 'e', 'i', 'o', 'u', 'u', 'n', 'a', 'e', 'i', 'o', 'u', 'u', 'n'],
            $text
        );

        // Pasar a minúsculas
        $text = strtolower($text);

        // Reemplazar cualquier cosa que no sea letras/números por guiones
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);

        // Quitar guiones al inicio/fin
        return trim($text, '-');
    }
}
