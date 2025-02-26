<?php

declare(strict_types=1);

namespace Tests\Keboola\Db\ImportExportFunctional\Snowflake;

use Keboola\Csv\CsvFile;
use Keboola\Db\ImportExport\Backend\Snowflake\Importer;
use Keboola\Db\ImportExport\ImportOptions;
use Keboola\Db\ImportExport\Storage;

class IncrementalImportTest extends SnowflakeImportExportBaseTest
{
    /**
     * @return array<mixed>
     */
    public function incrementalImportData(): array
    {
        // accounts
        $expectationAccountsFile = new CsvFile(self::DATA_DIR . 'expectation.tw_accounts.increment.csv');
        $expectedAccountsRows = [];
        foreach ($expectationAccountsFile as $row) {
            $expectedAccountsRows[] = $row;
        }
        $accountColumns = array_shift($expectedAccountsRows);
        $expectedAccountsRows = array_values($expectedAccountsRows);

        // multi pk
        $expectationMultiPkFile = new CsvFile(self::DATA_DIR . 'expectation.multi-pk.increment.csv');
        $expectedMultiPkRows = [];
        foreach ($expectationMultiPkFile as $row) {
            $expectedMultiPkRows[] = $row;
        }
        $multiPkColumns = array_shift($expectedMultiPkRows);
        $expectedMultiPkRows = array_values($expectedMultiPkRows);

        $tests = [];
        $tests[] = [
            $this->getSourceInstance('tw_accounts.csv', $accountColumns, false),
            $this->getSimpleImportOptions(),
            $this->getSourceInstance('tw_accounts.increment.csv', $accountColumns, false),
            $this->getSimpleIncrementalImportOptions(),
            new Storage\Snowflake\Table($this->getDestinationSchemaName(), 'accounts-3'),
            $expectedAccountsRows,
            4,
        ];
        $tests[] = [
            $this->getSourceInstance('tw_accounts.csv', $accountColumns, false),
            new ImportOptions(
                [],
                false,
                false, // disable timestamp
                ImportOptions::SKIP_FIRST_LINE
            ),
            $this->getSourceInstance('tw_accounts.increment.csv', $accountColumns, false),
            new ImportOptions(
                [],
                true, // incremental
                false, // disable timestamp
                ImportOptions::SKIP_FIRST_LINE
            ),
            new Storage\Snowflake\Table($this->getDestinationSchemaName(), 'accounts-without-ts'),
            $expectedAccountsRows,
            4,
        ];
        $tests[] = [
            $this->getSourceInstance('multi-pk.csv', $multiPkColumns, false),
            $this->getSimpleImportOptions(),
            $this->getSourceInstance('multi-pk.increment.csv', $multiPkColumns, false),
            $this->getSimpleIncrementalImportOptions(),
            new Storage\Snowflake\Table($this->getDestinationSchemaName(), 'multi-pk'),
            $expectedMultiPkRows,
            4,
        ];
        return $tests;
    }

    /**
     * @dataProvider  incrementalImportData
     * @param Storage\Snowflake\Table $destination
     * @param array<mixed> $expected
     */
    public function testIncrementalImport(
        Storage\SourceInterface $initialSource,
        ImportOptions $initialOptions,
        Storage\SourceInterface $incrementalSource,
        ImportOptions $incrementalOptions,
        Storage\DestinationInterface $destination,
        array $expected,
        int $expectedImportedRowCount
    ): void {
        (new Importer($this->connection))->importTable(
            $initialSource,
            $destination,
            $initialOptions
        );

        $result = (new Importer($this->connection))->importTable(
            $incrementalSource,
            $destination,
            $incrementalOptions
        );
        self::assertEquals($expectedImportedRowCount, $result->getImportedRowsCount());

        $this->assertTableEqualsExpected(
            $initialSource,
            $destination,
            $incrementalOptions,
            $expected,
            0
        );
    }
}
