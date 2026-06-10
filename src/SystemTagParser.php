<?php

namespace Lucinda\Templating;

/**
 * Parses system tags and appends them to compilation
 */
class SystemTagParser
{
    private AttributesParser $attributesParser;

    /**
     * Constructor instancing attributes parser
     */
    public function __construct()
    {
        $this->attributesParser = new AttributesParser();
    }

    /**
     * Looks for tags in views and returns an answer where each found match is converted to PHP.
     *
     * @param  string $subject
     * @return string
     * @throws ViewException
     */
    public function parse(string $subject): string
    {
        $this->validateStructure($subject);

        // match start & end tags
        $subject = preg_replace_callback(
            "/<:([a-z]+)(\s*(.*)\s*=\s*\"(.*)\"\s*)?\/?>/",
            function ($matches) {
                $parameters = isset($matches[2]) ? $this->attributesParser->parse($matches[2]) : [];
                return $this->getTagInstance($matches)->parseStartTag($parameters);
            },
            $subject
        );
        return preg_replace_callback(
            "/<\/:([a-z]+)>/",
            function ($matches) {
                $tag = $this->getTagInstance($matches);
                if (!$tag instanceof StartEndTag) {
                    throw new ViewException("System tag ':".$matches[1]."' must not have an end tag");
                }
                return $tag->parseEndTag();
            },
            $subject
        );
    }

    /**
     * Validates standard tag nesting before replacements are performed.
     *
     * @param string $subject
     * @return void
     * @throws ViewException If tag structure is invalid.
     */
    private function validateStructure(string $subject): void
    {
        preg_match_all("/<(?<end>\/)?:([a-z]+)(?<attrs>[^>]*)>/", $subject, $matches, PREG_SET_ORDER);
        $stack = [];
        foreach ($matches as $match) {
            $name = $match[2];
            $tag = $this->getTagInstance([1=>$name]);
            $isEndTag = ($match["end"] ?? "")==="/";
            $isSelfClosing = !$isEndTag && str_ends_with(trim($match["attrs"] ?? ""), "/");

            if ($isEndTag) {
                if (!$tag instanceof StartEndTag) {
                    throw new ViewException("System tag ':".$name."' must not have an end tag");
                }
                $previous = array_pop($stack);
                if ($previous===null || $previous!==$name) {
                    throw new ViewException("Unexpected system end tag: :".$name);
                }
                continue;
            }

            if ($tag instanceof StartEndTag) {
                if ($isSelfClosing) {
                    throw new ViewException("System tag ':".$name."' requires an end tag");
                }
                $stack[] = $name;
            } elseif ($name==="elseif" || $name==="else") {
                if (end($stack)!=="if") {
                    throw new ViewException("System tag ':".$name."' must follow an open ':if' tag");
                }
            }
        }

        if (!empty($stack)) {
            throw new ViewException("System tag ':".array_pop($stack)."' is missing an end tag");
        }
    }

    /**
     * Detects tag class from tag declaration.
     *
     * @param  array<int,string> $matches
     * @return StartTag|StartEndTag
     * @throws ViewException If tag is unknown.
     */
    private function getTagInstance(array $matches): StartTag|StartEndTag
    {
        $className = __NAMESPACE__."\\TagLib\\Std\\".ucwords($matches[1])."Tag";
        if (!class_exists($className)) {
            throw new ViewException("System tag not found: :".$matches[1]);
        }
        return new $className();
    }
}
