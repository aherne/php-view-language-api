# View Language API

Table of contents:

- [About](#about)
    - [Expressions](#expressions)
    - [Tags](#tags)
- [Configuration](#configuration)
- [Compilation](#compilation)
- [Installation](#installation)
- [Unit Tests](#unit-tests)
- [Examples](#examples)
- [Reference Guide](#reference-guide)

## About 

This API is the PHP compiler for the ViewLanguage templating engine, a markup language inspired by JSP/JSTL @ Java that acts like an extension of HTML standard, designed to remove scripting from views by:

- interfacing variables through **[expressions](#expressions)**.
- interfacing logic (control structures, repeating HTML segments) through **[tags](#tags)**

![diagram](https://www.lucinda-framework.com/view-language-api.svg)

In order to achieve its goals, following steps need to be observed:

- **[configuration](#configuration)**: setting up an XML file where templating is configured
- **[compilation](#compilation)**: using [Lucinda\Templating\Wrapper](https://github.com/aherne/php-view-language-api/blob/master/src/Wrapper.php) to read above XML and compile a template

API is fully PSR-4 compliant, requiring only PHP 8.1+ and the SimpleXML extension. To quickly see how it works, check:

- **[installation](#installation)**: describes how to install API on your computer, in light of steps above
- **[unit tests](#unit-tests)**: API has 100% Unit Test coverage, using [UnitTest API](https://github.com/aherne/unit-testing) instead of PHPUnit for greater flexibility
- **[examples](#examples)**: shows an example how to template with ViewLanguage, including explanations for each step
- **[reference guide](#reference-guide)**: shows list of tags that come with API

## Expressions

An **expression** is a ViewLanguage representation of a *scripting variable*. Syntax for an expression is:

```html
${variableName}
```
where **variableName** can be:

| Description | ViewLanguage Example | PHP Translation |
| --- | --- | --- |
| a scalar variable | ${foo} | $foo |
| an array variable, where hierarchy is represented by dots | ${foo.bar} | $foo["bar"] |
| a back-end helper function (native or user-defined) | ${htmlspecialchars(${foo.bar})} | htmlspecialchars($foo["bar"]) |
| a short if using ternary operators | ${(${foo.bar}!=3?"Y":"N")} | ($foo["bar"]!=3?"Y":"N") |

A very powerful feature is the **ability to nest expressions**: writing expressions whose key(s) are expressions themselves. This can go at any depth and it is very useful when iterating through more than one list and linking a one to another's key/value association:

| ViewLanguage Example | PHP Translation |
| --- | --- |
| ${foo.bar.${baz}} | $foo["bar"][$baz] |

Expressions are rendered as raw PHP output by default. If templates render user-controlled HTML, either escape values explicitly in templates or enable `escape_expressions="true"` in configuration, which wraps expression output in `htmlspecialchars`.

## Tags

A **tag** is a ViewLanguage representation of *scripting logic*. All tags act like an extension of HTML standard and as such they have names and, depending on tag type, attributes and bodies. There are two types of tags known by ViewLanguage:

- [macro tags](#macro-tags): api-defined tags to be processed before content is compiled.
- [library tags](#library-tags): api/user-defined tags subject to compilation that belong to a library and have a unique name within that library.

A very powerful feature is the **ability of tags to be recursive**: it is allowed to put View Language tags inside View Language tags. Whenever that happens, compiler goes progressively deeper until no tags are left!

### Macro Tags

**Macro tags** work in a way similar to C macros: before code is compiled, they are read and "expanded" so that compilation will run on a full source. Syntax is identical to that of normal HTML tags:

```html
<NAME ATTRIBUTE="value" .../>
```
Where:

- **NAME**: name of tag that performs a single logical operation.
- **ATTRIBUTE**: configures tag behavior

API defines following macro tags:

- [escape](#tag-escape): tag whose body will be ignored by compiler. This is necessary to mark content inside as not subject to parsing.
- [import](#tag-import): tag whose declaration will be replaced by compiler with the body of file pointed by its "file" attribute. This is crucial for layouting/templating. 
- [namespace](#tag-namespace): tag whose declaration will inform compiler where to look for tag libraries not found in default folder. 

At the moment, it is not possible for users to define their own macro tags!

### Library Tags

**Library tags** are compilable api/user-defined snippets expected to implement scripting logic in a ViewLanguage application. They are non-static repeating snippets of template code that depend on variables and thus cannot be loaded using a plain HTML include.
 
Standard tags may have bodies:
```html
<:TAG ATTRIBUTE="value" ...>...</:TAG>
```

Standard tags without bodies and user-defined tags are declared as single tags:
```html
<LIBRARY:TAG ATTRIBUTE="value" .../>
```

Where:
- *LIBRARY*: namespace that contains related logical operations to perform on a template. Rules:
    - Value must be alphanumeric or underscore.
    - "-" sign is allowed as well to replace spaces in multi-word values.
- *TAG*: name of tag that performs a single logical operation. Rules:
    - Value must be alphanumeric or underscore.
    - "-" sign is allowed as well to replace spaces in multi-word values.
- *ATTRIBUTE*: configures tag behavior (can be zero or more). Rules:
    - Name must be alphanumeric, underscore, dash or dot.
    - Value can only be primitive (string or number) or ViewLanguage expressions.
    - Value must be double-quoted. Empty values are allowed.
    - Unlike standard HTML, attributes cannot be multilined currently.
    
#### Standard Tags
API includes a **standard library** containing tags for programming language instructions where *LIBRARY* is empty:

- [:for](#tag-for): iterates numerically through a list
- [:foreach](#tag-foreach): iterates through a dictionary by key and value
- [:if](#tag-if): evaluates body fitting condition
- [:elseif](#tag-elseif): evaluates body fitting condition that did not match previous if/elseif.
- [:else](#tag-else): evaluates body that did not match previous if/elseif.
- [:set](#tag-set): creates a variable and/or sets a value for it.
- [:unset](#tag-unset): unsets a variable.
- [:while](#tag-while): performs a loop on condition.
- [:break](#tag-break): ends loop.
- [:continue](#tag-continue): skips evaluating the rest of current loop and moves to next iteration.

Standard tags work with *ATTRIBUTE* values of following types:
- *scalars*: strings or integers
- *EXPRESSION*: ViewLanguage expressions. If helper functions are used as attribute values, they must be left undecorated: *count(${asd})* instead of *${count(${asd})}*.
- *CONDITION*: syntax is expected to be C-like, but ultimately matches that of language code is compiled into (in our case, PHP). Example: *${x}==true* means in PHP *$x==true*.

#### User Defined Tags

In order to break up HTML response into discrete units, developers must create their own libraries & tags. User defined tags are found on disk according to these rules:

- library name must be a folder inside tags folder supplied on compilation.
- tag code must be a template file inside library folder whose name equals *name*.
- user-defined tags are expansion tags: they do not have closing tags or bodies.
- template, namespace and tag paths are contained inside their configured root folders. Absolute paths, empty path segments and `..` traversal are rejected.

| ViewLanguage Example | PHP Translation |
| --- | --- |
| <foo:baz attr="1"/> | $contents = file_get_contents($tagsFolder."/foo/baz.html"); <br/>// replaces all occurrences of $[attr] with 1 |

## Configuration

To configure this API you must have an XML with a **templating** tag inside:

```xml
<templating
    compilations_path="..."
    tags_path="..."
    templates_path="..."
    templates_extension="..."
    debug="..."
    escape_expressions="..." />
```

Where:

- **compilations_path**: (mandatory) path into which PHP compilations of ViewLanguage templates are saved 
- **tags_path**: (optional) path into which ViewLanguage tag libraries are located
- **templates_path**: (optional) path into which templates are located
- **templates_extension**: (optional) template files extension. If not set, "html" is assumed!
- **debug**: (optional) whether VL source comments should be kept in rendered HTML. If not set, "false" is assumed!
- **escape_expressions**: (optional) whether expression output should be HTML-escaped using `htmlspecialchars`. If not set, "false" is assumed!

Template imports, namespace folders and user tag files are resolved inside their configured roots. The compiler rejects absolute paths, empty path segments and `..` traversal.

Example:

```xml
<templating
    compilations_path="compilations"
    tags_path="application/taglib"
    templates_path="application/views"
    templates_extension="html"
    debug="false"
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
| compile | string $template, array $data | string | Compiles ViewLanguage template into HTML and returns result. Throws `ViewException` when compilation or rendering fails. |
 
 
### How are templates compiled?
 
As in any other templating language, compilation first traverses the tree of dependencies ever deeper and assembles result into a PHP file then produces an HTML by binding it to data received by user. It thus involves following steps:

- if a PHP compilation for *$template* argument exists, checks if elements referenced inside have changed since it was last updated. If it doesn't exist or it changed:
    - parses **[<import>](#tag-import)** tags recursively (backing-up **[<escape>](#tag-escape)** tag bodies in compilation file to be excluded from parsing) and appends results to compilation file
    - parses **[<namespace>](#tag-namespace)** tags defined in templates, to know where to locate user-defined tag libraries not defined in default taglib folder
    - parses **[library tags](#library-tags)** recursively (backing-up **[<escape>](#tag-escape)** tag bodies in compilation file to be excluded from parsing) and replaces them with relevant PHP/HTML code in compilation file.
    - parses **[expressions](#expressions)** and replaces them with relevant PHP code in compilation file.
    - restores backed up **[<escape>](#tag-escape)** tags bodies (if any) in compilation file
    - caches new compilation on disk along with a checksum of its parts (templates, tags) for future validations
- in output buffer, loads compilation file, binds it to *$data* supplied by user and produces a final HTML out of it 
     
Since the whole process is somewhat performance hungry, PHP compilation files will be cached on disk and returned directly on next requests unless one of its components (template or tag) has changed. This makes API able to compile in around 0.001 sec amortised time, thus bringing no performance taxation whatsoever but all the advantages of an elegant view!

### Debugging

Compiled files are wrapped with source comments:

```html
<!-- VL:START: path/to/template.html -->
...
<!-- VL:END: path/to/template.html -->
```

These comments allow runtime errors in compiled PHP files to be mapped back to the template or user tag that generated them. Rendered HTML strips these comments by default. Set `debug="true"` to keep them in the rendered output while debugging.

When a runtime warning or error happens while rendering a compiled view, it is wrapped in a `ViewException`. If the error points to the compiled file, the exception message includes the original source template/tag and line when possible.

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

Assuming *configuration.xml* (see **[configuration](#configuration)** and **[installation](#installation)**) is: 
 
 ```xml
<xml>
	<templating compilations_path="compilations" tags_path="application/taglib" templates_path="application/views" templates_extension="html"/>
</xml>
```

Let's create a *application/views/index.html* template with following body:

```html
<import file="header"/>
Hello, dear ${data.author}! Author of:
<ul>
    <:foreach var="${data.apis}" key="name" val="url">
    <my:api id="${name}" link="${url}"/>
    </:foreach>
</ul>
<import file="footer"/>
```
 
What above does is:

- loads body of a *application/views/header.html* template
- echoes value of *author* array key of $data via **[expressions](https://www.lucinda-framework.com/view-language/expressions)**
- loops through value of *apis* array key of $data via **[<:foreach>](https://www.lucinda-framework.com/view-language/standard-tags#:foreach)**
- on every iteration, loads body of *application/taglib/my/api.html* template (user tag), binding attributes to values
- loads body of a *application/views/footer.html* template

Contents of *application/views/header.html* file:

```html
<html>
    <head>
        <title>View Language API Tutorial</title>
    </head>
    <body>
```

Contents of *application/taglib/my/api.html* file:

```html
<li><a href="$[id]">$[link]</a></li>
```

Contents of *application/views/footer.html*:

```html    
    </body>
</html/>
```

As one can see above, templates depend on variables to be received from back-end (*author* & *apis*), both keys of **$data** array. Assuming value of latter is:

```php
$data = [
	"author" => "Lucian Popescu", 
	"apis" => ["View Language API" => "https://www.lucinda-framework.com/view-language", "STDOUT MVC API" => "https://www.lucinda-framework.com/stdout-mvc"]
];
```

Now let's compile *application/views/index.html* template (see **[compilation](#compilation)**) and bind it to *$data*:

```php
require(__DIR__."/vendor/autoload.php");
$wrapper = new Lucinda\Templating\Wrapper(simplexml_load_file("configuration.xml"));
$html = $wrapper->compile("index", $data);
```

First, ViewLanguage base template is compiled into PHP, results saved in a **compilations/index.php** file (in case it doesn't exist already). Simplified output, omitting `VL:START`/`VL:END` source comments, looks like this:

```html
<html>
    <head>
        <title>View Language API Tutorial</title>
    </head>
    <body>
        Hello, <?php echo $data["author"]; ?>, author of:
        <ul>
            <?php foreach($data["apis"] as $name=>$url) { ?>
            <li><a href="<?php echo $url; ?>"><?php echo $name; ?></a></li>
            <?php } ?>
        </ul>
    </body>
</html/>
```

Then above file is loaded in output buffer and bound with $data, so the final HTML returned will be:

```html
<html>
    <head>
        <title>View Language API Tutorial</title>
    </head>
    <body>
        Hello, Lucian Popescu, author of:
        <ul>
            <li><a href="https://www.lucinda-framework.com/view-language">View Language API</a></li>
            <li><a href="https://www.lucinda-framework.com/stdout-mvc">STDOUT MVC API</a></li>
        </ul>
    </body>
</html/>
```

## Reference Guide

### tag escape

Marks tag body to be ignored by ViewLanguage compiler.Syntax:

```html
<escape>
...
</escape>
```

Examples how this tag is compiled into PHP:

| ViewLanguage Example | PHP Translation |
| --- | --- |
| &lt;escape&gt;<br/>${foo.bar}<br/>&lt;/escape&gt; | ${foo.bar} |

### tag import

Includes another view language template into current one. Syntax:

```html
<import file="..."/>
```

Attributes:

| Name | Mandatory | Data Type | Description |
| --- | --- | --- | --- |
| file | Y | string | Location of file whose sources should replace tag declaration relative to views folder supplied to compiler |


Examples how this tag is compiled into PHP:

| ViewLanguage Example | PHP Translation |
| --- | --- |
| &lt;import file="header"/&gt; | contents of `$viewsFolder."/header.html"` are inlined into the compilation |

### tag namespace

Marks custom location of user defined tag library (must be placed BEFORE latter declaration). Syntax:

```html
<namespace taglib="..." folder="..."/>
```

Attributes:

| Name | Mandatory | Data Type | Description |
| --- | --- | --- | --- |
| taglib | Y | string | Name of tag library to look for. |
| folder | Y | string | Location of folder tag library should be looked for relative to tags folder supplied to compiler |

Examples how this tag is compiled into PHP:

| ViewLanguage Example | PHP Translation |
| --- | --- |
| &lt;namespace taglib="foo" folder="bar"/&gt;<br/>...<br/>&lt;foo:baz attr="1"/&gt; | ...<br/>$contents = file_get_contents($tagsFolder."/bar/foo/baz.html"); <br/>// replaces all instances of $[attr] with 1 |


### tag :for

Creates a FOR loop. Syntax:

```html
<:for var="..." start="..." end="..." (step="...")>
    ...
</:for>
```

Attributes:

| Name | Mandatory | Data Type | Description |
| --- | --- | --- | --- |
| var | Y | string | Name of counter variable. |
| start | Y | integer | Value of begin counter. |
| end | Y | integer | Value of end counter. |
| step | N | integer | Value of increment/decrement step (default: 1). |

Examples how this tag is compiled into PHP:

| ViewLanguage Example | PHP Translation |
| --- | --- |
| &lt;:for var="i" start="0" end="10"&gt;<br/>...<br/>&lt;/:for&gt; | for($i=0; $i<=10; $i+=1){<br/>...<br/>} |
| &lt;:for var="i" start="10" end="0" step="-1"&gt;<br/>...<br/>&lt;/:for&gt; | for($i=10; $i>=0; $i-=1){<br/>...<br/>} |

### tag :foreach

Creates a FOR EACH loop. Syntax:

```html
<:foreach var="..." (key="...") val="...">
    ...
</:foreach>
```

Attributes:

| Name | Mandatory | Data Type | Description |
| --- | --- | --- | --- |
| var | Y | EXPRESSION | Variable to iterate. |
| key | N | string | Name of key variable. |
| val | Y | string | Name of value variable. |

Examples how this tag is compiled into PHP:

| ViewLanguage Example | PHP Translation |
| --- | --- |
| &lt;:foreach var="${a}" key="k" val="v"&gt;<br/>...<br/>&lt;/:foreach&gt; | foreach($a as $k=>$v) {<br/>...<br/>} |
| &lt;:foreach var="${a}" val="v"&gt;<br/>...<br/>&lt;/:foreach&gt; | foreach($a as $v) {<br/>...<br/>} |


### tag :if

Creates an IF condition. Syntax:

```html
<:if test="...">
    ...
</:if>
```

If followed by [:elseif](#tag-elseif) or [:else](#tag-else), the `:if` block must be closed only once with `</:if>` after the final branch.

Attributes:

| Name | Mandatory | Data Type | Description |
| --- | --- | --- | --- |
| test | Y | CONDITION | Condition when body is executed. |

Examples how this tag is compiled into PHP:

| ViewLanguage Example | PHP Translation |
| --- | --- |
| &lt;:if test="${x}==2"&gt;<br/>...<br/>&lt;/:if&gt; | if($x==2) {<br/>...<br/>} |

<i>You can also run simple IF/ELSE statements from expressions using ternary operators!</i>

### tag :elseif

Creates an ELSE IF condition. Syntax:

```html
<:elseif test="...">
    ...
</:if>
```

The `:elseif` tag must appear inside an open `:if` block and is closed by the final `</:if>` tag.

Attributes:

| Name | Mandatory | Data Type | Description |
| --- | --- | --- | --- |
| test | Y | CONDITION | Condition when body is executed. |

Examples how this tag is compiled into PHP:

| ViewLanguage Example | PHP Translation |
| --- | --- |
| &lt;:elseif test="${x}==2"&gt;<br/>...<br/>&lt;/:if&gt; | } elseif($x==2) {<br/>...<br/>} |

### tag :else

Creates an ELSE condition. Syntax:

```html
<:else>
    ...
</:if>
```

Examples how this tag is compiled into PHP:

| ViewLanguage Example | PHP Translation |
| --- | --- |
| &lt;:else&gt;<br/>...<br/>&lt;/:if&gt; | } else {<br/>...<br/>} |

### tag :set

Sets a value to a variable. Syntax:

```html
<:set var="..." (val="...")/>
```

Attributes:

| Name | Mandatory | Data Type | Description |
| --- | --- | --- | --- |
| var | Y | string | Name of variable to be created/updated. |
| val | N | string<br/>EXPRESSION | Value of variable. If missing, variable is set to `null`. |

Examples how this tag is compiled into PHP:

| ViewLanguage Example | PHP Translation |
| --- | --- |
| &lt;:set var="a" val="10"/&gt; | $a = '10'; |
| &lt;:set var="a" val="${x}"/&gt; | $a = $x; |

### tag :unset

Removes variable from memory. Syntax:

```html
<:unset var="..."/>
```

Attributes:

| Name | Mandatory | Data Type | Description |
| --- | --- | --- | --- |
| var | Y | string | Name of variable to be unset. |


Examples how this tag is compiled into PHP:

| ViewLanguage Example | PHP Translation |
| --- | --- |
| &lt;:unset var="a"/&gt; | unset($a); |

### tag :while

Creates a WHILE loop. Syntax:

```html
<:while test="...">
    ...
</:while>
```

Attributes:

| Name | Mandatory | Data Type | Description |
| --- | --- | --- | --- |
| test | Y | CONDITION | Condition when body is executed. |

Examples how this tag is compiled into PHP:

| ViewLanguage Example | PHP Translation |
| --- | --- |
| &lt;:while test="${x}!=2"&gt;<br/>...<br/>&lt;/:while&gt; | while($x!=2) {<br/>...<br/>} |

### tag :break

Breaks a FOR/FOR EACH/WHILE statement loop. Syntax:

```html
<:break/>
```

Examples how this tag is compiled into PHP:

| ViewLanguage Example | PHP Translation |
| --- | --- |
| &lt;:break/&gt; | break; |

### tag :continue

Continues to next step within a FOR/FOR EACH/WHILE statement loop. Syntax:

```html
<:continue/>
```

Examples how this tag is compiled into PHP:

| ViewLanguage Example | PHP Translation |
| --- | --- |
| &lt;:continue/&gt; | continue; |
