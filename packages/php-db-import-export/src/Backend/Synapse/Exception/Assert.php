<?php

declare(strict_types=1);

namespace Keboola\Db\ImportExport\Backend\Synapse\Exception;

use Exception as InternalException;
use Keboola\Db\Import\Exception;
use Keboola\Db\ImportExport\Backend\Helper\Assert as BaseAssert;
use Keboola\Db\ImportExport\Backend\Synapse\DestinationTableOptions;
use Keboola\Db\ImportExport\Backend\Synapse\SynapseException;
use Keboola\Db\ImportExport\Backend\Synapse\SynapseImportOptions;
use Keboola\Db\ImportExport\Backend\Synapse\TableDistribution;
use Keboola\Db\ImportExport\ImportOptionsInterface;
use Keboola\Db\ImportExport\Storage\ABS\SourceFile;
use Keboola\Db\ImportExport\Storage\DestinationInterface;
use Keboola\Db\ImportExport\Storage\SourceInterface;
use Keboola\Db\ImportExport\Storage\Synapse\Table;
use Keboola\TableBackendUtils\Column\ColumnCollection;
use Keboola\TableBackendUtils\Column\SynapseColumn;
use Keboola\TableBackendUtils\Table\SynapseTableDefinition;
use LogicException;

final class Assert extends BaseAssert
{
    public static function assertColumns(
        SourceInterface $source,
        DestinationTableOptions $destinationTableOptions
    ): void {
        if (count($source->getColumnsNames()) === 0) {
            throw new Exception(
                'No columns found in CSV file.',
                Exception::NO_COLUMNS
            );
        }

        $moreColumns = array_diff($source->getColumnsNames(), $destinationTableOptions->getColumnNamesInOrder());
        if (!empty($moreColumns)) {
            throw new Exception(
                'Columns doest not match. Non existing columns: ' . implode(', ', $moreColumns),
                Exception::COLUMNS_COUNT_NOT_MATCH
            );
        }
    }

    public static function assertIsSynapseTableDestination(DestinationInterface $destination): void
    {
        if (!$destination instanceof Table) {
            throw new InternalException(sprintf(
                'Only "%s" is supported as destination "%s" provided.',
                Table::class,
                get_class($destination)
            ));
        }
    }

    public static function assertSynapseImportOptions(ImportOptionsInterface $options): void
    {
        if (!$options instanceof SynapseImportOptions) {
            throw new InternalException(sprintf(
                'Synapse importer expect $options to be instance of "%s", "%s" given.',
                SynapseImportOptions::class,
                get_class($options)
            ));
        }
    }

    public static function assertValidSource(SourceInterface $source): void
    {
        if ($source instanceof SourceFile
            && $source->getCsvOptions()->getEnclosure() === ''
        ) {
            throw new InternalException(
                'CSV property FIELDQUOTE|ECLOSURE must be set when using Synapse analytics.'
            );
        }
    }

    /**
     * @param string[] $hashDistributionColumnsNames
     */
    public static function assertValidHashDistribution(
        string $tableDistributionName,
        array $hashDistributionColumnsNames
    ): void {
        if ($tableDistributionName === TableDistribution::TABLE_DISTRIBUTION_HASH
            && count($hashDistributionColumnsNames) !== 1
        ) {
            throw new LogicException('HASH table distribution must have one distribution key specified.');
        }
    }

    public static function assertTableDistribution(string $tableDistributionName): void
    {
        if (!in_array($tableDistributionName, [
            TableDistribution::TABLE_DISTRIBUTION_HASH,
            TableDistribution::TABLE_DISTRIBUTION_ROUND_ROBIN,
            TableDistribution::TABLE_DISTRIBUTION_REPLICATE,
        ], true)) {
            throw new LogicException(sprintf(
                'Unknown table distribution "%s" specified.',
                $tableDistributionName
            ));
        }
    }

    public static function assertStagingTable(string $tableName): void
    {
        if ($tableName[0] !== '#') {
            throw new Exception(sprintf(
                'Staging table must start with "#" table name "%s" supplied.',
                $tableName
            ));
        }
    }

    public static function assertSameColumns(
        ColumnCollection $source,
        ColumnCollection $destination
    ): void {
        $it0 = $source->getIterator();
        $it1 = $destination->getIterator();
        while ($it0->valid() || $it1->valid()) {
            if ($it0->valid() && $it1->valid()) {
                /** @var SynapseColumn $sourceCol */
                $sourceCol = $it0->current();
                /** @var SynapseColumn $destCol */
                $destCol = $it1->current();
                if ($sourceCol->getColumnName() !== $destCol->getColumnName()) {
                    throw SynapseException::createColumnsNamesMismatch($sourceCol, $destCol);
                }
                $sourceDef = $sourceCol->getColumnDefinition();
                $destDef = $destCol->getColumnDefinition();

                if ($sourceDef->getType() !== $destDef->getType()) {
                    throw SynapseException::createColumnsMismatch($sourceCol, $destCol);
                }

                $isLengthMismatch = $sourceDef->getLength() !== $destDef->getLength()
                    && $sourceDef->getLength() !== (string) $destDef->getDefaultLength()
                    && $destDef->getLength() !== (string) $sourceDef->getDefaultLength()
                ;
                if ($isLengthMismatch) {
                    throw SynapseException::createColumnsMismatch($sourceCol, $destCol);
                }
            } else {
                throw SynapseException::createColumnsCountMismatch($source, $destination);
            }

            $it0->next();
            $it1->next();
        }
    }
}
