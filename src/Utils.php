<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr;

use Consolidation\Config\ConfigAwareInterface;
use Consolidation\Config\ConfigAwareTrait;
use Consolidation\Config\ConfigInterface;
use Pmkr\Pmkr\Model\Instance;
use Pmkr\Pmkr\Model\Patch;
use Pmkr\Pmkr\OpSys\OpSys;
use Sweetchuck\PearClient\DataType\Release as PearRelease;
use Pmkr\Pmkr\Model\Extension;
use Sweetchuck\Utils\ArrayFilterInterface;
use Sweetchuck\Utils\ComparerInterface;
use Sweetchuck\Utils\VersionNumber;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Process\Process;

class Utils implements ConfigAwareInterface
{

    use ConfigAwareTrait;

    public function __construct(ConfigInterface $config)
    {
        $this->setConfig($config);
    }

    public function getPmkrRoot(): string
    {
        return dirname(__DIR__);
    }

    public function getPmkrHome(): string
    {
        $appName = Application::NAME;

        return $this->getConfig()->get('env.HOME') . "/.$appName";
    }

    /**
     * @param array<string> $candidates
     * @param array<string> $array
     */
    public function findCandidate(
        array $candidates,
        array $array
    ): ?string {
        foreach ($candidates as $candidate) {
            if (array_key_exists($candidate, $array)) {
                return (string) $candidate;
            }
        }

        return null;
    }

    /**
     * @return array<string>
     */
    public function nameCandidates(
        string $prefix,
        VersionNumber $versionNumber,
        bool $isZts,
        string $suffix
    ): array {
        $suffixes = array_unique([
            $suffix,
            '',
        ]);

        $types  = [
            $isZts ? 'zts' : 'nts',
            '',
        ];

        $versions = [
            $versionNumber->format(VersionNumber::FORMAT_MA2MI2P2),
            $versionNumber->format(VersionNumber::FORMAT_MA2MI2),
            $versionNumber->format(VersionNumber::FORMAT_MA2),
            '',
        ];

        $candidates = [];
        foreach ($suffixes as $suffix) {
            foreach ($types as $type) {
                foreach ($versions as $version) {
                    $parts = array_filter([
                        $prefix,
                        $version,
                        $type,
                        $suffix,
                    ]);

                    $candidates[] = implode('-', $parts);
                }
            }
        }

        return array_filter($candidates, 'mb_strlen');
    }

    public function getPhpCoreDownloadUri(VersionNumber $coreVersionNumber): string
    {
        return sprintf(
            'https://www.php.net/distributions/php-%s.%s',
            $coreVersionNumber->format(VersionNumber::FORMAT_MA0DMI0DP0),
            'tar.bz2',
        );
    }

    public function patchCacheDestination(Patch $patch): string
    {
        return $this->getConfig()->get('dir.cache') . "/file/patch/{$patch->key}.patch";
    }

    /**
     * @todo Remove $config argument.
     */
    public function phpCoreCacheDestination(ConfigInterface $config, string $uri): string
    {
        $uriPath = (string) parse_url($uri, \PHP_URL_PATH);
        $uriBaseName = basename($uriPath);

        return $config->get('dir.cache') . "/file/php-core/$uriBaseName";
    }

    /**
     * @todo Remove $config argument.
     */
    public function phpExtensionCacheDestination(ConfigInterface $config, string $uri): string
    {
        $uriPath = (string) parse_url($uri, \PHP_URL_PATH);
        $uriBaseName = basename($uriPath);

        return $config->get('dir.cache') . "/file/php-extension/$uriBaseName";
    }

    /**
     * @todo Remove $config argument.
     */
    public function libraryCacheDestination(ConfigInterface $config, string $uri): string
    {
        $uriPath = (string) parse_url($uri, \PHP_URL_PATH);
        $uriBaseName = basename($uriPath);

        // @todo This not gonna work for every URL.
        // foo/stable.zip and bar/stable.zip will conflict.
        return $config->get('dir.cache') . "/file/library/$uriBaseName";
    }

    /**
     * @todo Remove $config argument.
     */
    public function gitUrlToCacheDestination(ConfigInterface $config, string $url): string
    {
        $parts = (array) parse_url($url);
        if (!isset($parts['host']) || !isset($parts['path'])) {
            $parts = $this->parseGitUrl($url);
        }

        $path = ltrim($parts['path'] ?? '', '/');
        $path = str_replace('/', \DIRECTORY_SEPARATOR, $path);

        $dst = implode(
            \DIRECTORY_SEPARATOR,
            [
                $config->get('dir.cache'),
                'git',
                $parts['host'] ?? 'unknown',
                $path,
            ]
        );

        if (preg_match('/\.git$/', $dst) !== 1) {
            $dst .= '.git';
        }

        return $dst;
    }

    /**
     * @return array{
     *     user?: string,
     *     host?: string,
     *     path?: string,
     * }
     */
    public function parseGitUrl(string $url): array
    {
        $matches = [];
        preg_match('!^(?P<user>[^@]+)@(?<host>[^:]+):(?P<path>.+)$!', $url, $matches);

        return $matches;
    }

    public function phpExtensionPeclDownloadUri(string $name, string $version): string
    {
        return sprintf(
            'https://pecl.php.net/get/%s-%s.tgz',
            $name,
            $version,
        );
    }

    /**
     * @param string $required
     * @param array<string, PearRelease> $list
     *
     * @return null|PearRelease
     */
    public function pickPearReleaseVersion(string $required, array $list): ?PearRelease
    {
        $isExact = preg_match('/^\d+\.\d+\.\d+/', $required) === 1;
        if ($isExact) {
            return $list[$required] ?? null;
        }
        /** @var callable(string $a, string $b): int $comparer */
        $comparer = 'version_compare';
        uksort($list, $comparer);
        /** @var PearRelease[] $list */
        $list = array_reverse($list, true);
        if ($required === 'stable') {
            foreach ($list as $release) {
                if ($release->stability === 'stable') {
                    return $release;
                }
            }
        }

        $pattern = '/^' . preg_quote($required). '\b/';
        foreach ($list as $release) {
            if (preg_match($pattern, $release->version) === 1) {
                return $release;
            }
        }

        return null;
    }

    public function isIgnoredExtension(string $instanceThreadType, Extension $extension): bool
    {
        if ($extension->ignore === 'never') {
            return false;
        }

        return $extension->ignore === $instanceThreadType;
    }

    /**
     * @param iterable<int|string, null|false|string> $envVars
     *
     * @return array<string>
     */
    public function envVarsToExpressions(iterable $envVars): array
    {
        $expressions = [];
        foreach ($envVars as $name => $value) {
            if ($value === false) {
                // Skip.
                continue;
            }

            if ($value === null) {
                // Unset.
                assert(!is_numeric($name));
                $expressions[] = "$name=";

                continue;
            }

            if (is_int($name)) {
                // Already an expression.
                $expressions[] = $value;

                continue;
            }

            $expressions[] = sprintf('%s=%s', $name, escapeshellarg($value));
        }

        return $expressions;
    }

    /**
     * @return string[]
     */
    public function explodeCommaSeparatedList(string $list): array
    {
        return preg_split(
            '/\s*,\s*/u',
            trim($list, "\n\r\t ,"),
            -1,
            PREG_SPLIT_NO_EMPTY,
        ) ?: [];
    }

    /**
     * @param null|string|array<string>|array<array<string>> $list
     *
     * @return array<string>
     */
    public function normalizeCommaSeparatedList($list): array
    {
        $result = [];
        foreach ((array) $list as $item) {
            $item = is_array($item) ?
                $this->normalizeCommaSeparatedList($item)
                : $this->explodeCommaSeparatedList($item);

            $result = array_merge($result, $item);
        }

        return $result;
    }

    /**
     * @param iterable<string, \Pmkr\Pmkr\Model\Instance> $instances
     *
     * @return array<string, string>
     */
    public function ioInstanceOptions(iterable $instances): array
    {
        $options = [];
        foreach ($instances as $instance) {
            if ($instance->hidden) {
                continue;
            }

            $parts = array_filter([
                $instance->key,
                $instance->coreVersion,
                ($instance->isZts ? 'ZTS' : 'NTS'),
                $instance->coreNameSuffix,
                $instance->extensionSetNameSuffix,
                // @todo Description.
            ]);

            $options[$instance->key] = implode(' ', $parts);
        }

        return $options;
    }

    /**
     * @param iterable<string, \Pmkr\Pmkr\Model\Variation> $variations
     *
     * @return array<string, string>
     */
    public function ioVariationOptions(iterable $variations): array
    {
        $options = [];
        foreach ($variations as $variation) {
            $parts = array_filter([
                $variation->key,
                $variation->instanceKey,
                $variation->instance->coreVersion,
                ($variation->instance->isZts ? 'ZTS' : 'NTS'),
            ]);

            $options[$variation->key] = implode(' ', $parts);
        }

        return $options;
    }

    /**
     * @return mixed
     */
    public function getInputValue(InputInterface $input, string $locator)
    {
        [$type, $name] = explode('.', $locator, 2);

        return $type === 'arg' ?
            $input->getArgument($name)
            : $input->getOption($name);
    }

    /**
     * @param mixed $value
     *
     * @return $this
     */
    public function setInputValue(InputInterface $input, string $locator, $value)
    {
        [$type, $name] = explode('.', $locator, 2);

        $type === 'arg' ?
            $input->setArgument($name, $value)
            : $input->setOption($name, $value);

        return $this;
    }

    public function getProcessCallback(OutputInterface $output): callable
    {
        $stdOutput = $output;
        $stdError = $output instanceof ConsoleOutputInterface ?
            $output->getErrorOutput()
            : $stdOutput;

        return function ($type, $text) use ($stdOutput, $stdError) {
            $type === Process::OUT ?
                $stdOutput->write($text)
                : $stdError->write($text);
        };
    }

    public function replaceFileExtension(string $fileName, string $new): string
    {
        // @todo Support for multipart. .tar.gz.
        return preg_replace('/\..+?$/', ".$new", $fileName);
    }

    public function boolToCompareDirection(bool $value): int
    {
        return $value ? ComparerInterface::DIR_DESCENDING : ComparerInterface::DIR_ASCENDING;
    }

    public function boolToAnsi(
        bool $value,
        string $true = "\xE2\x9C\x93",
        string $false = "\xE2\xA8\xAF"
    ): string {
        return $value ? "<fg=green>$true</>" : "<fg=red>$false</>";
    }

    /**
     * @return array<string>
     */
    public function splitLines(string $lines): array
    {
        if ($lines === '') {
            return [];
        }

        $chars = [
            "\n" => "\r",
            "\r" => "\n",
        ];

        foreach ($chars as $main => $other) {
            $mainPos = mb_strpos($lines, $main);
            if ($mainPos === false) {
                continue;
            }

            $lines = preg_replace('/' . $other . '/u', '', $lines);
            if (preg_match("/$main\$/u", $lines)) {
                $lines = mb_substr($lines, 0, -1);
            }
            break;
        }

        return $mainPos === false ? [$lines] : explode($main, $lines);
    }

    /**
     * @param iterable<string, bool|string> $mapping
     *
     * @return array<string, bool>
     */
    public function normalizeBooleanMapping(iterable $mapping): array
    {
        $result = [];
        foreach ($mapping as $key => $value) {
            if (!is_bool($value)) {
                $key = $value;
                $value = true;
            }

            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * @return array<string, bool>
     */
    public function fetchPackageDependenciesFromInstanceCore(
        OpSys $opSys,
        Instance $instance
    ): array {
        $opSysIdentifier = $opSys->pickOpSysIdentifier(array_keys(
            $instance->core->dependencies['packages'],
        ));
        if ($opSysIdentifier === null) {
            return [];
        }

        $packages = $instance->core->dependencies['packages'][$opSysIdentifier] ?? [];
        $packages = $this->normalizeBooleanMapping($packages);

        return array_filter($packages);
    }

    /**
     * @return array<string, bool>
     */
    public function fetchPackageDependenciesFromInstanceExtensions(
        OpSys $opSys,
        Instance $instance,
        ?ArrayFilterInterface $extensionFilter = null
    ): array {
        $packagesAll = [];
        $extensions = $instance->extensions;
        if ($extensionFilter) {
            $extensions = array_filter($extensions, $extensionFilter);
        }

        foreach ($extensions as $extension) {
            $packagesAll += $this->fetchPackageDependenciesFromExtension($opSys, $extension);
        }

        return $packagesAll;
    }

    /**
     * @return array<string, bool>
     */
    public function fetchPackageDependenciesFromExtension(
        OpSys $opSys,
        Extension $extension
    ): array {
        $opSysIdentifier = $opSys->pickOpSysIdentifier(
            array_keys($extension->dependencies['packages'] ?? []),
        );
        if ($opSysIdentifier === null) {
            return [];
        }

        $packages = $extension->dependencies['packages'][$opSysIdentifier] ?? [];
        $packages = $this->normalizeBooleanMapping($packages);

        return array_filter($packages);
    }

    /**
     * @param \Pmkr\Pmkr\OpSys\OpSys $opSys
     * @param array<string, array<string, bool>> $libraryReferences
     *
     * @return array<string, bool>
     */
    public function fetchLibraryKeys(
        OpSys $opSys,
        array $libraryReferences
    ): array {
        $opSysIdentifier = $opSys->pickOpSysIdentifier(array_keys($libraryReferences));

        return $opSysIdentifier !== null ?
            array_filter($libraryReferences[$opSysIdentifier])
            : [];
    }

    /**
     * @return array<string>
     */
    public function validateInstanceBinary(string $binary): array
    {
        $errors = [];
        if (trim($binary, ". \t\n\r\0\x0B") === '') {
            $errors[] = 'Must contain at least one alpha-numeric character.';
        }

        if (preg_match('@[\\\/]@', $binary) === 1) {
            $errors[] = 'Must not contain any "/" or "\\" characters.';
        }

        return $errors;
    }

    public function getOnlyChildDir(string $parentDir): ?SplFileInfo
    {
        $children = (new Finder())
            ->in($parentDir)
            ->depth(0);

        if ($children->count() !== 1) {
            return null;
        }

        $iterator = $children->getIterator();
        $iterator->rewind();
        /** @var \Symfony\Component\Finder\SplFileInfo $item */
        $item = $iterator->current();

        return $item->isDir() ? $item : null;
    }
}
