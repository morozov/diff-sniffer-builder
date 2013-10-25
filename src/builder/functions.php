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
 * @param string $config   Path to config file
 */
function create_phar($app_name, $src_dir, $filename, $config = null)
{
    $stub = <<<STUB
#!/usr/bin/env php
<?php

Phar::mapPhar('me.phar');
\$exit_code = require 'phar://me.phar/src/{$app_name}.php';
exit(\$exit_code);
__HALT_COMPILER();
STUB;

    $dir_name = dirname($filename);
    if (!file_exists($dir_name)) {
        mkdir($dir_name, 0755, $src_dir);
    }

    $phar = new Phar($filename);
    $phar->buildFromDirectory($src_dir);
    $phar->setStub($stub);

    if ($config) {
        $phar->addFile($config, 'config.php');
    }

    chmod($filename, 0755);
}

/**
 * Parses command line arguments
 *
 * @param array $args Command line arguments
 *
 * @return array Application parameters
 */
function parse_args(array $args)
{
    array_shift($args);
    $app_name = array_shift($args);
    $src_dir = array_shift($args);
    $output = null;
    $config = null;

    while (($arg = array_shift($args)) && ($output === null || $config === null)) {
        if (strpos($arg, '-c') === 0) {
            $config = array_shift($args);
        } else {
            $output = $arg;
        }
    }

    if ($output == null) {
        $output = $app_name . '.phar';
    }

    return compact('app_name', 'src_dir', 'output', 'config');
}
