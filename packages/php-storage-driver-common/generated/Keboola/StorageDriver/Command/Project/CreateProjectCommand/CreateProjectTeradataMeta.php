<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: proto/project.proto

namespace Keboola\StorageDriver\Command\Project\CreateProjectCommand;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>keboola.storageDriver.command.project.CreateProjectCommand.CreateProjectTeradataMeta</code>
 */
class CreateProjectTeradataMeta extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>string rootDatabase = 1;</code>
     */
    protected $rootDatabase = '';

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $rootDatabase
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Proto\Project::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>string rootDatabase = 1;</code>
     * @return string
     */
    public function getRootDatabase()
    {
        return $this->rootDatabase;
    }

    /**
     * Generated from protobuf field <code>string rootDatabase = 1;</code>
     * @param string $var
     * @return $this
     */
    public function setRootDatabase($var)
    {
        GPBUtil::checkString($var, True);
        $this->rootDatabase = $var;

        return $this;
    }

}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(CreateProjectTeradataMeta::class, \Keboola\StorageDriver\Command\Project\CreateProjectCommand_CreateProjectTeradataMeta::class);

