<?php

namespace Lucinda\Templating;

use Lucinda\Templating\History\SourceDocument;
use Lucinda\Templating\TagLib\System\EscapeTag;
use Lucinda\Templating\TagLib\System\NamespaceTag;

/**
 * Expands user tag declarations from configured tag-library roots
 *
 * The parser validates declarations, resolves tag files through the namespace policy
 * and records dependencies. Inserted tag bodies retain their own source locations,
 * while malformed declarations and unresolved files point to the calling template.
 * Recursive expansion protects escaped bodies and stops at its depth limit.
 *
 * @see UserTag
 * @see TagLib\System\NamespaceTag
 * @see SourceDocument
 */
final class UserTagParser
{
    /**
     * Extension appended when resolving user-tag template files
     */
    private string $tagExtension;
    /**
     * Compilation receiving dependencies discovered during user-tag expansion
     */
    private ViewCompilation $viewCompilation;
    /**
     * Namespace policy providing the effective root of each user-tag library
     */
    private NamespaceTag $namespaceTag;
    /**
     * Parser validating user-tag declaration attributes
     */
    private AttributesParser $attributesParser;

    /**
     * Instance operation resolving template paths and enforcing configured roots
     */
    private PathResolver $pathResolver;

    /**
     * Configures user-tag namespace resolution, file extension and dependency recording
     *
     * @param NamespaceTag $namespaces Policy resolving each library's effective root folder
     * @param string $tagExtension Extension shared by user-tag template files
     * @param ViewCompilation $viewCompilation Compilation receiving the expanded tag-file dependencies
     */
    public function __construct(NamespaceTag $namespaces, string $tagExtension, ViewCompilation $viewCompilation)
    {
        $this->pathResolver = new PathResolver();
        $this->namespaceTag = $namespaces;
        $this->tagExtension = $tagExtension;
        $this->viewCompilation = $viewCompilation;
        $this->attributesParser = new AttributesParser();
    }

    /**
     * Expands user-tag declarations in an untracked text string
     *
     * @param string $subject Template text whose imports and namespaces are already processed
     * @param EscapeTag $escaper Compilation-local store protecting literal escaped bodies
     * @return string Text after recursive user-tag expansion
     * @throws ViewException If a declaration, tag path or recursive expansion is invalid
     */
    public function parse(string $subject, EscapeTag $escaper): string
    {
        return $this->parseDocument(new SourceDocument($subject), $escaper)->getText();
    }

    /**
     * Expands user tags while retaining both caller and tag-template source locations
     *
     * Each pass expands declarations then protects newly inserted escaped bodies.
     * A maximum of 128 expansion passes prevents unbounded circular expansion.
     *
     * @param SourceDocument $subject Template document containing user-tag declarations
     * @param EscapeTag $escaper Compilation-local escaped-document store
     * @return SourceDocument Expanded document with tag bodies mapped to their own files
     * @throws ViewException If syntax, tag resolution or the expansion-depth limit fails
     */
    public function parseDocument(SourceDocument $subject, EscapeTag $escaper): SourceDocument
    {
        $pattern = '/<([\w\-]+):([\w\-]+)\s*([^>]*)?>/';
        for ($depth = 0; $depth < 128; ++$depth) {
            $this->validateDeclarations($subject);
            if (!preg_match($pattern, $subject->getText())) {
                return $subject;
            }
            $subject = $subject->replace($pattern, function (array $matches): SourceDocument {
                $parameters = isset($matches[3]) ? $this->attributesParser->parse($matches[3]) : [];
                return $this->getTagInstance($matches)->parseDocument($parameters);
            });
            $subject = $escaper->backupDocument($subject);
        }
        preg_match($pattern, $subject->getText(), $match, PREG_OFFSET_CAPTURE);
        throw $subject->createException("User tag expansion exceeds maximum depth (possible circular expansion)", $match[0][1] ?? 0);
    }

    /**
     * Validates the syntax and standalone form of user-tag declarations
     *
     * @param SourceDocument $subject Document whose user declarations are being checked
     * @return void
     * @throws ViewException If a declaration is malformed or supplies a closing user tag
     */
    private function validateDeclarations(SourceDocument $subject): void
    {
        preg_match_all('/<[^>]*:[^>]*>/', $subject->getText(), $matches, PREG_OFFSET_CAPTURE);
        foreach ($matches[0] as [$tag, $offset]) {
            if (str_starts_with($tag, "<!--") || str_starts_with($tag, "<:") || str_starts_with($tag, "</:")) {
                continue;
            }
            if (!preg_match('/^<[\w\-]+:[\w\-]+\s*[^>]*>$/', $tag)) {
                throw $subject->createException("Invalid user tag declaration: ".$tag, $offset);
            }
            if (str_starts_with($tag, "</")) {
                throw $subject->createException("User tag must not have an end tag: ".$tag, $offset);
            }
        }
    }

    /**
     * Gets the resolved user-tag template and records it as a compilation dependency
     *
     * @param array<int,string> $matches Captures containing library and tag names at indexes one and two
     * @return UserTag Instance representing the resolved user-tag file
     * @throws ViewException If the tag file is absent or escapes its allowed root
     */
    private function getTagInstance(array $matches): UserTag
    {
        $libraryName = $matches[1];
        $tagName = $matches[2];
        $tagFolder = $this->namespaceTag->getTagLibraryPath($libraryName);
        $fileLocation = $this->pathResolver->resolveUserTag($tagFolder, $libraryName, $tagName, $this->tagExtension);
        $this->viewCompilation->addComponent($fileLocation);
        return new UserTag($fileLocation);
    }
}
