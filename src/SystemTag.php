<?php

namespace Lucinda\Templating;

/**
 * Defines shared expression and attribute-validation operations for standard tags
 *
 * Concrete standard tags use these protected operations to translate conditions,
 * loop expressions and assignments. Only operations required by subclasses are
 * protected; tag-specific parsing remains with the concrete tag.
 *
 * @see TagExpressionParser
 * @see StartTag
 * @see StartEndTag
 */
abstract class SystemTag
{
    /**
     * Checks whether an attribute contains template-expression notation
     *
     * @param string $expression Attribute value to inspect
     * @return bool Whether the value contains an opening ${ marker
     */
    protected function isExpression(string $expression): bool
    {
        return str_contains($expression, '${');
    }

    /**
     * Translates an attribute expression into PHP without rendering it
     *
     * @param string $expression Attribute value containing template-variable notation
     * @return string PHP expression usable in a generated tag statement
     * @throws ViewException If expression matching fails
     */
    protected function parseExpression(string $expression): string
    {
        $expressionObject = new TagExpressionParser();
        return $expressionObject->parse($expression);
    }

    /**
     * Validates the required attributes of a concrete standard tag
     *
     * @param array<string,string> $parameters Attribute values supplied by the declaration
     * @param string[] $requiredParameters Names that must be present
     * @return void
     * @throws ViewException If any required attribute is absent
     */
    protected function checkParameters(array $parameters, array $requiredParameters): void
    {
        foreach ($requiredParameters as $name) {
            if (!isset($parameters[$name])) {
                preg_match("/Std(.*)Tag/", get_class($this), $matches);
                throw new ViewException("Tag '".strtolower($matches[1])."' requires attribute: ".$name);
            }
        }
    }
}
