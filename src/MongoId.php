<?php

namespace Saxulum\MongoId;

class MongoId implements \Serializable
{
    /**
     * @var string
     */
    private $id;

    /**
     * @param MongoId|string|null $id
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($id = null)
    {
        if ($id instanceof self) {
            $this->id = $id->id;

            return;
        }

        if (null !== $id) {
            if (!self::isValid($id)) {
                throw new \InvalidArgumentException(sprintf('Invalid id: %s', $id));
            }
            $this->id = $id;

            return;
        }

        $this->id = $this->createId();
    }

    /**
     * @param \DateTime $dateTime
     *
     * @return object
     */
    public static function createByDateTime(\DateTime $dateTime)
    {
        $reflectionClass = new \ReflectionClass(__CLASS__);
        $object = $reflectionClass->newInstanceWithoutConstructor();
        $object->id = $object->createId($dateTime->format('U'));

        return $object;
    }

    /**
     * @param int|null $time
     *
     * @return string
     */
    private function createId($time = null)
    {
        $pid = getmypid();
        $inc = $this->readInc($pid);

        $id = $this->intToMaxLengthHex(null !== $time ? (int) $time : time(), 8);
        $id .= $this->intToMaxLengthHex(crc32(self::getHostname()), 6);
        $id .= $this->intToMaxLengthHex($pid, 4);
        $id .= $this->intToMaxLengthHex($inc, 6);

        return $id;
    }

    /**
     * @param int $pid
     *
     * @return int
     */
    private function readInc($pid)
    {
        $res = shm_attach($pid);
        if (!shm_has_var($res, 0)) {
            shm_put_var($res, 0, 0);
        }

        $inc = shm_get_var($res, 0);
        if ($inc === 16777215) {
            $inc = 0;
        }

        ++$inc;

        shm_put_var($res, 0, $inc);

        return $inc;
    }

    /**
     * @param int $value
     * @param int $length
     *
     * @return string
     */
    private function intToMaxLengthHex($value, $length)
    {
        return str_pad(substr(dechex($value), 0, $length), $length, '0', STR_PAD_LEFT);
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
        return hexdec(substr($this->id, 18, 6));
    }

    /**
     * @return int
     */
    public function getPID()
    {
        $pid = hexdec(substr($this->id, 14, 4));

        return $pid;
    }

    /**
     * @return int
     */
    public function getTimestamp()
    {
        return hexdec(substr($this->id, 0, 8));
    }

    /**
     * @param string $value
     *
     * @return bool
     */
    public static function isValid($value)
    {
        if (strlen($value) !== 24) {
            return false;
        }

        if (strspn($value, '0123456789abcdefABCDEF') !== 24) {
            return false;
        }

        return true;
    }

    /**
     * @param array $props
     *
     * @return static
     *
     * @throws \InvalidArgumentException
     */
    public static function __set_state(array $props)
    {
        if (!isset($props['id'])) {
            throw new \InvalidArgumentException('There is no id key within props array!');
        }

        return new static($props['id']);
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
     *
     * @throws \InvalidArgumentException
     */
    public function unserialize($serialized)
    {
        if (!self::isValid($serialized)) {
            throw new \InvalidArgumentException(sprintf('Invalid id: %s', $serialized));
        }
        $this->id = $serialized;
    }
}
