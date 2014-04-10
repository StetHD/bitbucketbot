<?php
/**
 * OAuth for Bitbucket, step 1
 *
 * @version 1.0
 * @author John Noel <john.noel@rckt.co.uk>
 * @package BitbucketAsana
 */

use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Session\Session;
use Rckt\BitbucketAsana\Bitbucket;

require __DIR__.'/../vendor/autoload.php';
$config = require __DIR__.'/../config/config.php';

$bitbucket = new Bitbucket($config['bitbucket']['key'], $config['bitbucket']['secret']);
$request = Request::createFromGlobals();

if (!$request->hasPreviousSession()) {
    $request->setSession(new Session());
}

$session = $request->getSession();

if ($request->query->has('oauth_token') && $request->query->has('oauth_verifier')) {
    $oauthParams = $session->get('oauth');
    $bitbucket->setAccessToken($oauthParams['oauth_token'])
        ->setSecretToken($oauthParams['oauth_token_secret']);

    $verifier = $request->query->get('oauth_verifier');
    $tokens = $bitbucket->authorisationStep2($verifier);

    var_dump($tokens);
} else {
    $oauthParams = $bitbucket->authorisationStep1($request->getUri());
    $session->start();
    $session->set('oauth', $oauthParams);

    echo '<a href="https://bitbucket.org/api/1.0/oauth/authenticate?oauth_token='.$oauthParams['oauth_token'].'">Grant access</a>';
}
