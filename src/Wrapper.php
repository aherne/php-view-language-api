<?php

namespace Lucinda\Templating;

use Lucinda\Templating\History\SourceContext;

/**
 * Renders a configured ViewLanguage template with application data
 *
 * Construction reads template roots, the required extension, compilation storage
 * and optional HTML expression escaping from the supplied XML document. compile()
 * reuses or builds mapped PHP, executes it with the supplied $data array and returns
 * the captured text without sending headers or printing a response.
 *
 * HTML and console markup use the same rendering path. Runtime warnings and PHP
 * errors are wrapped with an available original template location while preserving
 * the previous throwable. Diagnostic metadata never enters the rendered output.
 * The class remains extensible through its protected bind() rendering hook.
 *
 * @see ViewLanguageParser
 * @see ViewException
 * @see SourceContext
 */
class Wrapper
{
    /**
     * Root folder used to resolve the requested template and its imports
     */
    private string $templatesFolder;
    /**
     * Storage root for generated PHP, dependency records and source maps
     */
    private string $compilationsFolder;
    /**
     * Required template-file extension without a leading dot
     */
    private string $templatesExtension;
    /**
     * Default root containing user-defined tag-library folders
     */
    private string $tagLibrariesFolder;
    /**
     * Whether rendered output expressions use HTML escaping
     */
    private bool $escapeExpressions;

    /**
     * Instance operation resolving template paths and enforcing configured roots
     */
    private PathResolver $pathResolver;

    /**
     * Operation resolving original template locations for execution failures
     */
    private SourceContext $sourceContext;

    /**
     * Reads the configuration required to render templates
     *
     * @param \SimpleXMLElement $xml Complete XML document containing a templating child element
     * @throws ConfigurationException If the element or a mandatory setting is missing
     */
    public function __construct(\SimpleXMLElement $xml)
    {
        $this->pathResolver = new PathResolver();
        $this->sourceContext = new SourceContext();
        $this->readConfiguration($xml);
    }

    /**
     * Reads template locations, compilation storage and expression-escaping policy
     *
     * @param \SimpleXMLElement $xml Complete XML document containing a templating child element
     * @return void
     * @throws ConfigurationException If templating, compilations_path or templates_extension is missing
     */
    private function readConfiguration(\SimpleXMLElement $xml): void
    {
        // parses XML
        $xml = $xml->templating;
        if (empty($xml)) {
            throw new ConfigurationException("Tag 'templating' missing");
        }

        // gets settings from attributes
        $this->compilationsFolder = (string) $xml["compilations_path"];
        if (!$this->compilationsFolder) {
            throw new ConfigurationException("Attribute 'compilations' is mandatory for 'templating' tag");
        }
        $this->tagLibrariesFolder = (string) $xml["tags_path"];
        $this->templatesFolder = (string) $xml["templates_path"];
        $this->templatesExtension = (string) $xml["templates_extension"];
        if (!$this->templatesExtension) {
            throw new ConfigurationException("Attribute 'templates_extension' is mandatory for 'templating' tag");
        }
        $this->escapeExpressions = ((string) $xml["escape_expressions"]==="true" || (string) $xml["escape_expressions"]==="1");
    }

    /**
     * Compiles or reuses a template and renders it with application data
     *
     * The returned string may contain HTML, console markup or other text. This method
     * does not emit a response and does not trim leading or trailing whitespace.
     *
     * @param string $viewFile Template path without its configured extension; the configured root prefix is accepted
     * @param array<int|string,mixed> $data Values exposed to the template through its $data variable
     * @return string Captured rendered output
     * @throws ViewException If template expansion or PHP execution fails
     * @throws \JsonException If compilation metadata cannot be encoded
     */
    public function compile(string $viewFile, array $data): string
    {
        // gets view file
        if ($this->templatesFolder && str_starts_with($viewFile, $this->templatesFolder)) {
            $viewFile = substr($viewFile, strlen($this->templatesFolder)+1);
        }
        $viewFile = $this->pathResolver->normalize($viewFile);

        // compiles templates recursively into a single compilation file
        $vlp = new ViewLanguageParser(
            $this->templatesFolder,
            $this->templatesExtension,
            $this->compilationsFolder,
            $this->tagLibrariesFolder,
            $this->escapeExpressions
        );
        $compilationFile = $vlp->compile($viewFile);

        // compiles PHP file into desired language
        return $this->bind($compilationFile, $data);
    }

    /**
     * Executes a compiled template inside an output buffer
     *
     * The buffer is closed on both success and failure. The protected method is an
     * extension point for renderers that specialize binding behavior.
     *
     * @param string $compilationFile PHP file produced by the template compiler
     * @param array<int|string,mixed> $data Values made available to the compiled template
     * @return string Captured template output without diagnostic text
     * @throws ViewException If execution throws or an enabled PHP warning is raised
     */
    protected function bind(string $compilationFile, array $data): string
    {
        try {
            ob_start();
            $this->includeCompilation($compilationFile, $data);
            $output = ob_get_contents();
        } catch (\Throwable $e) {
            throw $this->createRuntimeException($compilationFile, $e);
        } finally {
            ob_end_clean();
        }

        return $output;
    }

    /**
     * Includes compiled PHP while translating enabled warnings into exceptions
     *
     * The caller's error handler is restored even when PHP execution fails.
     *
     * @param string $compilationFile PHP compilation to execute in the current data scope
     * @param array<int|string,mixed> $data Values exposed through the compiled template's $data variable
     * @return void
     * @throws \Throwable If PHP parsing, execution or an enabled warning fails
     */
    private function includeCompilation(string $compilationFile, array $data): void
    {
        set_error_handler(
            function (int $severity, string $message, string $file, int $line) {
                if (!(error_reporting() & $severity)) {
                    return false;
                }
                throw new \ErrorException($message, 0, $severity, $file, $line);
            }
        );
        try {
            include $compilationFile;
        } finally {
            restore_error_handler();
        }
    }

    /**
     * Creates a diagnostic for a PHP failure and resolves its template call site
     *
     * The original exception location is checked first, followed by stack frames for
     * helper calls originating in the compiled template. Filesystem aliases are resolved
     * before comparing frame paths with the compilation path.
     *
     * @param string $compilationFile PHP compilation whose source map supplies template locations
     * @param \Throwable $previous Original PHP failure to preserve
     * @return ViewException Rendering failure with available original source context
     */
    private function createRuntimeException(string $compilationFile, \Throwable $previous): ViewException
    {
        $message = "Error while rendering compiled view: ".$previous->getMessage();
        $frames = [["file"=>$previous->getFile(), "line"=>$previous->getLine()], ...$previous->getTrace()];
        foreach ($frames as $frame) {
            if (!isset($frame["file"], $frame["line"])
                || $this->pathResolver->normalize(realpath($frame["file"]) ?: $frame["file"])
                    !== $this->pathResolver->normalize(realpath($compilationFile) ?: $compilationFile)) {
                continue;
            }
            $context = $this->sourceContext->getSourceLocation($compilationFile, $frame["line"]);
            $message .= " (compiled: ".$compilationFile.":".$frame["line"].")";
            return new ViewException($message, 0, $previous, $context);
        }
        return new ViewException($message, 0, $previous);
    }
}
