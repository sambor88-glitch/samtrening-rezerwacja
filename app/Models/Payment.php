<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['id', 'trainer_id', 'client_id', 'package_id', 'amount', 'date', 'method', 'status', 'note'];
    protected $casts = ['amount' => 'float', 'date' => 'date:Y-m-d'];

    public function trainer() { return $this->belongsTo(Trainer::class, 'trainer_id'); }
    public function client() { return $this->belongsTo(Client::class, 'client_id'); }
    public function package() { return $this->belongsTo(Package::class, 'package_id'); }
}
