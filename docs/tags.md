# Tag reference

[API overview and inputs/outputs](../README.md) · [Language guide](language.md)

- [escape](#tag-escape)
- [import](#tag-import)
- [namespace](#tag-namespace)
- [:for](#tag-for)
- [:foreach](#tag-foreach)
- [:if](#tag-if)
- [:elseif](#tag-elseif)
- [:else](#tag-else)
- [:set](#tag-set)
- [:unset](#tag-unset)
- [:while](#tag-while)
- [:break](#tag-break)
- [:continue](#tag-continue)

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
