<?php

declare(strict_types=1);

namespace Database\Factories\Support;

use Database\Factories\StaticSupport;
use Exception;
use Faker\Factory;
use Smknstd\FakerPicsumImages\FakerPicsumImagesProvider;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Exceptions\UnreachableUrl;

trait HasMediaFactory
{
    public function hasRandomMedia(?int $maxCount = null, string $collectionName = 'image'): static
    {
        return $this
            ->afterCreating(
                fn (HasMedia $model) => self::seedRandomMedia(
                    $model,
                    collectionName: $collectionName,
                    maximum: $maxCount ?? 3
                )
            );
    }

    public function hasSpecificMedia(): static
    {
        return $this
            ->afterCreating(
                fn (HasMedia $model) => self::seedSpecificMedia($model)
            );
    }

    /**
     * @throws \Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig
     * @throws \Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist
     * @throws Exception
     */
    private static function seedRandomMedia(
        HasMedia $model,
        string $collectionName = 'image',
        int $minimum = 1,
        int $maximum = 3,
    ): void {

        if (app()->runningUnitTests() || app()->isLocal()) {
            self::seedSpecificMedia(model: $model, collectionName: $collectionName);

            return;
        }

        /** @var \Spatie\MediaLibrary\MediaCollections\MediaCollection $mediaCollection */
        $mediaCollection = $model->getRegisteredMediaCollections()
            ->where('name', $collectionName)
            ->first();

        if ($mediaCollection->singleFile) {

            self::upload($model, $collectionName);

        } else {

            collect()
                ->range(
                    $minimum,
                    collect()
                        ->range($minimum, $maximum)
                        ->random()
                )
                ->map(fn () => self::upload($model, $collectionName));
        }
    }

    /** @throws Exception */
    private static function upload(
        HasMedia $model,
        string $collectionName = 'image',
    ): void {

        if (! StaticSupport::$hasNetworkAccess) {
            self::seedSpecificMedia($model, $collectionName);

            return;
        }

        try {
            $model
                ->addMediaFromUrl(self::imageUrl())
                ->toMediaCollection($collectionName);
        } catch (UnreachableUrl) {

            StaticSupport::$hasNetworkAccess = false;

        }
    }

    private static function imageUrl(): string
    {
        $faker = Factory::create();
        $faker->addProvider(new FakerPicsumImagesProvider($faker));

        return $faker->imageUrl();
    }

    /**
     * @throws \Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig
     * @throws \Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist
     */
    private static function seedSpecificMedia(
        HasMedia $model,
        string $collectionName = 'image'
    ): void {

        $model
            ->copyMedia(base_path('test_files/1-800x600.jpg'))
            ->toMediaCollection($collectionName);
    }
}
