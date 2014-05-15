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
use Rckt\BitbucketAsana\Bitbucket,
    Rckt\BitbucketAsana\Asana,
    Rckt\BitbucketAsana\Command\Command;

require __DIR__.'/../vendor/autoload.php';

// logging
$requestLog = new Logger('request');
$requestLog->pushHandler(new StreamHandler(__DIR__.'/../logs/request.log', Logger::DEBUG));

$appLog = new Logger('application');
$appLog->pushHandler(new StreamHandler(__DIR__.'/../logs/app.log', Logger::DEBUG));

$request = Request::createFromGlobals();
$requestLog->addDebug($request->__toString());

// validate request params
$projectId = $request->query->get('project_id', '');

if (empty($projectId)) {
    header('HTTP/1.1 400 Bad Request');
    $appLog->addError('No project_id supplied');
    die('No project_id supplied');
}

$payload = $request->getContent();

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

// todo validate payload

// do things
$config = require __DIR__.'/../config/config.php';

if (!array_key_exists($projectId, $config['project_map'])) {
    header('HTTP/1.1 404 Not Found');
    $msg = sprintf('No project found in map for ID %s', $projectId);
    $appLog->addError($msg);
    die($msg);
}

$repo = $config['project_map'][$projectId];

$bitbucket = new Bitbucket($config['bitbucket']['workspace'], $config['bitbucket']['key'], $config['bitbucket']['secret']);
$bitbucket->setAccessToken($config['bitbucket']['access_token'])
    ->setSecretToken($config['bitbucket']['secret_token']);

$asana = new Asana($config['asana']['workspace_id'], $config['asana']['client_id'], $config['asana']['client_secret']);
$asana->setApiKey($config['asana']['api_key']);

// todo since
$changesets = $bitbucket->getChangesets($repo);

foreach ($changesets->changesets as $changeset) {
    $message = trim($changeset->message);

    $commands = Command::parse($message);
    if (count($commands) == 0) {
        continue;
    }

    $appLog->addInfo(sprintf('Found %d commands', count($commands)));

    $author = $changeset->author;
    /*if (!array_key_exists($author, $config['user_map'])) {
        $appLog->addDebug(sprintf('Unable to map user %s', $author));
        continue;
    }*/

    $url = $bitbucket->getUrl($repo, $changeset->raw_node);

    foreach ($commands as $command) {
        if ($command->hasMessage()) {
            $msg = sprintf('This task was referenced by commit %s with the message: %s',
                $url, $command->getMessage());
            $asana->addComment($command->getId(), $msg);
        }

        if ($command->hasTags()) {
            $asana->updateTags($command->getId(), $command->getTags());
        }

        if ($command->hasReassignment()) {
            $user = $command->getReassignment();

            // get userId from map

            $asana->updateAssignee($comment->getId(), $userId);
        }
    }
}
