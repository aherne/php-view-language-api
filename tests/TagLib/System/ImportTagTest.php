<?php

namespace Test\Lucinda\Templating\TagLib\System;

use Lucinda\Templating\TagLib\System\ImportTag;
use Lucinda\Templating\ViewCompilation;
use Lucinda\Templating\ViewException;
use Lucinda\Templating\TagLib\System\EscapeTag;
use Lucinda\UnitTest\Result;

final class ImportTagTest
{
    public function parse()
    {
        $object = new ImportTag(dirname(__DIR__, 2)."/views", "html", new ViewCompilation(dirname(__DIR__, 3)."/compilations", "homepage", "html"));
        return new Result($object->parse("homepage", new EscapeTag()) === "<html>\n<body>\nWelcome to homepage!\n</body>\n</html>");
    }

    private function removeCWD(string $string): string
    {
        return str_replace(getcwd(),"", $string);
    }

    public function rejectTraversal()
    {
        $object = new ImportTag(dirname(__DIR__, 2)."/views", "html", new ViewCompilation(dirname(__DIR__, 3)."/compilations", "homepage", "html"));
        try {
            $object->parse("../tags/Greeting/client", new EscapeTag());
            return new Result(false);
        } catch (ViewException $e) {
            return new Result(str_contains($e->getMessage(), "Invalid relative path"));
        }
    }
    public function parseDocument()
    {
        $object = new ImportTag(dirname(__DIR__, 2).'/views', 'html', new ViewCompilation(dirname(__DIR__, 3).'/compilations', 'mapped-import'));
        $doc = $object->parseDocument('homepage', new EscapeTag());
        return new Result($doc->getSourceLocation(strpos($doc->getText(), 'Welcome')) === ['source'=>dirname(__DIR__, 2).'/views/homepage.html', 'line'=>2]);
    }
}
