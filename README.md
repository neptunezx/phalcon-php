# phalcon-php
[![Build Status](https://travis-ci.org/scento/phalcon-php.svg?branch=master)](http://travis-ci.org/scento/phalcon-php)
[![Coverage Status](https://img.shields.io/coveralls/scento/phalcon-php.svg)](https://coveralls.io/r/scento/phalcon-php)
phalcon-php is a free replacement for the [Phalcon Web Framework](https://github.com/phalcon/cphalcon), 
delivered as a set of PHP classes based on the [Phalcon Devtools](https://github.com/phalcon/phalcon-devtools). 
This project allows the usage of the Phalcon APsI in environments without the possibility to set up the phalcon extension (e.g. shared hosting) by providing a compatibility layer.

## Note
1. This project is extended from project scento/phalcon-php(https://github.com/scento/phalcon.git) which is not maintained yet. 
2. The goal of this project is to complete the PHP replacement of cphalcon 3.2.*, but remove the view component. 
3. The project is more suitable for web API development
3. The codebase is still under development. A large number of PHPUnit tests fails now.

## Todo tasks
  * PHQL Parser (Scanner + Tokenizer)
  * Unit Tests
  * Documentation (Wiki)
  * Swoole and cluster websocket communication support
  * Consul support

## Development
The project is more suitable for web API development and extended from project scento/phalcon-php(https://github.com/scento/phalcon.git).

## Documentation
It is possible to generate a low-level PHPDoc-based documentation of the entire code by using [PHP_UML](https://pear.php.net/manual/en/package.php.php-uml.command-line.php) or [phpDocumentator](http://www.phpdoc.org/). The repository contains a shell script, which generates the corresponding documentation if one or both of the tools are available.

## Branching
Although the cphalcon project uses branches to manage releases, this project uses tags.
After the first release the development version can be found in the `dev` branch and the stable 
version is `master`. We suggest the usage of feature branches if you want to submit something.

## License
To keep the compatibility with the framework, this legacy layer is licensed under the terms of the New BSD license.

## Usage
You can use this project as a fallback for the original framework. Add this to your composer.json:

```
	"require": {
		"neptunezx/phalcon-php": "dev-master"
	}
```
