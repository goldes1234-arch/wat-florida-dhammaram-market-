<?php

namespace Tests\Unit;

use Tests\TestCase;

/** Dates shown to people are American month/day/year. A "d/m/Y" format sneaking back in would show 03/10 for October 3rd. */
class DateFormatTest extends TestCase
{
    public function testNoViewServiceOrControllerFormatsADateDayFirst(): void
    {
        $offenders = [];
        foreach (['app', 'resources'] as $dir) {
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(BASE_PATH . '/' . $dir, \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }
                if (preg_match("#(?:date|format)\\(\\s*['\"]d/m#", (string) file_get_contents($file->getPathname()))) {
                    $offenders[] = substr($file->getPathname(), strlen(BASE_PATH) + 1);
                }
            }
        }

        $this->assertSame([], $offenders, 'day-first date format found in: ' . implode(', ', $offenders));
    }

    public function testTheAmericanFormatPutsTheMonthFirst(): void
    {
        $this->assertSame('10/03/2026', date('m/d/Y', strtotime('2026-10-03')));
    }
}
