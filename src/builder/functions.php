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
        '-s',
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
 * @param string $standard Path to standard
 */
function create_phar($app_name, $src_dir, $filename, $standard = null)
{
    $dir_name = dirname($filename);
    if (!file_exists($dir_name)) {
        mkdir($dir_name, 0755, $src_dir);
    }

    if (file_exists($filename)) {
        unlink($filename);
    }

    $phar = new Phar($filename);
    $phar->buildFromDirectory($src_dir);

    $stub = get_stub($app_name);
    $phar->setStub($stub);

    if ($standard) {
        if (is_custom_standard($standard)) {
            $path = copy_standard($phar, $standard);
            $value = '\' . __DIR__ . \'/' . $path;
        } else {
            $value = $standard;
        }

        $config = get_config($value);
        $phar->addFromString('config.php', $config);
    }

    chmod($filename, 0755);
}

/**
 * Determines whether the given standars is custom
 *
 * @param string $standard Standard name or path
 *
 * @return bool
 */
function is_custom_standard($standard)
{
    $rule_set = $standard . '/ruleset.xml';

    return file_exists($rule_set);
}

/**
 * Copies custom standard into archive
 *
 * @param Phar   $phar     Archive
 * @param string $standard Path to source standard directory
 *
 * @return string          Path to standard directory in archive
 */
function copy_standard(Phar $phar, $standard)
{
    $base_name = basename($standard);
    $path = 'standards/' . $base_name;

    $iterator = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($standard)
    );

    /** @var \SplFileInfo $fileInfo */
    foreach ($iterator as $fileInfo) {
        if ($fileInfo->isFile()) {
            $localName = $path . '/' . $iterator->getSubPathName();
            $phar->addFile((string) $fileInfo, $localName);
        }
    }

    return $path;
}

/**
 * Returns the contents for archive stub
 *
 * @param string $app_name Application name
 *
 * @return string          Stub contents
 */
function get_stub($app_name)
{
    return <<<STUB
#!/usr/bin/env php
<?php

Phar::mapPhar('me.phar');
\$exit_code = require 'phar://me.phar/src/$app_name.php';
exit(\$exit_code);
__HALT_COMPILER();
STUB;
}

/**
 * Returns the contents of application config
 *
 * @param string $standard The value of --standard option of PHP_CodeSniffer
 *
 * @return string          Config contents
 */
function get_config($standard)
{
    return <<<CFG
<?php

return array(
    '--standard=$standard',
);
CFG;
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
    $standard = null;

    while (($arg = array_shift($args))
        && ($output === null || $standard === null)) {
        if (strpos($arg, '-s') === 0) {
            $standard = array_shift($args);
        } else {
            $output = $arg;
        }
    }

    if ($output == null) {
        $output = $app_name . '.phar';
    }

    return compact('app_name', 'src_dir', 'output', 'standard');
}
