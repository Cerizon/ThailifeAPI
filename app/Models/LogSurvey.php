<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogSurvey extends Model
{
    use HasFactory;

    protected $table   = 'log_count_survey_monkey';
    public $timestamps = true;

    protected $fillable = ['ddate','response'];
}