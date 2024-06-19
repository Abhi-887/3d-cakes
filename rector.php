<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;
use Rector\ValueObject\PhpVersion;
use RectorLaravel\Set\LaravelSetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__.'/app',
        __DIR__.'/database',
        __DIR__.'/domain',
        __DIR__.'/resources',
        __DIR__.'/routes',
        __DIR__.'/tests',
    ]);

    $rectorConfig->sets([
        LaravelSetList::LARAVEL_110,
        SetList::PHP_83,
    ]);

    $rectorConfig->rules([
        Rector\Php55\Rector\ClassConstFetch\StaticToSelfOnFinalClassRector::class,
        Rector\Php72\Rector\FuncCall\GetClassOnNullRector::class,
        Rector\Php80\Rector\Catch_\RemoveUnusedVariableInCatchRector::class,
        Spatie\Ray\Rector\RemoveRayCallRector::class,
    ]);

    $rectorConfig->phpVersion(PhpVersion::PHP_83);

    //     $rectorConfig->phpstanConfig(__DIR__.'/phpstan.neon');

    // Ensure file system caching is used instead of in-memory.
    $rectorConfig->cacheClass(Rector\Caching\ValueObject\Storage\FileCacheStorage::class);

    // Specify a path that works locally as well as on CI job runners.
    $rectorConfig->cacheDirectory('build/rector');
};
