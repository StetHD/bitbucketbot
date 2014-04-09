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

$projectId = (array_key_exists('project_id', $_GET) && !empty($_GET['project_id'])) ? $_GET['project_id'] : null;

if ($projectId === null) {
    header('HTTP/1.1 400 Bad Request');
    die('No project_id supplied');
}

$payload = file_get_contents('php://input');
