<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: proto/table.proto

namespace Keboola\StorageDriver\Command\Table\TableExportToFileCommand\TeradataTableExportMeta;

use UnexpectedValueException;

/**
 * Protobuf type <code>keboola.storageDriver.command.table.TableExportToFileCommand.TeradataTableExportMeta.ExportAdapter</code>
 */
class ExportAdapter
{
    /**
     * Generated from protobuf enum <code>TPT = 0;</code>
     */
    const TPT = 0;

    private static $valueToName = [
        self::TPT => 'TPT',
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

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ExportAdapter::class, \Keboola\StorageDriver\Command\Table\TableExportToFileCommand_TeradataTableExportMeta_ExportAdapter::class);

