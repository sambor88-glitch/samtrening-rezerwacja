<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['id', 'trainer_id', 'name', 'email', 'phone', 'password_hash', 'status', 'note'];
    protected $hidden = ['password_hash'];

    public function trainer() { return $this->belongsTo(Trainer::class, 'trainer_id'); }
    public function bookings() { return $this->hasMany(Booking::class, 'client_id'); }
    public function packages() { return $this->hasMany(Package::class, 'client_id'); }
    public function payments() { return $this->hasMany(Payment::class, 'client_id'); }
    public function messages() { return $this->hasMany(Message::class, 'client_id'); }
    public function measurements() { return $this->hasMany(Measurement::class, 'client_id'); }
    public function trainingPlans() { return $this->hasMany(TrainingPlan::class, 'client_id'); }
}
