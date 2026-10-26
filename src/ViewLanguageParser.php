<?php

namespace Lucinda\Templating;

use Lucinda\Templating\History\SourceDocument;
use Lucinda\Templating\History\SourceMapBuilder;
use Lucinda\Templating\History\SourceMapStorage;
use Lucinda\Templating\TagLib\System\EscapeTag;
use Lucinda\Templating\TagLib\System\ImportTag;
use Lucinda\Templating\TagLib\System\NamespaceTag;

/**
 * Compiles a ViewLanguage template into cached PHP with a separate source map
 *
 * The operation expands imports and user tags, resolves namespaces, translates
 * system tags and expressions, then restores escaped bodies. Every stage carries
 * source locations in a SourceDocument. PHP layout preparation belongs to this
 * compiler and preserves literal line endings around generated directives.
 *
 * compile() returns a PHP file path rather than rendered text. Wrapper binds that
 * file to application data. Cache reuse requires current dependencies and a valid
 * map matching the compiler settings.
 *
 * @see SourceDocument
 * @see SourceMap
 * @see SourceMapBuilder
 * @see SourceMapStorage
 * @see ViewCompilation
 * @see Wrapper
 */
final class ViewLanguageParser
{
    /**
     * Root folder from which templates and imports are resolved
     */
    private string $templatesFolder;
    /**
     * Storage root for generated PHP, dependency records and source maps
     */
    private string $compilationsFolder;
    /**
     * Extension appended to relative template and user-tag names
     */
    private string $templatesExtension;
    /**
     * Default root containing user-defined tag-library folders
     */
    private string $tagLibrariesFolder;
    /**
     * Whether generated output expressions invoke HTML escaping
     */
    private bool $escapeExpressions;

    /**
     * Operation translating final source spans into compiled-line correspondences
     */
    private SourceMapBuilder $sourceMapBuilder;

    /**
     * Operation persisting and validating maps associated with cached PHP
     */
    private SourceMapStorage $sourceMapStorage;

    /**
     * Configures the roots, extension and expression policy of the compilation operation
     *
     * @param string $templatesFolder Root containing template files; may be relative or absolute
     * @param string $templatesExtension Required template extension without its leading dot
     * @param string $compilationsFolder Folder receiving compiled PHP, dependency records and source maps
     * @param string $tagLibFolder Default root containing user-defined tag-library folders
     * @param bool $escapeExpressions Whether output expressions use HTML escaping
     */
    public function __construct(
        string $templatesFolder,
        string $templatesExtension,
        string $compilationsFolder,
        string $tagLibFolder = "",
        bool $escapeExpressions = false
    ) {
        $this->templatesFolder = $templatesFolder;
        $this->templatesExtension = $templatesExtension;
        $this->compilationsFolder= $compilationsFolder;
        $this->tagLibrariesFolder = $tagLibFolder;
        $this->escapeExpressions = $escapeExpressions;
        $this->sourceMapBuilder = new SourceMapBuilder();
        $this->sourceMapStorage = new SourceMapStorage();
    }

    /**
     * Compiles a template dependency tree and returns its cached PHP path
     *
     * A cache hit requires unchanged dependencies and a valid map for the same compiler
     * configuration. Rebuilding carries source documents through every parser stage,
     * prepares PHP line layout and saves the PHP, dependencies and matching map.
     *
     * @param string $templatePath Template path relative to its root, without extension
     * @return string Path to the generated PHP file, ready for data binding
     * @throws ViewException If path resolution, expansion or compilation storage fails
     * @throws \JsonException If the source map cannot be encoded
     */
    public function compile(string $templatePath): string
    {
        // opens existing compilation (if exists)
        $viewCompilation = new ViewCompilation($this->compilationsFolder, $templatePath);

        // Compiler settings are part of cache identity, including escaping policy.
        $configuration = hash("sha256", serialize([
            $this->templatesFolder, $this->templatesExtension,
            $this->tagLibrariesFolder, $this->escapeExpressions
        ]));
        if (!$viewCompilation->hasChanged()
            && $this->sourceMapStorage->load($viewCompilation->getCompilationPath(), $configuration) !== null) {
            return $viewCompilation->getCompilationPath();
        }

        // instantiate template escaping logic
        $escapeTag = new EscapeTag();

        // includes dependant tree of templates
        $importTag = new ImportTag($this->templatesFolder, $this->templatesExtension, $viewCompilation);
        $outputStream = $importTag->parseDocument($templatePath, $escapeTag);

        $namespaceTag = new NamespaceTag($this->tagLibrariesFolder);
        $outputStream = $namespaceTag->parseDocument($outputStream);

        // run user tag parser
        $userTagParser = new UserTagParser($namespaceTag, $this->templatesExtension, $viewCompilation);
        $outputStream = $userTagParser->parseDocument($outputStream, $escapeTag);

        // run system tag parser
        $systemTagParser = new SystemTagParser();
        $outputStream = $systemTagParser->parseDocument($outputStream);

        // run expression parser
        $expressionParser = new ExpressionParser($this->escapeExpressions);
        $outputStream = $expressionParser->parseDocument($outputStream);

        // restore escaped content
        $outputStream = $escapeTag->restoreDocument($outputStream);

        // saves new compilation
        $outputStream = $this->preparePhpLayout($outputStream);
        $php = $outputStream->getText();
        $viewCompilation->save($php);
        $sourceMap = $this->sourceMapBuilder->build($outputStream);
        $this->sourceMapStorage->save($sourceMap, $viewCompilation->getCompilationPath(), $php, $configuration);

        return $viewCompilation->getCompilationPath();
    }

    /**
     * Separates PHP blocks in generated lines while preserving rendered text
     *
     * Whitespace inserted inside PHP disambiguates adjacent expressions. A synthetic
     * newline after a generated closing tag prevents PHP from consuming the template's
     * literal LF or CRLF. Closing tags supplied as raw PHP keep their native behavior.
     *
     * @param SourceDocument $document Final PHP text and source spans after all template transformations
     * @return SourceDocument PHP with adjusted layout and corresponding source locations
     */
    private function preparePhpLayout(SourceDocument $document): SourceDocument
    {
        $insertions = [];
        $offset = 0;
        foreach (token_get_all($document->getText()) as $token) {
            $text = is_array($token) ? $token[1] : $token;
            if (is_array($token) && $token[0] === T_CLOSE_TAG) {
                $insertions[] = ["offset"=>$offset, "origin"=>$offset];
                $closingSpans = $document->slice($offset, 2)->getSpans();
                if (strlen($text) > 2 && !($closingSpans[0]["literal"] ?? true)) {
                    // PHP consumes one newline immediately after a closing tag.
                    // Let it consume a synthetic LF instead of the template's
                    // literal LF/CRLF. Raw PHP retains its native semantics.
                    $insertions[] = ["offset"=>$offset + 2, "origin"=>$offset];
                }
            }
            if (is_array($token) && in_array($token[0], [T_OPEN_TAG, T_OPEN_TAG_WITH_ECHO], true)) {
                $insertions[] = ["offset"=>$offset + strlen($text), "origin"=>$offset];
            }
            $offset += strlen($text);
        }
        if ($insertions === []) {
            return $document;
        }
        $result = new SourceDocument();
        $cursor = 0;
        foreach ($insertions as $insertion) {
            $result = $result->append($document->slice($cursor, $insertion["offset"] - $cursor));
            $result = $result->append($document->createGeneratedDocument("\n", $insertion["origin"]));
            $cursor = $insertion["offset"];
        }
        return $result->append($document->slice($cursor));
    }
}
