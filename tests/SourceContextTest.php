<?php

namespace Test\Lucinda\Templating;

use Lucinda\Templating\SourceContext;
use Lucinda\UnitTest\Result;

class SourceContextTest
{

    public function fromCompiledLine()
    {
        $file = dirname(__DIR__)."/compilations/source-context-test.php";
        if (!file_exists(dirname($file))) {
            mkdir(dirname($file), 0777, true);
        }
        file_put_contents(
            $file,
            "<!-- VL:START: /tmp/source.html -->\n".
            "line 1\n".
            "line 2\n".
            "<!-- VL:END: /tmp/source.html -->\n"
        );
        return new Result(SourceContext::fromCompiledLine($file, 3)==["source"=>"/tmp/source.html", "line"=>2]);
    }
        

}
