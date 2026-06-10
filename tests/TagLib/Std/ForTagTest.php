<?php

namespace Test\Lucinda\Templating\TagLib\Std;

use Lucinda\Templating\TagLib\Std\ForTag;
use Lucinda\UnitTest\Result;

class ForTagTest
{
    public function parseStartTag()
    {
        $object = new ForTag();
        $results = [];
        $results[] = new Result($object->parseStartTag(["var"=>'i',"start"=>"0", "end"=>"10"])=='<?php for ($i=0; $i<=10; $i+=1) { ?>', "positive step");
        $results[] = new Result($object->parseStartTag(["var"=>'i',"start"=>"10", "end"=>"0", "step"=>"-1"])=='<?php for ($i=10; $i>=0; $i-=1) { ?>', "negative step");
        return $results;
    }


    public function parseEndTag()
    {
        $object = new ForTag();
        return new Result($object->parseEndTag()=='<?php } ?>');
    }
}
