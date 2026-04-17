<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trainer extends Model
{
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['id', 'name', 'name_short', 'password_hash', 'role', 'color', 'gradient'];
    protected $hidden = ['password_hash'];

    public function clients() { return $this->hasMany(Client::class, 'trainer_id'); }
    public function bookings() { return $this->hasMany(Booking::class, 'trainer_id'); }
    public function availability() { return $this->hasMany(Availability::class, 'trainer_id'); }
    public function blockedSlots() { return $this->hasMany(BlockedSlot::class, 'trainer_id'); }
    public function packages() { return $this->hasMany(Package::class, 'trainer_id'); }
    public function payments() { return $this->hasMany(Payment::class, 'trainer_id'); }
    public function messages() { return $this->hasMany(Message::class, 'trainer_id'); }
    public function settings() { return $this->hasOne(Setting::class, 'trainer_id'); }
}
