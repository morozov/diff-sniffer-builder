<?php

/**
 * DiffSniffer Builder functions
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
namespace builder;

use Phar;
use RuntimeException;

/**
 * Patches PHP_CodeSniffer source files
 *
 * @param string $src_dir   Source directory
 * @param string $diff_path Path to diff file
 * @param boolean $reverse  Reverse patch
 *
 * @throws \RuntimeException
 */
function patch($src_dir, $diff_path, $reverse)
{
    $cmd = array(
        'patch',
        '-p0',
        '-d',
        escapeshellarg($src_dir),
        '-i',
        escapeshellarg($diff_path),
    );
    
    if ($reverse) {
        $cmd[] = '-R';
    }

    $cmd = implode(' ', $cmd);
    passthru($cmd, $return_var);

    if ($return_var != 0) {
        throw new RuntimeException('Unable to patch the library');
    }
}

/**
 * Creates application phar archive
 *
 * @param string $app_name Application name
 * @param string $src_dir  Source directory
 * @param string $filename Output filename
 */
function create_phar($app_name, $src_dir, $filename)
{
    $stub = <<<STUB
#!/usr/bin/env php
<?php

Phar::mapPhar('me.phar');
require 'phar://me.phar/src/{$app_name}.php';
__HALT_COMPILER();
STUB;

    $dir_name = dirname($filename);
    if (!file_exists($dir_name)) {
        mkdir($dir_name, 0755, $src_dir);
    }

    $phar = new Phar($filename);
    $phar->buildFromDirectory($src_dir);
    $phar->setStub($stub);

    chmod($filename, 0755);
}
