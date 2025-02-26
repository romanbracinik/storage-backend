<?php

declare(strict_types=1);

use Keboola\StorageBackend\MostRecentTagWithoutRepositoryPrefixResolver;
use Keboola\StorageBackend\AddTagPerPackagesWorker;
use Symplify\MonorepoBuilder\Config\MBConfig;
use Symplify\MonorepoBuilder\Contract\Git\TagResolverInterface;
use Symplify\MonorepoBuilder\Release\ReleaseWorker\PushTagReleaseWorker;
use Symplify\MonorepoBuilder\Release\ReleaseWorker\SetCurrentMutualDependenciesReleaseWorker;
use Symplify\MonorepoBuilder\Release\ReleaseWorker\TagVersionReleaseWorker;
use Symplify\MonorepoBuilder\Release\ReleaseWorker\UpdateReplaceReleaseWorker;

return static function (MBConfig $mbConfig): void {
    $mbConfig->packageDirectories([__DIR__ . '/packages']);
    // register custom most recent tag resolver

    $mbConfig->packageDirectoriesExcludes([__DIR__ . '/packages/php-db-import-export/provisioning']);

    $servicesConfigurator = $mbConfig->services();
    $mbConfig->defaultBranch('main');
    $servicesConfigurator
        ->set(TagResolverInterface::class, MostRecentTagWithoutRepositoryPrefixResolver::class)
        ->autowire();

    $mbConfig->workers([
        UpdateReplaceReleaseWorker::class,
        SetCurrentMutualDependenciesReleaseWorker::class,
        TagVersionReleaseWorker::class,
        AddTagPerPackagesWorker::class,
//        PushTagReleaseWorker::class,
    ]);
};
