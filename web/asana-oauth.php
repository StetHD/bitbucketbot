<?php
/**
 * OAuth for Asana
 *
 * @version 1.0
 * @author John Noel <john.noel@rckt.co.uk>
 * @package BitbucketAsana
 */

use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Session\Session;
use Rckt\BitbucketAsana\Asana;

require __DIR__.'/../vendor/autoload.php';
$config = require __DIR__.'/../config/config.php';

$asana = new Asana($config['asana']['client_id'], $config['asana']['client_secret']);
$request = Request::createFromGlobals();

if (!$request->hasPreviousSession()) {
    $request->setSession(new Session());
}

$session = $request->getSession();
