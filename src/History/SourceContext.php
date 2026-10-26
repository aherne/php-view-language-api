<?php

namespace Lucinda\Templating\History;

/**
 * Resolves the original template location of a compiled PHP error
 *
 * This instance operation loads the matching source map and returns its
 * file and line entry. Missing, invalid or unmapped compilations return null; the
 * operation never inspects rendered content or alters the compilation.
 *
 * @see SourceMap
 * @see SourceMapStorage
 * @see Wrapper
 */
final class SourceContext
{
    /**
     * Storage operation validating and loading maps for compiled PHP files
     */
    private SourceMapStorage $sourceMapStorage;

    /**
     * Creates the storage operation used for original-source lookups
     */
    public function __construct()
    {
        $this->sourceMapStorage = new SourceMapStorage();
    }

    /**
     * Gets a template location by loading the map of a compiled PHP file
     *
     * Location entries contain source (file path string) and line (one-based integer).
     *
     * @param string $compiledFile PHP compilation path with an adjacent source-map file
     * @param int $compiledLine One-based line reported by PHP
     * @return array<string,string|int>|null Original source location, or null when unavailable
     */
    public function getSourceLocation(string $compiledFile, int $compiledLine): ?array
    {
        return $this->sourceMapStorage->load($compiledFile)?->getSourceLocation($compiledLine);
    }
}
