<?php

namespace Test\Lucinda\Templating\TagLib\Std;

use Lucinda\Templating\TagLib\Std\ForeachTag;
use Lucinda\UnitTest\Result;

final class ForeachTagTest
{
    public function parseStartTag()
    {
        $object = new ForeachTag();
        return new Result($object->parseStartTag(["var"=>'${asd}',"key"=>"k", "val"=>"v"])=='<?php foreach ($asd as $k=>$v) { ?>');
    }

    public function parseEndTag()
    {
        $object = new ForeachTag();
        return new Result($object->parseEndTag()=='<?php } ?>');
    }
}
