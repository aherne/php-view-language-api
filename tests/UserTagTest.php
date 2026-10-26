<?php

namespace Test\Lucinda\Templating;

use Lucinda\Templating\UserTag;
use Lucinda\UnitTest\Result;

final class UserTagTest
{
    public function parseStartTag()
    {
        $object = new UserTag(__DIR__."/tags/Greeting/client.html");
        return new Result($object->parseStartTag(["user"=>"Lucian"]) === "Hello, Lucian!");
    }

    private function removeCWD(string $string): string
    {
        return str_replace(getcwd(),"", $string);
    }
    public function parseDocument()
    {
        $doc = (new UserTag(__DIR__.'/tags/Greeting/client.html'))->parseDocument(['user'=>"A\nB"]);
        return new Result($doc->getSourceLocation(strpos($doc->getText(), 'B')) === ['source'=>__DIR__.'/tags/Greeting/client.html', 'line'=>1]);
    }
}
