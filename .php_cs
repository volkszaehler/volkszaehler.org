<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude('vendor')
    ->in(__DIR__)
;

return PhpCsFixer\Config::create()
    ->setRules([
        '@PSR2' => true,
        '@PhpCsFixer' => true,
        'strict_param' => false,
        'array_syntax' => ['syntax' => 'short'],
        'single_line_comment_style' => [
            'comment_types' => [], // don't fix comment style
        ],
        'braces' => [
            'allow_single_line_closure' => true,
        ],
        'header_comment' => [
            'separate' => 'bottom',
            'header' => <<<EOD
Copyright (c) 2011-2019 The volkszaehler.org project

For the full copyright and license information, please view
the LICENSE file that was distributed with this source code.
EOD
        ],
    ])
    ->setIndent("\t")
    ->setFinder($finder)
;
