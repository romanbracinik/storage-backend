<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: proto/info.proto

namespace Keboola\StorageDriver\Command\Info;

use UnexpectedValueException;

/**
 **
 * List of known object types
 * unknown objects will return exception
 *
 * Protobuf type <code>keboola.storageDriver.command.info.ObjectType</code>
 */
class ObjectType
{
    /**
     * Generated from protobuf enum <code>DATABASE = 0;</code>
     */
    const DATABASE = 0;
    /**
     * Generated from protobuf enum <code>SCHEMA = 1;</code>
     */
    const SCHEMA = 1;
    /**
     * Generated from protobuf enum <code>TABLE = 2;</code>
     */
    const TABLE = 2;
    /**
     * Generated from protobuf enum <code>VIEW = 3;</code>
     */
    const VIEW = 3;

    private static $valueToName = [
        self::DATABASE => 'DATABASE',
        self::SCHEMA => 'SCHEMA',
        self::TABLE => 'TABLE',
        self::VIEW => 'VIEW',
    ];

    public static function name($value)
    {
        if (!isset(self::$valueToName[$value])) {
            throw new UnexpectedValueException(sprintf(
                    'Enum %s has no name defined for value %s', __CLASS__, $value));
        }
        return self::$valueToName[$value];
    }


    public static function value($name)
    {
        $const = __CLASS__ . '::' . strtoupper($name);
        if (!defined($const)) {
            throw new UnexpectedValueException(sprintf(
                    'Enum %s has no value defined for name %s', __CLASS__, $name));
        }
        return constant($const);
    }
}

