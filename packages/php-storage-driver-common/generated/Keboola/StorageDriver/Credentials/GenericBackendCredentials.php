<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: proto/credentials.proto

namespace Keboola\StorageDriver\Credentials;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 **
 * Generic credentials object used to establish connection to backend
 * contains only common properties all backend specific properties are meant to be used as metadata
 *
 * Generated from protobuf message <code>keboola.storageDriver.credentials.GenericBackendCredentials</code>
 */
class GenericBackendCredentials extends \Google\Protobuf\Internal\Message
{
    /**
     * host name. Example vendor.snowflakecomputing.com
     *
     * Generated from protobuf field <code>string host = 1;</code>
     */
    protected $host = '';
    /**
     * user name
     *
     * Generated from protobuf field <code>string principal = 2;</code>
     */
    protected $principal = '';
    /**
     * password or token
     *
     * Generated from protobuf field <code>string secret = 3;</code>
     */
    protected $secret = '';
    /**
     * port for database
     *
     * Generated from protobuf field <code>uint32 port = 4;</code>
     */
    protected $port = 0;
    /**
     * metadata specific for each backend
     *
     * Generated from protobuf field <code>.google.protobuf.Any meta = 5;</code>
     */
    protected $meta = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $host
     *           host name. Example vendor.snowflakecomputing.com
     *     @type string $principal
     *           user name
     *     @type string $secret
     *           password or token
     *     @type int $port
     *           port for database
     *     @type \Google\Protobuf\Any $meta
     *           metadata specific for each backend
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Proto\Credentials::initOnce();
        parent::__construct($data);
    }

    /**
     * host name. Example vendor.snowflakecomputing.com
     *
     * Generated from protobuf field <code>string host = 1;</code>
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * host name. Example vendor.snowflakecomputing.com
     *
     * Generated from protobuf field <code>string host = 1;</code>
     * @param string $var
     * @return $this
     */
    public function setHost($var)
    {
        GPBUtil::checkString($var, True);
        $this->host = $var;

        return $this;
    }

    /**
     * user name
     *
     * Generated from protobuf field <code>string principal = 2;</code>
     * @return string
     */
    public function getPrincipal()
    {
        return $this->principal;
    }

    /**
     * user name
     *
     * Generated from protobuf field <code>string principal = 2;</code>
     * @param string $var
     * @return $this
     */
    public function setPrincipal($var)
    {
        GPBUtil::checkString($var, True);
        $this->principal = $var;

        return $this;
    }

    /**
     * password or token
     *
     * Generated from protobuf field <code>string secret = 3;</code>
     * @return string
     */
    public function getSecret()
    {
        return $this->secret;
    }

    /**
     * password or token
     *
     * Generated from protobuf field <code>string secret = 3;</code>
     * @param string $var
     * @return $this
     */
    public function setSecret($var)
    {
        GPBUtil::checkString($var, True);
        $this->secret = $var;

        return $this;
    }

    /**
     * port for database
     *
     * Generated from protobuf field <code>uint32 port = 4;</code>
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * port for database
     *
     * Generated from protobuf field <code>uint32 port = 4;</code>
     * @param int $var
     * @return $this
     */
    public function setPort($var)
    {
        GPBUtil::checkUint32($var);
        $this->port = $var;

        return $this;
    }

    /**
     * metadata specific for each backend
     *
     * Generated from protobuf field <code>.google.protobuf.Any meta = 5;</code>
     * @return \Google\Protobuf\Any|null
     */
    public function getMeta()
    {
        return $this->meta;
    }

    public function hasMeta()
    {
        return isset($this->meta);
    }

    public function clearMeta()
    {
        unset($this->meta);
    }

    /**
     * metadata specific for each backend
     *
     * Generated from protobuf field <code>.google.protobuf.Any meta = 5;</code>
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

