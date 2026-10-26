<?php

namespace Lucinda\Templating;

/**
 * Defines translation of a standard tag with both opening and closing declarations
 *
 * The inherited opening operation receives the tag attributes. The closing operation
 * returns the PHP text that terminates the corresponding control-flow block.
 *
 * @see StartTag
 * @see SystemTagParser
 */
interface StartEndTag extends StartTag
{
    /**
     * Translates the closing declaration of a block tag
     *
     * @return string PHP text that closes the corresponding control-flow block
     */
    public function parseEndTag(): string;
}
