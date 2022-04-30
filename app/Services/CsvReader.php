<?php declare(strict_types=1);

namespace App\Services;

/**
 * Class CsvReader
 *
 * Позволяет построчно читать CSV файл, получая значения полей,
 * указанных в конструкторе
 */
class CsvReader
{
    /**
     * @var string
     */
    protected string $csv;

    private ?string $currentLine;

    /**
     * @var string
     */
    protected string $csvColumnSeparator;

    public function __construct(
        string $csv,
        private array $requiredCsvFieldsWithNumber,
        private string $csvLineSeparator,
        string $csvColumnSeparator = ","
    ) {
        $this->csv                = $csv;
        $this->csvColumnSeparator = $csvColumnSeparator;

        $this->currentLine = strtok($this->csv, "\n");
    }

    public function getNextParsedLine(): ?array
    {
        if ($this->currentLine === null) {
            return null;
        }

        $parsedCsvLine = str_getcsv($this->currentLine, $this->csvColumnSeparator);
        $lineValues    = [];
        foreach ($this->requiredCsvFieldsWithNumber as $csvKeyNumber => $fieldName) {
            $lineValues[$fieldName] = $parsedCsvLine[$csvKeyNumber];
        }

        $line = strtok($this->csvLineSeparator);
        $this->currentLine = false === $line ? null : $line;

        return $lineValues;
    }
}
