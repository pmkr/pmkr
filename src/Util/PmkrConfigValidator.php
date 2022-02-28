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
            ->validateExtensionSets()
            ->validateInstances()
            ->validateAliases()
            ->validateVariations()
            ->errors;
    }

    /**
     * @return $this
     */
    protected function validateCores()
    {
        foreach ($this->pmkr->cores as $coreKey => $core) {
            foreach ($core->patchList as $patchId => $patchState) {
                if (!$this->pmkr->patches->offsetExists($patchId)) {
                    $this->errors[] = [
                        'type' => 'invalid_reference',
                        'path' => "/cores/$coreKey/patchList/$patchId",
                    ];
                }
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function validateExtensionSets()
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

    /**
     * @return $this
     */
    protected function validateInstances()
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

    /**
     * @return $this
     */
    protected function validateAliases()
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

    /**
     * @return $this
     */
    protected function validateVariations()
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
