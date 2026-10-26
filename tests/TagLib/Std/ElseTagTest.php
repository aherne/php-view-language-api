<?php

namespace Test\Lucinda\Templating\TagLib\Std;

use Lucinda\Templating\TagLib\Std\ElseTag;
use Lucinda\UnitTest\Result;

final class ElseTagTest
{
    public function parseStartTag()
    {
        $object = new ElseTag();
        return new Result($object->parseStartTag()=='<?php } else { ?>');
    }
}
