<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SMStoform extends Model
{
    use HasFactory;

    protected $table = 'smsverifies';

    protected $fillable = [
        "id",
        "sms_campaign",
        "pid",
        "phone",
        "link",
        "verify",
        "checked",
        "created_at",
        "updated_at"
    ];
}
