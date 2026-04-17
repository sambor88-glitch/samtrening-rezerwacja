<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientPrice extends Model
{
    protected $fillable = ['trainer_id', 'client_id', 'price'];
    protected $casts = ['price' => 'float'];

    public function trainer() { return $this->belongsTo(Trainer::class, 'trainer_id'); }
    public function client() { return $this->belongsTo(Client::class, 'client_id'); }
}
