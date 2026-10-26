<?php

namespace Lucinda\Templating;

/**
 * Defines translation of a tag's opening or standalone declaration into PHP
 *
 * Implementations receive attributes indexed by name and return replacement text.
 * Tags that also require a closing declaration implement StartEndTag.
 *
 * @see StartEndTag
 * @see SystemTagParser
 */
interface StartTag
{
    /**
     * Translates the attributes of an opening or standalone tag declaration
     *
     * @param array<string,string> $parameters Attribute values keyed by name
     * @return string Replacement PHP or template text
     * @throws ViewException If the declaration's attributes are invalid
     */
    public function parseStartTag(array $parameters=[]): string;
}
