<?php

namespace Test\Lucinda\Templating\History;

use Lucinda\Templating\History\SourceDocument;
use Lucinda\Templating\ViewException;
use Lucinda\UnitTest\Result;

final class SourceDocumentTest
{
    public function getText()
    {
        $text = "\r\n<>&\t\n";
        return new Result((new SourceDocument($text, 'plain.txt'))->getText() === $text);
    }

    public function getSpans()
    {
        $doc = (new SourceDocument('A', 'a.tpl'))->append(new SourceDocument('B', 'b.tpl'));
        return new Result(array_column($doc->getSpans(), 'source') === ['a.tpl', 'b.tpl']);
    }

    public function append()
    {
        $a = new SourceDocument('A', 'a.tpl');
        $ab = $a->append(new SourceDocument('B', 'b.tpl', 3));
        return new Result($a->getText() === 'A' && $ab->getSourceLocation(1) === ['source'=>'b.tpl', 'line'=>3]);
    }

    public function slice()
    {
        $doc = (new SourceDocument("a\nb", 'a.tpl'))->append(new SourceDocument("c\nd", 'b.tpl'));
        $slice = $doc->slice(2, 3);
        return new Result($slice->getText() === "bc\n" && $slice->getSourceLocation() === ['source'=>'a.tpl', 'line'=>2]
            && $slice->getSourceLocation(1) === ['source'=>'b.tpl', 'line'=>1]);
    }

    public function getSourceLocation()
    {
        $doc = new SourceDocument("é\r\nvalue", 'utf8.tpl');
        return new Result($doc->getSourceLocation(strpos($doc->getText(), 'value')) === ['source'=>'utf8.tpl', 'line'=>2]
            && $doc->getSourceLocation(999) === null);
    }

    public function createGeneratedDocument()
    {
        $doc = new SourceDocument("first\nmacro", 'macro.tpl');
        $code = $doc->createGeneratedDocument("first generated line\nsecond generated line", 6);
        return new Result($code->getSourceLocation(21) === ['source'=>'macro.tpl', 'line'=>2]);
    }

    public function createException()
    {
        $e = (new SourceDocument("a\nb", 'a.tpl'))->createException('Invalid tag', 2);
        return new Result($e->getMessage() === 'Invalid tag @ a.tpl:2' && $e->getSourceLocation() === ['source'=>'a.tpl', 'line'=>2]);
    }

    public function replace()
    {
        $doc = new SourceDocument("import\nafter", 'a.tpl');
        $expanded = $doc->replace('/import/', static fn() => new SourceDocument("B\nB\nB", 'b.tpl'));
        $expanded = $expanded->replace('/after/', static fn() => 'generated');
        $offset = strpos($expanded->getText(), 'generated');
        try {
            $expanded->replace('/generated/', static function () { throw new ViewException('Bad attribute'); });
            return new Result(false);
        } catch (ViewException $e) {
            return new Result($expanded->getSourceLocation($offset) === ['source'=>'a.tpl', 'line'=>2]
                && $e->getSourceLocation() === ['source'=>'a.tpl', 'line'=>2]);
        }
    }

}
