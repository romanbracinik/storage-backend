<?php

declare(strict_types=1);

namespace Keboola\Db\ImportExport\Backend;

use Keboola\Db\Import\Result;
use Keboola\Db\ImportExport\ImportOptionsInterface;
use Keboola\Db\ImportExport\Storage;

/**
 * @deprecated use ToStageImporterInterface and ToFinalTableImporterInterface combination
 */
interface ImporterInterface
{
    public const TIMESTAMP_COLUMN_NAME = '_timestamp';
    public const SLICED_FILES_CHUNK_SIZE = 1000;

    public function importTable(
        Storage\SourceInterface $source,
        Storage\DestinationInterface $destination,
        ImportOptionsInterface $options
    ): Result;

    /**
     * @param array<class-string<BackendImportAdapterInterface>> $adapters
     */
    public function setAdapters(array $adapters): void;
}
