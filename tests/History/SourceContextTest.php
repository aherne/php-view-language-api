<?php
namespace Test\Lucinda\Templating\History;
    
use Lucinda\Templating\History\SourceContext;
use Lucinda\Templating\History\SourceDocument;
use Lucinda\Templating\History\SourceMapBuilder;
use Lucinda\Templating\History\SourceMapStorage;
use Lucinda\UnitTest\Result;

class SourceContextTest
{

    public function getSourceLocation()
    {
        $file = tempnam(sys_get_temp_dir(), 'vl-context-');
        $doc = new SourceDocument("line 1\nline 2", '/tmp/source.tpl');
        try {
            file_put_contents($file, $doc->getText());
            (new SourceMapStorage())->save((new SourceMapBuilder())->build($doc), $file, $doc->getText(), '');
            return new Result((new SourceContext())->getSourceLocation($file, 2) === ['source'=>'/tmp/source.tpl', 'line'=>2]);
        } finally {
            unlink($file);
            unlink($file.'.map.json');
        }
    }
        

}
