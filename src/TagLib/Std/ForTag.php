<?php

namespace Lucinda\Templating\TagLib\Std;

use Lucinda\Templating\StartEndTag;
use Lucinda\Templating\SystemTag;
use Lucinda\Templating\ViewException;

/**
 * Translates a counted-loop tag into a PHP for block
 *
 * The start and end bounds are inclusive. The optional nonzero step selects ascending
 * or descending comparison and increment; it defaults to one.
 *
 * Tag syntax: <:for var="counter" start="0" end="10" step="1">body</:for>
 *
 * @see \Lucinda\Templating\SystemTagParser
 */
final class ForTag extends SystemTag implements StartEndTag
{
    /**
     * Translates a counted-loop tag into a PHP for block
     *
     * Requires var, start and end; step is optional and must be nonzero.
     *
     * @param array<string,string> $parameters Parsed declaration attributes keyed by name
     * @return string PHP replacement for the opening or standalone declaration
     * @throws ViewException If required attributes or expression syntax are invalid
     */
    public function parseStartTag(array $parameters=[]): string
    {
        $this->checkParameters($parameters, array("var", "start", "end"));
        $step = ($parameters['step'] ?? 1);
        if (!is_numeric($step) || (int) $step===0) {
            throw new ViewException("Invalid value of 'step' attribute @ ':for' tag: ".$step);
        }
        $left = '$'.$parameters['var'].'='.$this->parseCounter($parameters['start']);
        $middle = '$'.$parameters['var'].($step>0 ? "<" : ">").'='.$this->parseCounter($parameters['end']);
        $right = '$'.$parameters['var'].($step>0 ? "+=" : "-=").abs((int) $step);
        return '<?php for ('.$left.'; '.$middle.'; '.$right.') { ?>';
    }

    /**
     * Closes the PHP block opened by this tag
     *
     * @return string PHP closing-brace statement
     */
    public function parseEndTag(): string
    {
        return '<?php } ?>';
    }

    /**
     * Translates a numeric-loop bound into PHP
     *
     * @param string $expression Numeric literal, undecorated variable name or template expression
     * @return string PHP value or variable access used in the generated bound
     * @throws ViewException If template-expression matching fails
     */
    private function parseCounter(string $expression): string
    {
        if (is_numeric($expression)) {
            return (string) $expression;
        } elseif (!$this->isExpression($expression)) {
            return '$'.$expression;
        } else {
            return $this->parseExpression($expression);
        }
    }
}
