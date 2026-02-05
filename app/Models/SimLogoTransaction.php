<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SimLogoTransaction extends Model
{
    protected $table = 'sim_logo_transactions';

    protected $fillable = [
        'msisdn',
        'transaction_id',
        'transaction_spn',
        'transaction_pnn',
        'pnn',
        'pnn',
        'count',
        'status',
        'remark',
    ];

    protected $casts = [
        'count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
