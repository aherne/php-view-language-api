<?php

namespace Test\Lucinda\Templating\TagLib\Std;

use Lucinda\Templating\TagLib\Std\WhileTag;
use Lucinda\UnitTest\Result;

final class WhileTagTest
{
    public function parseStartTag()
    {
        $object = new WhileTag();
        return new Result($object->parseStartTag(["test"=>'${asd} == 1'])=='<?php while ($asd == 1) { ?>');
    }


    public function parseEndTag()
    {
        $object = new WhileTag();
        return new Result($object->parseEndTag()=='<?php } ?>');
    }
}
