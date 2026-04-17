<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlockedSlot extends Model
{
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['id', 'trainer_id', 'date', 'time', 'full_day', 'reason'];
    protected $casts = ['full_day' => 'boolean', 'date' => 'date:Y-m-d'];

    public function trainer() { return $this->belongsTo(Trainer::class, 'trainer_id'); }
}
