<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * public.js is one big function scope shared by unrelated features (lightbox, booth map, carousels).
 * A function or var declared twice in it silently overwrites the first one — that is how the map's
 * applyZoom()/zoom once broke the lightbox's zoom on event pages. Names declared more than once at
 * any nesting level are therefore listed here on purpose; a new collision fails this test.
 */
class PublicJsScopeTest extends TestCase
{
    /** Declared in more than one place, but verified harmless (separate IIFE/closure scopes or never shared). */
    private const KNOWN_DUPLICATES = ['i', 'e', 'el', 'dot', 'card', 'dx', 'dy', 'btn', 'im', 't', 'm', 'item', 'ev', 'd', 'entry', 'target', 'dropdown', 'toggle', 'radio', 'option', 'refresh', 'frame', 'progress', 'dist', 'canvas', 'rect', 'p', 'a', 'b'];

    private function declaredNames(string $js): array
    {
        // drop comments and string literals so words inside them are not counted
        $js = preg_replace('#/\*.*?\*/#s', '', $js);
        $js = preg_replace('#^\s*//.*$#m', '', $js);
        $js = preg_replace("#'(?:[^'\\\\\\n]|\\\\.)*'|\"(?:[^\"\\\\\\n]|\\\\.)*\"#", "''", $js);

        $names = [];
        preg_match_all('#\bfunction\s+([A-Za-z_$][\w$]*)\s*\(#', $js, $f);
        preg_match_all('#\bvar\s+([A-Za-z_$][\w$]*)#', $js, $v);
        foreach (array_merge($f[1], $v[1]) as $name) {
            $names[$name] = ($names[$name] ?? 0) + 1;
        }
        return $names;
    }

    public function testLightboxHelpersHaveUniqueNamesInTheSharedScope(): void
    {
        $js = file_get_contents(BASE_PATH . '/assets/js/public.js');
        $names = $this->declaredNames($js);

        foreach (['lbApplyZoom', 'lbResetZoom', 'lbClampPan', 'lbSetScale', 'lbZoom', 'LB_MAX_ZOOM'] as $name) {
            $this->assertSame(1, $names[$name] ?? 0, "$name must be declared exactly once");
        }
        // the map keeps its own, differently named helpers
        $this->assertTrue(($names['applyZoom'] ?? 0) <= 1, 'applyZoom (booth map) must not be declared twice');
        $this->assertTrue(($names['zoom'] ?? 0) <= 1, 'zoom (booth map) must not be declared twice');
    }

    public function testNoNamedFunctionIsDeclaredTwiceInPublicJs(): void
    {
        $js = file_get_contents(BASE_PATH . '/assets/js/public.js');
        preg_match_all('#\bfunction\s+([A-Za-z_$][\w$]*)\s*\(#', preg_replace(['#/\*.*?\*/#s', '#^\s*//.*$#m'], '', $js), $m);
        $dups = array_keys(array_filter(array_count_values($m[1]), static fn (int $n) => $n > 1));
        $dups = array_values(array_diff($dups, self::KNOWN_DUPLICATES));

        $this->assertSame([], $dups, 'functions declared twice overwrite each other: ' . implode(', ', $dups));
    }
}
