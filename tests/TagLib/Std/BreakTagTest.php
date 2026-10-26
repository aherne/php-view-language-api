<?php

namespace Test\Lucinda\Templating\TagLib\Std;

use Lucinda\Templating\TagLib\Std\BreakTag;
use Lucinda\UnitTest\Result;

final class BreakTagTest
{
    public function parseStartTag()
    {
        $object = new BreakTag();
        return new Result($object->parseStartTag()=='<?php break; ?>');
    }
}
