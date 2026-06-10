<?php

namespace Lucinda\Templating;

use Lucinda\Templating\TagLib\System\EscapeTag;
use Lucinda\Templating\TagLib\System\NamespaceTag;

/**
 * Parses user-defined tags and appends them to compilation
 */
class UserTagParser
{
    private string $tagExtension;
    private ViewCompilation $viewCompilation;
    private NamespaceTag $namespaces;
    private AttributesParser $attributesParser;

    /**
     * Creates a tag parser instance.
     *
     * @param NamespaceTag    $namespaces      Object that encapsulates location of user-defined tag libraries.
     * @param string          $tagExtension    Extension of user-defined tags.
     * @param ViewCompilation $viewCompilation Object that collects components that take part in view.
     */
    public function __construct(NamespaceTag $namespaces, string $tagExtension, ViewCompilation $viewCompilation)
    {
        $this->namespaces = $namespaces;
        $this->tagExtension = $tagExtension;
        $this->viewCompilation = $viewCompilation;
        $this->attributesParser = new AttributesParser();
    }

    /**
     * Looks for tags in views and returns an answer where each found match is converted to PHP.
     *
     * @param  string    $subject
     * @param  EscapeTag $escaper
     * @return string
     * @throws ViewException
     */
    public function parse(string $subject, EscapeTag $escaper): string
    {
        $this->validate($subject);

        // match start & end tags
        $subject = preg_replace_callback(
            "/<([\w\-]+):([\w\-]+)\s*([^>]*)?>/",
            function ($matches) {
                $parameters = isset($matches[3]) ? $this->attributesParser->parse($matches[3]) : [];
                return $this->getTagInstance($matches)->parseStartTag($parameters);
            },
            $subject
        );
        $subject = $escaper->backup($subject);

        // if it still contains tags, recurse until all tags are parsed
        if (preg_match("/<([\w\-]+):([\w\-]+)(.*?)>/", $subject)!=0) {
            $subject = $this->parse($subject, $escaper);
        }

        return $subject;
    }

    /**
     * Rejects malformed user tag declarations.
     *
     * @param string $subject
     * @return void
     * @throws ViewException
     */
    private function validate(string $subject): void
    {
        preg_match_all("/<[^>]*:[^>]*>/", $subject, $matches);
        foreach ($matches[0] as $tag) {
            if (str_starts_with($tag, "<!--")) {
                continue;
            }
            if (str_starts_with($tag, "<:") || str_starts_with($tag, "</:")) {
                continue;
            }
            if (!preg_match("/^<[\w\-]+:[\w\-]+\s*[^>]*>$/", $tag)) {
                throw new ViewException("Invalid user tag declaration: ".$tag);
            }
            if (str_starts_with($tag, "</")) {
                throw new ViewException("User tag must not have an end tag: ".$tag);
            }
        }
    }

    /**
     * Detects tag instance from tag declaration.
     *
     * @param  array<int,string> $matches
     * @throws ViewException
     * @return UserTag
     */
    private function getTagInstance(array $matches): UserTag
    {
        $libraryName = $matches[1];
        $tagName = $matches[2];
        $tagFolder = $this->namespaces->get($libraryName);
        $fileLocation = PathResolver::resolveUserTag($tagFolder, $libraryName, $tagName, $this->tagExtension);
        $this->viewCompilation->addComponent($fileLocation);
        return new UserTag($fileLocation);
    }
}
