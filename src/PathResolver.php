<?php

namespace Lucinda\Templating;

/**
 * Resolves template and tag paths while preventing traversal outside configured roots.
 */
class PathResolver
{
    /**
     * Resolves an existing file inside a configured root.
     *
     * @param string $root Root folder.
     * @param string $relativePath Path relative to root, without extension.
     * @param string $extension File extension without dot.
     * @param string $context Human-readable context used in exception messages.
     * @return string
     * @throws ViewException If path is invalid or outside root.
     */
    public static function resolveFile(string $root, string $relativePath, string $extension, string $context): string
    {
        self::assertRelativePath($relativePath, $context);
        $path = self::join($root, $relativePath.".".$extension);
        $realPath = realpath($path);
        if ($realPath === false || !is_file($realPath)) {
            throw new ViewException("Invalid value of 'file' attribute @ '".$context."' tag: ".$relativePath);
        }
        self::assertInsideRoot($root, $realPath, $context);
        return self::normalize($realPath);
    }

    /**
     * Resolves an existing folder inside a configured root.
     *
     * @param string $root Root folder.
     * @param string $folder Folder relative to root, or absolute when root is empty.
     * @param string $context Human-readable context used in exception messages.
     * @return string
     * @throws ViewException If folder is invalid or outside root.
     */
    public static function resolveFolder(string $root, string $folder, string $context): string
    {
        if ($root && $folder!==".") {
            self::assertRelativePath($folder, $context);
        }
        $path = ($root ? self::join($root, $folder) : $folder);
        $realPath = realpath($path);
        if ($realPath === false || !is_dir($realPath)) {
            throw new ViewException("Invalid value of 'folder' attribute @ '".$context."' tag: ".$folder);
        }
        if ($root) {
            self::assertInsideRoot($root, $realPath, $context);
        }
        return self::normalize($realPath);
    }

    /**
     * Resolves a user tag file inside a namespace folder.
     *
     * @param string $namespaceFolder Folder where tag libraries are located.
     * @param string $libraryName Tag library name.
     * @param string $tagName Tag name.
     * @param string $extension Tag file extension without dot.
     * @return string
     * @throws ViewException If path is invalid or outside namespace folder.
     */
    public static function resolveUserTag(
        string $namespaceFolder,
        string $libraryName,
        string $tagName,
        string $extension
    ): string {
        self::assertPathSegment($libraryName, "tag library");
        self::assertPathSegment($tagName, "tag");
        $path = self::join($namespaceFolder, $libraryName."/".$tagName.".".$extension);
        $realPath = realpath($path);
        if ($realPath === false || !is_file($realPath)) {
            throw new ViewException("User tag not found: ".$libraryName."/".$tagName);
        }
        self::assertInsideRoot($namespaceFolder, $realPath, "user tag");
        return self::normalize($realPath);
    }

    /**
     * @param string $path
     * @return string
     */
    public static function normalize(string $path): string
    {
        return str_replace("\\", "/", $path);
    }

    /**
     * @param string $root
     * @param string $path
     * @return void
     * @throws ViewException
     */
    private static function assertInsideRoot(string $root, string $path, string $context): void
    {
        if (!$root) {
            return;
        }
        $realRoot = realpath($root);
        if ($realRoot === false || !is_dir($realRoot)) {
            throw new ViewException("Invalid root folder @ '".$context."': ".$root);
        }
        $realRoot = rtrim(self::normalize($realRoot), "/");
        $path = self::normalize($path);
        if ($path!==$realRoot && !str_starts_with($path, $realRoot."/")) {
            throw new ViewException("Path escapes configured root @ '".$context."': ".$path);
        }
    }

    /**
     * @param string $path
     * @param string $context
     * @return void
     * @throws ViewException
     */
    private static function assertRelativePath(string $path, string $context): void
    {
        if ($path==="" || self::isAbsolutePath($path)) {
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
     * @param string $segment
     * @param string $context
     * @return void
     * @throws ViewException
     */
    private static function assertPathSegment(string $segment, string $context): void
    {
        if ($segment==="" || str_contains($segment, "/") || str_contains($segment, "\\") || $segment==="." || $segment==="..") {
            throw new ViewException("Invalid ".$context." path segment: ".$segment);
        }
    }

    /**
     * @param string $path
     * @return bool
     */
    private static function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, "/") || (bool) preg_match("/^[A-Z]:[\\\\\/]/i", $path);
    }

    /**
     * @param string $root
     * @param string $path
     * @return string
     */
    private static function join(string $root, string $path): string
    {
        return ($root ? rtrim($root, "/\\")."/" : "").$path;
    }
}
