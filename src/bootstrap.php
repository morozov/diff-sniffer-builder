<?php

/**
 * DiffSniffer Builder bootstrap file
 *
 * PHP version 5
 *
 * @category  DiffSniffer\Builder
 * @package   DiffSniffer\Builder
 * @author    Sergei Morozov <morozov@tut.by>
 * @copyright 2013 Sergei Morozov
 * @license   http://mit-license.org/ MIT Licence
 * @link      http://github.com/morozov/diff-sniffer-builder
 */
error_reporting(-1);

set_error_handler(
    function ($errNo, $errStr, $errFile, $errLine) {
        throw new ErrorException($errStr, $errNo, 0, $errFile, $errLine);
    }
);

set_exception_handler(
    function (Exception $e) {
        fwrite(STDERR, $e->getMessage() . PHP_EOL);
        exit(-1);
    }
);
