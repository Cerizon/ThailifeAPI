<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerSurveys extends Model
{
    use HasFactory;

    protected $table = 'profile_surveys';

    protected $fillable = [
        "id",
        "surveyId",
        "prefix",
        "firstname",
        "lastname",
        "telephone",
        "job",
        "created_at",
        "updated_at"
    ];
}
