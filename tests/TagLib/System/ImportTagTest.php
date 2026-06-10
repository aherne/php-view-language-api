<?php

namespace Test\Lucinda\Templating\TagLib\System;

use Lucinda\Templating\TagLib\System\ImportTag;
use Lucinda\Templating\ViewCompilation;
use Lucinda\Templating\ViewException;
use Lucinda\Templating\TagLib\System\EscapeTag;
use Lucinda\UnitTest\Result;

class ImportTagTest
{
    public function parse()
    {
        $object = new ImportTag(dirname(__DIR__, 2)."/views", "html", new ViewCompilation(dirname(__DIR__, 3)."/compilations", "homepage", "html"));
        // die($object->parse("homepage", new EscapeTag()));
        return new Result(
            $this->removeCWD($object->parse("homepage", new EscapeTag()))=='<!-- VL:START: /tests/views/homepage.html -->
<!-- VL:START: /tests/views/header.html -->
<html>
<body>
<!-- VL:END: /tests/views/header.html -->

Welcome to homepage!
<!-- VL:START: /tests/views/footer.html -->
</body>
</html>
<!-- VL:END: /tests/views/footer.html -->

<!-- VL:END: /tests/views/homepage.html -->
');
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
}
