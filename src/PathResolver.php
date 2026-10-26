<?php

namespace Lucinda\Templating;

/**
 * Resolves template and tag paths within caller-supplied filesystem roots
 *
 * File, namespace-folder and user-tag resolution reject invalid paths and enforce
 * root containment after resolving symlinks. Resolution is performed by an instance whose
 * cooperating methods receive roots, names, extensions and diagnostic context.
 *
 * @see TagLib\System\ImportTag
 * @see TagLib\System\NamespaceTag
 * @see UserTagParser
 */
final class PathResolver
{
    /**
     * Resolves an existing template file inside a configured root
     *
     * @param string $root Folder containing permitted templates
     * @param string $relativePath Template path relative to the root, without extension
     * @param string $extension File extension without its leading dot
     * @param string $context Tag or operation name used in diagnostics
     * @return string Canonical file path with normalized directory separators
     * @throws ViewException If the relative path is invalid, absent or outside the root
     */
    public function resolveFile(string $root, string $relativePath, string $extension, string $context): string
    {
        $this->assertRelativePath($relativePath, $context);
        $path = $this->join($root, $relativePath.".".$extension);
        $realPath = realpath($path);
        if ($realPath === false || !is_file($realPath)) {
            throw new ViewException("Invalid value of 'file' attribute @ '".$context."' tag: ".$relativePath);
        }
        $this->assertInsideRoot($root, $realPath, $context);
        return $this->normalize($realPath);
    }

    /**
     * Resolves an existing tag-library folder and verifies root containment
     *
     * @param string $root Allowed tag-library root, or an empty string to accept an independent folder
     * @param string $folder Relative folder, a dot for the root itself, or an absolute folder when no root is set
     * @param string $context Tag or operation name used in diagnostics
     * @return string Canonical folder path with normalized directory separators
     * @throws ViewException If the folder does not exist or violates the configured root
     */
    public function resolveFolder(string $root, string $folder, string $context): string
    {
        if ($root && $folder!==".") {
            $this->assertRelativePath($folder, $context);
        }
        $path = ($root ? $this->join($root, $folder) : $folder);
        $realPath = realpath($path);
        if ($realPath === false || !is_dir($realPath)) {
            throw new ViewException("Invalid value of 'folder' attribute @ '".$context."' tag: ".$folder);
        }
        if ($root) {
            $this->assertInsideRoot($root, $realPath, $context);
        }
        return $this->normalize($realPath);
    }

    /**
     * Resolves a user-tag template beneath its effective library root
     *
     * @param string $namespaceFolder Effective root containing user-tag libraries
     * @param string $libraryName Single library path segment from the declaration
     * @param string $tagName Single tag-name path segment from the declaration
     * @param string $extension Tag-template extension without its leading dot
     * @return string Canonical user-tag file path with normalized separators
     * @throws ViewException If a name is invalid or the resolved file is absent or outside its root
     */
    public function resolveUserTag(
        string $namespaceFolder,
        string $libraryName,
        string $tagName,
        string $extension
    ): string {
        $this->assertPathSegment($libraryName, "tag library");
        $this->assertPathSegment($tagName, "tag");
        $path = $this->join($namespaceFolder, $libraryName."/".$tagName.".".$extension);
        $realPath = realpath($path);
        if ($realPath === false || !is_file($realPath)) {
            throw new ViewException("User tag not found: ".$libraryName."/".$tagName);
        }
        $this->assertInsideRoot($namespaceFolder, $realPath, "user tag");
        return $this->normalize($realPath);
    }

    /**
     * Normalizes directory separators without resolving or validating a path
     *
     * @param string $path Relative or absolute path to normalize
     * @return string Supplied path with backslashes replaced by forward slashes
     */
    public function normalize(string $path): string
    {
        return str_replace("\\", "/", $path);
    }

    /**
     * Verifies that a resolved path belongs to an existing configured root
     *
     * @param string $root Allowed root folder, or an empty string to skip containment checks
     * @param string $path Canonical file or folder path to inspect
     * @param string $context Operation name used when reporting a containment failure
     * @return void
     * @throws ViewException If the root is invalid or the path lies outside it
     */
    private function assertInsideRoot(string $root, string $path, string $context): void
    {
        if (!$root) {
            return;
        }
        $realRoot = realpath($root);
        if ($realRoot === false || !is_dir($realRoot)) {
            throw new ViewException("Invalid root folder @ '".$context."': ".$root);
        }
        $realRoot = rtrim($this->normalize($realRoot), "/");
        $path = $this->normalize($path);
        if ($path!==$realRoot && !str_starts_with($path, $realRoot."/")) {
            throw new ViewException("Path escapes configured root @ '".$context."': ".$path);
        }
    }

    /**
     * Rejects absolute, empty and traversal-containing relative paths
     *
     * @param string $path Relative path whose segments must stay within the configured root
     * @param string $context Operation name used in diagnostic messages
     * @return void
     * @throws ViewException If the path is empty, absolute or contains forbidden segments
     */
    private function assertRelativePath(string $path, string $context): void
    {
        if ($path==="" || $this->isAbsolutePath($path)) {
            throw new ViewException("Invalid relative path @ '".$context."': ".$path);
        }
        $parts = preg_split("/[\\\\\/]+/", $path);
        foreach ($parts as $part) {
            if ($part==="" || $part==="." || $part==="..") {
                throw new ViewException("Invalid relative path @ '".$context."': ".$path);
            }
        }
    }

    /**
     * Validates one library or tag name as a single path segment
     *
     * @param string $segment Library or tag name to validate
     * @param string $context Kind of segment used in the error message
     * @return void
     * @throws ViewException If the segment is empty, contains a separator or denotes traversal
     */
    private function assertPathSegment(string $segment, string $context): void
    {
        if ($segment==="" || str_contains($segment, "/") || str_contains($segment, "\\") || $segment==="." || $segment==="..") {
            throw new ViewException("Invalid ".$context." path segment: ".$segment);
        }
    }

    /**
     * Checks for Unix-rooted or Windows drive-rooted path syntax
     *
     * @param string $path Path to inspect without accessing the filesystem
     * @return bool Whether the path starts with a recognized absolute-path prefix
     */
    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, "/") || (bool) preg_match("/^[A-Z]:[\\\\\/]/i", $path);
    }

    /**
     * Joins a configured root with a relative path using one directory separator
     *
     * @param string $root Optional root folder whose trailing separators are removed
     * @param string $path Relative file or folder path to append
     * @return string Joined path, or the supplied relative path when the root is empty
     */
    private function join(string $root, string $path): string
    {
        return ($root ? rtrim($root, "/\\")."/" : "").$path;
    }
}
