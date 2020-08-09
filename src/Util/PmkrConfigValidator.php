<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Util;

use Pmkr\Pmkr\Model\PmkrConfig;

class PmkrConfigValidator
{

    protected PmkrConfig $pmkr;

    protected array $errors = [];

    public function validate(PmkrConfig $pmkr): array
    {
        $this->pmkr = $pmkr;
        $this->errors = [];

        return $this
            ->validateExtensionSets()
            ->validateInstance()
            ->validateAlias()
            ->validateVariations()
            ->errors;
    }

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

    protected function validateInstance()
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

    protected function validateAlias()
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

    protected function validateVariations()
    {
        $validInstanceKeys = array_merge(
            array_keys(iterator_to_array($this->pmkr->instances->getIterator())),
            array_keys($this->pmkr->aliases),
        );

        $defaultVariationKey = $this->pmkr->defaultVariationKey;
        if (!$this->pmkr->variations->offsetExists($defaultVariationKey)) {
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
