<?php

declare(strict_types=1);

namespace Tests\Keboola\TableBackendUtils\Functional\Table\Snowflake;

use Generator;
use Keboola\Datatype\Definition\Snowflake;
use Keboola\TableBackendUtils\Column\ColumnCollection;
use Keboola\TableBackendUtils\Column\Snowflake\SnowflakeColumn;
use Keboola\TableBackendUtils\Escaping\Snowflake\SnowflakeQuote;
use Keboola\TableBackendUtils\Table\Snowflake\SnowflakeTableReflection;
use Keboola\TableBackendUtils\Table\TableStats;
use Tests\Keboola\TableBackendUtils\Functional\Connection\Snowflake\SnowflakeBaseCase;

/**
 * @covers SnowflakeTableReflection
 * @uses   ColumnCollection
 */
class SnowflakeTableReflectionTest extends SnowflakeBaseCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->cleanSchema(self::TEST_SCHEMA);
    }

    public function testGetTableColumnsNames(): void
    {
        $this->initTable();
        $ref = new SnowflakeTableReflection($this->connection, self::TEST_SCHEMA, self::TABLE_GENERIC);

        self::assertSame([
            'id',
            'first_name',
            'last_name',
        ], $ref->getColumnsNames());
    }

    public function testGetPrimaryKeysNames(): void
    {
        $this->initTable();
        $this->connection->executeQuery(
            sprintf(
                'ALTER TABLE %s.%s ADD PRIMARY KEY ("id")',
                SnowflakeQuote::quoteSingleIdentifier(self::TEST_SCHEMA),
                SnowflakeQuote::quoteSingleIdentifier(self::TABLE_GENERIC)
            )
        );
        $ref = new SnowflakeTableReflection($this->connection, self::TEST_SCHEMA, self::TABLE_GENERIC);
        self::assertEquals(['id'], $ref->getPrimaryKeysNames());
    }

    public function testGetRowsCount(): void
    {
        $this->initTable();
        $ref = new SnowflakeTableReflection($this->connection, self::TEST_SCHEMA, self::TABLE_GENERIC);
        self::assertEquals(0, $ref->getRowsCount());
        $data = [
            [1, 'franta', 'omacka'],
            [2, 'pepik', 'knedla'],
        ];
        foreach ($data as $item) {
            $this->insertRowToTable(self::TEST_SCHEMA, self::TABLE_GENERIC, ...$item);
        }
        self::assertEquals(2, $ref->getRowsCount());
    }

    /**
     * @dataProvider tableColsDataProvider
     */
    public function testColumnDefinition(
        string $sqlDef,
        string $expectedSqlDefinition,
        string $expectedType,
        ?string $expectedDefault,
        ?string $expectedLength,
        ?string $expectedNullable
    ): void {
        $this->cleanSchema(self::TEST_SCHEMA);
        $this->createSchema(self::TEST_SCHEMA);
        $sql = sprintf(
            '
            CREATE OR REPLACE TABLE %s.%s (
      "firstColumn" INT,
      "column" %s
);',
            SnowflakeQuote::quoteSingleIdentifier(self::TEST_SCHEMA),
            SnowflakeQuote::quoteSingleIdentifier(self::TABLE_GENERIC),
            $sqlDef
        );

        $this->connection->executeQuery($sql);
        $ref = new SnowflakeTableReflection($this->connection, self::TEST_SCHEMA, self::TABLE_GENERIC);
        /** @var Generator<SnowflakeColumn> $iterator */
        $iterator = $ref->getColumnsDefinitions()->getIterator();
        $iterator->next();
        $column = $iterator->current();
        /** @var Snowflake $definition */
        $definition = $column->getColumnDefinition();
        self::assertEquals($expectedLength, $definition->getLength(), 'length doesnt match');
        self::assertEquals($expectedDefault, $definition->getDefault(), 'default value doesnt match');
        self::assertEquals($expectedType, $definition->getType(), 'type doesnt match');
        self::assertEquals($expectedNullable, $definition->isNullable(), 'nullable flag doesnt match');
        self::assertEquals($expectedSqlDefinition, $definition->getSQLDefinition(), 'SQL definition doesnt match');
    }

    /**
     * @return Generator<string,array<mixed>>
     */
    public function tableColsDataProvider(): Generator
    {
        yield 'DECIMAL' => [
            'DECIMAL', // sql which goes to table
            'NUMBER(38,0)', // expected sql from getSQLDefinition
            'NUMBER', // expected type from db
            null, // default
            '38,0', // length
            true, // nullable
        ];
        yield 'NUMERIC' => [
            'NUMERIC', // sql which goes to table
            'NUMBER(38,0)', // expected sql from getSQLDefinition
            'NUMBER', // expected type from db
            null, // default
            '38,0', // length
            true, // nullable
        ];
        yield 'NUMERIC 20' => [
            'NUMERIC (20,0)', // sql which goes to table
            'NUMBER(20,0)', // expected sql from getSQLDefinition
            'NUMBER', // expected type from db
            null, // default
            '20,0', // length
            true, // nullable
        ];
        yield 'INT' => [
            'INT', // sql which goes to table
            'NUMBER(38,0)', // expected sql from getSQLDefinition
            'NUMBER', // expected type from db
            null, // default
            '38,0', // length
            true, // nullable
        ];
        yield 'INTEGER' => [
            'INTEGER', // sql which goes to table
            'NUMBER(38,0)', // expected sql from getSQLDefinition
            'NUMBER', // expected type from db
            null, // default
            '38,0', // length
            true, // nullable
        ];
        yield 'BIGINT' => [
            'BIGINT', // sql which goes to table
            'NUMBER(38,0)', // expected sql from getSQLDefinition
            'NUMBER', // expected type from db
            null, // default
            '38,0', // length
            true, // nullable
        ];
        yield 'SMALLINT' => [
            'SMALLINT', // sql which goes to table
            'NUMBER(38,0)', // expected sql from getSQLDefinition
            'NUMBER', // expected type from db
            null, // default
            '38,0', // length
            true, // nullable
        ];
        yield 'TINYINT' => [
            'TINYINT', // sql which goes to table
            'NUMBER(38,0)', // expected sql from getSQLDefinition
            'NUMBER', // expected type from db
            null, // default
            '38,0', // length
            true, // nullable
        ];
        yield 'BYTEINT' => [
            'BYTEINT', // sql which goes to table
            'NUMBER(38,0)', // expected sql from getSQLDefinition
            'NUMBER', // expected type from db
            null, // default
            '38,0', // length
            true, // nullable
        ];
        yield 'FLOAT' => [
            'FLOAT', // sql which goes to table
            'FLOAT', // expected sql from getSQLDefinition
            'FLOAT', // expected type from db
            null, // default
            null, // length
            true, // nullable
        ];
        yield 'FLOAT4' => [
            'FLOAT4', // sql which goes to table
            'FLOAT', // expected sql from getSQLDefinition
            'FLOAT', // expected type from db
            null, // default
            null, // length
            true, // nullable
        ];
        yield 'FLOAT8' => [
            'FLOAT8', // sql which goes to table
            'FLOAT', // expected sql from getSQLDefinition
            'FLOAT', // expected type from db
            null, // default
            null, // length
            true, // nullable
        ];
        yield 'DOUBLE' => [
            'DOUBLE', // sql which goes to table
            'FLOAT', // expected sql from getSQLDefinition
            'FLOAT', // expected type from db
            null, // default
            null, // length
            true, // nullable
        ];
        yield 'DOUBLE PRECISION' => [
            'DOUBLE PRECISION', // sql which goes to table
            'FLOAT', // expected sql from getSQLDefinition
            'FLOAT', // expected type from db
            null, // default
            null, // length
            true, // nullable
        ];
        yield 'REAL' => [
            'REAL', // sql which goes to table
            'FLOAT', // expected sql from getSQLDefinition
            'FLOAT', // expected type from db
            null, // default
            null, // length
            true, // nullable
        ];
        yield 'VARCHAR' => [
            'VARCHAR', // sql which goes to table
            'VARCHAR(16777216)', // expected sql from getSQLDefinition
            'VARCHAR', // expected type from db
            null, // default
            '16777216', // length
            true, // nullable
        ];
        yield 'CHAR' => [
            'CHAR', // sql which goes to table
            'VARCHAR(1)', // expected sql from getSQLDefinition
            'VARCHAR', // expected type from db
            null, // default
            '1', // length
            true, // nullable
        ];
        yield 'CHARACTER' => [
            'CHARACTER', // sql which goes to table
            'VARCHAR(1)', // expected sql from getSQLDefinition
            'VARCHAR', // expected type from db
            null, // default
            '1', // length
            true, // nullable
        ];
        yield 'STRING' => [
            'STRING', // sql which goes to table
            'VARCHAR(16777216)', // expected sql from getSQLDefinition
            'VARCHAR', // expected type from db
            null, // default
            '16777216', // length
            true, // nullable
        ];
        yield 'TEXT' => [
            'TEXT', // sql which goes to table
            'VARCHAR(16777216)', // expected sql from getSQLDefinition
            'VARCHAR', // expected type from db
            null, // default
            '16777216', // length
            true, // nullable
        ];
        yield 'BOOLEAN' => [
            'BOOLEAN', // sql which goes to table
            'BOOLEAN', // expected sql from getSQLDefinition
            'BOOLEAN', // expected type from db
            null, // default
            null, // length
            true, // nullable
        ];
        yield 'DATE' => [
            'DATE', // sql which goes to table
            'DATE', // expected sql from getSQLDefinition
            'DATE', // expected type from db
            null, // default
            null, // length
            true, // nullable
        ];
        yield 'DATETIME' => [
            'DATETIME', // sql which goes to table
            'TIMESTAMP_NTZ(9)', // expected sql from getSQLDefinition
            'TIMESTAMP_NTZ', // expected type from db
            null, // default
            '9', // length
            true, // nullable
        ];
        yield 'TIME' => [
            'TIME', // sql which goes to table
            'TIME(9)', // expected sql from getSQLDefinition
            'TIME', // expected type from db
            null, // default
            '9', // length
            true, // nullable
        ];
        yield 'TIMESTAMP' => [
            'TIMESTAMP', // sql which goes to table
            'TIMESTAMP_NTZ(9)', // expected sql from getSQLDefinition
            'TIMESTAMP_NTZ', // expected type from db
            null, // default
            '9', // length
            true, // nullable
        ];
        yield 'VARIANT' => [
            'VARIANT', // sql which goes to table
            'VARIANT', // expected sql from getSQLDefinition
            'VARIANT', // expected type from db
            null, // default
            null, // length
            true, // nullable
        ];
        yield 'BINARY' => [
            'BINARY', // sql which goes to table
            'BINARY(8388608)', // expected sql from getSQLDefinition
            'BINARY', // expected type from db
            null, // default
            '8388608', // length
            true, // nullable
        ];
        yield 'VARBINARY' => [
            'VARBINARY', // sql which goes to table
            'BINARY(8388608)', // expected sql from getSQLDefinition
            'BINARY', // expected type from db
            null, // default
            '8388608', // length
            true, // nullable
        ];
    }
}
