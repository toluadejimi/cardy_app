<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EMoney extends Model
{
    use HasFactory;

    protected $fillable = [
        'credit_balance',
        'user_id'

    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
