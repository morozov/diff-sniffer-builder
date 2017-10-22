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
 * @throws RuntimeException
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
 * @param array $config Default package configuration
 */
function create_phar($app_name, $src_dir, $filename, array $config = array())
{
    $dir_name = dirname($filename);
    if (!file_exists($dir_name)) {
        mkdir($dir_name, 0755, $src_dir);
    }

    if (file_exists($filename)) {
        unlink($filename);
    }

    $rdi = new \RecursiveDirectoryIterator(
        $src_dir,
        \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS
    );

    $filter = new BlackListFilter($rdi, array(
        '.gitignore',
        '.git',
        '.idea',
    ));

    $rii = new \RecursiveIteratorIterator($filter);

    $phar = new Phar($filename);
    $phar->buildFromIterator($rii, $src_dir);

    $stub = get_stub($src_dir, $app_name);
    $phar->setStub($stub);

    if (isset($config['default_standard'])) {
        if (is_external_standard($config['default_standard'])) {
            $path = copy_standard($phar, $config['default_standard']);
            $config['default_standard'] = 'phar://' . $app_name . '.phar/' . $path;
        }
    }

    if ($config) {
        $code = get_config_contents($config);
        $phar->addFromString('vendor/squizlabs/php_codesniffer/CodeSniffer.conf', $code);
    }

    chmod($filename, 0755);
}

/**
 * Determines whether the given standard is external
 *
 * @param string $standard Standard name or path
 *
 * @return bool
 */
function is_external_standard($standard)
{
    return strpbrk ($standard, '\\/') !== false;
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

    /** @var \RecursiveDirectoryIterator $iterator */
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
 * @param string $src_dir  Source directory
 * @param string $app_name Application name
 *
 * @return string          Stub contents
 */
function get_stub($src_dir, $app_name)
{
    $bin = $src_dir . '/bin/' .  $app_name;
    $contents = file_get_contents($bin);

    $contents = str_replace(<<<PHP
require __DIR__ . '/../include/bootstrap.php';
PHP
        ,
        <<<PHP
Phar::mapPhar('$app_name.phar');
require __DIR__ . '/../include/bootstrap.php';
PHP
        ,
        $contents
    );

    return str_replace("__DIR__ . '/../", "'phar://$app_name.phar/", $contents)
        . <<<PHP

__HALT_COMPILER();

PHP
    ;
}

/**
 * Returns the contents of application config
 *
 * @param string $config The value of --standard option of PHP_CodeSniffer
 *
 * @return string          Config contents
 */
function get_config_contents($config)
{
    $code = var_export($config, true);
    return <<<CFG
<?php

\$phpCodeSnifferConfig = $code;
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
