<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;

class FailedJob extends Model
{
    protected $table = 'failed_jobs';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'connection',
        'queue',
        'payload',
        'exception',
        'failed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'failed_at' => 'datetime',
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

    public function getExceptionMessageAttribute()
    {
        $lines = explode("\n", $this->exception);
        return $lines[0] ?? 'No exception message';
    }

    public function retry()
    {
        Artisan::call('queue:retry', ['id' => $this->uuid]);
        return $this;
    }

    public function forget()
    {
        Artisan::call('queue:forget', ['id' => $this->uuid]);
        $this->delete();
        return $this;
    }

    public static function retryAll()
    {
        Artisan::call('queue:retry', ['id' => 'all']);
    }

    public static function flushAll()
    {
        Artisan::call('queue:flush');
    }
}
