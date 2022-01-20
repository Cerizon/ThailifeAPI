<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentsMember extends Model
{
    use HasFactory;

    protected $table = 'agents_member';

    const CREATED_AT = 'createDate';
    const UPDATED_AT = 'updateDate';

    protected $guarded = [];

}