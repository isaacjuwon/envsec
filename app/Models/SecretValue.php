<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecretValue extends Model
{
    protected $fillable = [
        'secret_id',
        'environment',
        'value',
    ];

    protected $casts = [
        'environment' => \App\Enums\Environment::class,
    ];

    public function secret()
    {
        return $this->belongsTo(Secret::class);
    }

    public function history()
    {
        return $this->hasMany(SecretValueHistory::class);
    }
}
