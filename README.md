IniParser
=========

IniParser is a library that allows user to parse the ini file and outputs result into a meaningful array syntax. IniFetcher
is a wrapper around IniParser which does two thing; it invokes IniParser and parses the ini file, and it provides a getter
method to access ini property easily.

Example Usage
=============
There are two ways you can access ini file values using this library.

Using IniParser class
---------------------
```php
$parser = new IniParser($fileJson);
$parser->setFormat($parser::OUTPUT_FORMAT_ARRAY);
$data = $parser->parse();
echo "Using IniParser: " . $data['json']['list']['creditcards']['amex']['prefix'] . '<br/>';
```

Using IniFetcher(preferable)
----------------------------
```php
$fetcher = IniFetcher::getInstance($fileJson);
echo "Using IniFetcher: " . $fetcher::get('json.list.creditcards.amex.prefix');
```

Support
=======
IniParser supports various syntax in ini file. See below for supported syntax.

Inheritance
-----------
In your ini file you can have a section can inherit properties from other section. The syntax for that is `[childSection : parentSection]`.
See below the example for inheritance in ini file.

```ini
[parent]
key1 = parentValue1
key3 = parentValue3
[child : parent]
key1 = childValue1
key2 = childValue2
```

Each properties from the parent section will get extended into the child section. If the key name is similar, child section key
overrides parent section key. Please note that the parent section needs to declared before the child section because we can
not control the ordering of ini file section. Also multiple inheritance is not supported.

Global properties
-----------------
If the key value pair is defined without any section name, it will be considered in a global section. This is particularly useful
in a situation when you are not sure about where to put the entry.

Array literals
--------------


Comments
--------


Json string
-----------
