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
        $id .= $this->toHexWithLength($this->checkMaxIntSize(getmypid(), self::BYTE_MAX_NUMBER_2), 2);
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
     */
    private function checksum($value)
    {
        $mapping = array(
            'a' => 1000,
            'b' => 1100,
            'c' => 1200,
            'd' => 1300,
            'e' => 1400,
            'f' => 1500,
            'g' => 1600,
            'h' => 1700,
            'i' => 1800,
            'j' => 1900,
            'k' => 2000,
            'l' => 2100,
            'm' => 2200,
            'n' => 2300,
            'o' => 2400,
            'p' => 2500,
            'q' => 2600,
            'r' => 2700,
            's' => 2800,
            't' => 2900,
            'u' => 3000,
            'v' => 3100,
            'w' => 3200,
            'x' => 3300,
            'y' => 3400,
            'z' => 3600,
        );

        $checksum = 0;
        $length = strlen($value);
        for ($i = 0; $i < $length; ++$i) {
            $sign = $value[$i];
            if (is_numeric($sign)) {
                $checksum += (int) $sign * 10;
            } else {
                if(!isset($mapping[$sign])) {
                    throw new \Exception(sprintf('Can\'t find mapping for char %s', $sign));
                }
                $checksum += $mapping[$sign];
            }
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
