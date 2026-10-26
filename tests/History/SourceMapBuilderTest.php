<?php

namespace Test\Lucinda\Templating\History;

use Lucinda\Templating\History\SourceDocument;
use Lucinda\Templating\History\SourceMapBuilder;
use Lucinda\UnitTest\Result;

/** Verifies mapping construction across literal, generated and untracked spans. */
final class SourceMapBuilderTest
{
    /** @return Result[] Source-location checks across independent build operations */
    public function build()
    {
        $builder = new SourceMapBuilder();
        $document = (new SourceDocument("a\nb\n", 'a.tpl'))->append(new SourceDocument('c', 'b.tpl', 8));
        $map = $builder->build($document);
        $generated = (new SourceDocument("directive", 'expression.tpl', 6))->createGeneratedDocument("echo 1;\necho 2;");
        $generatedMap = $builder->build($generated);
        return [
            new Result($map->getSourceLocation(2) === ['source'=>'a.tpl', 'line'=>2]
                && $map->getSourceLocation(3) === ['source'=>'b.tpl', 'line'=>8], 'literal and imported lines'),
            new Result($generatedMap->getSourceLocation(1) === ['source'=>'expression.tpl', 'line'=>6]
                && $generatedMap->getSourceLocation(2) === ['source'=>'expression.tpl', 'line'=>6], 'generated lines stay at their directive'),
            new Result($builder->build(new SourceDocument('untracked'))->getSourceLocations() === [], 'untracked text is unmapped'),
            new Result($builder->build(new SourceDocument())->getSourceLocations() === []
                && $map->getSourceLocation(3) === ['source'=>'b.tpl', 'line'=>8], 'builds do not share mutable state')
        ];
    }
}
