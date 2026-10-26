<?php

namespace Lucinda\Templating\TagLib\System;

use Lucinda\Templating\AttributesParser;
use Lucinda\Templating\History\SourceDocument;
use Lucinda\Templating\PathResolver;
use Lucinda\Templating\ViewException;

/**
 * Resolves named tag-library roots declared by a template
 *
 * Namespace declarations associate library names with folders contained in the
 * configured tag-library root. Declarations disappear from the document after their
 * policy is recorded. Libraries without an explicit declaration use the default root.
 *
 * Tag syntax: <namespace taglib="name" folder="relative/folder"/>
 *
 * @see \Lucinda\Templating\UserTagParser
 */
final class NamespaceTag
{
    /**
     * Default root containing user-defined tag-library folders
     */
    private string $tagLibrariesFolder;
    /**
     * Parser requiring the taglib and folder declaration attributes
     */
    private AttributesParser $attributesParser;
    /**
     * @var array<string,string> Explicit root folders keyed by declared library name
     */
    private array $tagLibraryPaths = [];

    /**
     * Instance operation resolving template paths and enforcing configured roots
     */
    private PathResolver $pathResolver;

    /**
     * Configures the default root and required namespace-declaration attributes
     *
     * @param string $taglibFolder Default root containing user-defined tag-library folders
     */
    public function __construct(string $taglibFolder)
    {
        $this->pathResolver = new PathResolver();
        $this->tagLibrariesFolder = $taglibFolder;
        $this->attributesParser = new AttributesParser(array("taglib", "folder"));
    }

    /**
     * Records namespace declarations and removes them from untracked template text
     *
     * @param string $outputStream Template text containing namespace declarations
     * @return string Text without namespace declarations
     * @throws ViewException If required attributes or namespace-folder resolution fail
     */
    public function parse(string $outputStream): string
    {
        return $this->parseDocument(new SourceDocument($outputStream))->getText();
    }

    /**
     * Records namespace policy without losing the locations of remaining text
     *
     * @param SourceDocument $outputStream Document containing namespace declarations
     * @return SourceDocument Document with namespace declarations removed
     * @throws ViewException If declaration attributes or folder containment are invalid
     */
    public function parseDocument(SourceDocument $outputStream): SourceDocument
    {
        return $outputStream->replace(
            "/<namespace\s*(.*)\s*\/?>/",
            function ($matches) {
                $parameters = $this->attributesParser->parse($matches[1]);
                $folder = $this->pathResolver->resolveFolder($this->tagLibrariesFolder, $parameters["folder"], "namespace");
                $this->tagLibraryPaths[$parameters["taglib"]] = $folder;
                return "";
            }
        );
    }

    /**
     * Gets the effective root folder of a named user-tag library
     *
     * @param string $tagLib Library name from a user-tag declaration
     * @return string Explicitly declared folder or the configured default tag-library root
     */
    public function getTagLibraryPath(string $tagLib): string
    {
        return (!empty($this->tagLibraryPaths) && isset($this->tagLibraryPaths[$tagLib]) ? $this->tagLibraryPaths[$tagLib] : $this->tagLibrariesFolder);
    }
}
