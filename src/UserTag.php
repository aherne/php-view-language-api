<?php

namespace Lucinda\Templating;

use Lucinda\Templating\History\SourceDocument;

/**
 * Represents a user-defined tag template and its attribute substitution
 *
 * A tag instance holds one template file path. Attribute placeholders use $[name]
 * syntax. Missing attributes become empty text; replacements retain the placeholder
 * location in the tag file. Expansion returns text or a source-aware document.
 *
 * @see UserTagParser
 * @see SourceDocument
 */
final class UserTag implements StartTag
{
    /**
     * Resolved user-tag template file expanded by this instance
     */
    private string $filePath;

    /**
     * Associates the tag instance with its resolved template file
     *
     * @param string $filePath Existing user-tag template path
     */
    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
    }

    /**
     * Expands attribute placeholders and returns the tag's template text
     *
     * @param array<string,string> $parameters Values substituted for $[name] placeholders
     * @return string Tag template after attribute substitution
     * @throws ViewException If placeholder matching fails
     */
    public function parseStartTag(array $parameters=[]): string
    {
        return $this->parseDocument($parameters)->getText();
    }

    /**
     * Expands attribute placeholders while preserving the tag file's source locations
     *
     * @param array<string,string> $parameters Values substituted for $[name]; absent values become empty text
     * @return SourceDocument Expanded tag body with literal and generated spans
     * @throws ViewException If placeholder matching fails
     */
    public function parseDocument(array $parameters = []): SourceDocument
    {
        $document = new SourceDocument((new File($this->filePath))->getContents(), $this->filePath);
        return $document->replace('/[\$]\[([\w\-.]+)\]/',
            static fn(array $match): string => $parameters[$match[1]] ?? "");
    }
}
