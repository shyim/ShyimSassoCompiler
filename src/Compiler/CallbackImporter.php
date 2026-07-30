<?php declare(strict_types=1);

namespace Shyim\SassoCompiler\Compiler;

use Sasso\Compiler;
use Sasso\Importer;
use Sasso\ImporterResult;

/**
 * Resolves `@import`/`@use` urls through Shopware's import-path resolver callables.
 *
 * Shopware passes closures (`ThemeCompiler::getResolveImportPathsCallback()`) inside the
 * `importPaths` configuration, which map `~bundle/path` urls onto absolute file paths. They are
 * consulted here; returning `null` lets the configured directory import paths handle the url, which
 * already covers Sass's own partial and relative-resolution rules.
 *
 * @internal
 */
class CallbackImporter implements Importer
{
    /**
     * @param list<callable(string): ?string> $resolvers
     */
    public function __construct(private readonly array $resolvers)
    {
    }

    public function canonicalize(string $url, bool $fromImport, ?string $containingUrl = null): ?string
    {
        // An already-absolute file path reaches us when a resolved stylesheet imports one of its
        // own neighbours by absolute path.
        if (is_file($url)) {
            return self::canonicalPath($url);
        }

        foreach ($this->resolvers as $resolver) {
            $path = $resolver($url);

            if ($path !== null) {
                return self::canonicalPath($path);
            }
        }

        return null;
    }

    public function load(string $canonicalUrl): ?ImporterResult
    {
        $contents = @file_get_contents($canonicalUrl);

        if ($contents === false) {
            return null;
        }

        return new ImporterResult($contents, match (strtolower(pathinfo($canonicalUrl, \PATHINFO_EXTENSION))) {
            'sass' => Compiler::SYNTAX_SASS,
            'css' => Compiler::SYNTAX_CSS,
            default => Compiler::SYNTAX_SCSS,
        }, $canonicalUrl);
    }

    /**
     * The canonical string doubles as sasso's module-cache key, so resolve it to a real path to
     * keep two spellings of the same file from being compiled twice.
     */
    private static function canonicalPath(string $path): string
    {
        $resolved = realpath($path);

        return $resolved === false ? $path : $resolved;
    }
}
