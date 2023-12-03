<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Util;

use Pmkr\Pmkr\Model\PmkrConfig;

/**
 * @todo Validate library references.
 */
class PmkrConfigValidator
{

    protected PmkrConfig $pmkr;

    /**
     * @var array<int, array{
     *     type: string,
     *     path: string,
     * }>
     */
    protected array $errors = [];

    /**
     * @return array<int, array{
     *     type: string,
     *     path: string,
     * }>
     */
    public function validate(PmkrConfig $pmkr): array
    {
        $this->pmkr = $pmkr;
        $this->errors = [];

        return $this
            ->validateCores()
            ->validateExtensions()
            ->validateExtensionSets()
            ->validateInstances()
            ->validateAliases()
            ->validateVariations()
            ->errors;
    }

    protected function validateCores(): static
    {
        /**
         * @var string $coreKey
         * @var \Pmkr\Pmkr\Model\Core $core
         */
        foreach ($this->pmkr->cores as $coreKey => $core) {
            foreach ($core->patchList as $patchId => $patchState) {
                if (!$this->pmkr->patches->offsetExists($patchId)) {
                    $this->errors[] = [
                        'type' => 'invalid_reference',
                        'path' => "/cores/$coreKey/patchList/$patchId",
                    ];
                }
            }

            $this->validateDependenciesLibraries(
                "/cores/$coreKey/dependencies/libraries",
                $core->dependencies['libraries'] ?? [],
            );
        }

        return $this;
    }

    protected function validateExtensions(): static
    {
        /**
         * @var string $extensionKey
         * @var \Pmkr\Pmkr\Model\Extension $extension
         */
        foreach ($this->pmkr->extensions as $extensionKey => $extension) {
            $this->validateDependenciesLibraries(
                "/extensions/$extensionKey/dependencies/libraries",
                $extension->dependencies['libraries'] ?? [],
            );
        }

        return $this;
    }

    /**
     * @phpstan-param iterable<string, array<string, bool>> $libraries
     */
    protected function validateDependenciesLibraries(
        string $pathPrefix,
        iterable $libraries,
    ): static {
        $availableLibraries = $this->pmkr->libraries;

        foreach ($libraries as $opSys => $libs) {
            foreach ($libs as $libraryKey => $state) {
                if (isset($availableLibraries[$libraryKey])) {
                    continue;
                }

                $this->errors[] = [
                    'type' => 'invalid_reference',
                    'path' => "$pathPrefix/$opSys/$libraryKey",
                ];
            }
        }

        return $this;
    }

    protected function validateExtensionSets(): static
    {
        foreach ($this->pmkr->extensionSets as $extensionSetKey => $extensionSet) {
            foreach ($extensionSet as $extensionKey => $state) {
                if (!$this->pmkr->extensions->offsetExists($extensionKey)) {
                    $this->errors[] = [
                        'type' => 'invalid_reference',
                        'path' => "/extensionSets/$extensionSetKey/$extensionKey",
                    ];
                }
            }
        }

        return $this;
    }

    protected function validateInstances(): static
    {
        foreach ($this->pmkr->instances as $instanceKey => $instance) {
            if ($instance->coreName === null) {
                $this->errors[] = [
                    'type' => 'invalid_reference',
                    'path' => "/instances/$instanceKey/coreNameSuffix",
                ];
            }

            if ($instance->extensionSetName === null) {
                $this->errors[] = [
                    'type' => 'invalid_reference',
                    'path' => "/instances/$instanceKey/extensionSetNameSuffix",
                ];
            }
        }

        return $this;
    }

    protected function validateAliases(): static
    {
        foreach ($this->pmkr->aliases as $alias => $instanceKey) {
            if (!$this->pmkr->instances->offsetExists($instanceKey)) {
                $this->errors[] = [
                    'type' => 'invalid_reference',
                    'path' => "/aliases/$alias",
                ];
            }

            if ($this->pmkr->instances->offsetExists($alias)) {
                $this->errors[] = [
                    'type' => 'alias_ambiguous',
                    'path' => "/aliases/$alias",
                ];
            }
        }

        return $this;
    }

    protected function validateVariations(): static
    {
        $validInstanceKeys = array_merge(
            array_keys(iterator_to_array($this->pmkr->instances->getIterator())),
            array_keys($this->pmkr->aliases),
        );

        $defaultVariationKey = $this->pmkr->defaultVariationKey;
        if ($defaultVariationKey !== null
            && !$this->pmkr->variations->offsetExists($defaultVariationKey)
        ) {
            $this->errors[] = [
                'type' => 'invalid_reference',
                'path' => "/defaultVariationKey",
            ];
        }

        foreach ($this->pmkr->variations as $variationKey => $variation) {
            if (!in_array($variation->instanceKey, $validInstanceKeys)) {
                $this->errors[] = [
                    'type' => 'invalid_reference',
                    'path' => "/variations/$variationKey/instanceKey",
                ];
            }
        }

        return $this;
    }
}
