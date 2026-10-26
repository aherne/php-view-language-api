<?php

namespace Test\Lucinda\Templating\TagLib\System;

use Lucinda\Templating\TagLib\System\EscapeTag;
use Lucinda\Templating\History\SourceDocument;
use Lucinda\UnitTest\Result;

final class EscapeTagTest
{
    public function backup()
    {
        $object = new EscapeTag();
        $input = 'asd <escape>${qwe}</escape> fgh';
        $protected = $object->backup($input);
        return new Result(!str_contains($protected, '${qwe}') && $object->restore($protected) === 'asd ${qwe} fgh');
    }

    public function restore()
    {
        $object = new EscapeTag();
        return new Result($object->restore($object->backup('<bkp>0</bkp><escape>literal</escape>')) === '<bkp>0</bkp>literal');
    }

    public function backupDocument()
    {
        $object = new EscapeTag();
        $document = $object->backupDocument(new SourceDocument("<escape>a\nb</escape>\nafter", 'escape.tpl'));
        $offset = strpos($document->getText(), 'after');
        return new Result($document->getSourceLocation($offset) === ['source'=>'escape.tpl', 'line'=>3]);
    }

    public function restoreDocument()
    {
        $object = new EscapeTag();
        $document = $object->restoreDocument($object->backupDocument(new SourceDocument("<escape>a\nb</escape>", 'escape.tpl')));
        return new Result($document->getText() === "a\nb" && $document->getSourceLocation(2) === ['source'=>'escape.tpl', 'line'=>2]);
    }
}
