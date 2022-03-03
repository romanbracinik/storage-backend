<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: proto/bucket.proto

namespace Keboola\StorageDriver\Command\Bucket;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>keboola.storageDriver.command.bucket.DropBucketCommand</code>
 */
class DropBucketCommand extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>string bucketObjectName = 1;</code>
     */
    protected $bucketObjectName = '';
    /**
     * Generated from protobuf field <code>bool ignoreErrors = 2;</code>
     */
    protected $ignoreErrors = false;
    /**
     * Generated from protobuf field <code>string projectReadOnlyRoleName = 3;</code>
     */
    protected $projectReadOnlyRoleName = '';
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
     *     @type bool $ignoreErrors
     *     @type string $projectReadOnlyRoleName
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
     * Generated from protobuf field <code>bool ignoreErrors = 2;</code>
     * @return bool
     */
    public function getIgnoreErrors()
    {
        return $this->ignoreErrors;
    }

    /**
     * Generated from protobuf field <code>bool ignoreErrors = 2;</code>
     * @param bool $var
     * @return $this
     */
    public function setIgnoreErrors($var)
    {
        GPBUtil::checkBool($var);
        $this->ignoreErrors = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>string projectReadOnlyRoleName = 3;</code>
     * @return string
     */
    public function getProjectReadOnlyRoleName()
    {
        return $this->projectReadOnlyRoleName;
    }

    /**
     * Generated from protobuf field <code>string projectReadOnlyRoleName = 3;</code>
     * @param string $var
     * @return $this
     */
    public function setProjectReadOnlyRoleName($var)
    {
        GPBUtil::checkString($var, True);
        $this->projectReadOnlyRoleName = $var;

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

