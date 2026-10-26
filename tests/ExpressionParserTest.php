<?php

namespace Test\Lucinda\Templating;

use Lucinda\Templating\ExpressionParser;
use Lucinda\UnitTest\Result;

final class ExpressionParserTest
{
    public function parse()
    {
        $parser = new ExpressionParser();
        return new Result($parser->parse('<p>${asd.fgh}</p>')=='<p><?php echo $asd["fgh"]; ?></p>');
    }
    public function parseDocument()
    {
        $doc = new \Lucinda\Templating\History\SourceDocument("line\n\${data.name}", 'expression.tpl');
        $compiled = (new ExpressionParser())->parseDocument($doc);
        return new Result($compiled->getSourceLocation(strpos($compiled->getText(), '<?php')) === ['source'=>'expression.tpl', 'line'=>2]);
    }
}
