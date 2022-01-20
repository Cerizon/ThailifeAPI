<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssignHistory extends Model
{
    use HasFactory;

    protected $table = 'assign_history';

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'date:Y-m-d H:i:s',
        'updated_at' => 'date:Y-m-d H:i:s',
    ];

    public function getCreatedAtAttribute($value)
    {
        return Carbon::createFromTimestamp(strtotime($value))
            ->timezone('Asia/Bangkok')
            ->toDateTimeString()
        ;
    }

<<<<<<< HEAD
}
=======
<<<<<<< HEAD
}
=======
}
>>>>>>> 450ff48794bce83c0bbc302a47beb655e4de3250
>>>>>>> 185f47c3ef5479e4a795f44f42eb29575e5e7e6c
