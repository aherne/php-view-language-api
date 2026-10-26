<?php

namespace Lucinda\Templating\TagLib\Std;

use Lucinda\Templating\StartEndTag;
use Lucinda\Templating\SystemTag;
use Lucinda\Templating\ViewException;

/**
 * Translates collection iteration into a PHP foreach block
 *
 * The collection expression and value-variable name are required. The key-variable
 * name is optional. Opening and closing translations surround the repeated body.
 *
 * Tag syntax: <:foreach var="${data.items}" key="key" val="value">body</:foreach>
 *
 * @see \Lucinda\Templating\SystemTagParser
 */
final class ForeachTag extends SystemTag implements StartEndTag
{
    /**
     * Translates collection iteration into a PHP foreach block
     *
     * Requires a collection expression in var and a value variable in val; key is optional.
     *
     * @param array<string,string> $parameters Parsed declaration attributes keyed by name
     * @return string PHP replacement for the opening or standalone declaration
     * @throws ViewException If required attributes or expression syntax are invalid
     */
    public function parseStartTag(array $parameters=[]): string
    {
        $this->checkParameters($parameters, array("var","val"));
        $left = $this->parseExpression($parameters['var']);
        $middle = (!empty($parameters['key']) ? '$'.$parameters['key'].'=>' : '');
        $right = '$'.$parameters['val'];
        return '<?php foreach ('.$left.' as '.$middle.$right.') { ?>';
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
     * Validates collection-iteration attributes and requires an expression for var
     *
     * @param array<string,string> $parameters Parsed collection-iteration attributes
     * @param string[] $requiredParameters Required names supplied by the opening-tag operation
     * @return void
     * @throws ViewException If a required attribute is missing or var is not an expression
     */
    protected function checkParameters(array $parameters, array $requiredParameters): void
    {
        parent::checkParameters($parameters, $requiredParameters);
        if (!$this->isExpression($parameters['var'])) {
            throw new ViewException("Invalid value of 'var' attribute @ ':foreach' tag: ".$parameters['var']);
        }
    }
}
