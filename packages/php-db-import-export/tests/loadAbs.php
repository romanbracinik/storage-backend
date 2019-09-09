<?php

declare(strict_types=1);

/**
 * Loads test fixtures into ABS
 */

date_default_timezone_set('Europe/Prague');
ini_set('display_errors', '1');
error_reporting(E_ALL);

$basedir = dirname(__DIR__);

require_once $basedir . '/vendor/autoload.php';

$connString = sprintf(
    'DefaultEndpointsProtocol=https;AccountName=%s;AccountKey=%s;EndpointSuffix=core.windows.net',
    getenv('ABS_ACCOUNT_NAME'),
    getenv('ABS_ACCOUNT_KEY')
);

$blobClient = \MicrosoftAzure\Storage\Blob\BlobRestProxy::createBlobService($connString);

try {
    $blobClient->deleteContainer((string) getenv('ABS_CONTAINER_NAME'));
    sleep(1);
} catch (\MicrosoftAzure\Storage\Common\Exceptions\ServiceException $e) {
    if (preg_match('~The specified container does not exist~', $e->getMessage())) {
        echo 'Container does not exists. Deleting skipped';
    } else {
        throw $e;
    }
}

$created = false;
while ($created === false) {
    try {
        $blobClient->createContainer((string) getenv('ABS_CONTAINER_NAME'));
        $created = true;
    } catch (\MicrosoftAzure\Storage\Common\Exceptions\ServiceException $e) {
        if (preg_match('~The specified container is being deleted.~', $e->getMessage())) {
            echo "Waiting, because container is being deleted ... \n";
            sleep(2);
        } else {
            throw  $e;
        }
    }
}

$blobClient->createBlockBlob(
    (string) getenv('ABS_CONTAINER_NAME'),
    'file.csv',
    file_get_contents(__DIR__ . '/data/file.csv')
);
