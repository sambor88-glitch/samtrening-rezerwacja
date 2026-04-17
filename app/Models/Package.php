<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'trainer_id', 'client_id', 'total_sessions', 'used_sessions',
        'price', 'paid', 'valid_from', 'valid_to', 'status', 'note'
    ];
    protected $casts = [
        'paid' => 'boolean',
        'total_sessions' => 'integer',
        'used_sessions' => 'integer',
        'price' => 'float',
    ];

    public function trainer() { return $this->belongsTo(Trainer::class, 'trainer_id'); }
    public function client() { return $this->belongsTo(Client::class, 'client_id'); }
    public function bookings() { return $this->hasMany(Booking::class, 'package_id'); }
}
