<?php declare(strict_types=1);

use Shopware\Storefront\Theme\ScssPhpCompiler;
use Shyim\SassoCompiler\Compiler\SassoScssCompiler;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $container): void {
    $services = $container->services();

    // Everything in the storefront (ThemeCompiler, ThemeService, ThemeController, SCSSValidator)
    // is wired against the concrete ScssPhpCompiler service id, so that is the id to decorate.
    $services->set(SassoScssCompiler::class)
        ->decorate(ScssPhpCompiler::class);
};
