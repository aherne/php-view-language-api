<?php

namespace Lucinda\Templating;

/**
 * Represents a filesystem file used by template compilation
 *
 * The file path is held privately. The instance reads template bytes, writes PHP or
 * metadata while creating missing parent folders, and exposes existence and
 * modification time for dependency validation. It does not parse template content.
 *
 * @see ViewCompilation
 */
final class File
{
    /**
     * Filesystem path used for file reads, writes and metadata queries
     */
    private string $filePath;

    /**
     * Associates this file instance with a filesystem path
     *
     * @param string $name Relative or absolute file path used by subsequent operations
     */
    public function __construct(string $name)
    {
        $this->filePath = $name;
    }

    /**
     * Writes bytes to the file, creating missing parent directories
     *
     * @param string $content Template compilation or metadata bytes to write
     * @return void
     * @throws ViewException If PHP cannot write the file
     */
    public function setContents(string $content): void
    {
        $folder = substr($this->filePath, 0, strrpos($this->filePath, "/"));
        if (!file_exists($folder)) {
            $parts = explode("/", $folder);
            $folder = "";
            foreach ($parts as $component) {
                $folder .= $component."/";
                if (!file_exists($folder)) {
                    mkdir($folder);
                }
            }
        }
        $response = file_put_contents($this->filePath, $content);
        if ($response === false) {
            throw new ViewException("Could not write compilation: ".$this->filePath);
        }
    }

    /**
     * Gets the file's complete contents without text normalization
     *
     * PHP read failures follow the caller's configured warning-handler policy.
     *
     * @return string Exact file bytes on a successful read
     */
    public function getContents(): string
    {
        return file_get_contents($this->filePath);
    }

    /**
     * Checks whether the held file path exists
     *
     * @return bool Whether PHP finds a filesystem entry at the file path
     */
    public function exists(): bool
    {
        return file_exists($this->filePath);
    }

    /**
     * Gets the file's last modification timestamp for dependency comparisons
     *
     * PHP metadata failures follow the caller's configured warning-handler policy.
     *
     * @return int Unix timestamp reported by the filesystem on success
     */
    public function getModificationTime(): int
    {
        return filemtime($this->filePath);
    }
}
