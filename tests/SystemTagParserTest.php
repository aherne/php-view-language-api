<?php

namespace Test\Lucinda\Templating;

use Lucinda\Templating\SystemTagParser;
use Lucinda\Templating\ViewException;
use Lucinda\UnitTest\Result;

final class SystemTagParserTest
{
    public function parse()
    {
        $results = [];

        $object = new SystemTagParser();

        $results[] = new Result($object->parse('asd <:set var="zxc" val="vbn"/> fgh') == "asd <?php \$zxc = 'vbn'; ?> fgh", "start tag test");
        $results[] = new Result($object->parse('asd <:foreach var="${data.users}" val="user">${user}</:foreach> fgh') == 'asd <?php foreach ($data["users"] as $user) { ?>${user}<?php } ?> fgh', "start end tag test");
        try {
            $object->parse("<:unknown/>");
            $results[] = new Result(false, "unknown tag test");
        } catch (ViewException $e) {
            $results[] = new Result($e->getMessage()=="System tag not found: :unknown", "unknown tag test");
        }

        return $results;
    }

    public function parseMalformed()
    {
        $results = [];
        $object = new SystemTagParser();

        $cases = [
            "<:set var=\"x\"></:set>" => "must not have an end tag",
            '<:if test="${x}">' => "is missing an end tag",
            '<:if test="${x}"/>' => "requires an end tag",
            "<:else>" => "must follow an open ':if' tag"
        ];

        foreach ($cases as $template=>$message) {
            try {
                $object->parse($template);
                $results[] = new Result(false, $template);
            } catch (ViewException $e) {
                $results[] = new Result(str_contains($e->getMessage(), $message), $template);
            }
        }

        return $results;
    }
    public function parseDocument()
    {
        try {
            (new SystemTagParser())->parseDocument(new \Lucinda\Templating\History\SourceDocument("line\n<:unknown/>", 'system.tpl'));
            return new Result(false);
        } catch (ViewException $e) {
            return new Result($e->getSourceLocation() === ['source'=>'system.tpl', 'line'=>2]);
        }
    }
}
