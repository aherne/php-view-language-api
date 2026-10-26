<?php

namespace Lucinda\Templating\TagLib\Std;

use Lucinda\Templating\SystemTag;
use Lucinda\Templating\StartEndTag;
use Lucinda\Templating\ViewException;

/**
 * Translates a conditional loop into a PHP while block
 *
 * The required test condition is translated into PHP and reevaluated before each
 * iteration. The closing tag terminates the loop body.
 *
 * Tag syntax: <:while test="condition">body</:while>
 *
 * @see \Lucinda\Templating\SystemTagParser
 */
final class WhileTag extends SystemTag implements StartEndTag
{
    /**
     * Translates a conditional loop into a PHP while block
     *
     * Requires test, interpreted as the loop continuation condition.
     *
     * @param array<string,string> $parameters Parsed declaration attributes keyed by name
     * @return string PHP replacement for the opening or standalone declaration
     * @throws ViewException If required attributes or expression syntax are invalid
     */
    public function parseStartTag(array $parameters=[]): string
    {
        $this->checkParameters($parameters, array("test"));
        return '<?php while ('.$this->parseExpression($parameters['test']).') { ?>';
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
}
