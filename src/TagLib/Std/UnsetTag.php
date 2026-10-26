<?php

namespace Lucinda\Templating\TagLib\Std;

use Lucinda\Templating\SystemTag;
use Lucinda\Templating\StartTag;
use Lucinda\Templating\ViewException;

/**
 * Translates variable removal into a PHP unset statement
 *
 * The required var attribute names the variable removed when the rendered template
 * executes the generated statement.
 *
 * Tag syntax: <:unset var="name"/>
 *
 * @see \Lucinda\Templating\SystemTagParser
 */
final class UnsetTag extends SystemTag implements StartTag
{
    /**
     * Translates variable removal into a PHP unset statement
     *
     * Requires var, the name of the variable to remove.
     *
     * @param array<string,string> $parameters Parsed declaration attributes keyed by name
     * @return string PHP replacement for the opening or standalone declaration
     * @throws ViewException If required attributes or expression syntax are invalid
     */
    public function parseStartTag(array $parameters=[]): string
    {
        $this->checkParameters($parameters, array("var"));
        return '<?php unset($'.$parameters['var'].'); ?>';
    }
}
