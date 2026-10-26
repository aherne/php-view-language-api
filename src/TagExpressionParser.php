<?php

namespace Lucinda\Templating;

/**
 * Translates expressions in tag attributes into PHP expressions
 *
 * Unlike output-expression parsing, this operation does not create echo statements
 * or apply HTML escaping. System tags embed the resulting PHP expression inside
 * control-flow or assignment statements.
 *
 * @see ExpressionParser
 * @see SystemTag
 */
final class TagExpressionParser extends ExpressionParser
{
    /**
     * Converts one attribute expression to PHP without creating an echo statement
     *
     * @param array<int,string> $matches Complete template expression at index zero
     * @return string PHP expression suitable for embedding in a standard tag statement
     */
    protected function parseCallback(array $matches): string
    {
        return $this->convertToVariable($matches[0]);
    }
}
