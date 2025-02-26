<?php

declare(strict_types=1);

namespace Keboola\Db\ImportExport\Backend\Teradata\ToStage;

use Doctrine\DBAL\Connection;
use Keboola\Db\ImportExport\Backend\CopyAdapterInterface;
use Keboola\Db\ImportExport\Backend\Teradata\Helper\StorageABSHelper;
use Keboola\Db\ImportExport\Backend\Teradata\Helper\StorageS3Helper;
use Keboola\Db\ImportExport\Backend\Teradata\TeradataImportOptions;
use Keboola\Db\ImportExport\Backend\Teradata\ToFinalTable\SqlBuilder;
use Keboola\Db\ImportExport\Backend\Teradata\ToStage\Exception\FailedTPTLoadException;
use Keboola\Db\ImportExport\Backend\Teradata\ToStage\Exception\NoMoreRoomInTDException;
use Keboola\Db\ImportExport\ImportOptions;
use Keboola\Db\ImportExport\ImportOptionsInterface;
use Keboola\Db\ImportExport\Storage;
use Keboola\FileStorage\Abs\AbsProvider;
use Keboola\FileStorage\Path\RelativePath;
use Keboola\TableBackendUtils\Escaping\Teradata\TeradataQuote;
use Keboola\TableBackendUtils\Table\TableDefinitionInterface;
use Keboola\TableBackendUtils\Table\Teradata\TeradataTableDefinition;
use Keboola\TableBackendUtils\Table\Teradata\TeradataTableQueryBuilder;
use Keboola\TableBackendUtils\Table\Teradata\TeradataTableReflection;
use Keboola\Temp\Temp;
use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * @todo: get logs out on success
 */
class FromABSTPTAdapter implements CopyAdapterInterface
{
    private const TPT_TIMEOUT = 60 * 60 * 6; // 6 hours

    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param Storage\ABS\SourceFile $source
     * @param TeradataTableDefinition $destination
     * @param ImportOptions $importOptions
     */
    public function runCopyCommand(
        Storage\SourceInterface $source,
        TableDefinitionInterface $destination,
        ImportOptionsInterface $importOptions
    ): int {
        assert($source instanceof Storage\ABS\SourceFile);
        assert($destination instanceof TeradataTableDefinition);
        assert($importOptions instanceof TeradataImportOptions);

        // empty manifest. TPT cannot import empty data
        if ($source->isSliced() && count($source->getManifestEntries()) === 0) {
            return 0;
        }

        /**
         * @var Temp $temp
         */
        [
            $temp,
            $logTable,
            $errTable,
            $errTable2,
            $processCmd,
        ] = $this->generateTPTScript($source, $destination, $importOptions);

        $process = new Process(
            $processCmd,
        );
        $process->setTimeout(self::TPT_TIMEOUT);
        $process->start();
        // check end of process
        $process->wait();

        // debug stuff
//        foreach ($process as $type => $data) {
//            if ($process::OUT === $type) {
//                echo "\nRead from stdout: " . $data;
//            } else { // $process::ERR === $type
//                echo "\nRead from stderr: " . $data;
//            }
//        }
        $qb = new SqlBuilder();
        $isTableExists = function (string $databaseName, string $tableName) use ($qb) {
            return (bool) $this->connection->fetchOne($qb->getTableExistsCommand($databaseName, $tableName));
        };

        $logContent = null;
        if ($isTableExists($destination->getSchemaName(), $logTable)) {
            $logContent = $this->connection->fetchAllAssociative(
                sprintf(
                    'SELECT * FROM %s.%s',
                    TeradataQuote::quoteSingleIdentifier($destination->getSchemaName()),
                    TeradataQuote::quoteSingleIdentifier($logTable)
                )
            );
            $this->connection->executeStatement($qb->getDropTableUnsafe($destination->getSchemaName(), $logTable));
        }
        $err1Exists = false;
        if ($isTableExists($destination->getSchemaName(), $errTable)) {
            // ERR content isn't readable -> skip the SELECT for now
            $err1Exists = true;
            $this->connection->executeStatement($qb->getDropTableUnsafe($destination->getSchemaName(), $errTable));
        }
        $err2Exists = false;
        if ($isTableExists($destination->getSchemaName(), $errTable2)) {
            // ERR content isn't readable -> skip the SELECT for now
            $err2Exists = true;
            $this->connection->executeStatement($qb->getDropTableUnsafe($destination->getSchemaName(), $errTable2));
        }
        // TODO find the way how to get this out

        if ($err1Exists || $err2Exists || $process->getExitCode() !== 0) {
            $qb = new TeradataTableQueryBuilder();
            // drop destination table it's not usable
            $this->connection->executeStatement($qb->getDropTableCommand(
                $destination->getSchemaName(),
                $destination->getTableName()
            ));
            $stdOut = $process->getOutput();

            if (strpos($stdOut, 'No more room in database') !== false) {
                throw new NoMoreRoomInTDException($destination->getSchemaName());
            }

            throw new FailedTPTLoadException(
                $process->getErrorOutput(),
                $stdOut,
                $process->getExitCode(),
                $this->getLogData($temp),
                $logContent
            );
        }

        // delete temp files
        $temp->remove();

        $ref = new TeradataTableReflection(
            $this->connection,
            $destination->getSchemaName(),
            $destination->getTableName()
        );

        return $ref->getRowsCount();
    }

    private function getLogData(Temp $temp): string
    {
        if (file_exists($temp->getTmpFolder() . '/import-1.out')) {
            $data = file_get_contents($temp->getTmpFolder() . '/import-1.out') ?: 'unable to get error';
            // delete temp files
            $temp->remove();
            return $data;
        }

        return 'unable to get error';
    }

    /**
     * @return array{0: Temp, 1:string, 2:string, 3:string, 4: string[]}
     */
    private function generateTPTScript(
        Storage\ABS\SourceFile $source,
        TeradataTableDefinition $destination,
        TeradataImportOptions $importOptions
    ): array {
        $temp = new Temp();
        $folder = $temp->getTmpFolder();
        $target = sprintf(
            '%s.%s',
            TeradataQuote::quoteSingleIdentifier($destination->getSchemaName()),
            TeradataQuote::quoteSingleIdentifier($destination->getTableName()),
        );

        $absConfigDir = $folder . '/.abs';
        if (!mkdir($absConfigDir) && !is_dir($absConfigDir)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $absConfigDir));
        }
        $credentials = sprintf(
            <<<INI
            [default]
            StorageAccountName = %s
            StorageAccountKey = %s
            INI,
            $source->getAccountName(),
            $source->getBlobMasterKey(),
        );
        file_put_contents($absConfigDir . '/credentials', $credentials);

        // this whole thing expects, that manifest content is unified ->
        // a/ it is a single csv file
        // b/ it is list of real CSV files (even a single file)
        // c/ it is list of Fxx files generated by TD

        if (StorageABSHelper::isMultipartFile($source)) {
            // scenario c

            // file is abs://container/path/.../file.csv.gz/F00000
            // extract real filepath from path -> remove F00000 and do just one load
            // load with SPF = false

            [$prefix, $object] = StorageABSHelper::buildPrefixAndObject($source);
            $moduleStr = sprintf(
            // phpcs:ignore
                'AccessModuleInitStr = \'-ConfigDir "%s" -Container "%s" -Prefix "%s" -Object "%s" -SinglePartFile False\'',
                $absConfigDir,
                $source->getContainer(),
                $prefix,
                $object,
            );
        } else {
            if ($source->isSliced()) {
                // load with wildcard
                // scenario b

                $mask = StorageABSHelper::getMask($source);
                $path = RelativePath::createFromRootAndPath(new AbsProvider(), $source->getContainer(), $mask);
                $moduleStr = sprintf(
                // phpcs:ignore
                    'AccessModuleInitStr = \'-ConfigDir "%s" -Container "%s" -Prefix "%s" -Object "%s" -SinglePartFile True\'',
                    $absConfigDir,
                    $path->getRoot(),
                    $path->getPathWithoutRoot() ? ($path->getPathWithoutRoot() . '/') : '',
                    $path->getFileName(),
                );
            } else {
                // direct load with
                // scenario a

                $moduleStr = sprintf(
                // phpcs:ignore
                    'AccessModuleInitStr = \'-ConfigDir "%s" -Container "%s" -Prefix "%s" -Object "%s" -SinglePartFile True\'',
                    $absConfigDir,
                    $source->getContainer(),
                    $source->getPrefix(),
                    $source->getFileName(),
                );
            }
        }
        $tptScript = <<<EOD
USING CHARACTER SET UTF8
DEFINE JOB IMPORT_TO_TERADATA
DESCRIPTION 'Import data to Teradata from Microsoft Azure Blog Storage'
(

    SET TargetTable = '$target';

    STEP IMPORT_THE_DATA
    (
        APPLY \$INSERT TO OPERATOR (\$LOAD)
        SELECT * FROM OPERATOR (\$FILE_READER ()
            ATTR
            (
                AccessModuleName = 'libazureaxsmod.so',
                $moduleStr
            )
        );

    );
);
EOD;

        file_put_contents($folder . '/import_script.tpt', $tptScript);

        $host = $importOptions->getTeradataHost();
        $user = $importOptions->getTeradataUser();
        $pass = $importOptions->getTeradataPassword();
        $csvOptions = $source->getCsvOptions();
        $delimiter = $csvOptions->getDelimiter();
        if ($delimiter === "\t") {
            $delimiter = 'TAB';
        }
        $enclosure = $csvOptions->getEnclosure();
        if ($enclosure === '\'') {
            $enclosure = '\\\'';
        }
        $escapedBy = $csvOptions->getEscapedBy();
        if ($escapedBy !== '') {
            $escapedBy = sprintf(',FileReaderEscapeQuoteDelimiter = \'%s\'', $escapedBy);
        }
        $ignoredLines = $importOptions->getNumberOfIgnoredLines();

        $quotedDestination = TeradataQuote::quoteSingleIdentifier($destination->getSchemaName());
        $tablesPrefix = StorageS3Helper::generateStagingTableName();
        $logTable = $tablesPrefix . '_log';
        $logTableQuoted = $quotedDestination . '.' . TeradataQuote::quoteSingleIdentifier($logTable);
        $errTable1 = $tablesPrefix . '_e1';
        $errTableQuoted = $quotedDestination . '.' . TeradataQuote::quoteSingleIdentifier($errTable1);
        $errTable2 = $tablesPrefix . '_e2';
        $errTable2Quoted = $quotedDestination . '.' . TeradataQuote::quoteSingleIdentifier($errTable2);

        $jobVariableFile = <<<EOD
/********************************************************/
/* TPT attributes - Common for all Samples              */
/********************************************************/
TargetTdpId = '$host'
,TargetUserName = '$user'
,TargetUserPassword = '$pass'
,TargetErrorList = [ '3706','3803','3807' ]
,DDLPrivateLogName = 'DDL_OPERATOR_LOG'

/********************************************************/
/* TPT LOAD Operator attributes                         */
/********************************************************/
,LoadPrivateLogName = 'LOAD_OPERATOR_LOG'
,LoadTargetTable = '${target}'
,LoadLogTable = '${logTableQuoted}'
,LoadErrorTable1 = '${errTableQuoted}'
,LoadErrorTable2 = '${errTable2Quoted}'

/********************************************************/
/* TPT DataConnector Producer Operator                  */
/********************************************************/
,FileReaderFormat = 'Delimited'
,FileReaderOpenMode = 'Read'
,FileReaderTextDelimiter = '$delimiter'
,FileReaderSkipRows = $ignoredLines
,FileReaderOpenQuoteMark = '$enclosure'
,FileReaderCloseQuoteMark = '$enclosure'
,FileReaderQuotedData = 'Optional'
$escapedBy
,FileReaderTruncateColumns = 'Yes'
EOD;

        file_put_contents($folder . '/import_vars.txt', $jobVariableFile);

        return [
            $temp,
            $logTable,
            $errTable1,
            $errTable2,
            [
                'tbuild',
                '-j',
                'import',
                '-L',
                $folder,
                '-f',
                $folder . '/import_script.tpt',
                '-v',
                $folder . '/import_vars.txt',
            ],
        ];
    }
}
