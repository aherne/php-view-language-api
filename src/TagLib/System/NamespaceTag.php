<?php

namespace Lucinda\Templating\TagLib\System;

use Lucinda\Templating\AttributesParser;
use Lucinda\Templating\PathResolver;
use Lucinda\Templating\ViewException;

/**
 * Implements a tag that tracks location of user defined tag libraries.
 *
 * Tag syntax:
 * <namespace taglib="NAME" folder="FOLDERPATH">
 */
class NamespaceTag
{
    private string $taglibFolder;
    private AttributesParser $attributesParser;
    /**
     * @var array<string,string>
     */
    private array $namespaces = [];

    /**
     * Creates an instance with default user defined tag library location. This will be used if no <:namespace>
     * declaration is declared for that tag library
     *
     * @param string $taglibFolder Default location of tag libraries on disk.
     */
    public function __construct(string $taglibFolder)
    {
        $this->taglibFolder = $taglibFolder;
        $this->attributesParser = new AttributesParser(array("taglib", "folder"));
    }

    /**
     * Parses output stream for namespace tags. If found, remembers taglib/folder correspondence then removes
     * tag declaration.
     *
     * @param  string $outputStream Output stream possibly containing <:namespace ...> tag declarations.
     * @throws ViewException If referenced folder could not be located on disk.
     * @return string Output stream without <:namespace ...> tag declarations.
     */
    public function parse(string $outputStream): string
    {
        return preg_replace_callback(
            "/<namespace\s*(.*)\s*\/?>/",
            function ($matches) {
                $parameters = $this->attributesParser->parse($matches[1]);
                $folder = PathResolver::resolveFolder($this->taglibFolder, $parameters["folder"], "namespace");
                $this->namespaces[$parameters["taglib"]] = $folder;
                return "";
            },
            $outputStream
        );
    }

    /**
     * Gets taglib location on disk.
     *
     * @param  string $tagLib Name of tag library.
     * @return string Folder tag library folder lies into.
     */
    public function get(string $tagLib): string
    {
        return (!empty($this->namespaces) && isset($this->namespaces[$tagLib]) ? $this->namespaces[$tagLib] : $this->taglibFolder);
    }
}
