# Language guide

[API overview and inputs/outputs](../README.md) · [Tag reference](tags.md)

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

A **tag** is a ViewLanguage representation of *scripting logic*. Tags use XML-like names and, depending on their type, attributes and bodies. Their surrounding template text can target HTML, console markup or another format. There are two types of tags known by ViewLanguage:

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

- [escape](tags.md#tag-escape): tag whose body will be ignored by compiler. This is necessary to mark content inside as not subject to parsing.
- [import](tags.md#tag-import): tag whose declaration will be replaced by compiler with the body of file pointed by its "file" attribute. This is crucial for layouting/templating. 
- [namespace](tags.md#tag-namespace): tag whose declaration will inform compiler where to look for tag libraries not found in default folder. 

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
    - Standard-tag declarations support attributes across multiple lines.
    
#### Standard Tags
API includes a **standard library** containing tags for programming language instructions where *LIBRARY* is empty:

- [:for](tags.md#tag-for): iterates numerically through a list
- [:foreach](tags.md#tag-foreach): iterates through a dictionary by key and value
- [:if](tags.md#tag-if): evaluates body fitting condition
- [:elseif](tags.md#tag-elseif): evaluates body fitting condition that did not match previous if/elseif.
- [:else](tags.md#tag-else): evaluates body that did not match previous if/elseif.
- [:set](tags.md#tag-set): creates a variable and/or sets a value for it.
- [:unset](tags.md#tag-unset): unsets a variable.
- [:while](tags.md#tag-while): performs a loop on condition.
- [:break](tags.md#tag-break): ends loop.
- [:continue](tags.md#tag-continue): skips evaluating the rest of current loop and moves to next iteration.

Standard tags work with *ATTRIBUTE* values of following types:
- *scalars*: strings or integers
- *EXPRESSION*: ViewLanguage expressions. If helper functions are used as attribute values, they must be left undecorated: *count(${asd})* instead of *${count(${asd})}*.
- *CONDITION*: syntax is expected to be C-like, but ultimately matches that of language code is compiled into (in our case, PHP). Example: *${x}==true* means in PHP *$x==true*.

#### User Defined Tags

To break template output into reusable units, developers must create their own libraries & tags. User defined tags are found on disk according to these rules:

- library name must be a folder inside tags folder supplied on compilation.
- tag code must be a template file inside library folder whose name equals *name*.
- user-defined tags are expansion tags: they do not have closing tags or bodies.
- template, namespace and tag paths are contained inside their configured root folders. Absolute paths, empty path segments and `..` traversal are rejected.

| ViewLanguage Example | PHP Translation |
| --- | --- |
| <foo:baz attr="1"/> | $contents = file_get_contents($tagsFolder."/foo/baz.html"); <br/>// replaces all occurrences of $[attr] with 1 |
