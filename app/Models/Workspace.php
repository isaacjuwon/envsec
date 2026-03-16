<?php

declare(strict_types = 1)
;

namespace App\Models;

use App\Concerns\Tenant\IsTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Workspace extends Model
{
    /** @use HasFactory<\Database\Factories\WorkspaceFactory> */
    use HasFactory, IsTenant;

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'owner_id',
        'is_active',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class , 'owner_id');
    }

    public function members()
    {
        return $this->belongsToMany(User::class , 'workspace_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
