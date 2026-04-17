<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'trainer_id', 'client_id', 'client_name', 'date', 'time',
        'duration', 'type', 'status', 'completed', 'paid', 'package_id', 'price', 'note'
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'completed' => 'boolean',
        'paid' => 'boolean',
        'duration' => 'integer',
        'price' => 'float',
    ];

    public function trainer() { return $this->belongsTo(Trainer::class, 'trainer_id'); }
    public function client() { return $this->belongsTo(Client::class, 'client_id'); }
    public function package() { return $this->belongsTo(Package::class, 'package_id'); }
}
