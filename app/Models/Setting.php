<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $primaryKey = 'trainer_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['trainer_id', 'data'];
    protected $casts = ['data' => 'array'];

    public function trainer() { return $this->belongsTo(Trainer::class, 'trainer_id'); }
}
