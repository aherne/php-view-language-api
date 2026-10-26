<?php

namespace Test\Lucinda\Templating;

use Lucinda\Templating\UserTagParser;
use Lucinda\Templating\ViewCompilation;
use Lucinda\Templating\TagLib\System\NamespaceTag;
use Lucinda\Templating\TagLib\System\EscapeTag;
use Lucinda\Templating\ViewException;
use Lucinda\UnitTest\Result;

final class UserTagParserTest
{
    public function parse()
    {
        $object = new UserTagParser(
            new NamespaceTag(__DIR__."/tags"),
            "html",
            new ViewCompilation(dirname(__DIR__)."/compilations", "userTagParser", "html")
        );
        return new Result($object->parse('<p><Greeting:client user="Lucian"/></p>', new EscapeTag()) === "<p>Hello, Lucian!</p>");
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
    public function parseDocument()
    {
        $object = new UserTagParser(new NamespaceTag(__DIR__.'/tags'), 'html', new ViewCompilation(dirname(__DIR__).'/compilations', 'mapped-user'));
        $doc = $object->parseDocument(new \Lucinda\Templating\History\SourceDocument('<Greeting:client user="Lucian"/>', 'caller.tpl'), new EscapeTag());
        return new Result($doc->getSourceLocation() === ['source'=>__DIR__.'/tags/Greeting/client.html', 'line'=>1]);
    }
}
