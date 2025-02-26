<?php

declare(strict_types=1);

namespace Tests\Keboola\TableBackendUtils\Functional\Teradata;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Exception as InternalException;
use Keboola\TableBackendUtils\Connection\Teradata\TeradataConnection;
use Keboola\TableBackendUtils\Connection\Teradata\TeradataPlatform;
use Keboola\TableBackendUtils\Escaping\Teradata\TeradataQuote;
use PHPUnit\Framework\TestCase;

class TeradataBaseCase extends TestCase
{
    public const TESTS_PREFIX = 'utilsTest_';

    public const TEST_DATABASE = self::TESTS_PREFIX . 'refTableDatabase';
    public const TABLE_GENERIC = self::TESTS_PREFIX . 'refTab';
    public const VIEW_GENERIC = self::TESTS_PREFIX . 'refView';

    protected Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->connection = $this->getTeradataConnection();
    }

    protected function initTable(
        string $table = self::TABLE_GENERIC
    ): void {

        $database = $this->getDatabaseName();
        // char because of Stats test
        $this->connection->executeQuery(
            sprintf(
                'CREATE MULTISET TABLE %s.%s
     (
      "id" INTEGER NOT NULL,
      "first_name" VARCHAR(10000),
      "last_name" VARCHAR(10000)
     );',
                TeradataQuote::quoteSingleIdentifier($database),
                TeradataQuote::quoteSingleIdentifier($table)
            )
        );
    }

    public function getDatabaseName(): string
    {
        return getenv('TEST_PREFIX') . getenv('TERADATA_DB_PREFIX') . self::TEST_DATABASE;
    }

    protected function dbExists(string $dbname): bool
    {
        try {
            $this->connection->executeQuery(sprintf('HELP DATABASE %s', $dbname));
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    protected function cleanDatabase(string $dbname): void
    {
        if (!$this->dbExists($dbname)) {
            return;
        }

        // delete all objects in the DB
        $this->connection->executeQuery(sprintf('DELETE DATABASE %s ALL', $dbname));
        // drop the empty db
        $this->connection->executeQuery(sprintf('DROP DATABASE %s', $dbname));
    }

    public function createDatabase(string $dbName): void
    {
        $this->connection->executeQuery(sprintf('
CREATE DATABASE %s AS
       PERM = 1e8;
       ', $dbName));
    }

    private function getTeradataConnection(): Connection
    {
        $db = TeradataConnection::getConnection([
            'host' => (string) getenv('TERADATA_HOST'),
            'user' => (string) getenv('TERADATA_USERNAME'),
            'password' => (string) getenv('TERADATA_PASSWORD'),
            'port' => (int) getenv('TERADATA_PORT'),
            'dbname' => '',
        ]);

        if ((string) getenv('TERADATA_DATABASE') === '') {
            throw new InternalException('Variable "TERADATA_DATABASE" is missing.');
        }
        $db->executeStatement(sprintf(
            'SET SESSION DATABASE %s;',
            TeradataQuote::quoteSingleIdentifier((string) getenv('TERADATA_DATABASE'))
        ));

        return $db;
    }

    public function assertConnectionIsWorking(Connection $connection): void
    {
        self::assertEquals(
            1,
            $connection->fetchOne('SELECT 1')
        );
    }

    protected function tearDown(): void
    {
        $this->connection->close();
        parent::tearDown();
    }

    protected function setUpUser(string $userName): void
    {
        // list existing users
        /** @var array{array{RoleName: string, UserName: string}} $existingUsers */
        $existingUsers = $this->connection->fetchAllAssociative(sprintf(
            'SELECT  UserName FROM DBC.UsersV U WHERE "U"."Username" = %s',
            TeradataQuote::quote($userName)
        ));

        // delete existing users
        foreach ($existingUsers as $existingUser) {
            $this->connection->executeQuery(sprintf(
                'DROP USER %s',
                TeradataQuote::quoteSingleIdentifier($existingUser['UserName'])
            ));
        }

        // create user
        $this->connection->executeQuery(sprintf(
            'CREATE USER %s AS PERM = 0 PASSWORD=%s DEFAULT DATABASE = %s;',
            TeradataQuote::quoteSingleIdentifier($userName),
            TeradataQuote::quoteSingleIdentifier($this->generateRandomPassword()),
            TeradataQuote::quoteSingleIdentifier($userName . 'DB')
        ));
    }

    protected function setUpRole(string $roleName): void
    {
        // list existing roles
        /** @var array{array{RoleName: string}} $existingUsers */
        $existingUsers = $this->connection->fetchAllAssociative(sprintf(
            'SELECT RoleName FROM DBC.RoleInfoVX WHERE RoleName = %s',
            TeradataQuote::quote($roleName)
        ));

        // delete existing roles
        foreach ($existingUsers as $existingUser) {
            $this->connection->executeQuery(sprintf(
                'DROP ROLE %s',
                TeradataQuote::quoteSingleIdentifier($existingUser['RoleName'])
            ));
        }

        // create role
        $this->connection->executeQuery(sprintf(
            'CREATE ROLE %s;',
            TeradataQuote::quoteSingleIdentifier($roleName)
        ));
    }

    protected function insertRowToTable(
        string $dbName,
        string $tableName,
        int $id,
        string $firstName,
        string $lastName
    ): void {
        $this->connection->executeQuery(sprintf(
            'INSERT INTO %s.%s VALUES (%d, %s, %s)',
            TeradataQuote::quoteSingleIdentifier($dbName),
            TeradataQuote::quoteSingleIdentifier($tableName),
            $id,
            TeradataQuote::quote($firstName),
            TeradataQuote::quote($lastName)
        ));
    }

    /** generates password with specific rules - at least one uppercase, lowercase, digit and special char */
    private function generateRandomPassword(int $len = 8): string
    {
        //enforce min length 8
        if ($len < 8) {
            $len = 8;
        }

        //define character libraries - remove ambiguous characters like iIl|1 0oO
        $sets = [];
        $sets[] = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $sets[] = 'abcdefghjkmnpqrstuvwxyz';
        $sets[] = '23456789';
        $sets[]  = '~!@#$%^&*(){}[],./?';

        $password = '';

        //append a character from each set - gets first 4 characters
        foreach ($sets as $set) {
            $password .= $set[array_rand(str_split($set))];
        }

        //use all characters to fill up to $len
        while (strlen($password) < $len) {
            //get a random set
            $randomSet = $sets[array_rand($sets)];

            //add a random char from the random set
            $password .= $randomSet[array_rand(str_split($randomSet))];
        }

        //shuffle the password string before returning!
        return str_shuffle($password);
    }

    /**
     * @param array<int|string, mixed> $expected
     * @param array<int|string, mixed> $actual
     * @param int|string $sortKey
     */
    protected function assertArrayEqualsSorted(
        array $expected,
        array $actual,
        $sortKey,
        string $message = ''
    ): void {
        $comparison = fn($attrLeft, $attrRight) => $attrLeft[$sortKey] <=> $attrRight[$sortKey];
        usort($expected, $comparison);
        usort($actual, $comparison);
        $this->assertEqualsCanonicalizing($expected, $actual, $message);
    }
}
