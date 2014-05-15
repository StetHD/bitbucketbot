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

    protected $workspaceId;
    protected $tagMap;
    protected $userMap;

    public function __construct($workspaceId, $clientId, $clientSecret)
    {
        $this->workspaceId = $workspaceId;

        parent::__construct($clientId, $clientSecret);
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

    public function getTags()
    {
        return $this->api('GET', '/tags');
    }

    public function createTag($tag)
    {
        return $this->api('POST', '/tags', array(
            'name' => $tag,
            'workspace' => $this->workspaceId,
        ));
    }

    public function updateTags($taskId, $tags)
    {
        if ($this->tagMap === null) {
            $existingTags = $this->getTags();
            $this->tagMap = array();

            foreach ($existingTags->data as $tag) {
                $tagName = (function_exists('mb_strtolower')) ? mb_strtolower($tag->name) : strtolower($tag->name);
                $this->tagMap[$tagName] = $tag->id;
            }
        }

        foreach ($tags as $tag) {
            $tagName = (function_exists('mb_strtolower')) ? mb_strtolower($tag) : strtolower($tag);

            if (!array_key_exists($tagName, $this->tagMap)) {
                $resp = $this->createTag($tag);
                $this->tagMap[$tagName] = $resp->data->id;
            }

            $tagId = $this->tagMap[$tagName];
            $this->api('POST', '/tasks/'.$taskId.'/addTag', array(
                'tag' => $tagId,
            ));
        }
    }

    public function getUsers()
    {
        return $this->api('GET', '/users', array(
            'opt_fields' => 'name,email',
        ));
    }

    public function findUser($search)
    {
        if ($this->userMap === null) {
            $existingUsers = $this->getUsers();
            $this->userMap = array();

            foreach ($existingUsers->data as $user) {
                $this->userMap[$user->email] = $user;
            }
        }

        $userId = (array_key_exists($search, $this->userMap)) ? $this->userMap[$assignee]->id : null;

        if ($userId === null) {
            foreach ($this->userMap as $user) {
                $pattern = '/^'.preg_quote($search, '/').'/i';

                if (preg_match($pattern, $user->name) === 1) {
                    $userId = $user->id;
                    break;
                }

                if (preg_match($pattern, $user->email) === 1) {
                    $userId = $user->id;
                    break;
                }
            }
        }

        return $userId;
    }

    public function updateAssignee($taskId, $assignee)
    {
        $userId = $this->findUser($assignee);

        if ($userId === null) {
            throw new \RuntimeException(sprintf('Unable to find user with e-mail %s, available emails are %s',
                $assigneeEmail,
                implode(', ', array_keys($this->userMap))
            ));
        }

        $this->api('PUT', '/tasks/'.$taskId, array(
            'assignee' => $userId,
        ));
    }

    public function getWorkspaces()
    {
        return $this->api('GET', '/workspaces');
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
