<?php

namespace Lucinda\Templating\TagLib\Std;

use Lucinda\Templating\SystemTag;
use Lucinda\Templating\StartEndTag;
use Lucinda\Templating\ViewException;

/**
 * Translates a conditional tag into a PHP if block
 *
 * The test attribute is required. Optional :elseif and :else declarations extend
 * the group before its single closing tag.
 *
 * Tag syntax: <:if test="condition">body</:if>
 *
 * @see \Lucinda\Templating\SystemTagParser
 */
final class IfTag extends SystemTag implements StartEndTag
{
    /**
     * Translates a conditional tag into a PHP if block
     *
     * Requires test, interpreted as a PHP condition.
     *
     * @param array<string,string> $parameters Parsed declaration attributes keyed by name
     * @return string PHP replacement for the opening or standalone declaration
     * @throws ViewException If required attributes or expression syntax are invalid
     */
    public function parseStartTag(array $parameters=[]): string
    {
        $this->checkParameters($parameters, array("test"));
        return '<?php if ('.$this->parseExpression($parameters['test']).') { ?>';
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
