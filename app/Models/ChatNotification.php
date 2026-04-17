<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatNotification extends Model
{
    protected $fillable = ['trainer_id', 'client_id', 'unread_trainer', 'unread_client'];
    protected $casts = ['unread_trainer' => 'integer', 'unread_client' => 'integer'];
}
