<?php
/**
 * Bitbucket class
 *
 * @version 1.0
 * @author John Noel <john.noel@rckt.co.uk>
 * @package BitbucketAsana
 */

namespace Rckt\BitbucketAsana;

use Symfony\Component\HttpFoundation\Request;

class Bitbucket extends OAuth1
{
    const BASE_URL = 'https://bitbucket.org/api/1.0';

    public function __construct($consumerKey, $consumerSecret)
    {
        $this->consumerKey = $consumerKey;
        $this->consumerSecret = $consumerSecret;
    }

    public function authorisationStep1($callbackUrl)
    {
        $params = array(
            'oauth_callback' => $callbackUrl,
        );

        $url = 'https://bitbucket.org/api/1.0/oauth/request_token';

        $oauthParams = $this->getOAuthParameters('POST', $url, $params);
        $requestParams = array_merge($params, $oauthParams);

        $resp = $this->httpRequest('POST', $url, $requestParams);

        $ret = array();
        parse_str($resp, $ret);

        return $ret;
    }

    public function authorisationStep2($verifier)
    {
        $params = array(
            'oauth_verifier' => $verifier,
        );

        $url = 'https://bitbucket.org/api/1.0/oauth/access_token';

        $oauthParams = $this->getOAuthParameters('POST', $url, $params);
        $requestParams = array_merge($params, $oauthParams);

        $resp = $this->httpRequest('POST', $url.'?'.http_build_query($requestParams));

        $ret = array();
        parse_str($resp, $ret);

        return $ret;
    }

    public function getChangesets($repository)
    {
        $url = sprintf('%s/repositories/%s/%s/changesets',
            self::BASE_URL,
            'rckt',
            $repository
        );

        return json_decode($this->httpRequest('GET', $url));
    }

    public function getRepositories()
    {
        $url = sprintf('%s/user/repositories', self::BASE_URL);

        return json_decode($this->httpRequest('GET', $url));
    }
}
