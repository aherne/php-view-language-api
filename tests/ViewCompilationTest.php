<?php

namespace Test\Lucinda\Templating;

use Lucinda\Templating\ViewCompilation;
use Lucinda\UnitTest\Result;

final class ViewCompilationTest
{
    private $object;

    public function __construct()
    {
        $this->object = new ViewCompilation(dirname(__DIR__)."/compilations", "index", "html");
    }

    public function getCompilationPath()
    {
        return new Result($this->object->getCompilationPath()==dirname(__DIR__)."/compilations/index.php");
    }


    public function addComponent()
    {
        $this->object->addComponent(__DIR__."/tags/Greeting/client.html");
        return new Result(true);
    }


    public function save()
    {
        $this->object->save("asdfg");
        return new Result(file_get_contents($this->object->getCompilationPath())=="asdfg");
    }

    public function hasChanged()
    {
        return new Result(!$this->object->hasChanged());
    }
}
