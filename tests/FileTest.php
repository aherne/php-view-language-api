<?php

namespace Test\Lucinda\Templating;

use Lucinda\Templating\File;
use Lucinda\UnitTest\Result;

class FileTest
{
    private $object;

    public function __construct()
    {
        $this->object = new File(__DIR__."/test.txt");
    }

    public function putContents()
    {
        $this->object->putContents("asdf");
        $emptyFile = new File(__DIR__."/empty.txt");
        $emptyFile->putContents("");
        return new Result($emptyFile->getContents()==="");
    }


    public function getContents()
    {
        return new Result($this->object->getContents()=="asdf");
    }


    public function exists()
    {
        return new Result($this->object->exists());
    }


    public function getModificationTime()
    {
        return new Result($this->object->getModificationTime()==time());
    }
}
