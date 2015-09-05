<?php

namespace Saxulum\Tests\MongoId;

use Saxulum\MongoId\MongoId;

class MongoIdTest extends \PHPUnit_Framework_TestCase
{
    const SAMPLE_ID = '507f1f77bcf86cd799439011';

    public function testConstructNull()
    {
        $id = new MongoId();

        $this->assertEquals(1, $id->getInc());
        $this->assertEquals(getmypid(), $id->getPID());
    }

    public function testConstructString()
    {
        if (extension_loaded('mongo')) {
            $this->markTestSkipped('Mongo extension is loaded, can\'t test alias.');

            return;
        }

        $id = new MongoId(self::SAMPLE_ID);
        $mongoId = new \MongoId(self::SAMPLE_ID);

        $this->assertEquals($mongoId->getInc(), $id->getInc());
        $this->assertEquals($mongoId->getPID(), $id->getPID());
        $this->assertEquals($mongoId->getTimestamp(), $id->getTimestamp());
    }

    public function testConstructWithMongoId()
    {
        $origId = new MongoId(self::SAMPLE_ID);
        $id = new MongoId($origId);

        $this->assertEquals($origId->getInc(), $id->getInc());
        $this->assertEquals($origId->getPID(), $id->getPID());
        $this->assertEquals($origId->getTimestamp(), $id->getTimestamp());
    }

    public function testMultipleConstruct()
    {
        $this->assertNotEquals((string) new MongoId(), (string) new MongoId());
    }

    public function testSetState()
    {
        $id = new MongoId(self::SAMPLE_ID);

        $export = var_export($id, true);
        $exportedId = null;
        eval('$exportedId = '.$export.';');

        $this->assertEquals($id, $exportedId);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetStateInvalid()
    {
        MongoId::__set_state(array());
    }

    public function testSerializeAndUnserialize()
    {
        $id = new MongoId(self::SAMPLE_ID);

        $this->assertEquals('C:23:"Saxulum\MongoId\MongoId":24:{'.self::SAMPLE_ID.'}', serialize($id));
        $this->assertEquals($id, unserialize('C:23:"Saxulum\MongoId\MongoId":24:{'.self::SAMPLE_ID.'}'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testUnserializeWithInvalidData()
    {
        unserialize('C:23:"Saxulum\MongoId\MongoId":4:{aaaa}');
    }

    public function testToString()
    {
        $id = new MongoId(self::SAMPLE_ID);

        $this->assertEquals(self::SAMPLE_ID, (string) $id);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testWrongLengthId()
    {
        new MongoId('000');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testNonHexId()
    {
        new MongoId('zzzzzzzzzzzzzzzzzzzzzzzz');
    }
}
