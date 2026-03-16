<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Secret extends Model
{
    protected $fillable = [
        'project_id',
        'key',
        'description',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function values()
    {
        return $this->hasMany(SecretValue::class);
    }
}
