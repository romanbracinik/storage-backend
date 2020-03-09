<?php

declare(strict_types=1);

namespace Keboola\Db\ImportExport\Storage\ABS;

use Keboola\CsvOptions\CsvOptions;
use Keboola\Db\ImportExport\Backend\BackendImportAdapterInterface;
use Keboola\Db\ImportExport\Backend\ImporterInterface;
use Keboola\Db\ImportExport\ImportOptions;
use Keboola\Db\ImportExport\Backend\Snowflake\Helper\QuoteHelper;
use Keboola\Db\ImportExport\Storage\DestinationInterface;
use Keboola\Db\ImportExport\Storage\Snowflake\Table;
use Keboola\Db\ImportExport\Storage\SourceInterface;

class SnowflakeImportAdapter implements BackendImportAdapterInterface
{
    /**
     * @var SourceFile
     */
    private $source;

    /**
     * @param SourceFile $source
     */
    public function __construct(SourceInterface $source)
    {
        $this->source = $source;
    }

    /**
     * @param Table $destination
     * @param null $connection
     */
    public function getCopyCommands(
        DestinationInterface $destination,
        ImportOptions $importOptions,
        string $stagingTableName,
        $connection = null
    ): array {
        $filesToImport = $this->source->getManifestEntries();
        $commands = [];
        foreach (array_chunk($filesToImport, ImporterInterface::SLICED_FILES_CHUNK_SIZE) as $entries) {
            $commands[] = sprintf(
                'COPY INTO %s.%s 
FROM %s
CREDENTIALS=(AZURE_SAS_TOKEN=\'%s\')
FILE_FORMAT = (TYPE=CSV %s)
FILES = (%s)',
                QuoteHelper::quoteIdentifier($destination->getSchema()),
                QuoteHelper::quoteIdentifier($stagingTableName),
                QuoteHelper::quote($this->source->getContainerUrl()),
                $this->source->getSasToken(),
                implode(' ', $this->getCsvCopyCommandOptions($importOptions, $this->source->getCsvOptions())),
                implode(
                    ', ',
                    array_map(
                        function ($entry) {
                            return QuoteHelper::quote(strtr($entry, [$this->source->getContainerUrl() => '']));
                        },
                        $entries
                    )
                )
            );
        }
        return $commands;
    }

    private function getCsvCopyCommandOptions(
        ImportOptions $importOptions,
        CsvOptions $csvOptions
    ): array {
        $options = [
            sprintf('FIELD_DELIMITER = %s', QuoteHelper::quote($csvOptions->getDelimiter())),
        ];

        if ($importOptions->getNumberOfIgnoredLines() > 0) {
            $options[] = sprintf('SKIP_HEADER = %d', $importOptions->getNumberOfIgnoredLines());
        }

        if ($csvOptions->getEnclosure()) {
            $options[] = sprintf('FIELD_OPTIONALLY_ENCLOSED_BY = %s', QuoteHelper::quote($csvOptions->getEnclosure()));
            $options[] = 'ESCAPE_UNENCLOSED_FIELD = NONE';
        } elseif ($csvOptions->getEscapedBy()) {
            $options[] = sprintf('ESCAPE_UNENCLOSED_FIELD = %s', QuoteHelper::quote($csvOptions->getEscapedBy()));
        }
        return $options;
    }
}
