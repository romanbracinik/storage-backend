<?php

declare(strict_types=1);

/**
 * Loads test fixtures into ABS
 */

use Tests\Keboola\Db\ImportExportCommon\StubLoader\AbsLoader;
use Tests\Keboola\Db\ImportExportCommon\StubLoader\GCSLoader;
use Tests\Keboola\Db\ImportExportCommon\StubLoader\S3Loader;

date_default_timezone_set('Europe/Prague');
ini_set('display_errors', '1');
error_reporting(E_ALL);

$basedir = dirname(__DIR__);

require_once $basedir . '/../../vendor/autoload.php';

switch ($argv[1]) {
    case 'abs':
        require_once 'AbsLoader.php';

        $loader = new AbsLoader(
            (string) getenv('ABS_ACCOUNT_NAME'),
            (string) getenv('ABS_CONTAINER_NAME')
        );
        $loader->deleteContainer();
        $loader->createContainer();
        $loader->load();
        break;
    case 's3':
        require_once 'S3Loader.php';

        $loader = new S3Loader(
            (string) getenv('AWS_REGION'),
            (string) getenv('AWS_S3_BUCKET'),
            (string) getenv('AWS_S3_KEY')
        );
        $loader->clearBucket();
        $loader->load();
        break;
    case 'gcs-snowflake':
        require_once 'GCSLoader.php';

        /** @var array{
         * type: string,
         * project_id: string,
         * private_key_id: string,
         * private_key: string,
         * client_email: string,
         * client_id: string,
         * auth_uri: string,
         * token_uri: string,
         * auth_provider_x509_cert_url: string,
         * client_x509_cert_url: string,
         * } $credentials
         */
        $credentials = json_decode((string) getenv('GCS_CREDENTIALS'), true, 512, JSON_THROW_ON_ERROR);
        $loader = new GCSLoader(
            $credentials,
            (string) getenv('GCS_BUCKET_NAME'),
        );
        $loader->clearBucket();
        $loader->load();
        break;
    case 'gcs-bigquery':
        require_once 'GCSLoader.php';

        /** @var array{
         * type: string,
         * project_id: string,
         * private_key_id: string,
         * private_key: string,
         * client_email: string,
         * client_id: string,
         * auth_uri: string,
         * token_uri: string,
         * auth_provider_x509_cert_url: string,
         * client_x509_cert_url: string,
         * } $credentials
         */
        $credentials = json_decode((string) getenv('BQ_KEY_FILE'), true, 512, JSON_THROW_ON_ERROR);
        $loader = new GCSLoader(
            $credentials,
            (string) getenv('BQ_BUCKET_NAME'),
        );
        $loader->clearBucket();
        $loader->load();
        break;
    default:
        throw new Exception('Only abs|s3 options are supported.');
}
