<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\VariationPickResult;

use Pmkr\Pmkr\Util\EnvPathHandler;
use Sweetchuck\EnvVarStorage\EnvVarStorageInterface;

class VariationPickResultConverter
{

    protected EnvPathHandler $envPathHandler;

    protected EnvVarStorageInterface $envVarStorage;

    public function __construct(
        EnvPathHandler $envPathHandler,
        EnvVarStorageInterface $envVarStorage
    ) {
        $this->envPathHandler = $envPathHandler;
        $this->envVarStorage = $envVarStorage;
    }

    /**
     * @todo
     *   Make it configurable in the VariationPickResult:
     *   - `A=a B=b C=c` Single statement without inner and trailing semicolon.
     *   - `A=a B=b C=c;` Single statement without inner but with trailing semicolon.
     *   - `export A=a B=b C=c;`
     *
     * @see \Pmkr\Pmkr\VariationPickResult\VariationPickResult
     */
    public function toShellVarSetter(VariationPickResult $result): string
    {
        $replacementPairs = $this->getReplacementPairs($result);

        $path = (string) $this->envVarStorage->get('PATH');
        $pattern = $result->export ? 'export %s=%s ;' : '%s=%s ;';

        $scanDir = $result->implodePhpIniScanDir();

        $vars = [
            'PATH' => $result->instance ?
                $this->envPathHandler->override($path, $result->instance)
                : $this->envPathHandler->remove($path),
            'PHPRC' => $result->phpRc ? strtr($result->phpRc, $replacementPairs) : null,
            'PHP_INI_SCAN_DIR' => $scanDir ? strtr($scanDir, $replacementPairs) : null,
        ];

        $shell = [];
        foreach ($vars as $name => $value) {
            $shell[] = $value === null ?
                "unset $name ;"
                : sprintf($pattern, $name, escapeshellarg($value));
        }

        return implode(\PHP_EOL, $shell) . \PHP_EOL;
    }

    /**
     * @return ?array{
     *     command: array<string>,
     *     envVars: array<string, string>,
     * }
     */
    public function toProcessArgs(VariationPickResult $result): ?array
    {
        if ($result->instance === null) {
            return null;
        }

        $replacementPairs = $this->getReplacementPairs($result);
        $binary = $result->binary ?: 'php';

        $args = [
            'command' => [
                 "{$result->instance->shareDir}/bin/$binary",
            ],
            'envVars' => [],
        ];

        $envVars = [
            'PHPRC' => $result->phpRc,
            'PHP_INI_SCAN_DIR' => $result->implodePhpIniScanDir(),
        ];
        foreach ($envVars as $name => $value) {
            if ($value === null) {
                continue;
            }

            $args['envVars'][$name] = strtr($value, $replacementPairs);
        }

        return $args;
    }

    public function toShellExecutable(VariationPickResult $result): ?string
    {
        $args = $this->toProcessArgs($result);
        if (!$args) {
            return null;
        }

        $parts = [];
        foreach ($args['envVars'] as $name => $value) {
            $parts[] = "$name=" . escapeshellarg($value);
        }

        if (!empty($args['command'])) {
            $parts[] = escapeshellcmd($args['command'][0]);
            for ($i = 1; $i < count($args['command']); $i++) {
                $parts[] = escapeshellarg($args['command'][$i]);
            }
        }

        return implode(' ', $parts);
    }

    /**
     * @return array<string, string>
     */
    protected function getReplacementPairs(VariationPickResult $result): array
    {
        return [
            '{{ instance.shareDir }}' => $result->instance ? $result->instance->shareDir : '/dev/null',
        ];
    }
}
