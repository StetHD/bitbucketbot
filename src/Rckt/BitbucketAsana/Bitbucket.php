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

    protected $workspace;

    public function __construct($workspace, $consumerKey, $consumerSecret)
    {
        $this->workspace = $workspace;

        parent::__construct($consumerKey, $consumerSecret);
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

    public function getChangesets($repository, $lastChange = null)
    {
        $url = sprintf('%s/repositories/%s/%s/changesets',
            self::BASE_URL,
            $this->workspace,
            $repository
        );

        $params = array(
            'limit' => 30,
        );

        $raw = json_decode($this->httpRequest('GET', $url, $params));
        $changesets = $raw->changesets;

        // do reverse date ordering of them
        usort($changesets, function($a, $b) {
            $aDate = \DateTime::createFromFormat('Y-m-d H:i:sP', $a->utctimestamp);
            $bDate = \DateTime::createFromFormat('Y-m-d H:i:sP', $b->utctimestamp);
            $aTs = $aDate->getTimestamp();
            $bTs = $bDate->getTimestamp();

            if ($aTs == $bTs) {
                return 0;
            }

            return ($aTs > $bTs) ? -1 : 1;
        });

        if ($lastChange === null) {
            return $changesets;
        }

        $ret = array();
        foreach ($changesets as $changeset) {
            if ($changeset->raw_node == $lastChange) {
                return $ret;
            }

            $ret[] = $changeset;
        }

        return $ret;
    }

    public function getRepositories()
    {
        $url = sprintf('%s/user/repositories', self::BASE_URL);

        return json_decode($this->httpRequest('GET', $url));
    }

    public function getUrl($repository, $commitId)
    {
        return sprintf('https://bitbucket.org/%s/%s/commits/%s',
            $this->workspace,
            $repository,
            $commitId
        );
    }
}
