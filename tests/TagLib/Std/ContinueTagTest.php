<?php

namespace Test\Lucinda\Templating\TagLib\Std;

use Lucinda\Templating\TagLib\Std\ContinueTag;
use Lucinda\UnitTest\Result;

final class ContinueTagTest
{
    public function parseStartTag()
    {
        $object = new ContinueTag();
        return new Result($object->parseStartTag()=='<?php continue; ?>');
    }
}
