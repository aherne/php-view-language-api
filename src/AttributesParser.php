<?php

namespace Lucinda\Templating;

/**
 * Parses double-quoted tag attributes and validates required attribute names
 *
 * The configured required-name list belongs to the parser instance. parse() returns
 * attribute values indexed by name and rejects unmatched attribute syntax or missing
 * required names. Source-aware callers attach the declaration's file and line to
 * these syntax errors.
 *
 * @see SystemTagParser
 * @see UserTagParser
 */
final class AttributesParser
{
    /**
     * @var string[] Attribute names required by every declaration parsed by this instance
     */
    private array $requiredAttributes = [];

    /**
     * Configures which attribute names must be present in each parsed declaration
     *
     * @param string[] $required Required names; an empty list allows any valid attribute set
     */
    public function __construct(array $required=[])
    {
        $this->requiredAttributes = $required;
    }

    /**
     * Parses double-quoted attribute assignments and validates required names
     *
     * A trailing self-closing slash is accepted. Any remaining non-attribute syntax is
     * rejected instead of being silently discarded.
     *
     * @param string $parameters Attribute text captured from a tag declaration
     * @return array<string,string> Attribute values keyed by attribute name
     * @throws ViewException If syntax is invalid or a required attribute is missing
     */
    public function parse(string $parameters): array
    {
        if (!$parameters || $parameters=="/") {
            if (empty($this->requiredAttributes)) {
                return [];
            } else {
                throw new ViewException("Tag '".$this->getTagName()."' requires attributes: ".implode(", ", $this->requiredAttributes));
            }
        }
        $tmp = [];
        preg_match_all('/([\w\-.]+)\s*=\s*"([^"]*)"/', $parameters, $tmp, PREG_SET_ORDER);
        $output=[];
        foreach ($tmp as $values) {
            $output[$values[1]]=$values[2];
        }
        $leftover = trim(preg_replace('/([\w\-.]+)\s*=\s*"([^"]*)"/', "", $parameters));
        if ($leftover && $leftover!=="/") {
            throw new ViewException("Invalid attributes @ tag '".$this->getTagName()."': ".$parameters);
        }
        foreach ($this->requiredAttributes as $attributeName) {
            if (!isset($output[$attributeName])) {
                throw new ViewException("Tag '".$this->getTagName()."' requires attribute: ".$attributeName);
            }
        }
        return $output;
    }

    /**
     * Gets a best-effort caller tag name for attribute error messages
     *
     * @return string Tag name inferred from the caller path, or unknown when no matching tag path is available
     */
    private function getTagName(): string
    {
        $matches = [];
        $trace = debug_backtrace();
        preg_match("/([[:alpha:]])\/([[:alpha:]])Tag.php$/", $trace[1]["file"], $matches);
        if (empty($matches)) {
            return "unknown";
        }
        return ($matches[1]=="Std" ? ":" : "").strtolower($matches[2]);
    }
}
