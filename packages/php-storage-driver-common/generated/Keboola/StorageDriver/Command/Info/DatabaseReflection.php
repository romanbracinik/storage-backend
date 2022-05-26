<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: proto/info.proto

namespace Keboola\StorageDriver\Command\Info;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>keboola.storageDriver.command.info.DatabaseReflection</code>
 */
class DatabaseReflection extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>repeated .keboola.storageDriver.command.info.InternalObject objects = 1;</code>
     */
    private $objects;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Keboola\StorageDriver\Command\Info\InternalObject[]|\Google\Protobuf\Internal\RepeatedField $objects
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Proto\Info::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>repeated .keboola.storageDriver.command.info.InternalObject objects = 1;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getObjects()
    {
        return $this->objects;
    }

    /**
     * Generated from protobuf field <code>repeated .keboola.storageDriver.command.info.InternalObject objects = 1;</code>
     * @param \Keboola\StorageDriver\Command\Info\InternalObject[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setObjects($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Keboola\StorageDriver\Command\Info\InternalObject::class);
        $this->objects = $arr;

        return $this;
    }

}

