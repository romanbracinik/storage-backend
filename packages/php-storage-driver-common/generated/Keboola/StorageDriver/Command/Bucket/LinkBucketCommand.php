<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: proto/bucket.proto

namespace Keboola\StorageDriver\Command\Bucket;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>keboola.storageDriver.command.bucket.LinkBucketCommand</code>
 */
class LinkBucketCommand extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>string bucketObjectName = 1;</code>
     */
    protected $bucketObjectName = '';
    /**
     * Generated from protobuf field <code>string sourceShareRole = 2;</code>
     */
    protected $sourceShareRole = '';
    /**
     * Generated from protobuf field <code>string projectReadOnlyRole = 3;</code>
     */
    protected $projectReadOnlyRole = '';
    /**
     * Generated from protobuf field <code>.google.protobuf.Any meta = 4;</code>
     */
    protected $meta = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $bucketObjectName
     *     @type string $sourceShareRole
     *     @type string $projectReadOnlyRole
     *     @type \Google\Protobuf\Any $meta
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Proto\Bucket::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>string bucketObjectName = 1;</code>
     * @return string
     */
    public function getBucketObjectName()
    {
        return $this->bucketObjectName;
    }

    /**
     * Generated from protobuf field <code>string bucketObjectName = 1;</code>
     * @param string $var
     * @return $this
     */
    public function setBucketObjectName($var)
    {
        GPBUtil::checkString($var, True);
        $this->bucketObjectName = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>string sourceShareRole = 2;</code>
     * @return string
     */
    public function getSourceShareRole()
    {
        return $this->sourceShareRole;
    }

    /**
     * Generated from protobuf field <code>string sourceShareRole = 2;</code>
     * @param string $var
     * @return $this
     */
    public function setSourceShareRole($var)
    {
        GPBUtil::checkString($var, True);
        $this->sourceShareRole = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>string projectReadOnlyRole = 3;</code>
     * @return string
     */
    public function getProjectReadOnlyRole()
    {
        return $this->projectReadOnlyRole;
    }

    /**
     * Generated from protobuf field <code>string projectReadOnlyRole = 3;</code>
     * @param string $var
     * @return $this
     */
    public function setProjectReadOnlyRole($var)
    {
        GPBUtil::checkString($var, True);
        $this->projectReadOnlyRole = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>.google.protobuf.Any meta = 4;</code>
     * @return \Google\Protobuf\Any
     */
    public function getMeta()
    {
        return $this->meta;
    }

    /**
     * Generated from protobuf field <code>.google.protobuf.Any meta = 4;</code>
     * @param \Google\Protobuf\Any $var
     * @return $this
     */
    public function setMeta($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\Any::class);
        $this->meta = $var;

        return $this;
    }

}

