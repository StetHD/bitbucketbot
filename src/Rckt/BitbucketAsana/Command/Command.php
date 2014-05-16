<?php
/**
 * Command
 *
 * @version 1.0
 * @author John Noel <john.noel@rckt.co.uk>
 * @package BitbucketAsana
 * @subpackage Command
 */

namespace Rckt\BitbucketAsana\Command;

/**
 * Represents a command that will be sent to Asana
 */
class Command
{
    protected $id;
    protected $message;
    protected $tags = array();
    protected $resassign;

    public function __construct()
    {
    }

    /**
     * Parse commands from a given text string
     *
     * @param string $text
     * @return array An array of Command
     */
    public static function parse($text)
    {
        if (strpos($text, '[') === false) {
            return array();
        }

        $matches = array();
        $pattern = '/\[[^\]]+\]/';
        $count = preg_match_all($pattern, $text, $matches);

        if (!$count) {
            return array();
        }

        $globalMessage = trim(preg_replace($pattern, '', $text));

        $ret = array();

        foreach ($matches[0] as $match) {
            $match = substr(trim($match), 1, -1);

            $localMatches = array();
            $pattern = '/#(?P<id>\d+)|tags:(?P<tags>[^\s]+)|reassign:(?P<reassign>[^\s]+)/';
            $count = preg_match_all($pattern, $match, $localMatches);

            if (!$count) {
                continue;
            }

            $id = array_filter($localMatches['id']);
            $id = reset($id);

            if (empty($id)) { // if we don't have an ID, skip
                continue;
            }

            $tags = array_filter($localMatches['tags']);
            $tags = array_filter(explode(',', reset($tags)));
            $reassign = array_filter($localMatches['reassign']);
            $reassign = reset($reassign);

            $message = trim(preg_replace($pattern, '', $match));
            $message = (empty($message)) ? $globalMessage : $message;

            $command = new self();
            $command->setId($id)
                ->setTags($tags)
                ->setReassignment($reassign)
                ->setMessage($message);

            $ret[] = $command;
        }

        return $ret;
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function hasMessage()
    {
        return !empty($this->message);
    }

    public function setTags(array $tags)
    {
        $this->tags = $tags;
        return $this;
    }

    public function getTags()
    {
        return $this->tags;
    }

    public function hasTags()
    {
        return !empty($this->tags);
    }

    public function setReassignment($reassign)
    {
        $this->reassign = $reassign;
        return $this;
    }

    public function getReassignment()
    {
        return $this->reassign;
    }

    public function hasReassignment()
    {
        return !empty($this->reassign);
    }

    public function __toString()
    {
        $parts = array('#'.$this->id);

        if ($this->hasMessage()) {
            $parts[] = $this->message;
        }

        if ($this->hasTags()) {
            $parts[] = 'tags:'.implode(',', $this->tags);
        }

        if ($this->hasReassignment()) {
            $parts[] = 'reassign:'.$this->reassignment;
        }

        return '['.implode(' ', $parts).']';
    }
}
