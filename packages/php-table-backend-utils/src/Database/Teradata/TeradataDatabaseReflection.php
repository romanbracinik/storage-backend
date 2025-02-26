<?php

declare(strict_types=1);

namespace Keboola\TableBackendUtils\Database\Teradata;

use Doctrine\DBAL\Connection;
use Keboola\TableBackendUtils\Database\DatabaseReflectionInterface;
use Keboola\TableBackendUtils\Escaping\Teradata\TeradataQuote;

final class TeradataDatabaseReflection implements DatabaseReflectionInterface
{
    private Connection $connection;

    /** @var string[] */
    private static array $excludedUsers = [
        'TDPUSER',
        'Crashdumps',
        'tdwm',
        'DBC',
        'LockLogShredder',
        'TDMaps',
        'Sys_Calendar',
        'SysAdmin',
        'SystemFe',
        'External_AP',
        'console',
        'viewpoint',
    ];

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @return string[]
     */
    public function getUsersNames(?string $like = null): array
    {
        // build escaped list of system users
        $where = sprintf(
            'U.UserName NOT IN (%s)',
            implode(', ', array_map(static fn($item) => TeradataQuote::quote($item), self::$excludedUsers))
        );

        // add LIKE
        if ($like !== null) {
            $where .= sprintf(
                ' AND U.UserName LIKE %s',
                TeradataQuote::quote("%$like%")
            );
        }

        // load the data
        /** @var array<array{UserName:string}> $users */
        $users = $this->connection->fetchAllAssociative(sprintf(
            'SELECT U.UserName FROM DBC.UsersV U WHERE %s',
            $where
        ));

        // extract data to primitive array
        return array_map(static fn($record) => trim($record['UserName']), $users);
    }

    /**
     * @return string[]
     */
    public function getRolesNames(?string $like = null): array
    {
        // build WHERE clausule
        $where = '';
        if ($like !== null) {
            $where = sprintf(
                ' WHERE RoleName LIKE %s',
                TeradataQuote::quote("%$like%")
            );
        }

        // load data
        /** @var array<array{RoleName:string}> $roles */
        $roles = $this->connection->fetchAllAssociative(sprintf(
            'SELECT RoleName FROM DBC.RoleInfoVX %s',
            $where
        ));

        // extract data to primitive array. Has to be trimmed because it comes with some extra spaces
        return array_map(static fn($record) => trim($record['RoleName']), $roles);
    }
}
