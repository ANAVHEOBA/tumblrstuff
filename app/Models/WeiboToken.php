<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeiboToken extends Model
{
    protected $fillable = ['user_id', 'oauth_token', 'oauth_token_secret'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}