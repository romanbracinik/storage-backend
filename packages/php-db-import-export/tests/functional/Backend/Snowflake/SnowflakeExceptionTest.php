<?php

declare(strict_types=1);

namespace Tests\Keboola\Db\ImportExportFunctional\Backend\Snowflake;

use Generator;
use Keboola\Db\Import\Exception as ImportException;
use Keboola\Db\ImportExport\Backend\Snowflake\SnowflakeException;
use Keboola\Db\ImportExport\Storage\FileNotFoundException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class SnowflakeExceptionTest extends TestCase
{
    /**
     * @dataProvider provideExceptions
     * @param class-string<object> $expectedException
     */
    public function testCovertException(
        string $inputMessage,
        string $expectedException,
        string $expectedMessage,
        int $expectedCode,
        bool $hasPrevious
    ): void {
        $inputException = new RuntimeException($inputMessage);
        $convertedException = SnowflakeException::covertException($inputException);

        $this->assertInstanceOf($expectedException, $convertedException);
        $this->assertSame($expectedMessage, $convertedException->getMessage());
        $this->assertSame($expectedCode, $convertedException->getCode());
        if ($hasPrevious) {
            $this->assertSame($inputException, $convertedException->getPrevious());
        } else {
            $this->assertNull($convertedException->getPrevious());
        }
    }

    public function provideExceptions(): Generator
    {
        yield 'remote file not found' => [
            "Remote file 'abc' was not found",
            FileNotFoundException::class,
            "Load error: Remote file 'abc' was not found",
            7, // MANDATORY_FILE_NOT_FOUND
            false,
        ];

        yield 'constraint violation not null' => [
            'An exception occurred while executing a query: NULL result in a non-nullable column',
            ImportException::class,
            'Load error: An exception occurred while executing a query: NULL result in a non-nullable column',
            1, // UNKNOWN_ERROR
            true,
        ];

        yield 'value conversion' => [
            "Numeric value 'male' is not recognized",
            ImportException::class,
            "Load error: Numeric value 'male' is not recognized",
            13, // VALUE_CONVERSION
            true,
        ];

        yield 'unknown exception' => [
            'Some error',
            RuntimeException::class,
            'Some error',
            0,
            false,
        ];
    }
}
