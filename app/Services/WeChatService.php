<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Config;

class WeChatService
{
    protected $client;
    protected $appId;
    protected $secret;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://api.weixin.qq.com/',
        ]);
        $this->appId = Config::get('services.wechat.appid');
        $this->secret = Config::get('services.wechat.secret');
    }

    public function getAuthorizeUrl($redirectUri, $scope = 'snsapi_base')
    {
        $params = [
            'appid' => $this->appId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => $scope,
            'state' => csrf_token(),
        ];

        return 'https://open.weixin.qq.com/connect/oauth2/authorize?' . http_build_query($params) . '#wechat_redirect';
    }

    public function getAccessToken($code)
    {
        $response = $this->client->get('sns/oauth2/access_token', [
            'query' => [
                'appid' => $this->appId,
                'secret' => $this->secret,
                'code' => $code,
                'grant_type' => 'authorization_code',
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    public function getUserInfo($accessToken, $openId)
    {
        $response = $this->client->get('sns/userinfo', [
            'query' => [
                'access_token' => $accessToken,
                'openid' => $openId,
                'lang' => 'zh_CN',
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    public function publishPost($accessToken, $post)
    {
        // Implement the logic to publish to WeChat
        // This will depend on the specific WeChat API endpoints and requirements
        // You'll need to refer to WeChat's API documentation for the exact implementation
    }
}