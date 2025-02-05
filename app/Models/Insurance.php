<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Insurance extends Model
{
    //
    protected $fillable = [
        'assure',
        'telephone',
        'echeance',
        'immatriculation',
        'sync_status',
        'sync_message'
    ];

    protected $dates = ['echeance'];
}
