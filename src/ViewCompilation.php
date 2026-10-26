<?php

namespace Lucinda\Templating;

/**
 * Represents a cached PHP compilation and its template dependencies
 *
 * The compilation owns its PHP path, dependency-record path and dependency list.
 * Previously saved dependencies determine cache freshness. Recording components
 * for a rebuild starts a fresh dependency set; saving writes the set and PHP text.
 * Source-map persistence is coordinated by ViewLanguageParser.
 *
 * @see ViewLanguageParser
 * @see SourceMap
 */
final class ViewCompilation
{
    /**
     * Filesystem path of the compiled PHP artifact
     */
    private string $compilationPath;
    /**
     * Filesystem path of the saved compilation dependency list
     */
    private string $checksumPath;
    /**
     * @var string[] Previously saved or currently collected template and user-tag dependency paths
     */
    private array $components = [];
    /**
     * Whether dependency collection for this rebuild has started
     */
    private bool $collectingComponents = false;

    /**
     * Creates a compilation reference and loads its previous dependency list
     *
     * @param string $compilationsFolder Root folder used to store PHP and dependency records
     * @param string $templatePath Relative template path without extension
     */
    public function __construct(string $compilationsFolder, string $templatePath)
    {
        $this->compilationPath = $compilationsFolder."/".$templatePath.".php";
        $this->checksumPath = $compilationsFolder."/checksums/".crc32($templatePath).".crc";
        // preset components referenced in checksum
        $file = new File($this->checksumPath);
        if ($file->exists()) {
            $contents = $file->getContents();
            $this->components = explode(",", $contents);
        }
    }

    /**
     * Gets the filesystem path of the compiled PHP artifact
     *
     * @return string Compilation path derived from the root folder and template path
     */
    public function getCompilationPath(): string
    {
        return $this->compilationPath;
    }

    /**
     * Records one template or tag dependency for the current rebuild
     *
     * The first addition discards the previous compilation's dependency set. Repeated
     * references to the same path are stored once.
     *
     * @param string $path Path of a template or user tag included in this compilation
     * @return void
     */
    public function addComponent(string $path): void
    {
        // The saved dependencies validate the old compilation only. A rebuild
        // collects a fresh set, so removed imports cannot survive in its cache.
        if (!$this->collectingComponents) {
            $this->components = [];
            $this->collectingComponents = true;
        }
        if (!in_array($path, $this->components, true)) {
            $this->components[] = $path;
        }
    }

    /**
     * Gets the newest modification time among the recorded dependencies
     *
     * @return int Latest dependency timestamp, zero for an empty set, or -1 when a dependency is missing
     */
    private function getLatestModificationTime(): int
    {
        $latestDate = 0;
        foreach ($this->components as $file) {
            $file = new File($file);
            if (!$file->exists()) {
                return -1;
            }
            $time = $file->getModificationTime();
            if ($time>$latestDate) {
                $latestDate = $time;
            }
        }
        return $latestDate;
    }

    /**
     * Writes compiled PHP and the dependency list used for future freshness checks
     *
     * @param string $outputStream Generated PHP bytes to persist
     * @return void
     * @throws ViewException If either artifact cannot be written
     */
    public function save(string $outputStream): void
    {
        // saves checksum
        $file = new File($this->checksumPath);
        $file->setContents(implode(",", $this->components));

        // saves compilation
        $compilation = new File($this->compilationPath);
        $compilation->setContents($outputStream);
    }

    /**
     * Checks whether the saved compilation is absent or older than a dependency
     *
     * Missing dependencies invalidate the old list. Source-map and configuration
     * validation are performed separately by ViewLanguageParser.
     *
     * @return bool Whether the compilation must be rebuilt from its template dependencies
     */
    public function hasChanged(): bool
    {
        $compilation = new File($this->compilationPath);
        if (!empty($this->components)) {
            if ($compilation->exists()) {
                $time = $this->getLatestModificationTime();
                if ($time==-1) {
                    $this->components = [];
                    return true;
                }
                if ($compilation->getModificationTime() >= $time) {
                    return false;
                }
            }
            // reset components
            $this->components = [];
        }
        return true;
    }
}
