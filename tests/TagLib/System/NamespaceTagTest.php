<?php

namespace Test\Lucinda\Templating\TagLib\System;

use Lucinda\Templating\TagLib\System\NamespaceTag;
use Lucinda\UnitTest\Result;

class NamespaceTagTest
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


    public function get()
    {
        return new Result($this->object->get("Greeting")==dirname(__DIR__, 2).'/tags');
    }

    public function parseRelativeFolder()
    {
        $object = new NamespaceTag(dirname(__DIR__, 2)."/tags");
        $object->parse('<namespace taglib="Greeting" folder="."/>');
        return new Result($object->get("Greeting")==str_replace("\\", "/", realpath(dirname(__DIR__, 2)."/tags")));
    }
}
