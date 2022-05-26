<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: proto/info.proto

namespace Keboola\StorageDriver\Command\Info;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>keboola.storageDriver.command.info.ObjectResponse</code>
 */
class ObjectResponse extends \Google\Protobuf\Internal\Message
{
    /**
     * type of object inspected
     *
     * Generated from protobuf field <code>.keboola.storageDriver.command.info.ObjectResponse.ObjectType objectType = 1;</code>
     */
    protected $objectType = 0;
    /**
     **
     * object information's
     *
     * Generated from protobuf field <code>.google.protobuf.Any objectReflection = 2;</code>
     */
    protected $objectReflection = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type int $objectType
     *           type of object inspected
     *     @type \Google\Protobuf\Any $objectReflection
     *          *
     *           object information's
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Proto\Info::initOnce();
        parent::__construct($data);
    }

    /**
     * type of object inspected
     *
     * Generated from protobuf field <code>.keboola.storageDriver.command.info.ObjectResponse.ObjectType objectType = 1;</code>
     * @return int
     */
    public function getObjectType()
    {
        return $this->objectType;
    }

    /**
     * type of object inspected
     *
     * Generated from protobuf field <code>.keboola.storageDriver.command.info.ObjectResponse.ObjectType objectType = 1;</code>
     * @param int $var
     * @return $this
     */
    public function setObjectType($var)
    {
        GPBUtil::checkEnum($var, \Keboola\StorageDriver\Command\Info\ObjectResponse\ObjectType::class);
        $this->objectType = $var;

        return $this;
    }

    /**
     **
     * object information's
     *
     * Generated from protobuf field <code>.google.protobuf.Any objectReflection = 2;</code>
     * @return \Google\Protobuf\Any|null
     */
    public function getObjectReflection()
    {
        return $this->objectReflection;
    }

    public function hasObjectReflection()
    {
        return isset($this->objectReflection);
    }

    public function clearObjectReflection()
    {
        unset($this->objectReflection);
    }

    /**
     **
     * object information's
     *
     * Generated from protobuf field <code>.google.protobuf.Any objectReflection = 2;</code>
     * @param \Google\Protobuf\Any $var
     * @return $this
     */
    public function setObjectReflection($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\Any::class);
        $this->objectReflection = $var;

        return $this;
    }

}

