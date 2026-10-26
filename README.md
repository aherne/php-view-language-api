# View Language API

View Language is a PHP template compiler for HTML, console markup and other text. It combines template files with an application data array, translates expressions and tags into cached PHP, and returns the rendered string. Source maps keep template diagnostics separate from output.

Table of contents:

- [About](#about)
- [Inputs and outputs](#inputs-and-outputs)
- [Language guide](docs/language.md)
- [Configuration](#configuration)
- [Compilation](#compilation)
- [Diagnostics](#debugging)
- [Class responsibilities](#class-responsibilities)
- [Installation](#installation)
- [Unit tests](#unit-tests)
- [Examples](docs/examples.md)
- [Tag reference](docs/tags.md)

## About 

The ViewLanguage syntax is inspired by JSP/JSTL and uses XML-like tags and expressions to separate application data from presentation:

- interfacing variables through **[expressions](docs/language.md#expressions)**.
- interfacing logic (control structures and repeated template fragments) through **[tags](docs/language.md#tags)**

![diagram](https://www.lucinda-framework.com/view-language-api.svg)

In order to achieve its goals, following steps need to be observed:

- **[configuration](#configuration)**: setting up an XML file where templating is configured
- **[compilation](#compilation)**: using [Lucinda\Templating\Wrapper](https://github.com/aherne/php-view-language-api/blob/master/src/Wrapper.php) to read above XML and compile a template

API is fully PSR-4 compliant, requiring only PHP 8.1+ and the SimpleXML extension. To quickly see how it works, check:

- **[installation](#installation)**: describes how to install API on your computer, in light of steps above
- **[unit tests](#unit-tests)**: the API uses [UnitTest API](https://github.com/aherne/unit-testing) instead of PHPUnit for greater flexibility
- **[examples](docs/examples.md)**: shows an example how to template with ViewLanguage, including explanations for each step
- **[reference guide](docs/tags.md)**: shows list of tags that come with API

## Inputs and outputs

The public rendering entry point is `Lucinda\Templating\Wrapper`:

```php
$renderer = new Lucinda\Templating\Wrapper($xml);
$output = $renderer->compile("greeting", ["name" => "Lucian"]);
```

| Input | Contract |
| --- | --- |
| `$xml` | A `SimpleXMLElement` with a `templating` child; compilation storage and template extension are mandatory. |
| Template name | A path relative to the configured template root, without the extension. |
| Application data | An array exposed as `$data` inside the template; `${data.name}` accesses the example value. |
| Template dependencies | Import files, namespace declarations and user-tag files resolved within their configured roots. |

| Output | Contract |
| --- | --- |
| Rendered text | A string containing HTML, console markup or any other template text. The caller owns HTTP headers, ANSI conversion and display. |
| Compiled artifacts | Cached PHP, a dependency record and a `.map.json` source map under the configured compilation folder. |
| Configuration failure | `ConfigurationException` for missing required XML configuration. |
| Template failure | `ViewException` for expansion or rendering errors; `getSourceLocation()` exposes an available original file and line. |

Expression values are raw by default. Enable `escape_expressions` for HTML escaping when appropriate. Literal line endings after generated directives are preserved; diagnostics add no text to the output. See [diagnostics](#debugging) for raw PHP and source-map behavior.

## Configuration

To configure this API you must have an XML with a **templating** tag inside:

```xml
<templating
    compilations_path="..."
    tags_path="..."
    templates_path="..."
    templates_extension="..."
    escape_expressions="..." />
```

Where:

- **compilations_path**: (mandatory) path into which PHP compilations of ViewLanguage templates are saved 
- **tags_path**: (optional) path into which ViewLanguage tag libraries are located
- **templates_path**: (optional) path into which templates are located
- **templates_extension**: (mandatory) template file extension, without the dot.
- **escape_expressions**: (optional) whether expression output should be HTML-escaped using `htmlspecialchars`. If not set, "false" is assumed!

Template imports, namespace folders and user tag files are resolved inside their configured roots. The compiler rejects absolute paths, empty path segments and `..` traversal.

Example:

```xml
<templating
    compilations_path="compilations"
    tags_path="application/taglib"
    templates_path="application/views"
    templates_extension="html"
    escape_expressions="true"/>
```

## Compilation

Once you have completed step above, you need to instantiate [Lucinda\Templating\Wrapper](https://github.com/aherne/php-view-language-api/blob/master/src/Wrapper.php) in order to be able to compile templates later on:

```php
$wrapper = new Lucinda\Templating\Wrapper(simplexml_load_file(XML_FILE_NAME));
```

Object has following method that can be used to compile one or more templates:

| Method | Arguments | Returns | Description |
| --- | --- | --- | --- |
| compile | string $template, array $data | string | Renders the template as text and returns the result. Throws `ViewException` when compilation or rendering fails. |
 
 
### How are templates compiled?
 
As in any other templating language, compilation first traverses the tree of dependencies ever deeper and assembles result into a PHP file then renders text by binding it to application data. It thus involves following steps:

- if a PHP compilation for *$template* argument exists, checks if elements referenced inside have changed since it was last updated. If it doesn't exist or it changed:
    - parses **[<import>](docs/tags.md#tag-import)** tags recursively (backing-up **[<escape>](docs/tags.md#tag-escape)** tag bodies in compilation file to be excluded from parsing) and appends results to compilation file
    - parses **[<namespace>](docs/tags.md#tag-namespace)** tags defined in templates, to know where to locate user-defined tag libraries not defined in default taglib folder
    - parses **[library tags](docs/language.md#library-tags)** recursively (backing-up **[<escape>](docs/tags.md#tag-escape)** tag bodies in compilation file to be excluded from parsing) and replaces them with the corresponding PHP or template text in compilation file.
    - parses **[expressions](docs/language.md#expressions)** and replaces them with relevant PHP code in compilation file.
    - restores backed up **[<escape>](docs/tags.md#tag-escape)** tags bodies (if any) in compilation file
    - caches new compilation on disk along with a checksum of its parts (templates, tags) for future validations
- in output buffer, loads compilation file, binds it to *$data* supplied by user and produces the rendered text
     
PHP compilations are cached and reused while their dependencies, compiler settings and source maps remain valid. Expansion and translation run only when rebuilding is required.

### Debugging

The compiler preserves the original file and line while expanding imports, user tags, escaped sections, system tags and expressions. Template-language errors include the location of the failing construct. `ViewException::getSourceLocation()` returns that location as `['source' => $file, 'line' => $line]`, or `null` when unavailable.

Each compiled PHP file has a separate `.map.json` file containing its source map. `History\SourceMapBuilder` creates the correspondence from the final document; `History\SourceMapStorage` owns serialization and cache validation. `History\SourceMap` itself holds and exposes only source locations. PHP syntax errors and runtime warnings/errors are wrapped in a `ViewException` with the mapped template or tag location and the original throwable as its previous exception. For exceptions thrown by helper functions, the renderer also checks the stack for the compiled template's call site.

Literal PHP maps to its original lines. PHP generated from a directive maps to the directive's starting line. Separate PHP blocks are laid out on separate generated lines using whitespace inside PHP, so adjacent inline expressions can be distinguished without adding rendered whitespace.

Literal line endings after generated expressions and system tags are preserved; raw PHP closing tags retain PHP's native newline consumption. Whitespace on lines containing control tags remains part of the template.

No diagnostic comments are inserted into the output, and ordinary template comments and literal text are preserved. The former `debug` option has no effect; source diagnostics are always available.

Maps contain a format version, a compilation hash and the compiler configuration identity. Existing compilations without a valid matching map are rebuilt automatically. Deploy or clear the compiled PHP, map and dependency checksum together.

## Class responsibilities

| Class or group | Encapsulated concept or operation |
| --- | --- |
| `Wrapper` | Rendering a configured template with application data. |
| `ViewLanguageParser` | Compiling the dependency tree and preparing PHP layout. |
| `History\SourceDocument` | Immutable text with original source spans. |
| `History\SourceMap` | Immutable correspondence between compiled PHP lines and original source locations. |
| `History\SourceMapBuilder` | Building line correspondences from the final source-aware document. |
| `History\SourceMapStorage` | Persisting maps and validating their format, PHP hash and configuration. |
| `History\SourceContext` | Resolving a compiled error location through its map. |
| `ViewCompilation` | Cached PHP and its recorded template dependencies. |
| `File` / `PathResolver` | File access and resolution within configured roots. |
| `ExpressionParser` / `TagExpressionParser` | Translating output and attribute expressions. |
| `AttributesParser` | Parsing and validating named declaration attributes. |
| `ImportTag` / `EscapeTag` / `NamespaceTag` | Import expansion, protected literal bodies and library-root policy. |
| `UserTag` / `UserTagParser` | A user-tag template and its recursive expansion. |
| `SystemTag` / `SystemTagParser` | Shared standard-tag operations and structured translation. |
| `TagLib\Std` classes | One PHP control-flow or variable operation per tag. |
| `StartTag` / `StartEndTag` | Contracts for opening and closing tag translation. |
| `ConfigurationException` / `ViewException` | Configuration and source-aware template failures. |

Concrete implementation classes are final unless they have an explicit extension role. `ExpressionParser` supports `TagExpressionParser`, `Wrapper` retains its protected `bind()` hook, and `SystemTag` is the abstract base for standard tag operations.

Path resolution, map construction, map storage and source-context lookup are instance operations. Their cooperating methods have no static call chain. Per-compilation state is held by instances.

## Installation

First choose a folder where API will be installed then write this command there using console:

```console
composer require lucinda/view-language
```

Then create a *configuration.xml* file holding configuration settings (see [configuration](#configuration) above) and a *index.php* file in project root with following code:

```php
require(__DIR__."/vendor/autoload.php");
$wrapper = new Lucinda\Templating\Wrapper(simplexml_load_file("configuration.xml"));
```

To compile a template:

```php
$html = $wrapper->compile(TEMPLATE_NAME, USER_DATA);
```

Where:

- TEMPLATE_NAME is the *base template* that must obey following rules:
    - must be a path to a file located in *templates_path* (see **[configuration](#configuration)**)
    - file it points to must have *templates_extension* (see **[configuration](#configuration)**)
    - because of above, must not include extension
- USER_DATA is a list of values from back-end to be accessed in template, obeying following rules:
	- must be an array
	- entry keys must be strings or integers (the usual PHP requirements)
	- entry values must be scalars or arrays
	- if array is multidimensional, keys and values in siblings must obey same rules as above

To display results:

```php
header("Content-Type: text/html; charset=UTF-8");
echo $html;
```

## Unit Tests

For tests and examples, check following files/folders in API sources:

- [test.php](https://github.com/aherne/php-view-language-api/blob/master/test.php): runs unit tests in console.
- [unit-tests.xml](https://github.com/aherne/php-view-language-api/blob/master/unit-tests.xml): configures source and test namespaces for the UnitTest runner.
- [tests](https://github.com/aherne/php-view-language-api/tree/v3.0.0/tests): unit tests for classes from [src](https://github.com/aherne/php-view-language-api/tree/v3.0.0/src) folder.
- [phpstan.neon](https://github.com/aherne/php-view-language-api/blob/master/phpstan.neon): configures static analysis for `src`.

Run unit tests:

```console
composer test
```

Run static analysis:

```console
composer analyse
```

## Examples

See the [complete templating example](docs/examples.md) for imports, iteration, user tags, application data and the rendered result.

## Reference Guide

See the [language guide](docs/language.md) for expressions and tag libraries, and the [tag reference](docs/tags.md) for the attributes and PHP translation of each built-in tag.
