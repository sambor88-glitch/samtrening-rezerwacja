<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Availability extends Model
{
    protected $fillable = ['trainer_id', 'day_of_week', 'start_time', 'end_time', 'active'];
    protected $casts = ['active' => 'boolean'];

    public function trainer() { return $this->belongsTo(Trainer::class, 'trainer_id'); }
}
