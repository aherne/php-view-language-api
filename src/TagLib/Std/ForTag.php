<?php

namespace Lucinda\Templating\TagLib\Std;

use Lucinda\Templating\StartEndTag;
use Lucinda\Templating\SystemTag;
use Lucinda\Templating\ViewException;

/**
 * Implements how a FOR clause is translated into a tag.
 *
 * Tag syntax:
 * <:for var="VARNAME" start="EXPRESSION|INTEGER" end="EXPRESSION|INTEGER" step="INTEGER">BODY</:for>
 */
class ForTag extends SystemTag implements StartEndTag
{
    /**
     * Parses start tag.
     *
     * @param  array<string,string> $parameters
     * @return string
     * @throws ViewException If required parameters aren't supplied
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
     * Parses end tag.
     *
     * @return string
     */
    public function parseEndTag(): string
    {
        return '<?php } ?>';
    }

    /**
     * Parses start & end attributes, which may be either integers or expressions.
     *
     * @param  string $expression
     * @return string
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
