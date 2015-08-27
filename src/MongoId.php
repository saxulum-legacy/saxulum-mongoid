<?php

namespace Saxulum\MongoId;

class MongoId implements \Serializable
{
    /**
     * @var string
     */
    private $id;

    const BYTE_MAX_NUMBER_2 = 65535;
    const BYTE_MAX_NUMBER_3 = 16777215;
    const BYTE_MAX_NUMBER_4 = 4294967295;

    /**
     * @param MongoId|string|null $id
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
     * @return string
     */
    private function createId()
    {
        static $counter;

        if (null === $counter) {
            $counter = 0;
        }

        $id = $this->toHexWithLength($this->checkMaxIntSize(time(), self::BYTE_MAX_NUMBER_4), 8);
        $id .= $this->toHexWithLength($this->checkMaxIntSize($this->checksum(self::getHostname()), self::BYTE_MAX_NUMBER_3), 6);
        $id .= $this->toHexWithLength($this->checkMaxIntSize(getmypid(), self::BYTE_MAX_NUMBER_2), 4);
        $id .= $this->toHexWithLength($this->checkMaxIntSize(++$counter, self::BYTE_MAX_NUMBER_3), 6);

        return $id;
    }

    /**
     * @param int $value
     * @param int $length
     *
     * @return string
     */
    private function toHexWithLength($value, $length)
    {
        return str_pad(dechex($value), $length, '0', STR_PAD_LEFT);
    }

    /**
     * @param int$value
     * @param int $maxInteger
     *
     * @return int
     */
    private function checkMaxIntSize($value, $maxInteger)
    {
        return $value > $maxInteger ? $maxInteger : $value;
    }

    /**
     * @param string $value
     *
     * @return int
     *
     * @throws \Exception
     */
    private function checksum($value)
    {
        $sha1 = strtolower(sha1($value));

        $mapping = array(
            'a' => 10,
            'b' => 11,
            'c' => 12,
            'd' => 13,
            'e' => 14,
            'f' => 15,
        );

        $checksum = 0;
        $length = strlen($sha1);
        for ($i = 0; $i < $length; ++$i) {
            $sign = $sha1[$i];

            if (is_numeric($sign)) {
                $checksum += (int) $sign;
                continue;
            }

            $checksum += $mapping[$sign];
        }

        return $checksum;
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
     * @return MongoId
     */
    public static function __set_state(array $props)
    {
        return new static('000000000000000000000000');
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
        if (!self::isValid($serialized)) {
            throw new \InvalidArgumentException(sprintf('Invalid id: %s', $serialized));
        }
        $this->id = $serialized;
    }
}
