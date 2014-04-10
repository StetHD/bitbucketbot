<?php
/**
 * Bitbucket / Asana connector
 *
 * Pops a story on relevant Asana tasks when a commit is pushed to BB
 *
 * @version 1.0
 * @author John Noel <john.noel@rckt.co.uk>
 * @package BitbucketAsana
 */

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Symfony\Component\HttpFoundation\Request;

require __DIR__.'/vendor/autoload';

// logging
$requestLog = new Logger('request');
$requestLog->pushHandler(new StreamHandler(__DIR__.'/logs/request.log', Logger::DEBUG));

$appLog = new Logger('application');
$appLog->pushHandler(new StreamHandler(__DIR__.'/logs/app.log', Logger::DEBUG));

$request = Request::createFromGlobals();
$requestLog->addDebug($request->__toString());

// validate request params
$projectId = (array_key_exists('project_id', $_GET) && !empty($_GET['project_id'])) ? $_GET['project_id'] : null;

if ($projectId === null) {
    header('HTTP/1.1 400 Bad Request');
    $appLog->addError('No project_id supplied');
    die('No project_id supplied');
}

$payload = file_get_contents('php://input');

if (empty($payload)) {
    header('HTTP/1.1 400 Bad Request');
    $appLog->addError('No payload supplied');
    die('No payload supplied');
}

$payload = json_decode($payload);

if ($payload === null) {
    header('HTTP/1.1 400 Bad Request');
    $appLog->addError('Unable to decode payload');
    die('Unable to decode payload');
}

// do things

