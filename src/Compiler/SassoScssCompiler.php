<?php declare(strict_types=1);

namespace Shyim\SassoCompiler\Compiler;

use Sasso\Compiler;
use ScssPhp\ScssPhp\OutputStyle;
use Shopware\Storefront\Theme\AbstractCompilerConfiguration;
use Shopware\Storefront\Theme\AbstractScssCompiler;

/**
 * Compiles theme SCSS with the `sasso` compiler, decorating Shopware's scssphp compiler.
 */
class SassoScssCompiler extends AbstractScssCompiler
{
    public function compileString(AbstractCompilerConfiguration $config, string $scss, ?string $path = null): string
    {
        $compiler = new Compiler();

        $compiler->setStyle(
            $config->getValue('outputStyle') === OutputStyle::EXPANDED
                ? Compiler::STYLE_EXPANDED
                : Compiler::STYLE_COMPRESSED
        );

        if ($path !== null) {
            // Enables sasso's byte-exact error snippets for the input itself.
            $compiler->setUrl($path);
        }

        // Shopware mixes plain directories and resolver callables into `importPaths`:
        // ThemeCompiler::getResolveImportPathsCallback() maps `~bundle/...` urls onto absolute
        // paths. scssphp accepts callables as import paths directly, sasso keeps the two apart -
        // directories go to setImportPaths(), everything else through an Importer.
        $importPaths = $config->getValue('importPaths');

        $directories = [];
        $resolvers = [];

        if (\is_array($importPaths)) {
            foreach ($importPaths as $importPath) {
                if (\is_string($importPath)) {
                    $directories[] = $importPath;
                } elseif (\is_callable($importPath)) {
                    $resolvers[] = $importPath;
                }
            }
        }

        $compiler->setImportPaths($directories);

        if ($resolvers !== []) {
            $compiler->setImporter(new CallbackImporter($resolvers));
        }

        return $compiler->compile($scss);
    }
}
