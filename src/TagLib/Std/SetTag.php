<?php

namespace Lucinda\Templating\TagLib\Std;

use Lucinda\Templating\SystemTag;
use Lucinda\Templating\StartTag;
use Lucinda\Templating\ViewException;

/**
 * Translates variable assignment into a PHP statement
 *
 * The variable name is required. An omitted value assigns null. Expressions and
 * recognized helper calls become PHP; other supplied values become quoted strings.
 *
 * Tag syntax: <:set var="name" val="value"/>
 *
 * @see \Lucinda\Templating\SystemTagParser
 */
final class SetTag extends SystemTag implements StartTag
{
    /**
     * Translates variable assignment into a PHP statement
     *
     * Requires var; val may be a literal, expression or helper call and defaults to null.
     *
     * @param array<string,string> $parameters Parsed declaration attributes keyed by name
     * @return string PHP replacement for the opening or standalone declaration
     * @throws ViewException If required attributes or expression syntax are invalid
     */
    public function parseStartTag(array $parameters=[]): string
    {
        $this->checkParameters($parameters, array("var"));
        $output = '<?php $'.$parameters['var'].' = ';
        if (isset($parameters["val"])) {
            if ($this->isExpression($parameters['val'])) {
                $output .= $this->parseExpression($parameters['val']);
            } else if ($this->isFunction($parameters['val'])) {
                $output .= $parameters['val'];
            } else {
                $output .= "'".addslashes($parameters['val'])."'";
            }
        } else {
            $output .= "null";
        }
        return $output.'; ?>';
    }

    /**
     * Checks whether a value uses the supported undecorated helper-call syntax
     *
     * @param string $expression Attribute value to inspect before quoting it as a literal
     * @return bool Whether the value is recognized as a helper call
     */
    private function isFunction(string $expression): bool
    {
        return (bool) preg_match("/^[a-zA-Z0-9_'\"]+\s*\(([^\)]+)\)$/", $expression);
    }
}
