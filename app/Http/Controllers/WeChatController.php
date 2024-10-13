<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WeChatService;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\WeChatToken;

class WeChatController extends Controller
{
    protected $wechatService;

    public function __construct(WeChatService $wechatService)
    {
        $this->wechatService = $wechatService;
    }

    public function connect()
    {
        $redirectUrl = route('wechat.callback');
        $authorizeUrl = $this->wechatService->getAuthorizeUrl($redirectUrl, 'snsapi_userinfo');
        return redirect($authorizeUrl);
    }

    public function callback(Request $request)
    {
        $code = $request->input('code');
        if (!$code) {
            return redirect()->route('home')->with('error', 'Failed to connect WeChat account.');
        }

        try {
            $tokenData = $this->wechatService->getAccessToken($code);
            $userInfo = $this->wechatService->getUserInfo($tokenData['access_token'], $tokenData['openid']);

            $user = User::updateOrCreate(
                ['wechat_id' => $userInfo['openid']],
                [
                    'name' => $userInfo['nickname'],
                    'wechat_avatar' => $userInfo['headimgurl'],
                ]
            );

            WeChatToken::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'access_token' => $tokenData['access_token'],
                    'refresh_token' => $tokenData['refresh_token'],
                    'expires_at' => now()->addSeconds($tokenData['expires_in']),
                ]
            );

            Auth::login($user);

            return redirect()->route('compose')->with('success', 'WeChat account connected successfully!');
        } catch (\Exception $e) {
            return redirect()->route('home')->with('error', 'Failed to connect WeChat account: ' . $e->getMessage());
        }
    }
}