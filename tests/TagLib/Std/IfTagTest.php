<?php

namespace Test\Lucinda\Templating\TagLib\Std;

use Lucinda\Templating\TagLib\Std\IfTag;
use Lucinda\UnitTest\Result;

final class IfTagTest
{
    public function parseStartTag()
    {
        $object = new IfTag();
        return new Result($object->parseStartTag(["test"=>'${asd} == 1'])=='<?php if ($asd == 1) { ?>');
    }


    public function parseEndTag()
    {
        $object = new IfTag();
        return new Result($object->parseEndTag()=='<?php } ?>');
    }
}
