<?php

declare(strict_types=1);

namespace Keboola\Db\ImportExport;

class ImportOptions implements ImportOptionsInterface
{
    /** @var string[] */
    private array $ignoreColumns;

    private bool $useTimestamp;

    /** @var string[] */
    private array $convertEmptyValuesToNull;

    private bool $isIncremental;

    private int $numberOfIgnoredLines;

    /** @var self::USING_TYPES_* */
    protected string $usingTypes;

    public const SAME_TABLES_REQUIRED = true;
    public const SAME_TABLES_NOT_REQUIRED = false;
    public const NULL_MANIPULATION_ENABLED = true;
    public const NULL_MANIPULATION_SKIP = false;

    /**
     * @param string[] $convertEmptyValuesToNull
     * @param self::USING_TYPES_* $usingTypes
     * @param string[] $ignoreColumns
     */
    public function __construct(
        array $convertEmptyValuesToNull = [],
        bool $isIncremental = false,
        bool $useTimestamp = false,
        int $numberOfIgnoredLines = self::SKIP_NO_LINE,
        string $usingTypes = self::USING_TYPES_STRING,
        array $ignoreColumns = []
    ) {
        $this->useTimestamp = $useTimestamp;
        $this->convertEmptyValuesToNull = $convertEmptyValuesToNull;
        $this->isIncremental = $isIncremental;
        $this->numberOfIgnoredLines = $numberOfIgnoredLines;
        $this->usingTypes = $usingTypes;
        $this->ignoreColumns = $ignoreColumns;
    }

    /**
     * @return string[]
     */
    public function getConvertEmptyValuesToNull(): array
    {
        return $this->convertEmptyValuesToNull;
    }

    public function getNumberOfIgnoredLines(): int
    {
        return $this->numberOfIgnoredLines;
    }

    public function isIncremental(): bool
    {
        return $this->isIncremental;
    }

    public function useTimestamp(): bool
    {
        return $this->useTimestamp;
    }

    public function usingUserDefinedTypes(): bool
    {
        return $this->usingTypes === self::USING_TYPES_USER;
    }

    public function ignoreColumns(): array
    {
        return $this->ignoreColumns;
    }
}
