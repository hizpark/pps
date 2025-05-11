<?php

declare(strict_types=1);

/**
 * This file contains all placeholder keys used in the scaffolding process.
 *
 * After initializing your project with the pps tool, replace these placeholders
 * throughout the codebase (composer.json, phpunit.xml, README.md, .github/workflows/ci.yml, etc.).
 *
 * You can locate placeholders by running:
 *   grep 'pps\.' -r .
 *
 * Example usage (in composer.json):
 *   "name": "pps.vendor/pps.repo_name"
 *
 * Replace with:
 *   "name": "hizpark/example-project"
 *
 * Note:
 * - Double backslashes (\\) are required in namespaces.
 * - PHPStan version should use numeric format: 80200 = PHP 8.2.0
 */

return [
    // ─── composer.json | README.md | .github/workflows/ci.yml ───────
    'pps.vendor'               => '', // e.g., 'hizpark'
    'pps.repo_name'            => '', // e.g., 'example-project'

    // ─── composer.json ──────────────────────────────────────────────
    'pps.repo_type'            => '', // e.g., 'library'
    'pps.repo_description'     => '', // e.g., 'A PHP package for any functions...'
    'pps.repo_php_version'     => '', // e.g., '8.2'
    'pps.repo#author.name'     => '', // e.g., 'Harper Jang'
    'pps.repo@author.email'    => '', // e.g., 'harper.jang@outlook.com'
    'pps.repo_src_namespace'   => '', // e.g., 'Hizpark\\ExampleProject'
    'pps.repo_tests_namespace' => '', // e.g., 'Hizpark\\ExampleProject\\Tests'

    // ─── phpunit.xml.dist ───────────────────────────────────────────
    'pps.testsuite_name'       => '', // e.g., 'ExampleProject'

    // ─── phpstan.neon.dist ──────────────────────────────────────────
    'pps.stan_level'           => '', // e.g., 'max'
    'pps.stan_php_version'     => '', // e.g., '80200'

    // ─── LICENSE ────────────────────────────────────────────────────
    'pps.license_year'         => '', // e.g., '2025'
    'pps.license_owner'        => '', // e.g., 'hizpark'

    // ─── README.md ──────────────────────────────────────────────────
    'pps.doc_title'            => '', // e.g., 'Example Project'
    'pps.doc_tagline'          => '', // e.g., 'A very cool project'
];
