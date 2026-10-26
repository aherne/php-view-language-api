<?php

namespace Test\Lucinda\Templating;

use Lucinda\Templating\PathResolver;
use Lucinda\Templating\ViewException;
use Lucinda\UnitTest\Result;

final class PathResolverTest
{
    public function resolveFile()
    {
        $path = (new PathResolver())->resolveFile(__DIR__."/views", "test", "html", "import");
        return new Result(str_ends_with($path, "/tests/views/test.html"));
    }

    public function rejectTraversal()
    {
        try {
            (new PathResolver())->resolveFile(__DIR__."/views", "../test", "txt", "import");
            return new Result(false);
        } catch (ViewException $e) {
            return new Result(str_contains($e->getMessage(), "Invalid relative path"));
        }
    }
    public function resolveFolder()
    {
        return new Result((new PathResolver())->resolveFolder(__DIR__, "views", "namespace")==str_replace("\\", "/", realpath(__DIR__."/views")));
    }
        

    public function resolveUserTag()
    {
        $path = (new PathResolver())->resolveUserTag(__DIR__."/tags", "Greeting", "client", "html");
        return new Result(str_ends_with($path, "/tests/tags/Greeting/client.html"));
    }
        

    public function normalize()
    {
        return new Result((new PathResolver())->normalize("a\\b/c")==="a/b/c");
    }
        

}
