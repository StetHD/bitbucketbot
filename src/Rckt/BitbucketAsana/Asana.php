<?php
/**
 * Asana class
 *
 * @version 1.0
 * @author John Noel <john.noel@rckt.co.uk>
 * @package BitbucketAsana
 */

namespace Rckt\BitbucketAsana;

class Asana extends OAuth2
{
    protected $apiKey;

    public function __construct($clientId, $clientSecret)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
    }

    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
        return $this;
    }

    public function getApiKey()
    {
        return $this->apiKey;
    }

    public function authorisationStep2($redirectUri, $code)
    {
        $params = array(
            'grant_type' => 'authorization_code',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $redirectUri,
            'code' => $code,
        );

        $url = 'https://app.asana.com/-/oauth_token';

        $resp = $this->httpRequest('POST', $url, $params);
        $json = json_decode($resp);

        return $json;
    }

    public function getProjectTasks($projectId)
    {
        return $this->api('GET', '/projects/'.$projectId.'/tasks');
    }

    public function addComment($taskId, $text)
    {
        return $this->api('POST', '/tasks/'.$taskId.'/stories', array(
            'text' => $text,
        ));
    }

    protected function refreshToken()
    {
        $params = array(
            'grant_type' => 'authorisation_code',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $redirectUri,
            'refresh_token' => $token,
        );

        $url = 'https://app.asana.com/-/oauth_token';

        $resp = $this->httpRequest('POST', $url, $params);
        $json = json_decode($resp);

        return $json;
    }

    protected function api($method, $url, array $parameters = array())
    {
        $fullUrl = 'https://app.asana.com/api/1.0/'.ltrim($url, '/');

        $headers = array();
        if ($this->token !== null) {
            $headers[] = 'Authorization: Bearer '.$this->token;
        } else if ($this->apiKey !== null) {
            // todo make httpRequest accept username and password
            $headers[] = 'Authorization: Basic '.(base64_encode($this->apiKey.':'));
        }

        $resp = $this->httpRequest($method, $fullUrl, $parameters, $headers);
        return json_decode($resp);
    }
}
