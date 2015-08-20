<?php

namespace Saxulum\MongoId;

class MongoId implements \Serializable
{
    /**
     * @var string
     */
    private $id;

    const HEX_LENGTH_TIMESTAMP = 8;
    const HEX_LENGTH_HOSTNAME = 6;
    const HEX_LENGTH_PID = 4;
    const HEX_LENGTH_INCREMENT = 6;

    public function __construct($id = null)
    {
        if($id instanceof self) {
            $this->id = $id->id;
            return;
        }

        if(null !== $id) {
            if(!self::isValid($id)) {
                throw new \InvalidArgumentException(sprintf('Invalid id: %s', $id));
            }
            $this->id = $id;
            return;
        }

        $this->id = $this->createId();
    }

    /**
     * @return string
     */
    private function createId()
    {
        static $counter;

        if(null === $counter) {
            $counter = 0;
        }

        $id = $this->toHexWithLength(time(), self::HEX_LENGTH_TIMESTAMP);
        $id .= $this->toHexWithLength(crc32(self::getHostname()), self::HEX_LENGTH_HOSTNAME);
        $id .= $this->toHexWithLength(getmypid(), self::HEX_LENGTH_PID);
        $id .= $this->toHexWithLength(++$counter, self::HEX_LENGTH_INCREMENT);

        return $id;
    }

    /**
     * @param int $value
     * @param int $length
     * @return string
     */
    private function toHexWithLength($value, $length)
    {
        return substr(str_pad(dechex($value), $length, '0', STR_PAD_LEFT), 0, $length);
    }

    /**
     * @return string
     */
    public static function getHostname()
    {
        return gethostname();
    }

    /**
     * @return int
     */
    public function getInc()
    {
        return hexdec(substr($this->id, self::HEX_LENGTH_TIMESTAMP + self::HEX_LENGTH_HOSTNAME + self::HEX_LENGTH_PID, self::HEX_LENGTH_INCREMENT));
    }

    /**
     * @return int
     */
    public function getPID()
    {
        return hexdec(substr($this->id, self::HEX_LENGTH_TIMESTAMP + self::HEX_LENGTH_HOSTNAME, self::HEX_LENGTH_PID));
    }

    /**
     * @return int
     */
    public function getTimestamp()
    {
        return hexdec(substr($this->id, 0, self::HEX_LENGTH_TIMESTAMP));
    }

    /**
     * @param string $value
     * @return bool
     */
    public static function isValid($value)
    {
        if(strlen($value) !== 24 || !ctype_xdigit($value)) {
            return false;
        }

        return true;
    }

    /**
     * @param array $props
     * @return MongoId
     */
    public static function __set_state(array $props)
    {
        return new static("000000000000000000000000");
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function serialize()
    {
        return $this->id;
    }

    /**
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        if(!self::isValid($serialized)) {
            throw new \InvalidArgumentException(sprintf('Invalid id: %s', $serialized));
        }
        $this->id = $serialized;
    }
}