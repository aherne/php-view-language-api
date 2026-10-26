# Templating example

[API overview and inputs/outputs](../README.md) · [Language guide](language.md)

Assuming *configuration.xml* (see **[configuration](../README.md#configuration)** and **[installation](../README.md#installation)**) is: 
 
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

Now let's compile *application/views/index.html* template (see **[compilation](../README.md#compilation)**) and bind it to *$data*:

```php
require(__DIR__."/vendor/autoload.php");
$wrapper = new Lucinda\Templating\Wrapper(simplexml_load_file("configuration.xml"));
$html = $wrapper->compile("index", $data);
```

First, ViewLanguage base template is compiled into PHP, results saved in a **compilations/index.php** file (in case it doesn't exist already). Simplified generated PHP, with internal PHP whitespace abbreviated, looks like this:

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
