<?php

namespace Test\Lucinda\Templating;

use Lucinda\Templating\Wrapper;
use Lucinda\Templating\ViewException;
use Lucinda\UnitTest\Result;

final class WrapperTest
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
            $object->compile("test", ["users"=>["John Doe", "Jane Doe"]]) === "<h1>Hello, Lucian!</h1>\n<ul>\n    \n    <li>John Doe</li>\n    \n    <li>Jane Doe</li>\n    \n</ul>"
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
        return new Result($object->compile("test", ["users"=>["John Doe"]]) === "<h1>Hello, Lucian!</h1>\n<ul>\n    \n    <li>John Doe</li>\n    \n</ul>");
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

    public function macroErrorLocations()
    {
        $cases = [
            ["before\n<import/>", 2, 'requires attribute: file'],
            ["<escape>one\ntwo\nthree</escape>\n<:unknown/>", 4, 'System tag not found'],
            ["before\n<:set val=\"x\"/>", 2, 'requires attribute'],
            ["before\n<namespace folder=\".\"/>", 2, 'requires attribute'],
            ["before\n<Bad:../tag/>", 2, 'Invalid user tag'],
            ["before\n<Greeting:missing/>", 2, 'User tag not found'],
            ["<import file=\"C\"/>\n<:if test=\"\${data.flag}\">", 2, 'missing an end tag'],
            ["before\n<:if test=\"\${data.flag}\"/>\n", 2, 'requires an end tag'],
            ["before\n<:else>\n", 2, 'must follow'],
            ["before\n<:if test=\"\${data.flag}\"></:foreach>", 2, 'Unexpected system end tag']
        ];
        $results = [];
        foreach ($cases as [$body, $line, $message]) {
            $results[] = $this->withTemplates(['A'=>"<import file=\"C\"/>\n<import file=\"B\"/>", 'B'=>$body, 'C'=>"many\nextra\nlines"],
                function (Wrapper $wrapper) use ($line, $message): Result {
                    try {
                        $wrapper->compile('A', []);
                        return new Result(false, $message);
                    } catch (ViewException $e) {
                        return new Result(str_contains($e->getMessage(), $message)
                            && str_ends_with($e->getSourceLocation()['source'] ?? '', '/B.tpl')
                            && $e->getSourceLocation()['line'] === $line, $message);
                    }
                });
        }
        return $results;
    }

    public function generatedPhpErrorLocations()
    {
        $cases = [
            ['A'=>"<import file=\"B\"/>\n<?php echo \$missing; ?>", 'B'=>"many\nextra\nlines", 'file'=>'A.tpl', 'line'=>2, 'type'=>\ErrorException::class],
            ['A'=>'<import file="B"/>', 'B'=>"first\n<?php echo ; ?>", 'file'=>'B.tpl', 'line'=>2, 'type'=>\ParseError::class],
            ['A'=>'prefix<import file="B"/>suffix', 'B'=>'${data.missing}', 'file'=>'B.tpl', 'line'=>1, 'type'=>\ErrorException::class],
            ['A'=>'${data.ok}<import file="B"/>${data.ok}', 'B'=>'${data.missing}', 'file'=>'B.tpl', 'line'=>1, 'type'=>\ErrorException::class],
            ['A'=>'<import file="B"/>', 'B'=>"<escape>first\n<?php echo \$missing; ?></escape>", 'file'=>'B.tpl', 'line'=>2, 'type'=>\ErrorException::class],
            ['A'=>'<Greeting:broken/>', 'B'=>'unused', 'tag'=>"first\n\${data.missing}", 'file'=>'broken.tpl', 'line'=>2, 'type'=>\ErrorException::class],
            ['A'=>'<import file="B"/>', 'B'=>"<import file=\"C\"/>\n\n\${data.missing}", 'C'=>"extra\nlines\n", 'file'=>'B.tpl', 'line'=>3, 'type'=>\ErrorException::class],
            ['A'=>"<:if\n test=\"\${data.missing}\">x</:if>", 'B'=>'unused', 'file'=>'A.tpl', 'line'=>1, 'type'=>\ErrorException::class]
        ];
        $results = [];
        foreach ($cases as $case) {
            $files = ['A'=>$case['A'], 'B'=>$case['B'], 'C'=>$case['C'] ?? ''];
            if (isset($case['tag'])) {
                $files['tags/Greeting/broken'] = $case['tag'];
            }
            $results[] = $this->withTemplates($files, function (Wrapper $wrapper) use ($case): Result {
                $bufferLevel = ob_get_level();
                try {
                    $wrapper->compile('A', ['ok'=>'yes']);
                    return new Result(false, $case['file'].':'.$case['line']);
                } catch (ViewException $e) {
                    $location = $e->getSourceLocation();
                    return new Result(str_ends_with($location['source'] ?? '', '/'.$case['file'])
                        && ($location['line'] ?? null) === $case['line']
                        && $e->getPrevious() instanceof $case['type'] && ob_get_level() === $bufferLevel,
                        $case['file'].':'.$case['line'].' '.$e->getMessage());
                }
            });
        }
        return $results;
    }

    public function helperErrorLocation()
    {
        return $this->withTemplates(['A'=>"first\n<?php \$data['helper'](); ?>"], function (Wrapper $wrapper): Result {
            try {
                $wrapper->compile('A', ['helper'=>static function () { throw new \RuntimeException('helper failure'); }]);
                return new Result(false);
            } catch (ViewException $e) {
                return new Result(str_ends_with($e->getSourceLocation()['source'] ?? '', '/A.tpl')
                    && $e->getSourceLocation()['line'] === 2 && $e->getPrevious() instanceof \RuntimeException);
            }
        });
    }

    public function phpSyntaxAtEndOfFile()
    {
        return $this->withTemplates(['A'=>"first\n<?php if (true) {\n"], function (Wrapper $wrapper): Result {
            try {
                $wrapper->compile('A', []);
                return new Result(false);
            } catch (ViewException $e) {
                return new Result(str_ends_with($e->getSourceLocation()['source'] ?? '', '/A.tpl')
                    && $e->getPrevious() instanceof \ParseError);
            }
        });
    }

    public function expressionNewlines()
    {
        $cases = [
            ['Hello', 'Hello'],
            ["Hello\n", "Hello\n"],
            ['Hello ${data.name}', 'Hello Lucian'],
            ["Hello \${data.name}\n", "Hello Lucian\n"],
            ["Hello \${data.name}\r\n", "Hello Lucian\r\n"],
            ["\${data.name}\n\nnext", "Lucian\n\nnext"],
            ["<:set var=\"name\" val=\"Lucian\"/>\n\${name}", "\nLucian"],
            ["<?php echo 'raw'; ?>\nnext", 'rawnext']
        ];
        $results = [];
        foreach ($cases as [$template, $expected]) {
            $results[] = $this->withTemplates(['A'=>$template], function (Wrapper $wrapper) use ($expected): Result {
                $output = $wrapper->compile('A', ['name'=>'Lucian']);
                return new Result($output === $expected, json_encode($output).' expected '.json_encode($expected));
            });
        }
        return $results;
    }

    public function exactOutputAndCache()
    {
        $literal = "\t<!-- user comment -->\r\n<!-- VL:START: literal text -->\n<&>";
        return $this->withTemplates(['A'=>'start<import file="B"/>end', 'B'=>$literal],
            function (Wrapper $wrapper, string $root) use ($literal): Result {
                $expected = 'start'.$literal.'end';
                $first = $wrapper->compile('A', []);
                $mapFile = $root.'/compiled/A.php.map.json';
                $savedMap = file_get_contents($mapFile);
                $second = $wrapper->compile('A', []);
                file_put_contents($mapFile, '{}');
                $third = $wrapper->compile('A', []);
                unlink($mapFile);
                $fourth = $wrapper->compile('A', []);
                return new Result($first === $expected && $second === $expected && $third === $expected
                    && $fourth === $expected && file_get_contents($mapFile) === $savedMap);
            });
    }

    public function escapingCacheIdentity()
    {
        return $this->withTemplates(['A'=>'${data.value}'], function (Wrapper $wrapper, string $root): Result {
            $raw = $wrapper->compile('A', ['value'=>'<&>']);
            $xml = $this->templateXml($root);
            $xml->templating['escape_expressions'] = 'true';
            $escaped = (new Wrapper($xml))->compile('A', ['value'=>'<&>']);
            $rawAgain = $wrapper->compile('A', ['value'=>'<&>']);
            return new Result($raw === '<&>' && $escaped === '&lt;&amp;&gt;' && $rawAgain === '<&>');
        });
    }

    public function circularImport()
    {
        return $this->withTemplates(['A'=>'<import file="B"/>', 'B'=>"first\n<import file=\"A\"/>"], function (Wrapper $wrapper): Result {
            try {
                $wrapper->compile('A', []);
                return new Result(false);
            } catch (ViewException $e) {
                return new Result(str_contains($e->getMessage(), 'Circular import')
                    && str_ends_with($e->getSourceLocation()['source'] ?? '', '/B.tpl')
                    && $e->getSourceLocation()['line'] === 2);
            }
        });
    }

    private function templateXml(string $root): \SimpleXMLElement
    {
        $xml = new \SimpleXMLElement('<xml><templating/></xml>');
        $xml->templating['templates_path'] = $root;
        $xml->templating['tags_path'] = $root.'/tags';
        $xml->templating['templates_extension'] = 'tpl';
        $xml->templating['compilations_path'] = $root.'/compiled';
        return $xml;
    }

    private function withTemplates(array $files, callable $test): Result
    {
        $root = sys_get_temp_dir().'/vl-diagnostics-'.bin2hex(random_bytes(8));
        mkdir($root);
        mkdir($root.'/tags');
        try {
            foreach ($files as $name=>$body) {
                $path = $root.'/'.$name.'.tpl';
                if (!is_dir(dirname($path))) {
                    mkdir(dirname($path), 0777, true);
                }
                file_put_contents($path, $body);
            }
            return $test(new Wrapper($this->templateXml($root)), $root);
        } finally {
            $entries = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($entries as $entry) {
                $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
            }
            rmdir($root);
        }
    }
}
