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

    protected function init()
    {
        $this->conn->query('CREATE TABLE IF NOT EXISTS changesets
            ("repo" VARCHAR(255) PRIMARY KEY NOT NULL, "node" VARCHAR(40) NOT NULL, "timestamp" INTEGER)');
    }
}
