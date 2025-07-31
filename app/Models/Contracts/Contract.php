<?php

namespace App\Models\Contracts;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    /** @use HasFactory<\Database\Factories\Contracts\ContractFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'started_at',
        'ended_at',
        'canceled_at',
    ];
}
