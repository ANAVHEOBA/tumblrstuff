<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $phone_number
 * @property string $pin
 * @property string $password
 * @property string $wechat_id
 * @property string $wechat_avatar
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $guarded = [];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(Transaction::class, 'sender');
    }

    public function account(): HasOne
    {
        return $this->hasOne(Account::class, 'user_id');
    }

    public function oauthTokens(): HasMany
    {
        return $this->hasMany(OauthToken::class);
    }

    public function weiboToken(): HasOne
    {
        return $this->hasOne(WeiboToken::class);
    }

    public function wechatToken(): HasOne
    {
        return $this->hasOne(WeChatToken::class);
    }

    public function douyinToken()
    {
        return $this->oauthTokens()->where('provider', 'douyin')->first();
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function baiduToken(): HasOne
    {
        return $this->hasOne(BaiduToken::class);
    }

    public function meetupUser(): HasOne
    {
        return $this->hasOne(MeetupUser::class, 'user_id');
    }
}