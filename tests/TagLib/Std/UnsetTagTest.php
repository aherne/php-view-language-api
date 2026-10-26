<?php

namespace Test\Lucinda\Templating\TagLib\Std;

use Lucinda\Templating\TagLib\Std\UnsetTag;
use Lucinda\UnitTest\Result;

final class UnsetTagTest
{
    public function parseStartTag()
    {
        $object = new UnsetTag();
        return new Result($object->parseStartTag(["var"=>'x'])=='<?php unset($x); ?>');
    }
}
