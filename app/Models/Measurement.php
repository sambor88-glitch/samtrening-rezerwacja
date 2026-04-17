<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Measurement extends Model
{
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['id', 'trainer_id', 'client_id', 'date', 'weight', 'body_fat', 'muscle_mass', 'circumferences', 'note'];
    protected $casts = [
        'circumferences' => 'array',
        'weight' => 'float',
        'body_fat' => 'float',
        'muscle_mass' => 'float',
        'date' => 'date:Y-m-d',
    ];

    public function trainer() { return $this->belongsTo(Trainer::class, 'trainer_id'); }
    public function client() { return $this->belongsTo(Client::class, 'client_id'); }
}
