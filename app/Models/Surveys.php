<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Surveys extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'date:Y-m-d H:i:s',
        'updated_at' => 'date:Y-m-d H:i:s',
    ];

    protected $fillable = [
        'surveyId', 'title', 'isPublic', 'available', 'surveyEmbed', 'isAgents', 'type_surveys',
        'surveyPdfUrl', 'responseCount', 'limitResponse',
        'completedAt', 'created_at', 'updated_at', 'header_desktop', 'header_mobile',
    ];

    public function getCreatedAtAttribute($value)
    {
        return Carbon::createFromTimestamp(strtotime($value))
            ->timezone('Asia/Bangkok')
            ->toDateTimeString();
    }
}
