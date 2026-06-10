<?php

namespace Test\Lucinda\Templating;

use Lucinda\Templating\Wrapper;
use Lucinda\Templating\ViewException;
use Lucinda\UnitTest\Result;

class WrapperTest
{
    public function compile()
    {
        $object = new Wrapper(
            \simplexml_load_string(
                '
        <xml>
            <templating compilations_path="'.dirname(__DIR__).'/compilations" tags_path="'.__DIR__.'/tags" templates_path="'.__DIR__.'/views" templates_extension="html"/>
        </xml>'
            )
        );
        return new Result(
            $object->compile("test", ["users"=>["John Doe", "Jane Doe"]])=='
<h1>
Hello, Lucian!

</h1>
<ul>
        <li>John Doe</li>
        <li>Jane Doe</li>
    </ul>

'
        );
    }

    public function compileDebug()
    {
        $object = new Wrapper(
            \simplexml_load_string(
                '
        <xml>
            <templating debug="true" compilations_path="'.dirname(__DIR__).'/compilations" tags_path="'.__DIR__.'/tags" templates_path="'.__DIR__.'/views" templates_extension="html"/>
        </xml>'
            )
        );
        return new Result(str_contains($object->compile("test", ["users"=>["John Doe"]]), "VL:START"));
    }

    public function compileEscapedExpressions()
    {
        $object = new Wrapper(
            \simplexml_load_string(
                '
        <xml>
            <templating escape_expressions="true" compilations_path="'.dirname(__DIR__).'/compilations" tags_path="'.__DIR__.'/tags" templates_path="'.__DIR__.'/views" templates_extension="html"/>
        </xml>'
            )
        );
        return new Result(trim($object->compile("escape-expression", ["name"=>"<b>Lucian</b>"]))=="<p>&lt;b&gt;Lucian&lt;/b&gt;</p>");
    }

    public function compileRuntimeError()
    {
        $object = new Wrapper(
            \simplexml_load_string(
                '
        <xml>
            <templating compilations_path="'.dirname(__DIR__).'/compilations" tags_path="'.__DIR__.'/tags" templates_path="'.__DIR__.'/views" templates_extension="html"/>
        </xml>'
            )
        );
        try {
            $object->compile("runtime-error", []);
            return new Result(false);
        } catch (ViewException $e) {
            return new Result(str_contains($e->getMessage(), "runtime-error.html:2"));
        }
    }
}
