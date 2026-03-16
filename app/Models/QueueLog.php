<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QueueLog extends Model
{
    protected $fillable = [
        'job_id',
        'job_name',
        'status',
        'started_at',
        'finished_at',
        'message',
        'data',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'data' => 'array',
    ];

    public function getDurationAttribute()
    {
        if ($this->started_at && $this->finished_at) {
            $seconds = $this->started_at->diffInSeconds($this->finished_at);

            if ($seconds < 60) {
                return $seconds . 's';
            } elseif ($seconds < 3600) {
                $minutes = floor($seconds / 60);
                $remainingSeconds = $seconds % 60;
                return $minutes . 'm ' . $remainingSeconds . 's';
            } else {
                $hours = floor($seconds / 3600);
                $remainingMinutes = floor(($seconds % 3600) / 60);
                return $hours . 'h ' . $remainingMinutes . 'm';
            }
        }
        return null;
    }

    public function getDurationInSecondsAttribute()
    {
        if ($this->started_at && $this->finished_at) {
            return $this->started_at->diffInSeconds($this->finished_at);
        }
        return null;
    }

    public function getProgressAttribute()
    {
        return match ($this->status) {
            'pending' => 0,
            'processing' => 50,
            'completed' => 100,
            'failed' => 0,
            default => 0,
        };
    }

    public function getShortJobNameAttribute()
    {
        return class_basename($this->job_name);
    }

    public function getExceptionMessageAttribute()
    {
        if ($this->message) {
            $lines = explode("\n", $this->message);
            return $lines[0] ?? 'No message';
        }
        return null;
    }

    public function getPayloadAttribute()
    {
        if ($this->data) {
            return $this->data;
        }

        // Jika tidak ada data yang disimpan, coba ambil dari jobs table
        if ($this->job_id) {
            $job = \DB::table('jobs')->find($this->job_id);
            if ($job && isset($job->payload)) {
                return json_decode($job->payload, true);
            }
        }

        return null;
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
    }
}
