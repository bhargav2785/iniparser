Build Status
------------
[![Build Status](https://secure.travis-ci.org/bhargav2785/iniparser.png)](https://travis-ci.org/bhargav2785/iniparser)


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
; For inheritance to work in ini files parent must be defined
; as a section before its child is defined. In other words parent
; needs to go on top and child needs to be at bottom.

; default set of properties
[common]
section = common
type = parent
site.url = http://example.com

; prod extends common and overrides same property found in common
[prod : common]
section = prod
type = child
site.url = http://prod.example.com

; test extends common and overrides same property found in common
[test : common]
section = test
type = child
site.url = http://test.example.com

; dev extends common and overrides same property found in common
[dev : common]
section = dev
type = child
site.url = http://dev.example.com

; 'bhargav' extends dev and overrides same property found in dev
[bhargav : dev]
section = bhargav

; lazydev is an alias of dev since it doesn't contain any self property
[lazydev : dev]
```

Each properties from the parent section will get extended into the child section. If the key name is similar, child section key
overrides parent section key. Please note that the parent section needs to declared before the child section because we can
not control the ordering of ini file section. Also multiple inheritance is not supported.

Global properties
-----------------
If the key value pair is defined without any section name, it will be considered in a global section. This is particularly useful
in a situation when you are not sure about where to put the entry.

```ini
system.section = global
system.includePath.public = "public/global"
system.includePath.components = "components/"
system.phpSettings.display_errors = 0
system.phpSettings.display_warning = 1
site.url = http://bhargavvadher.com/about
[section]
key1 = parentValue1
key3 = parentValue3
```

Array literals
--------------
IniParser supports array literals. That means you can pass in values in an array literal for a key. This is particularly
useful when you have multiple possible values for a given key e.g all admin users as in `system.admins = [user1,user2,user3]`.
If you have inheritance with array literals, the child class will have priority over the parent class as explained in the
example below.

```ini
; example of a array like literal's ini file

; some global just for fun
site.url.primary = 'http://bhargavvadher.com'
site.url.secondary = 'http://bhargav.me'

[array]
system.users = [user1,user2,user3]
system.section = [prod]
system.admins = ['user1',user2,1234,12.34]

[empty1]
[empty2 : empty1]
[empty3]
```

Please note that all empty section in an ini file will be ignored. In above example all `empty1`, `empty2` and `empty3`
will be ignored because both `empty1` and `empty3` are actually empty and `empty2` is extending `empty1` but again the
section after the inheritance is empty.

Comments
--------
Comments are not parsed but it is rather important part of an ini file. It helps documenting ini correctly for future
use. IniParser provides variety of comment styles as shown in below example. Note that none of those comment lines will
be parsed into a value.

```ini
; prod only properties
[prod]
system.section = prod

; test only properties
[test : prod]
system.section = test

; dev only properties
[dev : test]
system.section = dev

; # tab comment, php will ignore this
#   space comment
#        tab-space comment
###   multiple hash comment
#   comment starting and ending with hash #

############################################################################
################   @author Bhargav Vadher           ########################
################   @site http://bhargavvadher.com   ########################
############################################################################
```

All the lines that starts with `;` or `#` will be considered as a comment and will not be parsed. After the first pass
of parsing the ini would look something like below. Note that empty lines will be ignored as well.

```ini
[prod]
system.section = prod
[test : prod]
system.section = test
[dev : test]
system.section = dev
```

Json string
-----------
This is pretty useful when you have a json structure of values that you want to use in your config system. Use case will
be lets say you have a job that gets configuration fields from database on each deployment and then creates a json out
of it. Now you can use that json blob directly into the ini files and IniParser will break it down into multi dimensional
array like structure. Please see below example for more detail.

```ini
# global json property section
[json]
list = '{
	"colors" : [
		{
			"colorName" : "red",
			"hexValue" : "#f00"
		},
		{
			"colorName" : "green",
			"hexValue" : "#0f0"
		},
		{
			"colorName" : "blue",
			"hexValue" : "#00f"
		}
	],
	"creditcards" : {
		"amex" : {"name": "American Express","prefix": "34","length": 15},
		"bankcard" : {"name": "Bankcard","prefix": "5610","length": 16},
		"chinaunion" : {"name": "China UnionPay","prefix": "62","length": 16},
		"dccarte" : {"name": "Diners Club Carte Blanche","prefix": "300","length": 14},
		"dcenroute" : {"name": "Diners Club enRoute","prefix": "2014","length": 15},
		"dcintl" : {"name": "Diners Club International","prefix": "36","length": 14},
		"dcusc" : {"name": "Diners Club United States & Canada","prefix": "54","length": 16},
		"discover" : {"name": "Discover Card","prefix": "6011","length": 16},
		"instapay" : {"name": "Insta Payment","prefix": "637","length": 16},
		"jcb" : {"name": "JCB","prefix": "3528","length": 16},
		"laser" : {"name": "Laser","prefix": "6304","length": 16},
		"maestro" : {"name": "Maestro","prefix": "5018","length": 16},
		"mc" : {"name": "Mastercard","prefix": "51","length": 16},
		"solo" : {"name": "Solo","prefix": "6334","length": 16},
		"switch" : {"name": "Switch","prefix": "4903","length": 16},
		"visa" : {"name": "Visa","prefix": "4","length": 16},
		"electron" : {"name": "Visa Electron","prefix": "4026","length": 16}
	}
}'

# regular ini property section
[people]
name.first = Bhargav
name.last = Vadher
```
