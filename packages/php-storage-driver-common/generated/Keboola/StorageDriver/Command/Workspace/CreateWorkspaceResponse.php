<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: proto/workspace.proto

namespace Keboola\StorageDriver\Command\Workspace;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 **
 * Response contain all resources names created by create workspace command
 *
 * Generated from protobuf message <code>keboola.storageDriver.command.workspace.CreateWorkspaceResponse</code>
 */
class CreateWorkspaceResponse extends \Google\Protobuf\Internal\Message
{
    /**
     * newly created user name associated with workspace
     *
     * Generated from protobuf field <code>string workspaceUserName = 1;</code>
     */
    protected $workspaceUserName = '';
    /**
     * newly created role name associated with workspace
     *
     * Generated from protobuf field <code>string workspaceRoleName = 2;</code>
     */
    protected $workspaceRoleName = '';
    /**
     * workspace user password
     *
     * Generated from protobuf field <code>string workspacePassword = 3;</code>
     */
    protected $workspacePassword = '';
    /**
     * resulting object name actually stored in backend
     *
     * Generated from protobuf field <code>string workspaceObjectName = 4;</code>
     */
    protected $workspaceObjectName = '';

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $workspaceUserName
     *           newly created user name associated with workspace
     *     @type string $workspaceRoleName
     *           newly created role name associated with workspace
     *     @type string $workspacePassword
     *           workspace user password
     *     @type string $workspaceObjectName
     *           resulting object name actually stored in backend
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Proto\Workspace::initOnce();
        parent::__construct($data);
    }

    /**
     * newly created user name associated with workspace
     *
     * Generated from protobuf field <code>string workspaceUserName = 1;</code>
     * @return string
     */
    public function getWorkspaceUserName()
    {
        return $this->workspaceUserName;
    }

    /**
     * newly created user name associated with workspace
     *
     * Generated from protobuf field <code>string workspaceUserName = 1;</code>
     * @param string $var
     * @return $this
     */
    public function setWorkspaceUserName($var)
    {
        GPBUtil::checkString($var, True);
        $this->workspaceUserName = $var;

        return $this;
    }

    /**
     * newly created role name associated with workspace
     *
     * Generated from protobuf field <code>string workspaceRoleName = 2;</code>
     * @return string
     */
    public function getWorkspaceRoleName()
    {
        return $this->workspaceRoleName;
    }

    /**
     * newly created role name associated with workspace
     *
     * Generated from protobuf field <code>string workspaceRoleName = 2;</code>
     * @param string $var
     * @return $this
     */
    public function setWorkspaceRoleName($var)
    {
        GPBUtil::checkString($var, True);
        $this->workspaceRoleName = $var;

        return $this;
    }

    /**
     * workspace user password
     *
     * Generated from protobuf field <code>string workspacePassword = 3;</code>
     * @return string
     */
    public function getWorkspacePassword()
    {
        return $this->workspacePassword;
    }

    /**
     * workspace user password
     *
     * Generated from protobuf field <code>string workspacePassword = 3;</code>
     * @param string $var
     * @return $this
     */
    public function setWorkspacePassword($var)
    {
        GPBUtil::checkString($var, True);
        $this->workspacePassword = $var;

        return $this;
    }

    /**
     * resulting object name actually stored in backend
     *
     * Generated from protobuf field <code>string workspaceObjectName = 4;</code>
     * @return string
     */
    public function getWorkspaceObjectName()
    {
        return $this->workspaceObjectName;
    }

    /**
     * resulting object name actually stored in backend
     *
     * Generated from protobuf field <code>string workspaceObjectName = 4;</code>
     * @param string $var
     * @return $this
     */
    public function setWorkspaceObjectName($var)
    {
        GPBUtil::checkString($var, True);
        $this->workspaceObjectName = $var;

        return $this;
    }

}

