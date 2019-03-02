<?php

/**
 * DiffSniffer Builder CLI endpoint
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

if ($_SERVER['argc'] < 3) {
    fwrite(
        STDERR,
        'Usage: ' . basename($_SERVER['argv'][0])
        . ' app-name src-dir <-s /path/to/standard> <output>' . PHP_EOL
    );
    exit(1);
}

require __DIR__ . '/../vendor/autoload.php';

$app_name = $src_dir = $output = $standard = $bin = null;
$params = builder\parse_args($_SERVER['argv']);
extract($params, EXTR_IF_EXISTS);

$config = array();
if ($standard) {
    $config['default_standard'] = $standard;
}

// use "git diff --no-prefix" in order to create proper diff
$diff_path = __DIR__ . '/../data/1254.diff';

$phpcs_src_dir = $src_dir . '/vendor/squizlabs/php_codesniffer';

if ($config) {
    builder\patch($phpcs_src_dir, $diff_path, false);

    register_shutdown_function(function () use ($phpcs_src_dir, $diff_path) {
        builder\patch($phpcs_src_dir, $diff_path, true);
    });
}

builder\create_phar($app_name, $src_dir, $output, $config);
