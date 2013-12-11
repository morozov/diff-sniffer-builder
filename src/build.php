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
require __DIR__ . '/bootstrap.php';
require __DIR__ . '/builder/functions.php';
require __DIR__ . '/builder/BlackListFilter.php';

if ($_SERVER['argc'] < 3) {
    fwrite(
        STDERR,
        'Usage: ' . basename($_SERVER['argv'][0])
        . ' app-name src-dir <-s /path/to/standard> <output>' . PHP_EOL
    );
    exit(1);
}

$app_name = $src_dir = $output = $standard = null;
$params = builder\parse_args($_SERVER['argv']);
extract($params, EXTR_IF_EXISTS);

// use "git diff --no-prefix" in order to create proper diff
$diff_path = __DIR__ . '/../data/realpath.diff';

$phpcs_src_dir = $src_dir . '/vendor/squizlabs/php_codesniffer';

builder\patch($phpcs_src_dir, $diff_path, false);

register_shutdown_function(function () use ($phpcs_src_dir, $diff_path) {
    builder\patch($phpcs_src_dir, $diff_path, true);
});

builder\create_phar($app_name, $src_dir, $output, $standard);
