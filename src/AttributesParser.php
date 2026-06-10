<?php

namespace Lucinda\Templating;

/**
 * Class performing regex parsing of tags for attributes.
 */
class AttributesParser
{
    /**
     * @var string[]
     */
    private array $required = [];

    /**
     * Constructs parser from required attributes.
     *
     * @param string[] $required Required attributes for tag.
     */
    public function __construct(array $required=[])
    {
        $this->required = $required;
    }

    /**
     * Parses string for tag attributes via regex
     *
     * @param  string $parameters
     * @throws ViewException If string doesn't included attributes required by tag
     * @return array<string,string> Attributes by name and value.
     */
    public function parse(string $parameters): array
    {
        if (!$parameters || $parameters=="/") {
            if (empty($this->required)) {
                return [];
            } else {
                throw new ViewException("Tag '".$this->getTagName()."' requires attributes: ".implode(", ", $this->required));
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
        foreach ($this->required as $attributeName) {
            if (!isset($output[$attributeName])) {
                throw new ViewException("Tag '".$this->getTagName()."' requires attribute: ".$attributeName);
            }
        }
        return $output;
    }

    /**
     * Gets current tag name
     *
     * @return string
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
