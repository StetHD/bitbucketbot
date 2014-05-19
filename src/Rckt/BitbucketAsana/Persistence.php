<?php
/**
 * Persistence... thing
 *
 * @version 1.0
 * @author John Noel <john.noel@rckt.co.uk>
 * @package BitbucketAsana
 */

namespace Rckt\BitbucketAsana;

use Doctrine\DBAL\Configuration,
    Doctrine\DBAL\DriverManager;

class Persistence
{
    protected $conn;

    public function __construct(array $options = array())
    {
        $config = new Configuration();
        $this->conn = DriverManager::getConnection($options, $config);
        $this->init();
    }

    public function getLastChange($repo)
    {
        $stmt = $this->conn->prepare('SELECT node FROM changesets WHERE repo = :repo');
        $stmt->execute(array(
            'repo' => $repo,
        ));

        $rows = $stmt->fetchAll();

        if (count($rows) === 0) {
            return null;
        }

        return $rows[0]['node'];
    }

    public function setLastChange($repo, $node)
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));

        $stmt = $this->conn->prepare('REPLACE INTO changesets (repo, node, timestamp) VALUES (:repo, :node, :timestamp)');
        $stmt->execute(array(
            'repo' => $repo,
            'node' => $node,
            'timestamp' => $now->format('U'),
        ));
    }

    public function isLocked($repo)
    {
        $stmt = $this->conn->prepare('SELECT timestamp FROM locks WHERE repo = :repo');
        $stmt->execute(array(
            'repo' => $repo,
        ));

        $rows = $stmt->fetchAll();

        if (count($rows) === 0) {
            return false;
        }

        $timestamp = $rows[0]['timestamp'];
        $datetime = new \DateTime('now', new \DateTimeZone('UTC'));
        $datetime->setTimestamp($timestamp);
        $datetime->add(new \DateInterval('PT5M')); // 5 minute window

        $now = new \DateTime('now', new \DateTimeZone('UTC'));

        return ($now < $datetime);
    }

    public function lock($repo)
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));

        $stmt = $this->conn->prepare('REPLACE INTO locks (repo, timestamp) VALUES (:repo, :timestamp)');
        $stmt->execute(array(
            'repo' => $repo,
            'timestamp' => $now->format('U'),
        ));
    }

    public function unlock($repo)
    {
        $stmt = $this->conn->prepare('DELETE FROM locks WHERE repo = :repo');
        $stmt->execute(array(
            'repo' => $repo,
        ));
    }

    protected function init()
    {
        $this->conn->query('CREATE TABLE IF NOT EXISTS changesets
            ("repo" VARCHAR(255) PRIMARY KEY NOT NULL, "node" VARCHAR(40) NOT NULL, "timestamp" INTEGER)');

        $this->conn->query('CREATE TABLE IF NOT EXISTS locks
            ("repo" VARCHAR(255) PRIMARY KEY NOT NULL, "timestamp" INTEGER)');
    }
}
