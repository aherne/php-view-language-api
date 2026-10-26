<?php

namespace Lucinda\Templating\TagLib\Std;

use Lucinda\Templating\StartTag;
use Lucinda\Templating\SystemTag;

/**
 * Translates the fallback branch of an open conditional into PHP
 *
 * The parser requires an open :if block. The generated text closes the preceding
 * branch and opens its fallback branch; the final :if closing tag ends the group.
 *
 * Tag syntax: <:else>body</:if>
 *
 * @see \Lucinda\Templating\SystemTagParser
 */
final class ElseTag extends SystemTag implements StartTag
{
    /**
     * Translates the fallback branch of an open conditional into PHP
     *
     * Attributes are unused by the fallback branch.
     *
     * @param array<string,string> $parameters Parsed declaration attributes keyed by name
     * @return string PHP replacement for the opening or standalone declaration
     */
    public function parseStartTag(array $parameters=[]): string
    {
        return '<?php } else { ?>';
    }
}
