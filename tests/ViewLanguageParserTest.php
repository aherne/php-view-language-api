<?php

namespace Test\Lucinda\Templating;

use Lucinda\Templating\ViewLanguageParser;
use Lucinda\Templating\History\SourceContext;
use Lucinda\UnitTest\Result;

final class ViewLanguageParserTest
{
    public function compile()
    {
        $object = new ViewLanguageParser(__DIR__."/views", "html", dirname(__DIR__)."/compilations", __DIR__."/tags");
        $path = $object->compile("test");
        $php = file_get_contents($path);
        $offset = strpos($php, 'echo $user;');
        $line = 1 + substr_count(substr($php, 0, $offset), "\n");
        return new Result(!str_contains($php, '<!--') && (new SourceContext())->getSourceLocation($path, $line) === [
            'source'=>__DIR__.'/views/test.html', 'line'=>4
        ]);
    }
}
