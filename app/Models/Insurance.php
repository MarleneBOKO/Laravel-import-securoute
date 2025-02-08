<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Insurance extends Model
{
    use HasFactory;

    protected $fillable = [
        'immatriculation',
        'assure',
        'telephone',
        'echeance',
        'sync_status',
        'sync_message',
        'last_sync_attempt'
    ];

    protected $dates = [
        'echeance',
        'last_sync_attempt'
    ];

    protected $casts = [
        'echeance' => 'date',
        'last_sync_attempt' => 'datetime'
    ];

    public function scopePending($query)
    {
        return $query->where('sync_status', 'pending');
    }

    public function scopeSynced($query)
    {
        return $query->where('sync_status', 'synced');
    }

    public function scopeFailed($query)
    {
        return $query->where('sync_status', 'failed');
    }
}
