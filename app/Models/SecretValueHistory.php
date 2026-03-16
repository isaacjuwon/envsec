<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecretValueHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'secret_value_id',
        'value',
        'changed_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function secretValue()
    {
        return $this->belongsTo(SecretValue::class);
    }

    public function changedByUser()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
