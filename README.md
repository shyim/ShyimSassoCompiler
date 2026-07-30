# ShyimSassoCompiler

Compiles the Shopware storefront theme SCSS with [sasso](https://crates.io/crates/sasso) — a
pure-Rust dart-sass alternative — instead of `scssphp`, by decorating
`Shopware\Storefront\Theme\ScssPhpCompiler`.

Uses the `shyim/sasso-ffi` package, which talks to the native library through `ext-ffi`; no PHP
extension has to be compiled.

## Install

This is a plain Symfony bundle, not a Shopware plugin — there is no install/activate step and no
database record, so it also works on setups where plugin management is locked down.

```console
composer require shyim/sasso-shopware-compiler
```

If your project uses [Symfony Flex](https://github.com/symfony/flex), that is all — Flex detects the
bundle and writes it into `config/bundles.php` itself. Shopware projects are not Flex apps by
default, so register it manually:

```php
// config/bundles.php
return [
    // …
    Shyim\SassoCompiler\SassoCompilerBundle::class => ['all' => true],
];
```

Clear the cache and the next `bin/console theme:compile` goes through sasso. Position in the list
does not matter — service decoration is resolved after every bundle has registered.

To go back to `scssphp`, remove the line from `config/bundles.php`.

## Why

`scssphp` is unmaintained upstream and is the slowest part of a theme build. On this repository's
default Storefront theme:

| Compiler | Time | Output |
| --- | --- | --- |
| scssphp | 1.145s | 349,630 bytes |
| sasso | 0.116s | 353,613 bytes |

Roughly a **10x** speedup for the SCSS compile step.

## Output differences

The CSS is semantically identical; where the bytes differ, sasso follows current dart-sass and
`scssphp` is the outlier:

- `.5rem` instead of `0.5rem` (leading zero dropped)
- `[data-x^=bottom]` instead of `[data-x^="bottom"]` (unquoted attribute values)
- `rgba(0,0,0,0)` instead of `transparent`
- a UTF-8 BOM instead of an `@charset "UTF-8"` rule in compressed output
- `/*! … */` banner comments are stripped
- rules emitted by media-query mixins (Bootstrap's RFS) are split into separate blocks, and some
  selector lists are ordered differently — the resulting cascade is the same

`SCSSValidator` behaviour matches `scssphp` exactly, including which values throw.

## Import resolution

Shopware puts both plain directories *and* resolver closures into the compiler's `importPaths`
configuration — `ThemeCompiler::getResolveImportPathsCallback()` maps `~bundle/...` urls onto
absolute paths. `scssphp` accepts callables as import paths directly; sasso separates the two, so
directories go to `setImportPaths()` and the closures are adapted onto sasso's two-phase importer
protocol by `CallbackImporter`.

## Validate

```console
php bin/console theme:compile --sync
```
