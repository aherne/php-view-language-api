<?php

namespace Test\Lucinda\Templating\TagLib\Std;

use Lucinda\Templating\TagLib\Std\ElseifTag;
use Lucinda\UnitTest\Result;

final class ElseifTagTest
{
    public function parseStartTag()
    {
        $object = new ElseifTag();
        return new Result($object->parseStartTag(["test"=>'${asd}==1'])=='<?php } else if ($asd==1) { ?>');
    }
}
