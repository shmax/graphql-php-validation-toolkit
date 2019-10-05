<?php

declare(strict_types=1);

namespace GraphQL\Tests;

use function array_slice;
use function count;
use function implode;
use function preg_match;
use function preg_split;
use function str_replace;

class Utils
{
    public static function nowdoc(string $str) : string
    {
        $lines = preg_split('/\\n/', $str);

        if($lines === false) {
            return '';
        }

        if (count($lines) <= 2) {
            return '';
        }

        // Toss out the first and last lines.
        $lines = array_slice($lines, 1, count($lines) - 2);

        // take the tabs from the first line, and subtract them from all lines
        $matches = [];
        preg_match('/(^[ \t]+)/', $lines[0], $matches);

        $numLines = count($lines);
        for ($i = 0; $i < $numLines; $i++) {
            $lines[$i] = str_replace($matches[0], '', $lines[$i]);
        }

        return implode("\n", $lines);
    }
}
