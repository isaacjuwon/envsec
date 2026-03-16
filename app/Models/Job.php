<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Job extends Model
{
    protected $table = 'jobs';

    public $timestamps = false;

    protected $fillable = [
        'queue',
        'payload',
        'attempts',
        'reserved_at',
        'available_at',
        'created_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'attempts' => 'integer',
        'reserved_at' => 'integer',
        'available_at' => 'integer',
        'created_at' => 'integer',
    ];

    public function getJobNameAttribute()
    {
        $payload = is_string($this->payload) ? json_decode($this->payload, true) : $this->payload;

        if (isset($payload['displayName'])) {
            return $payload['displayName'];
        }

        if (isset($payload['data']['commandName'])) {
            return class_basename($payload['data']['commandName']);
        }

        return 'Unknown Job';
    }

    public function getCreatedDateAttribute()
    {
        return \Carbon\Carbon::createFromTimestamp($this->created_at);
    }

    public function getAvailableDateAttribute()
    {
        return \Carbon\Carbon::createFromTimestamp($this->available_at);
    }

    public function getReservedDateAttribute()
    {
        return $this->reserved_at ? \Carbon\Carbon::createFromTimestamp($this->reserved_at) : null;
    }

    public function getStatusAttribute()
    {
        if ($this->reserved_at) {
            return 'processing';
        }
        return $this->available_at > time() ? 'delayed' : 'pending';
    }

    public function scopeQueue($query, $queue = 'default')
    {
        return $query->where('queue', $queue);
    }

    public function scopePending($query)
    {
        return $query->whereNull('reserved_at')
                     ->where('available_at', '<=', time());
    }

    public function scopeDelayed($query)
    {
        return $query->whereNull('reserved_at')
                     ->where('available_at', '>', time());
    }

    public function scopeProcessing($query)
    {
        return $query->whereNotNull('reserved_at');
    }
}
