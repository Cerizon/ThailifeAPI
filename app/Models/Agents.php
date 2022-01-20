<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agents extends Model
{
    use HasFactory;

    const CREATED_AT = 'createDate';
    const UPDATED_AT = 'updateDate';

    protected $table = 'agents_member';

}