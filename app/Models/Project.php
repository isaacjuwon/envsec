<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = [
        'workspace_id',
        'name',
        'slug',
        'description',
    ];

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'project_user')
            ->using(ProjectMembership::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function secrets()
    {
        return $this->hasMany(Secret::class);
    }
}
