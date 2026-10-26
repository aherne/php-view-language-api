<?php

namespace Lucinda\Templating\TagLib\Std;

use Lucinda\Templating\StartTag;
use Lucinda\Templating\SystemTag;

/**
 * Translates a conditional branch following an open if block into PHP
 *
 * The test attribute is required and is translated as a PHP condition. The enclosing
 * :if group owns the closing tag shared by all branches.
 *
 * Tag syntax: <:elseif test="condition">body</:if>
 *
 * @see \Lucinda\Templating\SystemTagParser
 */
final class ElseifTag extends SystemTag implements StartTag
{
    /**
     * Translates a conditional branch following an open if block into PHP
     *
     * Requires test, interpreted as a PHP condition.
     *
     * @param array<string,string> $parameters Parsed declaration attributes keyed by name
     * @return string PHP replacement for the opening or standalone declaration
     * @throws \Lucinda\Templating\ViewException If required attributes or expression syntax are invalid
     */
    public function parseStartTag(array $parameters=[]): string
    {
        $this->checkParameters($parameters, array("test"));
        return '<?php } else if ('.$this->parseExpression($parameters['test']).') { ?>';
    }
}
