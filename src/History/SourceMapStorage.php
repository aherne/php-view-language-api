<?php

namespace Lucinda\Templating\History;

use Lucinda\Templating\File;
use Lucinda\Templating\ViewException;

/**
 * Persists source maps and verifies their association with compiled PHP
 *
 * Maps are stored beside PHP compilations in JSON files with a .map.json suffix.
 * Loading checks the metadata format, PHP content hash, optional configuration
 * identity and location entries before constructing a SourceMap. Missing or invalid
 * storage returns null so the compiler can rebuild and diagnostics can fall back.
 *
 * This operation owns the storage format; the map itself contains no filesystem,
 * hashing or serialization behavior. All per-call state is local to the operation.
 *
 * @see Maps
 * @see ViewLanguageParser
 * @see Context
 */
final class SourceMapStorage
{
    /**
     * Metadata format version used to reject incompatible cached maps
     */
    private const VERSION = 2;

    /**
     * Saves a map beside the PHP artifact whose lines it describes
     *
     * @param SourceMap $sourceMap Original source locations indexed by generated line
     * @param string $compiledFile PHP compilation path; metadata uses the .map.json suffix
     * @param string $php Exact compiled bytes used to calculate the content hash
     * @param string $configuration Identity of compiler roots, extension and escaping policy
     * @return void
     * @throws ViewException If the metadata file cannot be written
     * @throws \JsonException If map metadata cannot be encoded as JSON
     */
    public function save(SourceMap $sourceMap, string $compiledFile, string $php, string $configuration): void
    {
        (new File($compiledFile.".map.json"))->setContents(json_encode([
            "version"=>self::VERSION,
            "hash"=>hash("sha256", $php),
            "configuration"=>$configuration,
            "lines"=>$sourceMap->getSourceLocations()
        ], JSON_THROW_ON_ERROR));
    }

    /**
     * Loads a map only when its metadata matches the compiled PHP and configuration
     *
     * @param string $compiledFile Existing PHP compilation path
     * @param string|null $configuration Expected compiler identity, or null to skip that comparison
     * @return SourceMap|null Valid map, or null for missing, unreadable, invalid or mismatched metadata
     */
    public function load(string $compiledFile, ?string $configuration = null): ?SourceMap
    {
        if (!is_file($compiledFile) || !is_file($compiledFile.".map.json")) {
            return null;
        }
        $contents = file_get_contents($compiledFile.".map.json");
        if ($contents === false) {
            return null;
        }
        $data = json_decode($contents, true);
        if (!is_array($data) || ($data["version"] ?? null) !== self::VERSION
            || ($data["hash"] ?? null) !== hash_file("sha256", $compiledFile)
            || ($configuration !== null && ($data["configuration"] ?? null) !== $configuration)
            || !is_array($data["lines"] ?? null)) {
            return null;
        }
        $sourceLocationsByCompiledLine = [];
        foreach ($data["lines"] as $line=>$location) {
            if (!is_int($line) || $line < 1 || !is_array($location)
                || !is_string($location["source"] ?? null)
                || !is_int($location["line"] ?? null) || $location["line"] < 1) {
                return null;
            }
            $sourceLocationsByCompiledLine[$line] = ["source"=>$location["source"], "line"=>$location["line"]];
        }
        return new SourceMap($sourceLocationsByCompiledLine);
    }
}
