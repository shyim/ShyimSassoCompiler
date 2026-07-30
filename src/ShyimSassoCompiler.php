<?php declare(strict_types=1);

namespace Shyim\SassoCompiler;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * A plain Symfony bundle rather than a Shopware\Core\Framework\Plugin, so it needs no plugin
 * install/activate step and no database record - `composer require` plus a line in
 * `config/bundles.php` is enough. Nothing here uses the plugin lifecycle (no migrations, no
 * config, no assets), so the extra machinery would buy nothing.
 */
class ShyimSassoCompiler extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        // Shopware's Bundle base class autoloads Resources/config/services.*; a plain Symfony
        // bundle does not, so load it here.
        $loader = new PhpFileLoader($container, new FileLocator($this->getPath() . '/Resources/config'));
        $loader->load('services.php');
    }
}
