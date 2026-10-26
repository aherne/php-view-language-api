<?php

namespace Lucinda\Templating\TagLib\System;

use Lucinda\Templating\History\SourceDocument;
use Lucinda\Templating\ViewCompilation;
use Lucinda\Templating\File;
use Lucinda\Templating\PathResolver;
use Lucinda\Templating\ViewException;

/**
 * Expands template imports recursively while preserving each file's source locations
 *
 * The configured template root and extension determine where imports are resolved.
 * Every loaded template is recorded as a compilation dependency. An instance-local
 * active-path stack detects circular imports and is unwound even when expansion
 * fails. Escaped bodies remain protected from import parsing.
 *
 * Tag syntax: <import file="relative/template"/>
 *
 * @see SourceDocument
 * @see EscapeTag
 */
final class ImportTag
{
    /**
     * Compilation receiving the dependency path of every loaded template
     */
    private ViewCompilation $viewCompilation;
    /**
     * Configured root containing templates available for import
     */
    private string $templatesFolder;
    /**
     * Extension appended to relative import paths
     */
    private string $templatesExtension;
    /**
     * @var string[] Canonical paths currently being expanded, in recursive call order
     */
    private array $activeImportPaths = [];

    /**
     * Instance operation resolving template paths and enforcing configured roots
     */
    private PathResolver $pathResolver;

    /**
     * Configures import-file resolution and compilation dependency recording
     *
     * @param string $templatesFolder Root folder containing templates available to import
     * @param string $templatesExtension Extension appended to relative import names
     * @param ViewCompilation $viewCompilation Compilation receiving imported-file dependencies
     */
    public function __construct(string $templatesFolder, string $templatesExtension, ViewCompilation $viewCompilation)
    {
        $this->pathResolver = new PathResolver();
        $this->templatesFolder = $templatesFolder;
        $this->templatesExtension = $templatesExtension;
        $this->viewCompilation = $viewCompilation;
    }

    /**
     * Loads and recursively expands a template into plain text
     *
     * @param string $templateFile Relative template path without its extension
     * @param EscapeTag $escaper Compilation-local store protecting escaped bodies
     * @return string Imported template text with nested imports expanded
     * @throws ViewException If a file is invalid, an import is malformed or a cycle is detected
     */
    public function parse(string $templateFile, EscapeTag $escaper): string
    {
        return $this->parseDocument($templateFile, $escaper)->getText();
    }

    /**
     * Loads and recursively expands imports without losing original file locations
     *
     * The active-import stack is unwound in a finally block. Nested parser errors keep
     * their existing location; unresolved imports gain the calling declaration location.
     *
     * @param string $templateFile Relative template path without its extension
     * @param EscapeTag $escaper Store shared by every import in this compilation
     * @return SourceDocument Expanded text retaining the source locations of each file
     * @throws ViewException If resolution, import syntax or circular-dependency validation fails
     */
    public function parseDocument(string $templateFile, EscapeTag $escaper): SourceDocument
    {
        $path = $this->pathResolver->resolveFile($this->templatesFolder, $templateFile, $this->templatesExtension, "import");
        if (in_array($path, $this->activeImportPaths, true)) {
            throw new ViewException("Circular import: ".implode(" -> ", [...$this->activeImportPaths, $path]));
        }
        $this->activeImportPaths[] = $path;
        try {
            $document = new SourceDocument((new File($path))->getContents(), $path);
            $document = $escaper->backupDocument($document);
            $this->viewCompilation->addComponent($path);
            return $document->replace(
                '/<import\s*(file\s*=\s*"(.*?)")?\s*\/?>/',
                function (array $matches) use ($escaper): SourceDocument {
                    if (empty($matches[2])) {
                        throw new ViewException("Tag 'import' requires attribute: file");
                    }
                    return $this->parseDocument($matches[2], $escaper);
                }
            );
        } finally {
            array_pop($this->activeImportPaths);
        }
    }
}
