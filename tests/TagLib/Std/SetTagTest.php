<?php

namespace Test\Lucinda\Templating\TagLib\Std;

use Lucinda\Templating\TagLib\Std\SetTag;
use Lucinda\UnitTest\Result;

final class SetTagTest
{
    public function parseStartTag()
    {
        $object = new SetTag();
        return new Result($object->parseStartTag(["var"=>'x', "val"=>"y"])=='<?php $x = \'y\'; ?>');
    }
}
