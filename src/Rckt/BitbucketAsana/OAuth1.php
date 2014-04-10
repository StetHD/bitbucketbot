<?php
/**
 * OAuth 1.0a class
 *
 * @version 1.0
 * @author John Noel <john.noel@rckt.co.uk>
 * @package BitbucketAsana
 * @subpackage
 */

namespace Rckt\BitbucketAsana;

abstract class OAuth1 extends CurlEnabled
{
    protected $consumerKey;
    protected $consumerSecret;
    protected $accessToken;
    protected $secretToken;

    public function getAccessToken()
    {
        return $this->accessToken;
    }

    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
        return $this;
    }

    public function getSecretToken()
    {
        return $this->secretToken;
    }

    public function setSecretToken($secretToken)
    {
        $this->secretToken = $secretToken;
        return $this;
    }

    public function getConsumerKey()
    {
        return $this->consumerKey;
    }

    public function setConsumerKey($consumerKey)
    {
        $this->consumerKey = $consumerKey;
        return $this;
    }

    public function getConsumerSecret()
    {
        return $this->consumerSecret;
    }

    public function setConsumerSecret($consumerSecret)
    {
        $this->consumerSecret = $consumerSecret;
        return $this;
    }

    protected function getOAuthParameters($method, $url, $requestParams = array())
    {
        $oauthParts = array(
            'oauth_consumer_key' => $this->consumerKey,
            'oauth_nonce' => $this->generateNonce(32),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => time(),
            'oauth_version' => '1.0',
        );

        if ($this->accessToken !== null) {
            $oauthParts['oauth_token'] = $this->accessToken;
        }

        $params = array_merge($oauthParts, $requestParams);

        $encodedParams = array();
        foreach ($params as $k => $v) {
            $encodedParams[rawurlencode($k)] = rawurlencode($v);
        }
        uksort($encodedParams, 'strcmp');

        $output = '';
        foreach ($encodedParams as $k => $v) {
            $output .= (empty($output)) ? '' : '&';
            $output .= $k.'='.$v;
        }

        $output = strtoupper($method).'&'.rawurlencode($url).'&'.rawurlencode($output);

        $signingKey = rawurlencode($this->consumerSecret).'&';
        $signingKey .= ($this->secretToken !== null) ? rawurlencode($this->secretToken) : '';

        $oauthParts['oauth_signature'] = base64_encode(hash_hmac('sha1', $output, $signingKey, true));

        return $oauthParts;
    }

    protected function getAuthorisationHeader($method, $url, $requestParams)
    {
        $oauthParts = $this->getOAuthParts($method, $url, $requestParams);
        ksort($oauthParts, SORT_STRING);

        $ret = '';
        foreach ($oauthParts as $k => $v) {
            $ret .= (empty($ret)) ? '' : ', ';
            $ret .= rawurlencode($k).'="'.rawurlencode($v).'"';
        }

        $ret = 'OAuth '.$ret;

        return $ret;
    }

    protected function generateNonce($length = 32)
    {
        if (function_exists('openssl_random_pseudo_bytes')) {
            return base64_encode(openssl_random_pseudo_bytes($length));
        }

        $string = '';
        for ($i = 0; $i < $length; $i++) {
            $string .= chr(mt_rand(0, 255));
        }

        return base64_encode($string);
    }
}
