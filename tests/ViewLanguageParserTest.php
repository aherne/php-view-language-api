<?php

namespace Test\Lucinda\Templating;

use Lucinda\Templating\ViewLanguageParser;
use Lucinda\UnitTest\Result;

class ViewLanguageParserTest
{
    public function compile()
    {
        $object = new ViewLanguageParser(
            __DIR__."/views",
            "html",
            dirname(__DIR__)."/compilations",
            __DIR__."/tags"
        );
        $compilationPath = $object->compile("test");
        return new Result(
            $this->removeCWD(file_get_contents($compilationPath))=='<!-- VL:START: /tests/views/test.html -->
<h1><!-- VL:START: /tests/tags/Greeting/client.html -->
Hello, Lucian!
<!-- VL:END: /tests/tags/Greeting/client.html -->
</h1>
<ul>
    <?php foreach ($data["users"] as $user) { ?>
    <li><?php echo $user; ?></li>
    <?php } ?>
</ul>
<!-- VL:END: /tests/views/test.html -->
');
    }

    private function removeCWD(string $string): string
    {
        return str_replace(getcwd(),"", $string);
    }
}
