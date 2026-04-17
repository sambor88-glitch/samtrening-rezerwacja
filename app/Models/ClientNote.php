<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientNote extends Model
{
    protected $fillable = ['trainer_id', 'client_id', 'note'];

    public function trainer() { return $this->belongsTo(Trainer::class, 'trainer_id'); }
    public function client() { return $this->belongsTo(Client::class, 'client_id'); }
}
