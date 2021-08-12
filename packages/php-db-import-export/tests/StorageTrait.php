<?php

declare(strict_types=1);

namespace Tests\Keboola\Db\ImportExport;

use Aws\S3\S3Client;
use Keboola\Csv\CsvFile;
use Keboola\CsvOptions\CsvOptions;
use Keboola\Db\ImportExport\Storage\DestinationInterface;
use Keboola\Db\ImportExport\Storage\S3;
use Keboola\Db\ImportExport\Storage\ABS;
use Keboola\Db\ImportExport\Storage;
use Keboola\FileStorage\Abs\ClientFactory;
use Keboola\Temp\Temp;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Blob\Models\Blob;
use MicrosoftAzure\Storage\Blob\Models\ListBlobsOptions;

trait StorageTrait
{
    use ABSSourceTrait;
    use S3SourceTrait;

    protected function getDestinationInstance(
        string $filePath
    ): DestinationInterface {
        switch (getenv('STORAGE_TYPE')) {
            case StorageType::STORAGE_S3:
                return new Storage\S3\DestinationFile(
                    (string) getenv('AWS_ACCESS_KEY_ID'),
                    (string) getenv('AWS_SECRET_ACCESS_KEY'),
                    (string) getenv('AWS_REGION'),
                    (string) getenv('AWS_S3_BUCKET'),
                    $filePath
                );
            case StorageType::STORAGE_ABS:
                return new Storage\ABS\DestinationFile(
                    (string) getenv('ABS_CONTAINER_NAME'),
                    $filePath,
                    $this->getCredentialsForAzureContainer((string) getenv('ABS_CONTAINER_NAME'), 'rwla'),
                    (string) getenv('ABS_ACCOUNT_NAME'),
                    (string) getenv('ABS_ACCOUNT_KEY')
                );
            default:
                throw new \Exception(sprintf('Unknown STORAGE_TYPE "%s".', getenv('STORAGE_TYPE')));
        }
    }

    /**
     * @param string[] $columns
     * @param string[]|null $primaryKeys
     * @return S3\SourceFile|ABS\SourceFile|S3\SourceDirectory|ABS\SourceDirectory
     */
    public function getSourceInstance(
        string $filePath,
        array $columns = [],
        bool $isSliced = false,
        bool $isDirectory = false,
        ?array $primaryKeys = null
    ) {
        switch (getenv('STORAGE_TYPE')) {
            case StorageType::STORAGE_S3:
                $getSourceInstance = 'createS3SourceInstance';
                $manifestPrefix = 'S3.';
                break;
            case StorageType::STORAGE_ABS:
                $getSourceInstance = 'createABSSourceInstance';
                $manifestPrefix = '';
                break;
            default:
                throw new \Exception(sprintf('Unknown STORAGE_TYPE "%s".', getenv('STORAGE_TYPE')));
        }

        $filePath = str_replace('%MANIFEST_PREFIX%', $manifestPrefix, $filePath);
        return $this->$getSourceInstance(
            $filePath,
            $columns,
            $isSliced,
            $isDirectory,
            $primaryKeys
        );
    }

    /**
     * @param string[] $columns
     * @param string[]|null $primaryKeys
     * @return S3\SourceFile|ABS\SourceFile|S3\SourceDirectory|ABS\SourceDirectory
     */
    public function getSourceInstanceFromCsv(
        string $filePath,
        CsvOptions $options,
        array $columns = [],
        bool $isSliced = false,
        bool $isDirectory = false,
        ?array $primaryKeys = null
    ) {
        switch (getenv('STORAGE_TYPE')) {
            case StorageType::STORAGE_S3:
                $getSourceInstanceFromCsv = 'createS3SourceInstanceFromCsv';
                $manifestPrefix = 'S3.';
                break;
            case StorageType::STORAGE_ABS:
                $getSourceInstanceFromCsv = 'createABSSourceInstanceFromCsv';
                $manifestPrefix = '';
                break;
            default:
                throw new \Exception(sprintf('Unknown STORAGE_TYPE "%s".', getenv('STORAGE_TYPE')));
        }

        $filePath = str_replace('%MANIFEST_PREFIX%', $manifestPrefix, $filePath);
        return $this->$getSourceInstanceFromCsv(
            $filePath,
            $options,
            $columns,
            $isSliced,
            $isDirectory,
            $primaryKeys
        );
    }

    public function clearDestination(string $dirToClear): void
    {
        switch (getenv('STORAGE_TYPE')) {
            case StorageType::STORAGE_S3:
                /** @var S3Client $client */
                $client = $this->createClient();
                $result = $client->listObjects([
                    'Bucket' => (string) getenv('AWS_S3_BUCKET'),
                    'Prefix' => $dirToClear,
                ]);
                $objects = $result->get('Contents');
                if ($objects) {
                    $client->deleteObjects([
                        'Bucket' => (string) getenv('AWS_S3_BUCKET'),
                        'Delete' => [
                            'Objects' => array_map(static function ($object) {
                                return [
                                    'Key' => $object['Key'],
                                ];
                            }, $objects),
                        ],
                    ]);
                }
                return;
            case StorageType::STORAGE_ABS:
                /** @var BlobRestProxy $client */
                $client = $this->createClient();
                // delete blobs from EXPORT_BLOB_DIR
                $listOptions = new ListBlobsOptions();
                $listOptions->setPrefix($dirToClear);
                $blobs = $client->listBlobs((string) getenv('ABS_CONTAINER_NAME'), $listOptions);
                foreach ($blobs->getBlobs() as $blob) {
                    $client->deleteBlob((string) getenv('ABS_CONTAINER_NAME'), $blob->getName());
                }
                return;
            default:
                throw new \Exception(sprintf('Unknown STORAGE_TYPE "%s".', getenv('STORAGE_TYPE')));
        }
    }

    /**
     * @return S3Client|BlobRestProxy
     */
    public function createClient()
    {
        switch (getenv('STORAGE_TYPE')) {
            case StorageType::STORAGE_S3:
                return new S3Client([
                    'credentials' => [
                        'key' => (string) getenv('AWS_ACCESS_KEY_ID'),
                        'secret' => (string) getenv('AWS_SECRET_ACCESS_KEY'),
                    ],
                    'region' => (string) getenv('AWS_REGION'),
                    'version' => '2006-03-01',
                ]);
            case StorageType::STORAGE_ABS:
                $connectionString = sprintf(
                    'DefaultEndpointsProtocol=https;AccountName=%s;AccountKey=%s;EndpointSuffix=core.windows.net',
                    (string) getenv('ABS_ACCOUNT_NAME'),
                    (string) getenv('ABS_ACCOUNT_KEY')
                );
                return ClientFactory::createClientFromConnectionString(
                    $connectionString
                );
            default:
                throw new \Exception(sprintf('Unknown STORAGE_TYPE "%s".', getenv('STORAGE_TYPE')));
        }
    }

    /**
     * @return Blob[]|array<mixed>|null
     */
    public function listFiles(string $dir): ?array
    {
        switch (getenv('STORAGE_TYPE')) {
            case StorageType::STORAGE_S3:
                /** @var S3Client $client */
                $client = $this->createClient();
                $result = $client->listObjects([
                    'Bucket' => (string) getenv('AWS_S3_BUCKET'),
                    'Prefix' => $dir,
                ]);
                return $result->get('Contents');
            case StorageType::STORAGE_ABS:
                /** @var BlobRestProxy $client */
                $client = $this->createClient();
                $listOptions = new ListBlobsOptions();
                $listOptions->setPrefix($dir);
                $blobs = $client->listBlobs((string) getenv('ABS_CONTAINER_NAME'), $listOptions);
                return $blobs->getBlobs();
            default:
                throw new \Exception(sprintf('Unknown STORAGE_TYPE "%s".', getenv('STORAGE_TYPE')));
        }
    }

    /**
     * @return CsvFile<string[]>
     */
    public function getCsvFileFromStorage(
        string $filePath,
        string $tmpName = 'tmp.csv'
    ): CsvFile {
        $tmp = new Temp();
        $tmp->initRunFolder();
        $actualName = $tmp->getTmpFolder() . $tmpName;
        switch (getenv('STORAGE_TYPE')) {
            case StorageType::STORAGE_S3:
                /** @var S3Client $client */
                $client = $this->createClient();
                $result = $client->getObject([
                    'Bucket' => (string) getenv('AWS_S3_BUCKET'),
                    'Key' => $filePath,
                ]);
                file_put_contents($actualName, $result['Body']);
                return new CsvFile($actualName);
            case StorageType::STORAGE_ABS:
                /** @var BlobRestProxy $client */
                $client = $this->createClient();
                $content = stream_get_contents(
                    $client->getBlob((string) getenv('ABS_CONTAINER_NAME'), $filePath)
                    ->getContentStream()
                );
                if ($content === false) {
                    throw new \Exception();
                }
                file_put_contents($actualName, $content);
                return new CsvFile($actualName);
            default:
                throw new \Exception(sprintf('Unknown STORAGE_TYPE "%s".', getenv('STORAGE_TYPE')));
        }
    }
}
