<?php

namespace Test\Lucinda\Templating;

use Lucinda\Templating\UserTagParser;
use Lucinda\Templating\ViewCompilation;
use Lucinda\Templating\TagLib\System\NamespaceTag;
use Lucinda\Templating\TagLib\System\EscapeTag;
use Lucinda\Templating\ViewException;
use Lucinda\UnitTest\Result;

class UserTagParserTest
{
    public function parse()
    {
        $object = new UserTagParser(
            new NamespaceTag(__DIR__."/tags"),
            "html",
            new ViewCompilation(dirname(__DIR__)."/compilations", "userTagParser", "html")
        );
        return new Result($this->removeCWD($object->parse('<p><Greeting:client user="Lucian"/></p>', new EscapeTag()))=="<p><!-- VL:START: /tests/tags/Greeting/client.html -->
Hello, Lucian!
<!-- VL:END: /tests/tags/Greeting/client.html -->
</p>");
    }

    private function removeCWD(string $string): string
    {
        return str_replace(getcwd(),"", $string);
    }

    public function rejectTraversal()
    {
        $object = new UserTagParser(
            new NamespaceTag(__DIR__."/tags"),
            "html",
            new ViewCompilation(dirname(__DIR__)."/compilations", "userTagParserTraversal", "html")
        );
        try {
            $object->parse('<Greeting:../client user="Lucian"/>', new EscapeTag());
            return new Result(false);
        } catch (ViewException $e) {
            return new Result(str_contains($e->getMessage(), "User tag not found") || str_contains($e->getMessage(), "Invalid user tag"));
        }
    }
}
