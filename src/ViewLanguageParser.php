<?php

namespace Lucinda\Templating;

use Lucinda\Templating\TagLib\System\EscapeTag;
use Lucinda\Templating\TagLib\System\ImportTag;
use Lucinda\Templating\TagLib\System\NamespaceTag;

/**
 * Compiles a ViewLanguage template recursively into a PHP file on disk based on:
 * - folder templates are located into (can be relative or absolute)
 * - extension template files are using (eg: html)
 * - folder in which PHP compilation file will be saved
 * - folder in which user-defined tag libraries will be located
 */
class ViewLanguageParser
{
    private string $templatesFolder;
    private string $compilationsFolder;
    private string $templatesExtension;
    private string $tagLibFolder;
    private bool $escapeExpressions;

    /**
     * Constructs a view language parser.
     *
     * @param string $templatesFolder    Relative path to templates folder on disk.
     * @param string $templatesExtension Extension of template files without dot (eg: php).
     * @param string $compilationsFolder Absolute path to compilations folder on disk
     * @param string $tagLibFolder       Relative path to user-defined tag libraries folder.
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
        $this->tagLibFolder = $tagLibFolder;
        $this->escapeExpressions = $escapeExpressions;
    }

    /**
     * Compiles ViewLanguage instructions in input file/string into PHP, saves global view into a compilation file,
     * then returns location to that file.
     *
     * @param  string $templatePath Relative path to template file that needs to be compiled within templates folder
     * @return string Compilation file name, containing response stream after view language constructs were parsed.
     * @throws ViewException If compilation fails
     */
    public function compile(string $templatePath): string
    {
        // opens existing compilation (if exists)
        $viewCompilation = new ViewCompilation($this->compilationsFolder, $templatePath);

        // if compilation components haven't changed, do not go further
        if (!$viewCompilation->hasChanged()) {
            return $viewCompilation->getCompilationPath();
        }

        // instantiate template escaping logic
        $escapeTag = new EscapeTag();

        // includes dependant tree of templates
        $importTag = new ImportTag($this->templatesFolder, $this->templatesExtension, $viewCompilation);
        $outputStream = $importTag->parse($templatePath, $escapeTag);

        $namespaceTag = new NamespaceTag($this->tagLibFolder);
        $outputStream = $namespaceTag->parse($outputStream);

        // run user tag parser
        $userTagParser = new UserTagParser($namespaceTag, $this->templatesExtension, $viewCompilation);
        $outputStream = $userTagParser->parse($outputStream, $escapeTag);

        // run system tag parser
        $systemTagParser = new SystemTagParser();
        $outputStream = $systemTagParser->parse($outputStream);

        // run expression parser
        $expressionParser = new ExpressionParser($this->escapeExpressions);
        $outputStream = $expressionParser->parse($outputStream);

        // restore escaped content
        $outputStream = $escapeTag->restore($outputStream);

        // saves new compilation
        $viewCompilation->save($outputStream);

        return $viewCompilation->getCompilationPath();
    }
}
