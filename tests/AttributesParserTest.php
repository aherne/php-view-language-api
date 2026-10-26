<?php

namespace Test\Lucinda\Templating;

use Lucinda\Templating\AttributesParser;
use Lucinda\UnitTest\Result;

final class AttributesParserTest
{
    public function parse()
    {
        $object = new AttributesParser();
        return new Result($object->parse('asd="fgh" qwe="rty" empty=""')==["asd"=>"fgh", "qwe"=>"rty", "empty"=>""]);
    }

    public function parseInvalid()
    {
        try {
            $object = new AttributesParser();
            $object->parse("asd='fgh'");
            return new Result(false);
        } catch (\Lucinda\Templating\ViewException $e) {
            return new Result(str_contains($e->getMessage(), "Invalid attributes"));
        }
    }
}
