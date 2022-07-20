<?php

declare(strict_types=1);

namespace Tests\Keboola\Db\ImportExportFunctional\Snowflake\ToFinal;

use Generator;
use Keboola\Csv\CsvFile;
use Keboola\Db\ImportExport\Backend\Snowflake\SnowflakeImportOptions;
use Keboola\Db\ImportExport\Backend\Snowflake\ToFinalTable\FullImporter;
use Keboola\Db\ImportExport\Backend\Snowflake\ToFinalTable\IncrementalImporter;
use Keboola\Db\ImportExport\Backend\Snowflake\ToFinalTable\SqlBuilder;
use Keboola\Db\ImportExport\Backend\Snowflake\ToStage\StageTableDefinitionFactory;
use Keboola\Db\ImportExport\Backend\Snowflake\ToStage\ToStageImporter;
use Keboola\Db\ImportExport\ImportOptions;
use Keboola\Db\ImportExport\Storage;
use Keboola\TableBackendUtils\Table\Snowflake\SnowflakeTableDefinition;
use Keboola\TableBackendUtils\Table\Snowflake\SnowflakeTableQueryBuilder;
use Keboola\TableBackendUtils\Table\Snowflake\SnowflakeTableReflection;
use Tests\Keboola\Db\ImportExportCommon\StorageTrait;
use Tests\Keboola\Db\ImportExportFunctional\Snowflake\SnowflakeBaseTestCase;

class IncrementalImportTest extends SnowflakeBaseTestCase
{
    use StorageTrait;

    protected function getSnowflakeIncrementalImportOptions(
        int $skipLines = ImportOptions::SKIP_FIRST_LINE
    ): SnowflakeImportOptions {
        return new SnowflakeImportOptions(
            [],
            true,
            true,
            $skipLines
        );
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->cleanSchema($this->getDestinationSchemaName());
        $this->cleanSchema($this->getSourceSchemaName());
        $this->createSchema($this->getSourceSchemaName());
        $this->createSchema($this->getDestinationSchemaName());
    }

    /**
     * @return \Generator<string, array<mixed>>
     */
    public function incrementalImportData(): Generator
    {
        // accounts
        $expectationAccountsFile = new CsvFile(self::DATA_DIR . 'expectation.tw_accounts.increment.csv');
        $expectedAccountsRows = [];
        foreach ($expectationAccountsFile as $row) {
            $expectedAccountsRows[] = $row;
        }
        /** @var string[] $accountColumns */
        $accountColumns = array_shift($expectedAccountsRows);
        $expectedAccountsRows = array_values($expectedAccountsRows);

        // multi pk
        $expectationMultiPkFile = new CsvFile(self::DATA_DIR . 'expectation.multi-pk_not-null.increment.csv');
        $expectedMultiPkRows = [];
        foreach ($expectationMultiPkFile as $row) {
            $expectedMultiPkRows[] = $row;
        }
        /** @var string[] $multiPkColumns */
        $multiPkColumns = array_shift($expectedMultiPkRows);
        $expectedMultiPkRows = array_values($expectedMultiPkRows);

        $tests = [];
        yield 'simple' => [
            $this->getSourceInstance(
                'tw_accounts.csv',
                $accountColumns,
                false,
                false,
                ['id']
            ),
            $this->getSnowflakeImportOptions(),
            $this->getSourceInstance(
                'tw_accounts.increment.csv',
                $accountColumns,
                false,
                false,
                ['id']
            ),
            $this->getSnowflakeIncrementalImportOptions(),
            [$this->getDestinationSchemaName(), 'accounts_3'],
            $expectedAccountsRows,
            4,
            self::TABLE_ACCOUNTS_3,
        ];
        yield 'simple no timestamp' => [
            $this->getSourceInstance(
                'tw_accounts.csv',
                $accountColumns,
                false,
                false,
                ['id']
            ),
            new SnowflakeImportOptions(
                [],
                false,
                false, // disable timestamp
                ImportOptions::SKIP_FIRST_LINE
            ),
            $this->getSourceInstance(
                'tw_accounts.increment.csv',
                $accountColumns,
                false,
                false,
                ['id']
            ),
            new SnowflakeImportOptions(
                [],
                true, // incremental
                false, // disable timestamp
                ImportOptions::SKIP_FIRST_LINE
            ),
            [$this->getDestinationSchemaName(), 'accounts_bez_ts'],
            $expectedAccountsRows,
            4,
            self::TABLE_ACCOUNTS_BEZ_TS,
        ];
        yield 'multi pk' => [
            $this->getSourceInstance(
                'multi-pk_not-null.csv',
                $multiPkColumns,
                false,
                false,
                ['VisitID', 'Value', 'MenuItem']
            ),
            $this->getSnowflakeImportOptions(),
            $this->getSourceInstance(
                'multi-pk_not-null.increment.csv',
                $multiPkColumns,
                false,
                false,
                ['VisitID', 'Value', 'MenuItem']
            ),
            $this->getSnowflakeIncrementalImportOptions(),
            [$this->getDestinationSchemaName(), 'multi_pk_ts'],
            $expectedMultiPkRows,
            3,
            self::TABLE_MULTI_PK_WITH_TS,
        ];
        return $tests;
    }

    /**
     * @dataProvider  incrementalImportData
     * @param string[] $table
     * @param array<mixed> $expected
     */
    public function testIncrementalImport(
        Storage\SourceInterface $fullLoadSource,
        SnowflakeImportOptions $fullLoadOptions,
        Storage\SourceInterface $incrementalSource,
        SnowflakeImportOptions $incrementalOptions,
        array $table,
        array $expected,
        int $expectedImportedRowCount,
        string $tablesToInit
    ): void {
        $this->initTable($tablesToInit);

        [$schemaName, $tableName] = $table;
        $destination = (new SnowflakeTableReflection(
            $this->connection,
            $schemaName,
            $tableName
        ))->getTableDefinition();

        $toStageImporter = new ToStageImporter($this->connection);
        $fullImporter = new FullImporter($this->connection);
        $incrementalImporter = new IncrementalImporter($this->connection);

        $fullLoadStagingTable = StageTableDefinitionFactory::createStagingTableDefinition(
            $destination,
            $fullLoadSource->getColumnsNames()
        );
        $incrementalLoadStagingTable = StageTableDefinitionFactory::createStagingTableDefinition(
            $destination,
            $incrementalSource->getColumnsNames()
        );

        try {
            // full load
            $qb = new SnowflakeTableQueryBuilder();
            $this->connection->executeStatement(
                $qb->getCreateTableCommandFromDefinition($fullLoadStagingTable)
            );

            $importState = $toStageImporter->importToStagingTable(
                $fullLoadSource,
                $fullLoadStagingTable,
                $fullLoadOptions
            );
            $fullImporter->importToTable(
                $fullLoadStagingTable,
                $destination,
                $fullLoadOptions,
                $importState
            );
            // incremental load
            $qb = new SnowflakeTableQueryBuilder();
            $this->connection->executeStatement(
                $qb->getCreateTableCommandFromDefinition($incrementalLoadStagingTable)
            );
            $importState = $toStageImporter->importToStagingTable(
                $incrementalSource,
                $incrementalLoadStagingTable,
                $incrementalOptions
            );
            $result = $incrementalImporter->importToTable(
                $incrementalLoadStagingTable,
                $destination,
                $incrementalOptions,
                $importState
            );
        } finally {
            $this->connection->executeStatement(
                (new SqlBuilder())->getDropTableIfExistsCommand(
                    $fullLoadStagingTable->getSchemaName(),
                    $fullLoadStagingTable->getTableName()
                )
            );
            $this->connection->executeStatement(
                (new SqlBuilder())->getDropTableIfExistsCommand(
                    $incrementalLoadStagingTable->getSchemaName(),
                    $incrementalLoadStagingTable->getTableName()
                )
            );
        }

        self::assertEquals($expectedImportedRowCount, $result->getImportedRowsCount());

        /** @var SnowflakeTableDefinition $destination */
        $this->assertSnowflakeTableEqualsExpected(
            $fullLoadSource,
            $destination,
            $incrementalOptions,
            $expected,
            0
        );
    }
}
