<?php

namespace Test\Lucinda\Templating;

use Lucinda\Templating\UserTag;
use Lucinda\UnitTest\Result;

class UserTagTest
{
    public function parseStartTag()
    {
        $object = new UserTag(__DIR__."/tags/Greeting/client.html");
        return new Result($this->removeCWD($object->parseStartTag(["user"=>"Lucian"]))=="<!-- VL:START: /tests/tags/Greeting/client.html -->
Hello, Lucian!
<!-- VL:END: /tests/tags/Greeting/client.html -->
");
    }

    private function removeCWD(string $string): string
    {
        return str_replace(getcwd(),"", $string);
    }
}
