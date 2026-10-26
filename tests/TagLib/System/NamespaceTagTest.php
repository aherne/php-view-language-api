<?php

namespace Test\Lucinda\Templating\TagLib\System;

use Lucinda\Templating\TagLib\System\NamespaceTag;
use Lucinda\UnitTest\Result;

final class NamespaceTagTest
{
    private $object;

    public function __construct()
    {
        $this->object = new NamespaceTag("");
    }

    public function parse()
    {
        return new Result(
            $this->object->parse(
                '<namespace taglib="Greeting" folder="'.dirname(__DIR__, 2).'/tags"/>
asdf'
            )=='
asdf'
        );
    }


    public function getTagLibraryPath()
    {
        return new Result($this->object->getTagLibraryPath("Greeting")==dirname(__DIR__, 2).'/tags');
    }

    public function parseRelativeFolder()
    {
        $object = new NamespaceTag(dirname(__DIR__, 2)."/tags");
        $object->parse('<namespace taglib="Greeting" folder="."/>');
        return new Result($object->getTagLibraryPath("Greeting")==str_replace("\\", "/", realpath(dirname(__DIR__, 2)."/tags")));
    }
    public function parseDocument()
    {
        $object = new NamespaceTag(dirname(__DIR__, 2).'/tags');
        try {
            $object->parseDocument(new \Lucinda\Templating\History\SourceDocument("line\n<namespace folder=\".\"/>", 'namespace.tpl'));
            return new Result(false);
        } catch (\Lucinda\Templating\ViewException $e) {
            return new Result($e->getSourceLocation() === ['source'=>'namespace.tpl', 'line'=>2]);
        }
    }
}
