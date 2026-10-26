<?php

namespace Test\Lucinda\Templating;

use Lucinda\Templating\TagExpressionParser;
use Lucinda\UnitTest\Result;

final class TagExpressionParserTest
{
    public function parse()
    {
        $object = new TagExpressionParser();
        return new Result($object->parse('asd <:if test="${qwe.rty}"/>fgh</if> jkl') == 'asd <:if test="$qwe["rty"]"/>fgh</if> jkl');
    }
    public function parseDocument()
    {
        $doc = new \Lucinda\Templating\History\SourceDocument('${data.name}', 'attribute.tpl', 3);
        $compiled = (new \Lucinda\Templating\TagExpressionParser())->parseDocument($doc);
        return new \Lucinda\UnitTest\Result($compiled->getText() === '$data["name"]' && $compiled->getSourceLocation()['line'] === 3);
    }
}
