<?php declare(strict_types=1);

namespace GraphQlPhpValidationToolkit\Tests;

class Utils
{
    public static function nowdoc(string $str): string
    {
        $lines = \preg_split('/\\n/', $str);

        if ($lines === false) {
            return '';
        }

        if (\count($lines) <= 2) {
            return '';
        }

        // Toss out the first and last lines.
        $lines = \array_slice($lines, 1, \count($lines) - 2);

        // take the tabs from the first line, and subtract them from all lines
        $matches = [];
        \preg_match('/(^[ \t]+)/', $lines[0], $matches);

        $numLines = \count($lines);
        for ($i = 0; $i < $numLines; ++$i) {
            $lines[$i] = \str_replace($matches[0], '', $lines[$i]);
        }

        return \implode("\n", $lines);
    }

    // same as native var_export, but uses short array syntax
    public static function varExport(mixed $expression, bool $return = false): ?string
    {
        $export = var_export($expression, true);
        $patterns = [
            "/array \(/" => '[',
            "/^([ ]*)\)(,?)$/m" => '$1]$2',
            "/=>[ ]?\n[ ]+\[/" => '=> [',
            "/([ ]*)(\'[^\']+\') => ([\[\'])/" => '$1$2 => $3',
        ];
        $export = preg_replace(array_keys($patterns), array_values($patterns), $export);
        if ($return) {
            return $export;
        }

        echo $export;
        return null;
    }

    public static function toNowDoc(string $str, int $numSpaces = 0): string
    {
        $lines = \preg_split('/\\n/', $str);
        assert($lines !== false);
        for ($i = 0; $i < \count($lines); ++$i) {
            $lines[$i] = str_repeat(' ', $numSpaces) . $lines[$i];
        }
        array_unshift($lines, '');
        $lines[] = '  ';

        return \implode("\n", $lines);
    }

    public static function isSequentialArray($var)
    {
        if (!is_array($var)) {
            return false;
        }

        // Check if the array keys are sequential and numeric
        return array_keys($var) === range(0, count($var) - 1);
    }
}
