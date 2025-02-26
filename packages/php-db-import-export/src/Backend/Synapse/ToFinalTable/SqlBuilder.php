<?php

declare(strict_types=1);

namespace Keboola\Db\ImportExport\Backend\Synapse\ToFinalTable;

use Keboola\Datatype\Definition\BaseType;
use Keboola\Datatype\Definition\Synapse;
use Keboola\Db\Import\Exception;
use Keboola\Db\ImportExport\Backend\Synapse\SynapseImportOptions;
use Keboola\Db\ImportExport\Backend\ToStageImporterInterface;
use Keboola\Db\ImportExport\Utils\StringCaseSensitivity;
use Keboola\TableBackendUtils\Column\SynapseColumn;
use Keboola\TableBackendUtils\Escaping\SynapseQuote;
use Keboola\TableBackendUtils\Table\Synapse\TableIndexDefinition;
use Keboola\TableBackendUtils\Table\SynapseTableDefinition;

class SqlBuilder
{
    public function getBeginTransaction(): string
    {
        return 'BEGIN TRANSACTION';
    }

    public function getCommitTransaction(): string
    {
        return 'COMMIT';
    }

    /**
     * @param string[] $primaryKeys
     */
    public function getDedupCommand(
        SynapseTableDefinition $stagingTableDefinition,
        SynapseTableDefinition $deduplicationTableDefinition,
        array $primaryKeys
    ): string {
        if (empty($primaryKeys)) {
            return '';
        }

        $pkSql = $this->getColumnsString(
            $primaryKeys,
            ','
        );

        $stage = sprintf(
            '%s.%s',
            SynapseQuote::quoteSingleIdentifier($stagingTableDefinition->getSchemaName()),
            SynapseQuote::quoteSingleIdentifier($stagingTableDefinition->getTableName())
        );

        $depudeSql = sprintf(
            'SELECT %s FROM ('
            . 'SELECT %s, ROW_NUMBER() OVER (PARTITION BY %s ORDER BY %s) AS "_row_number_" '
            . 'FROM %s'
            . ') AS a '
            . 'WHERE a."_row_number_" = 1',
            $this->getColumnsString($deduplicationTableDefinition->getColumnsNames(), ',', 'a'),
            $this->getColumnsString($deduplicationTableDefinition->getColumnsNames(), ', '),
            $pkSql,
            $pkSql,
            $stage
        );

        $deduplication = sprintf(
            '%s.%s',
            SynapseQuote::quoteSingleIdentifier($deduplicationTableDefinition->getSchemaName()),
            SynapseQuote::quoteSingleIdentifier($deduplicationTableDefinition->getTableName())
        );

        return sprintf(
            'INSERT INTO %s (%s) %s',
            $deduplication,
            $this->getColumnsString($deduplicationTableDefinition->getColumnsNames()),
            $depudeSql
        );
    }

    public function getCtasDedupCommand(
        SynapseTableDefinition $stagingTableDefinition,
        SynapseTableDefinition $destinationTableDefinition,
        SynapseImportOptions $importOptions,
        string $timestamp
    ): string {
        if (empty($destinationTableDefinition->getPrimaryKeysNames())) {
            return '';
        }
        $distributionSql = $this->getSqlDistributionPart($destinationTableDefinition);
        $indexSql = $this->getSqlIndexPart($destinationTableDefinition);

        $pkSql = $this->getColumnsString(
            $destinationTableDefinition->getPrimaryKeysNames(),
            ','
        );

        $columnsInOrder = $destinationTableDefinition->getColumnsNames();
        $timestampColIndex = array_search(
            ToStageImporterInterface::TIMESTAMP_COLUMN_NAME,
            $columnsInOrder,
            true
        );
        if ($timestampColIndex !== false) {
            // remove timestamp column if exists in ordered columns
            unset($columnsInOrder[$timestampColIndex]);
        }

        $timestampNotInColumns = !StringCaseSensitivity::isInArrayCaseInsensitive(
            ToStageImporterInterface::TIMESTAMP_COLUMN_NAME,
            $stagingTableDefinition->getColumnsNames()
        );
        $useTimestamp = $timestampNotInColumns && $importOptions->useTimestamp();

        if ($useTimestamp) {
            $columnsInOrder[] = ToStageImporterInterface::TIMESTAMP_COLUMN_NAME;
        }
        $createTableColumns = $this->getColumnsString($columnsInOrder, ',', 'a');

        $columnsSetSql = $this->getCTASColumnsSetSql(
            $stagingTableDefinition,
            $destinationTableDefinition,
            $importOptions
        );

        if ($useTimestamp) {
            $columnsSetSql[] = sprintf(
                'CAST(%s as %s) AS %s',
                SynapseQuote::quote($timestamp),
                Synapse::TYPE_DATETIME2,
                SynapseQuote::quoteSingleIdentifier(ToStageImporterInterface::TIMESTAMP_COLUMN_NAME)
            );
        }

        $depudeSql = sprintf(
            'SELECT %s FROM ('
            . 'SELECT %s, ROW_NUMBER() OVER (PARTITION BY %s ORDER BY %s) AS "_row_number_" '
            . 'FROM %s.%s'
            . ') AS a '
            . 'WHERE a."_row_number_" = 1',
            $createTableColumns,
            implode(',', $columnsSetSql),
            $pkSql,
            $pkSql,
            SynapseQuote::quoteSingleIdentifier($stagingTableDefinition->getSchemaName()),
            SynapseQuote::quoteSingleIdentifier($stagingTableDefinition->getTableName())
        );

        return sprintf(
            'CREATE TABLE %s.%s WITH (%s,%s) AS %s',
            SynapseQuote::quoteSingleIdentifier($destinationTableDefinition->getSchemaName()),
            SynapseQuote::quoteSingleIdentifier($destinationTableDefinition->getTableName()),
            $distributionSql,
            $indexSql,
            $depudeSql
        );
    }

    /**
     * @param string[] $columns
     */
    public function getColumnsString(
        array $columns,
        string $delimiter = ', ',
        ?string $tableAlias = null
    ): string {
        return implode($delimiter, array_map(function ($columns) use (
            $tableAlias
        ) {
            $alias = $tableAlias === null ? '' : $tableAlias . '.';
            return $alias . SynapseQuote::quoteSingleIdentifier($columns);
        }, $columns));
    }

    public function getDeleteOldItemsCommand(
        SynapseTableDefinition $stagingTableDefinition,
        SynapseTableDefinition $destinationTableDefinition
    ): string {
        $stagingTable = sprintf(
            '%s.%s',
            SynapseQuote::quoteSingleIdentifier($stagingTableDefinition->getSchemaName()),
            SynapseQuote::quoteSingleIdentifier($stagingTableDefinition->getTableName())
        );

        $destinationTable = sprintf(
            '%s.%s',
            SynapseQuote::quoteSingleIdentifier($destinationTableDefinition->getSchemaName()),
            SynapseQuote::quoteSingleIdentifier($destinationTableDefinition->getTableName())
        );

        return sprintf(
            'DELETE %s WHERE EXISTS (SELECT * FROM %s WHERE %s)',
            $stagingTable,
            $destinationTable,
            $this->getPrimaryKeyWhereConditions(
                $destinationTableDefinition->getPrimaryKeysNames(),
                $stagingTable,
                $destinationTable
            )
        );
    }

    /**
     * @param string[] $primaryKeys
     */
    private function getPrimaryKeyWhereConditions(
        array $primaryKeys,
        string $sourceTable,
        string $destinationTable
    ): string {
        $pkWhereSql = array_map(function (string $col) use ($sourceTable, $destinationTable) {
            return sprintf(
                '%s.%s = %s.%s',
                $destinationTable,
                SynapseQuote::quoteSingleIdentifier($col),
                $sourceTable,
                SynapseQuote::quoteSingleIdentifier($col)
            );
        }, $primaryKeys);

        return rtrim(implode(' AND ', $pkWhereSql) . ' ');
    }

    public function getDropCommand(
        string $schema,
        string $tableName
    ): string {
        return sprintf(
            'DROP TABLE %s.%s',
            SynapseQuote::quoteSingleIdentifier($schema),
            SynapseQuote::quoteSingleIdentifier($tableName)
        );
    }

    public function getDropTableIfExistsCommand(
        string $schema,
        string $tableName
    ): string {
        $table = sprintf(
            '%s.%s',
            SynapseQuote::quoteSingleIdentifier($schema),
            SynapseQuote::quoteSingleIdentifier($tableName)
        );
        return sprintf(
            'IF OBJECT_ID (N\'%s\', N\'U\') IS NOT NULL DROP TABLE %s',
            $table,
            $table
        );
    }

    public function getInsertAllIntoTargetTableCommand(
        SynapseTableDefinition $sourceTableDefinition,
        SynapseTableDefinition $destinationTableDefinition,
        SynapseImportOptions $importOptions,
        string $timestamp
    ): string {
        $destinationTable = sprintf(
            '%s.%s',
            SynapseQuote::quoteSingleIdentifier($destinationTableDefinition->getSchemaName()),
            SynapseQuote::quoteSingleIdentifier($destinationTableDefinition->getTableName())
        );

        $insColumns = $sourceTableDefinition->getColumnsNames();
        $includesTimestamp = StringCaseSensitivity::isInArrayCaseInsensitive(
            ToStageImporterInterface::TIMESTAMP_COLUMN_NAME,
            $insColumns
        );
        $useTimestamp = !$includesTimestamp && $importOptions->useTimestamp();

        if ($useTimestamp) {
            $insColumns = array_merge(
                $sourceTableDefinition->getColumnsNames(),
                [ToStageImporterInterface::TIMESTAMP_COLUMN_NAME]
            );
        }

        $columnsSetSql = [];

        /** @var SynapseColumn $columnDefinition */
        foreach ($sourceTableDefinition->getColumnsDefinitions() as $columnDefinition) {
            $convertEmptyToNull = StringCaseSensitivity::isInArrayCaseInsensitive(
                $columnDefinition->getColumnName(),
                $importOptions->getConvertEmptyValuesToNull()
            );
            if ($convertEmptyToNull) {
                // use nullif only for string base type
                if ($columnDefinition->getColumnDefinition()->getBasetype() === BaseType::STRING) {
                    $columnsSetSql[] = sprintf(
                        'NULLIF(%s, \'\')',
                        SynapseQuote::quoteSingleIdentifier($columnDefinition->getColumnName())
                    );
                } else {
                    $columnsSetSql[] = SynapseQuote::quoteSingleIdentifier($columnDefinition->getColumnName());
                }
            } elseif ($columnDefinition->getColumnDefinition()->getBasetype() === BaseType::STRING) {
                $columnsSetSql[] = sprintf(
                    'CAST(COALESCE(%s, \'\') as %s) AS %s',
                    SynapseQuote::quoteSingleIdentifier($columnDefinition->getColumnName()),
                    $this->getColumnTypeSqlDefinition($columnDefinition),
                    SynapseQuote::quoteSingleIdentifier($columnDefinition->getColumnName())
                );
            } else {
                // on columns other than string dont use COALESCE, use direct cast
                // this will fail if the column is not null, but this is expected
                $columnsSetSql[] = sprintf(
                    'CAST(%s as %s) AS %s',
                    SynapseQuote::quoteSingleIdentifier($columnDefinition->getColumnName()),
                    $this->getColumnTypeSqlDefinition($columnDefinition),
                    SynapseQuote::quoteSingleIdentifier($columnDefinition->getColumnName())
                );
            }
        }

        if ($useTimestamp) {
            $columnsSetSql[] = SynapseQuote::quote($timestamp);
        }

        return sprintf(
            'INSERT INTO %s (%s) (SELECT %s FROM %s.%s AS [src])',
            $destinationTable,
            $this->getColumnsString($insColumns),
            implode(',', $columnsSetSql),
            SynapseQuote::quoteSingleIdentifier($sourceTableDefinition->getSchemaName()),
            SynapseQuote::quoteSingleIdentifier($sourceTableDefinition->getTableName())
        );
    }

    public function getCTASInsertAllIntoTargetTableCommand(
        SynapseTableDefinition $sourceTableDefinition,
        SynapseTableDefinition $destinationTableDefinition,
        SynapseImportOptions $importOptions,
        string $timestamp
    ): string {
        $destinationTable = sprintf(
            '%s.%s',
            SynapseQuote::quoteSingleIdentifier($destinationTableDefinition->getSchemaName()),
            SynapseQuote::quoteSingleIdentifier($destinationTableDefinition->getTableName())
        );

        $useTimestamp = !StringCaseSensitivity::isInArrayCaseInsensitive(
            ToStageImporterInterface::TIMESTAMP_COLUMN_NAME,
            $sourceTableDefinition->getColumnsNames()
        ) && $importOptions->useTimestamp();

        $columnsSetSql = $this->getCTASColumnsSetSql(
            $sourceTableDefinition,
            $destinationTableDefinition,
            $importOptions
        );

        if ($useTimestamp) {
            $columnsSetSql[] = sprintf(
                '%s AS %s',
                SynapseQuote::quote($timestamp),
                ToStageImporterInterface::TIMESTAMP_COLUMN_NAME
            );
        }

        $distributionSql = $this->getSqlDistributionPart($destinationTableDefinition);
        $indexSql = $this->getSqlIndexPart($destinationTableDefinition);

        return sprintf(
            'CREATE TABLE %s WITH (%s,%s) AS SELECT %s FROM %s.%s',
            $destinationTable,
            $distributionSql,
            $indexSql,
            implode(',', $columnsSetSql),
            SynapseQuote::quoteSingleIdentifier($sourceTableDefinition->getSchemaName()),
            SynapseQuote::quoteSingleIdentifier($sourceTableDefinition->getTableName())
        );
    }

    public function getRenameTableCommand(
        string $schema,
        string $sourceTableName,
        string $targetTable
    ): string {
        return sprintf(
            'RENAME OBJECT %s.%s TO %s',
            SynapseQuote::quoteSingleIdentifier($schema),
            SynapseQuote::quoteSingleIdentifier($sourceTableName),
            SynapseQuote::quoteSingleIdentifier($targetTable)
        );
    }

    private function getSqlDistributionPart(SynapseTableDefinition $definition): string
    {
        $distributionSql = sprintf(
            'DISTRIBUTION=%s',
            $definition->getTableDistribution()->getDistributionName()
        );

        if ($definition->getTableDistribution()->isHashDistribution()) {
            $distributionSql = sprintf(
                '%s(%s)',
                $distributionSql,
                SynapseQuote::quoteSingleIdentifier(
                    $definition->getTableDistribution()->getDistributionColumnsNames()[0]
                )
            );
        }
        return $distributionSql;
    }

    private function getSqlIndexPart(SynapseTableDefinition $definition): string
    {
        if ($definition->getTableIndex()->getIndexType() === TableIndexDefinition::TABLE_INDEX_TYPE_CLUSTERED_INDEX) {
            $quotedColumns = array_map(function ($columnName) {
                return SynapseQuote::quoteSingleIdentifier($columnName);
            }, $definition->getTableIndex()->getIndexedColumnsNames());
            $indexSql = sprintf(
                '%s(%s)',
                $definition->getTableIndex()->getIndexType(),
                implode(',', $quotedColumns)
            );
        } else {
            $indexSql = $definition->getTableIndex()->getIndexType();
        }

        return $indexSql;
    }

    public function getTruncateTableWithDeleteCommand(
        string $schema,
        string $tableName
    ): string {
        return sprintf(
            'DELETE FROM %s.%s',
            SynapseQuote::quoteSingleIdentifier($schema),
            SynapseQuote::quoteSingleIdentifier($tableName)
        );
    }

    public function getUpdateWithPkCommand(
        SynapseTableDefinition $stagingTableDefinition,
        SynapseTableDefinition $destinationDefinition,
        SynapseImportOptions $importOptions,
        string $timestamp
    ): string {
        $dest = sprintf(
            '%s.%s',
            SynapseQuote::quoteSingleIdentifier($destinationDefinition->getSchemaName()),
            SynapseQuote::quoteSingleIdentifier($destinationDefinition->getTableName())
        );

        $columnsSet = [];
        /** @var SynapseColumn $columnDefinition */
        foreach ($stagingTableDefinition->getColumnsDefinitions() as $columnDefinition) {
            $isPrimaryKey = StringCaseSensitivity::isInArrayCaseInsensitive(
                $columnDefinition->getColumnName(),
                $destinationDefinition->getPrimaryKeysNames()
            );
            if ($isPrimaryKey) {
                // primary keys are not updated
                continue;
            }
            $convertEmptyToNull = StringCaseSensitivity::isInArrayCaseInsensitive(
                $columnDefinition->getColumnName(),
                $importOptions->getConvertEmptyValuesToNull()
            );
            if ($convertEmptyToNull) {
                // use nullif only for string base type
                if ($columnDefinition->getColumnDefinition()->getBasetype() === BaseType::STRING) {
                    $columnsSet[] = sprintf(
                        '%s = NULLIF([src].%s, \'\')',
                        SynapseQuote::quoteSingleIdentifier($columnDefinition->getColumnName()),
                        SynapseQuote::quoteSingleIdentifier($columnDefinition->getColumnName())
                    );
                } else {
                    $columnsSet[] = sprintf(
                        '%s = [src].%s',
                        SynapseQuote::quoteSingleIdentifier($columnDefinition->getColumnName()),
                        SynapseQuote::quoteSingleIdentifier($columnDefinition->getColumnName())
                    );
                }
            } else {
                $sql = sprintf(
                    '%s = [src].%s',
                    SynapseQuote::quoteSingleIdentifier($columnDefinition->getColumnName()),
                    SynapseQuote::quoteSingleIdentifier($columnDefinition->getColumnName())
                );

                if ($columnDefinition->getColumnDefinition()->getBasetype() === BaseType::STRING) {
                    $sql = sprintf(
                        '%s = COALESCE([src].%s, \'\')',
                        SynapseQuote::quoteSingleIdentifier($columnDefinition->getColumnName()),
                        SynapseQuote::quoteSingleIdentifier($columnDefinition->getColumnName())
                    );
                }

                $columnsSet[] = $sql;
            }
        }

        if ($importOptions->useTimestamp()) {
            $columnsSet[] = sprintf(
                '%s = \'%s\'',
                SynapseQuote::quoteSingleIdentifier(ToStageImporterInterface::TIMESTAMP_COLUMN_NAME),
                $timestamp
            );
        }

        if (count($columnsSet) === 0) {
            // if it's a table only with primary keys then there is nothing to update
            // as PK's are not updated
            return '';
        }

        // remove primary keys for columns used in where condition
        /** @var SynapseColumn[] $columnsForComparison */
        $columnsForComparison = array_filter(
            iterator_to_array($stagingTableDefinition->getColumnsDefinitions()),
            static fn(SynapseColumn $columnDefinition): bool => !StringCaseSensitivity::isInArrayCaseInsensitive(
                $columnDefinition->getColumnName(),
                $destinationDefinition->getPrimaryKeysNames()
            )
        );

        // update only changed rows - mysql TIMESTAMP ON UPDATE behaviour simulation
        $columnsComparisonSql = array_map(
            function (SynapseColumn $columnDefinition) use ($dest) {

                $useCoalesce = $columnDefinition->getColumnDefinition()->getBasetype() === BaseType::STRING;

                $sqlTemplate = 'CAST(%s.%s AS %s) != [src].%s';
                if ($useCoalesce) {
                    $sqlTemplate = 'COALESCE(CAST(%s.%s AS %s), \'\') != COALESCE([src].%s, \'\')';
                }

                return sprintf(
                    $sqlTemplate,
                    $dest,
                    SynapseQuote::quoteSingleIdentifier($columnDefinition->getColumnName()),
                    $this->getColumnTypeSqlDefinition($columnDefinition),
                    SynapseQuote::quoteSingleIdentifier($columnDefinition->getColumnName())
                );
            },
            $columnsForComparison
        );

        $and = '';
        if (count($columnsComparisonSql) !== 0) {
            $and = sprintf(
                'AND (%s) ',
                implode(' OR ', $columnsComparisonSql)
            );
        }

        return sprintf(
            'UPDATE %s SET %s FROM %s.%s AS [src] WHERE %s %s',
            $dest,
            implode(', ', $columnsSet),
            SynapseQuote::quoteSingleIdentifier($stagingTableDefinition->getSchemaName()),
            SynapseQuote::quoteSingleIdentifier($stagingTableDefinition->getTableName()),
            $this->getPrimaryKeyWhereConditions($destinationDefinition->getPrimaryKeysNames(), '[src]', $dest),
            $and
        );
    }

    private function getColumnTypeSqlDefinition(SynapseColumn $column): string
    {
        $columnTypeDefinition = $column->getColumnDefinition()->getType();
        $length = $column->getColumnDefinition()->getLength();
        if ($length !== null && $length !== '') {
            $columnTypeDefinition .= sprintf('(%s)', $length);
        }
        return $columnTypeDefinition;
    }

    /**
     * @return string[]
     */
    private function getCTASColumnsSetSql(
        SynapseTableDefinition $sourceTableDefinition,
        SynapseTableDefinition $destinationTableDefinition,
        SynapseImportOptions $importOptions
    ): array {
        $columnsSetSql = [];
        /** @var SynapseColumn $column */
        foreach ($sourceTableDefinition->getColumnsDefinitions() as $column) {
            $destinationColumn = null;
            // case sensitive search
            /** @var SynapseColumn $col */
            foreach ($destinationTableDefinition->getColumnsDefinitions() as $col) {
                if (StringCaseSensitivity::isEqualCaseInsensitive($col->getColumnName(), $column->getColumnName())) {
                    $destinationColumn = $col;
                    break;
                }
            }
            if ($destinationColumn === null) {
                throw new Exception(
                    sprintf(
                        'Columns "%s" can be imported as it was not found between columns "%s" of destination table.',
                        $column->getColumnName(),
                        implode(', ', $destinationTableDefinition->getColumnsNames())
                    ),
                    Exception::UNKNOWN_ERROR
                );
            }
            $columnTypeDefinition = $this->getColumnTypeSqlDefinition($destinationColumn);
            $convertEmptyToNull = StringCaseSensitivity::isInArrayCaseInsensitive(
                $column->getColumnName(),
                $importOptions->getConvertEmptyValuesToNull()
            );
            if ($convertEmptyToNull) {
                $columnsSetSql[] = $this->getCTASColumnSetSQLWithConvertEmptyValues(
                    $column,
                    $importOptions,
                    $columnTypeDefinition,
                    $destinationColumn
                );
            } else {
                $columnsSetSql[] = $this->getCTASColumnSetSQL(
                    $column,
                    $importOptions,
                    $columnTypeDefinition,
                    $destinationColumn
                );
            }
        }
        return $columnsSetSql;
    }

    private function getCTASColumnSetSQLWithConvertEmptyValues(
        SynapseColumn $source,
        SynapseImportOptions $importOptions,
        string $columnTypeDefinition,
        SynapseColumn $destinationColumn
    ): string {
        if ($destinationColumn->getColumnDefinition()->getBasetype() === BaseType::STRING) {
            // convert nulls only for strings
            $colSql = sprintf(
                'NULLIF(%s, \'\')',
                SynapseQuote::quoteSingleIdentifier($source->getColumnName())
            );
        } else {
            $colSql = SynapseQuote::quoteSingleIdentifier($source->getColumnName());
        }
        if ($importOptions->getCastValueTypes()) {
            $colSql = sprintf(
                'CAST(%s as %s)',
                $colSql,
                $columnTypeDefinition
            );
        }
        return sprintf(
            '%s AS %s',
            $colSql,
            SynapseQuote::quoteSingleIdentifier($destinationColumn->getColumnName())
        );
    }

    private function getCTASColumnSetSQL(
        SynapseColumn $sourceColumn,
        SynapseImportOptions $importOptions,
        string $columnTypeDefinition,
        SynapseColumn $destinationColumn
    ): string {
        if ($importOptions->getCastValueTypes()) {
            $colSql = sprintf(
                'CAST(%s as %s)',
                SynapseQuote::quoteSingleIdentifier($sourceColumn->getColumnName()),
                $columnTypeDefinition
            );
        } else {
            $colSql = SynapseQuote::quoteSingleIdentifier($sourceColumn->getColumnName());
        }
        if ($destinationColumn->getColumnDefinition()->getBasetype() === BaseType::STRING) {
            if ($destinationColumn->getColumnDefinition()->isNullable()) {
                // Columns with coalesce are nullable in CTAS
                $colSql = sprintf(
                    'COALESCE(%s, \'\')',
                    $colSql
                );
            } else {
                // Columns with isnull are not null in CTAS
                $colSql = sprintf(
                    'ISNULL(%s, \'\')',
                    $colSql
                );
            }
        }
        return sprintf(
            '%s AS %s',
            $colSql,
            SynapseQuote::quoteSingleIdentifier($destinationColumn->getColumnName())
        );
    }
}
