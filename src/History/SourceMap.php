<?php

namespace Lucinda\Templating\History;

/**
 * Represents the correspondence between compiled PHP lines and template locations
 *
 * A map holds original file and line entries indexed by generated PHP line. Its
 * contents are supplied during construction and are not modified afterwards.
 * Each location contains source (file path string) and line (one-based integer).
 * Building mappings and persisting them belong to separate instance operations.
 *
 * @see SourceMapBuilder
 * @see SourceMapStorage
 * @see SourceContext
 */
final class SourceMap
{
    /**
     * @var array<int,array<string,string|int>> Original locations indexed by one-based generated PHP line
     */
    private array $sourceLocationsByCompiledLine;

    /**
     * Creates a map from known compiled-line and original-source correspondences
     *
     * @param array<int,array<string,string|int>> $sourceLocationsByCompiledLine Original locations indexed by positive compiled line
     */
    public function __construct(array $sourceLocationsByCompiledLine = [])
    {
        $this->sourceLocationsByCompiledLine = $sourceLocationsByCompiledLine;
    }

    /**
     * Gets the original template location of one generated PHP line
     *
     * @param int $line One-based line reported by PHP for the compiled file
     * @return array<string,string|int>|null Original location, or null when the line is unmapped
     */
    public function getSourceLocation(int $line): ?array
    {
        return $this->sourceLocationsByCompiledLine[$line] ?? null;
    }

    /**
     * Gets all compiled-line correspondences represented by this map
     *
     * @return array<int,array<string,string|int>> Original source locations indexed by compiled PHP line
     */
    public function getSourceLocations(): array
    {
        return $this->sourceLocationsByCompiledLine;
    }
}
