<?php

namespace Lucinda\Templating;

use Lucinda\Templating\History\SourceDocument;

/**
 * Translates standard tag declarations into PHP while retaining source locations
 *
 * Structural validation checks known tag names, nesting and branch placement before
 * start and end declarations are replaced. Errors identify the offending declaration
 * or the opening declaration whose closing tag is missing.
 *
 * @see SystemTag
 * @see SourceDocument
 */
final class SystemTagParser
{
    /**
     * Parser used to convert standard-tag attribute text into named values
     */
    private AttributesParser $attributesParser;

    /**
     * Creates the attribute parser shared by standard-tag translations
     */
    public function __construct()
    {
        $this->attributesParser = new AttributesParser();
    }

    /**
     * Translates standard tags in an untracked text string
     *
     * @param string $subject Template text containing standard tag declarations
     * @return string Text with standard tags replaced by PHP
     * @throws ViewException If a tag is unknown, malformed or incorrectly nested
     */
    public function parse(string $subject): string
    {
        return $this->parseDocument(new SourceDocument($subject))->getText();
    }

    /**
     * Validates and translates standard tags with original source context
     *
     * @param SourceDocument $subject Template text after macro and user-tag expansion
     * @return SourceDocument Text with opening and closing declarations translated into PHP
     * @throws ViewException If tag structure or attributes are invalid
     */
    public function parseDocument(SourceDocument $subject): SourceDocument
    {
        $this->validateStructure($subject);
        $subject = $subject->replace(
            '/<:([a-z]+)\b((?:[^>"]|"[^"]*")*)>/',
            function (array $matches): string {
                $parameters = isset($matches[2]) ? $this->attributesParser->parse($matches[2]) : [];
                return $this->getTagInstance($matches)->parseStartTag($parameters);
            }
        );
        return $subject->replace('/<\/:([a-z]+)>/', function (array $matches): string {
            $tag = $this->getTagInstance($matches);
            if (!$tag instanceof StartEndTag) {
                throw new ViewException("System tag ':".$matches[1]."' must not have an end tag");
            }
            return $tag->parseEndTag();
        });
    }

    /**
     * Validates tag names, block nesting and conditional branch placement
     *
     * Unclosed blocks report their opening location. Unexpected closing tags and
     * misplaced branches report the declaration currently being inspected.
     *
     * @param SourceDocument $subject Document containing standard tag declarations
     * @return void
     * @throws ViewException If a declaration violates standard-tag structure
     */
    private function validateStructure(SourceDocument $subject): void
    {
        preg_match_all('/<(\/)?:([a-z]+)\b((?:[^>"]|"[^"]*")*)>/', $subject->getText(), $matches,
            PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
        $stack = [];
        foreach ($matches as $match) {
            $offset = $match[0][1];
            $name = $match[2][0];
            try {
                $tag = $this->getTagInstance([1=>$name]);
            } catch (ViewException $e) {
                throw $subject->createException($e->getMessage(), $offset, $e);
            }
            $isEndTag = ($match[1][0] ?? "") === "/";
            $isSelfClosing = !$isEndTag && str_ends_with(trim($match[3][0] ?? ""), "/");
            if ($isEndTag) {
                if (!$tag instanceof StartEndTag) {
                    throw $subject->createException("System tag ':".$name."' must not have an end tag", $offset);
                }
                $previous = array_pop($stack);
                if ($previous === null || $previous["name"] !== $name) {
                    throw $subject->createException("Unexpected system end tag: :".$name, $offset);
                }
            } elseif ($tag instanceof StartEndTag) {
                if ($isSelfClosing) {
                    throw $subject->createException("System tag ':".$name."' requires an end tag", $offset);
                }
                $stack[] = ["name"=>$name, "offset"=>$offset];
            } elseif (($name === "elseif" || $name === "else") && (end($stack)["name"] ?? null) !== "if") {
                throw $subject->createException("System tag ':".$name."' must follow an open ':if' tag", $offset);
            }
        }
        if ($stack !== []) {
            $open = array_pop($stack);
            throw $subject->createException("System tag ':".$open["name"]."' is missing an end tag", $open["offset"]);
        }
    }

    /**
     * Gets a translator instance for a standard tag name
     *
     * @param array<int,string> $matches Captures with the standard tag name at index one
     * @return StartTag|StartEndTag Translator for the requested declaration
     * @throws ViewException If the standard tag name has no implementation
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
