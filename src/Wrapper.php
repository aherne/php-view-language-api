<?php

namespace Lucinda\Templating;

/**
 * Reads templating tag in order to bind it to a view and compiles response
 */
class Wrapper
{
    private string $templatesFolder;
    private string $compilationsFolder;
    private string $templatesExtension;
    private string $tagLibFolder;
    private bool $debug;
    private bool $escapeExpressions;

    /**
     * Calls for interpreting contents of <application> XML tag.
     *
     * @param  \SimpleXMLElement $xml XML file holding compiler settings.
     * @throws ConfigurationException If XML is improperly configured.
     */
    public function __construct(\SimpleXMLElement $xml)
    {
        $this->readConfiguration($xml);
    }

    /**
     * Reads XML then delegates to ViewLanguageAPI to compile a templated view recursively into a PHP file.
     *
     * @param  \SimpleXMLElement $xml XML file holding compiler settings.
     * @throws ConfigurationException If XML is improperly configured.
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
        $this->tagLibFolder = (string) $xml["tags_path"];
        $this->templatesFolder = (string) $xml["templates_path"];
        $this->templatesExtension = (string) $xml["templates_extension"];
        if (!$this->templatesExtension) {
            $this->templatesExtension = "html";
        }
        $this->debug = ((string) $xml["debug"]==="true" || (string) $xml["debug"]==="1");
        $this->escapeExpressions = ((string) $xml["escape_expressions"]==="true" || (string) $xml["escape_expressions"]==="1");
    }

    /**
     * Loads compilation file, binds it to data and returns HTML to be rendered
     *
     * @param  string $viewFile View file location (without extension, optionally including views folder path)
     * @param  array  $data
     * @return string
     * @throws ViewException If compilation failed due to a developer error.
     */
    public function compile(string $viewFile, array $data): string
    {
        // gets view file
        if ($this->templatesFolder && str_starts_with($viewFile, $this->templatesFolder)) {
            $viewFile = substr($viewFile, strlen($this->templatesFolder)+1);
        }
        $viewFile = PathResolver::normalize($viewFile);

        // compiles templates recursively into a single HTML compilation file
        $vlp = new ViewLanguageParser(
            $this->templatesFolder,
            $this->templatesExtension,
            $this->compilationsFolder,
            $this->tagLibFolder,
            $this->escapeExpressions
        );
        $compilationFile = $vlp->compile($viewFile);

        // compiles PHP file into HTML
        return $this->bind($compilationFile, $data);
    }

    /**
     * Binds compilation file to data, returning final HTML
     *
     * @param string $compilationFile
     * @param array $data
     * @return string
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

        // removes comments, by default
        return ($this->debug ? $output : preg_replace("/<!-- VL:(START|END):\s*(.*?)\s*-->/", "", $output));
    }

    /**
     * Includes compiled PHP while converting runtime warnings into exceptions.
     *
     * @param string $compilationFile
     * @param array $data
     * @return void
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
     * Builds a source-aware runtime exception from a compiled template error.
     *
     * @param string $compilationFile
     * @param \Throwable $previous
     * @return ViewException
     */
    private function createRuntimeException(string $compilationFile, \Throwable $previous): ViewException
    {
        $message = "Error while rendering compiled view: ".$previous->getMessage();
        if (PathResolver::normalize($previous->getFile())===PathResolver::normalize($compilationFile)) {
            $context = SourceContext::fromCompiledLine($compilationFile, $previous->getLine());
            if ($context!==null) {
                $message .= " @ ".$context["source"].":".$context["line"];
            }
            $message .= " (compiled: ".$compilationFile.":".$previous->getLine().")";
        }
        return new ViewException($message, 0, $previous);
    }
}
