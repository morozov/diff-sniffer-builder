Diff Sniffer PHAR Builder
============================

This tool allows building pre-configured PHAR packages with [diff-sniffer-pre-commit](https://github.com/morozov/diff-sniffer-pre-commit) and [diff-sniffer-pull-request](https://github.com/morozov/diff-sniffer-pull-request).

Installation
------------

Clone this repository:
```
$ git clone git@github.com:morozov/diff-sniffer-builder.git
```

Usage
-----

Clone and install [diff-sniffer-pre-commit](https://github.com/morozov/diff-sniffer-pre-commit) or [diff-sniffer-pull-request](https://github.com/morozov/diff-sniffer-pull-request):
```
$ git clone git@github.com:morozov/diff-sniffer-pre-commit.git
$ cd diff-sniffer-pre-commit
$ composer update
$ cd -
```

Run the builder:
```
$ diff-sniffer-builder/bin/build pre-commit diff-sniffer-pre-commit -s PSR2
```

Where `pre-commit` is the application name (will be suffixed by ".phar" and used as output file name), `diff-sniffer-pre-commit` is the source directory, `-s PSR2` is optional and will tell the built app to use PSR2 as the default standard.
