<?php

namespace Lucinda\Templating\TagLib\System;

use Lucinda\Templating\TagComment;
use Lucinda\Templating\ViewCompilation;
use Lucinda\Templating\File;
use Lucinda\Templating\PathResolver;
use Lucinda\Templating\ViewException;

/**
 * Implements a tag whose only attribute value points to a PHP (template) file whose sources are loaded.
 *
 * Tag syntax:
 * <import file="FILEPATH"/>
 */
class ImportTag
{
    private ViewCompilation $viewCompilation;
    private string $templatesFolder;
    private string $templatesExtension;

    /**
     * Sets up path in which template are looked after and the time of modification for page-specific view file.
     *
     * @param string          $templatesFolder
     * @param string          $templatesExtension
     * @param ViewCompilation $viewCompilation
     */
    public function __construct(string $templatesFolder, string $templatesExtension, ViewCompilation $viewCompilation)
    {
        $this->templatesFolder = $templatesFolder;
        $this->templatesExtension = $templatesExtension;
        $this->viewCompilation = $viewCompilation;
    }

    /**
     * Parses template source file for import tags recursively. For each template file loaded, modification time is
     * adjusted to confirm to the latest.
     *
     * @param  string    $templateFile
     * @param  EscapeTag $escaper
     * @return string
     * @throws ViewException
     */
    public function parse(string $templateFile, EscapeTag $escaper): string
    {
        $path = PathResolver::resolveFile($this->templatesFolder, $templateFile, $this->templatesExtension, "import");
        $file = new File($path);
        $subject = $file->getContents();
        $subject = $escaper->backup($subject);
        $this->viewCompilation->addComponent($path);

        $comment = new TagComment($path);
        return $comment->start().preg_replace_callback(
            "/<import\s*(file\s*\=\s*\"(.*?)\")?\s*\/?>/",
            function ($matches) use ($escaper) {
                if (empty($matches[2])) {
                    throw new ViewException("Tag 'import' requires attribute: file");
                }
                return $this->parse($matches[2], $escaper);
            },
            $subject
        ).$comment->end();
    }
}
