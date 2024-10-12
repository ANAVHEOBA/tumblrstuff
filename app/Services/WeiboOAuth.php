<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WeiboOAuth
{
    protected $appKey;
    protected $appSecret;
    protected $callbackUrl;
    protected $baseUrl = 'https://api.t.sina.com.cn/oauth/';

    public function __construct($appKey, $appSecret, $callbackUrl)
    {
        $this->appKey = $appKey;
        $this->appSecret = $appSecret;
        $this->callbackUrl = $callbackUrl;
    }

    public function getRequestToken()
    {
        $url = $this->baseUrl . 'request_token';
        $params = [
            'oauth_callback' => $this->callbackUrl,
            'oauth_consumer_key' => $this->appKey,
            'oauth_nonce' => $this->generateNonce(),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => time(),
            'oauth_version' => '1.0'
        ];

        $baseString = $this->generateBaseString('POST', $url, $params);
        $params['oauth_signature'] = $this->generateSignature($baseString);

        $response = Http::withHeaders([
            'Authorization' => $this->buildAuthHeader($params)
        ])->post($url);

        if ($response->successful()) {
            parse_str($response->body(), $result);
            return $result;
        }

        throw new \Exception('Failed to get request token: ' . $response->body());
    }

    public function getAuthorizeUrl($requestToken)
    {
        return $this->baseUrl . 'authorize?oauth_token=' . $requestToken;
    }

    public function getAccessToken($oauthToken, $oauthVerifier)
    {
        $url = $this->baseUrl . 'access_token';
        $params = [
            'oauth_consumer_key' => $this->appKey,
            'oauth_nonce' => $this->generateNonce(),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => time(),
            'oauth_token' => $oauthToken,
            'oauth_verifier' => $oauthVerifier,
            'oauth_version' => '1.0'
        ];

        $baseString = $this->generateBaseString('POST', $url, $params);
        $params['oauth_signature'] = $this->generateSignature($baseString, $oauthToken);

        $response = Http::withHeaders([
            'Authorization' => $this->buildAuthHeader($params)
        ])->post($url);

        if ($response->successful()) {
            parse_str($response->body(), $result);
            return $result;
        }

        throw new \Exception('Failed to get access token: ' . $response->body());
    }

    protected function generateNonce()
    {
        return md5(Str::random(40));
    }

    public function generateBaseString($method, $url, $params)
    {
        $parts = [
            strtoupper($method),
            urlencode($url),
            urlencode(http_build_query($params, '', '&', PHP_QUERY_RFC3986))
        ];
        return implode('&', $parts);
    }

    public function generateSignature($baseString, $oauthTokenSecret = '')
    {
        $key = $this->appSecret . '&' . $oauthTokenSecret;
        return base64_encode(hash_hmac('sha1', $baseString, $key, true));
    }

    public function buildAuthHeader($params, $signature = null)
    {
        if ($signature) {
            $params['oauth_signature'] = $signature;
        }
        $parts = [];
        foreach ($params as $key => $value) {
            if (substr($key, 0, 5) === 'oauth') {
                $parts[] = sprintf('%s="%s"', $key, urlencode($value));
            }
        }
        return 'OAuth ' . implode(', ', $parts);
    }
}