<?php
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector;

return static function (RectorConfig $rectorConfig): void {
    // register single rule
    $rectorConfig->rule(TypedPropertyFromStrictConstructorRector::class);


    // Define what rule sets will be applied
    $rectorConfig->import(SetList::CODE_QUALITY);
    $rectorConfig->import(LevelSetList::UP_TO_PHP_82);
    $rectorConfig->phpstanConfig(getcwd().'/phpstan.neon');
};
