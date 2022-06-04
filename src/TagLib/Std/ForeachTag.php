<?php

namespace Lucinda\Templating\TagLib\Std;

use Lucinda\Templating\StartEndTag;
use Lucinda\Templating\SystemTag;
use Lucinda\Templating\ViewException;

/**
 * Implements how a FOREACH clause is translated into a tag.
 *
 * Tag syntax:
 * <:foreach var="EXPRESSION" key="KEYNAME" val="VALUENAME">BODY</:foreach>
 */
class ForeachTag extends SystemTag implements StartEndTag
{
    /**
     * Parses start tag.
     *
     * @param array<string,string> $parameters
     * @return string
     * @throws ViewException If required parameters aren't supplied
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
     * Parses end tag.
     *
     * @return string
     */
    public function parseEndTag(): string
    {
        return '<?php } ?>';
    }

    /**
     * Verifies if tag has required attributes defined.
     *
     * @param array<string,string> $parameters
     * @param string[]             $requiredParameters
     * @throws ViewException If a required attribute is not found.
     */
    protected function checkParameters(array $parameters, array $requiredParameters): void
    {
        parent::checkParameters($parameters, $requiredParameters);
        if (!$this->isExpression($parameters['var'])) {
            throw new ViewException("Invalid value of 'var' attribute @ ':foreach' tag: ".$parameters['var']);
        }
    }
}
