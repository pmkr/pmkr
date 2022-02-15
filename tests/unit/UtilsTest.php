<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Tests\Unit;

use Codeception\Test\Unit;
use org\bovigo\vfs\vfsStream;
use Pmkr\Pmkr\Model\PmkrConfig;
use Pmkr\Pmkr\Tests\Helper\Dummy\ConsoleOutput;
use Pmkr\Pmkr\Tests\UnitTester;
use Pmkr\Pmkr\Util\Filter\PhpExtensionFilter;
use Pmkr\Pmkr\Utils;
use Sweetchuck\PearClient\DataType\Release as PearRelease;
use Sweetchuck\Utils\ComparerInterface;
use Sweetchuck\Utils\VersionNumber;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Process\Process;

/**
 * @covers \Pmkr\Pmkr\Utils
 */
class UtilsTest extends Unit
{
    protected UnitTester $tester;

    /**
     * @param array<string, mixed> ...$configLayers
     *
     * @return \Pmkr\Pmkr\Utils
     */
    protected function createUtils(...$configLayers): Utils
    {
        $config = $this->tester->grabConfig(...$configLayers);

        return new Utils($config);
    }

    public function testGetPmkrRoot(): void
    {
        $utils = $this->createUtils();

        $this->tester->assertSame(getcwd(), $utils->getPmkrRoot());
    }

    public function testGetPmkrHome(): void
    {
        $utils = $this->createUtils();

        $this->tester->assertSame('/home/me/.pmkr', $utils->getPmkrHome());
    }

    /**
     * @return array<string, mixed>
     */
    public function casesFindCandidate(): array
    {
        return [
            'empty' => [
                null,
                [],
                [],
            ],
            'empty candidates' => [
                null,
                [],
                [
                    'a-key' => 'a-value',
                ],
            ],
            'empty array' => [
                null,
                [
                    'a-key',
                ],
                [],
            ],
            'match' => [
                'b-key',
                [
                    'a-value',
                    'b-key',
                    'c-key',
                ],
                [
                    'a-key' => 'a-value',
                    'b-key' => 'b-value',
                    'c-key' => 'c-value',
                ],
            ],
        ];
    }

    /**
     * @param array<string> $candidates
     * @param array<string> $array
     *
     * @dataProvider casesFindCandidate
     */
    public function testFindCandidate(?string $expected, array $candidates, array $array): void
    {
        $utils = $this->createUtils();

        $this->tester->assertSame($expected, $utils->findCandidate($candidates, $array));
    }

    /**
     * @return array<string, mixed>
     */
    public function casesNameCandidates(): array
    {
        return [
            'prefix-1 suffix-1' => [
                [
                    'myp-010203-nts-mys',
                    'myp-0102-nts-mys',
                    'myp-01-nts-mys',
                    'myp-nts-mys',
                    'myp-010203-mys',
                    'myp-0102-mys',
                    'myp-01-mys',
                    'myp-mys',
                    'myp-010203-nts',
                    'myp-0102-nts',
                    'myp-01-nts',
                    'myp-nts',
                    'myp-010203',
                    'myp-0102',
                    'myp-01',
                    'myp',
                ],
                'myp',
                VersionNumber::createFromString('1.2.3'),
                false,
                'mys',
            ],
            'prefix-1 suffix-0' => [
                [
                    'myp-010203-nts',
                    'myp-0102-nts',
                    'myp-01-nts',
                    'myp-nts',
                    'myp-010203',
                    'myp-0102',
                    'myp-01',
                    'myp',
                ],
                'myp',
                VersionNumber::createFromString('1.2.3'),
                false,
                '',
            ],
            'prefix-0 suffix-1' => [
                [
                    '010203-nts-mys',
                    '0102-nts-mys',
                    '01-nts-mys',
                    'nts-mys',
                    '010203-mys',
                    '0102-mys',
                    '01-mys',
                    'mys',
                    '010203-nts',
                    '0102-nts',
                    '01-nts',
                    'nts',
                    '010203',
                    '0102',
                    '01',
                ],
                '',
                VersionNumber::createFromString('1.2.3'),
                false,
                'mys',
            ],
            'prefix-0 suffix-0' => [
                [
                    '010203-nts',
                    '0102-nts',
                    '01-nts',
                    'nts',
                    '010203',
                    '0102',
                    '01',
                ],
                '',
                VersionNumber::createFromString('1.2.3'),
                false,
                '',
            ],
        ];
    }

    /**
     * @param array<string> $expected
     *
     * @dataProvider casesNameCandidates
     */
    public function testNameCandidates(
        array $expected,
        string $prefix,
        VersionNumber $versionNumber,
        bool $isZts,
        string $suffix
    ): void {
        $utils = $this->createUtils();

        $this->tester->assertEquals(
            $expected,
            $utils->nameCandidates($prefix, $versionNumber, $isZts, $suffix),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function casesGetPhpCoreDownloadUri(): array
    {
        return [
            'basic' => [
                'https://www.php.net/distributions/php-1.2.3.tar.bz2',
                VersionNumber::createFromString('1.2.3'),
            ],
        ];
    }

    /**
     * @dataProvider casesGetPhpCoreDownloadUri
     */
    public function testGetPhpCoreDownloadUri(string $expected, VersionNumber $versionNumber): void
    {
        $utils = $this->createUtils();
        $this->tester->assertSame($expected, $utils->getPhpCoreDownloadUri($versionNumber));
    }

    public function testPhpCoreCacheDestination(): void
    {
        $utils = $this->createUtils();
        $uri = 'https://www.php.net/distributions/php-1.2.3.tar.bz2';
        $config = $this->tester->grabConfig();
        $expected = '/home/me/.cache/pmkr/file/php-core/php-1.2.3.tar.bz2';
        $this->tester->assertSame($expected, $utils->phpCoreCacheDestination($config, $uri));
    }

    public function testPhpExtensionCacheDestination(): void
    {
        $utils = $this->createUtils();
        $uri = 'https://pecl.php.net/get/pcov-1.0.11.tgz';
        $config = $this->tester->grabConfig();
        $expected = '/home/me/.cache/pmkr/file/php-extension/pcov-1.0.11.tgz';
        $this->tester->assertSame($expected, $utils->phpExtensionCacheDestination($config, $uri));
    }

    public function testLibraryCacheDestination(): void
    {
        $utils = $this->createUtils();
        $uri = 'https://nih.at/libzip/libzip-1.2.0.tar.gz';
        $config = $this->tester->grabConfig();
        $expected = '/home/me/.cache/pmkr/file/library/libzip-1.2.0.tar.gz';
        $this->tester->assertSame($expected, $utils->libraryCacheDestination($config, $uri));
    }

    public function testGitUrlToCacheDestination(): void
    {
        $utils = $this->createUtils();
        $config = $utils->getConfig();

        $expected = '/home/me/.cache/pmkr/git/github.com/foo/bar.git';

        $gitUri = 'https://github.com/foo/bar.git';
        $this->tester->assertSame($expected, $utils->gitUrlToCacheDestination($config, $gitUri));

        $gitUri = 'https://github.com/foo/bar';
        $this->tester->assertSame($expected, $utils->gitUrlToCacheDestination($config, $gitUri));

        $gitUri = 'git@github.com:foo/bar.git';
        $this->tester->assertSame($expected, $utils->gitUrlToCacheDestination($config, $gitUri));
    }

    public function testPhpExtensionPeclDownloadUri(): void
    {
        $utils = $this->createUtils();
        $expected = 'https://pecl.php.net/get/a-1.2.3.tgz';
        $name = 'a';
        $version = '1.2.3';
        $this->tester->assertSame($expected, $utils->phpExtensionPeclDownloadUri($name, $version));
    }

    /**
     * @return array<string, mixed>
     */
    public function casesPickPearReleaseVersion(): array
    {
        $v123 = PearRelease::__set_state([
            'version' => '1.2.3',
            'stability' => 'stable',
        ]);
        $v124 = PearRelease::__set_state([
            'version' => '1.2.4',
            'stability' => 'stable',
        ]);
        $v125 = PearRelease::__set_state([
            'version' => '1.2.5',
            'stability' => 'stable',
        ]);
        $v213 = PearRelease::__set_state([
            'version' => '2.1.3',
            'stability' => 'stable',
        ]);

        return [
            'empty' => [
                null,
                '1.2.3',
                [],
            ],
            'exact' => [
                $v124,
                '1.2.4',
                [
                    '1.2.3' => $v123,
                    '1.2.4' => $v124,
                    '1.2.5' => $v125,
                ],
            ],
            'major.minor' => [
                $v125,
                '1.2',
                [
                    '1.2.3' => $v123,
                    '1.2.4' => $v124,
                    '1.2.5' => $v125,
                    '2.1.3' => $v213,
                ],
            ],
            'stable' => [
                $v213,
                'stable',
                [
                    '1.2.3' => $v123,
                    '1.2.4' => $v124,
                    '1.2.5' => $v125,
                    '2.1.3' => $v213,
                ],
            ],
            'not-exists' => [
                null,
                '3.0',
                [
                    '1.2.3' => $v123,
                    '1.2.4' => $v124,
                    '1.2.5' => $v125,
                    '2.1.3' => $v213,
                ],
            ],
        ];
    }

    /**
     * @param array<PearRelease> $list
     *
     * @dataProvider casesPickPearReleaseVersion
     */
    public function testPickPearReleaseVersion(
        ?PearRelease $expected,
        string $required,
        array $list
    ): void {
        $utils = $this->createUtils();

        $this->tester->assertSame($expected, $utils->pickPearReleaseVersion($required, $list));
    }

    /**
     * @return array<string, mixed>
     */
    public function casesIsIgnoredExtension(): array
    {
        $configLayer = [
            'extensions' => [
                'ext_never' => [
                    'ignore' => 'never',
                ],
                'ext_nts' => [
                    'ignore' => 'nts',
                ],
                'ext_zts' => [
                    'ignore' => 'zts',
                ],
            ],
        ];

        return [
            'nts ext_never' => [
                false,
                'nts',
                'ext_never',
                $configLayer,
            ],
            'zts ext_never' => [
                false,
                'zts',
                'ext_never',
                $configLayer,
            ],
            'nts ext_nts' => [
                true,
                'nts',
                'ext_nts',
                $configLayer,
            ],
            'zts ext_nts' => [
                false,
                'zts',
                'ext_nts',
                $configLayer,
            ],
            'nts ext_zts' => [
                false,
                'nts',
                'ext_zts',
                $configLayer,
            ],
            'zts ext_zts' => [
                true,
                'zts',
                'ext_zts',
                $configLayer,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $configLayer
     *
     * @dataProvider casesIsIgnoredExtension
     */
    public function testIsIgnoredExtension(
        bool $expected,
        string $threadType,
        string $extName,
        array $configLayer
    ): void {
        $utils = $this->createUtils(null, $configLayer);
        $config = $utils->getConfig();
        $pmkr = PmkrConfig::__set_state([
            'config' => $config,
            'configPath' => [],
        ]);
        $extension = $pmkr->extensions[$extName];
        $this->tester->assertSame($expected, $utils->isIgnoredExtension($threadType, $extension));
    }

    /**
     * @return array<string, mixed>
     */
    public function casesEnvVarsToExpressions(): array
    {
        return [
            'empty' => [
                [],
                [],
            ],
            'basic' => [
                [
                    'a=b',
                    'c=',
                    "e='f'",
                ],
                [
                    'a=b',
                    'c' => null,
                    'd' => false,
                    'e' => 'f',
                ],
            ],
        ];
    }

    /**
     * @param array<string> $expected
     * @param array<int|string, null|false|string> $envVars
     *
     * @dataProvider casesEnvVarsToExpressions
     */
    public function testEnvVarsToExpressions(array $expected, array $envVars): void
    {
        $utils = $this->createUtils();

        $this->tester->assertSame($expected, $utils->envVarsToExpressions($envVars));
    }

    /**
     * @return array<string, mixed>
     */
    public function casesNormalizeCommaSeparatedList(): array
    {
        return [
            'string empty' => [
                [],
                '',
            ],
            'string single' => [
                ['a'],
                'a',
            ],
            'string single trailing comma' => [
                ['a'],
                'a,',
            ],
            'string multi' => [
                ['a', 'b', 'c'],
                'a, b, c',
            ],

            'all-in-one' => [
                ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'],
                ['a, b, c', 'd,', ' e ,', ',,f, ,', ['g,h']],
            ],
        ];
    }

    /**
     * @param array<string> $expected
     * @param mixed $items
     *
     * @dataProvider casesNormalizeCommaSeparatedList
     */
    public function testNormalizeCommaSeparatedList(array $expected, $items): void
    {
        $utils = $this->createUtils();

        $this->tester->assertEquals(
            $expected,
            $utils->normalizeCommaSeparatedList($items)
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function casesIoInstanceOptions(): array
    {
        return [
            'empty' => [
                [],
                [],
            ],
            'basic' => [
                [
                    '7-nts' => '7-nts 7.1.2 NTS cns esns',
                    '8-zts' => '8-zts 8.3.4 ZTS',
                ],
                [
                    'instances' => [
                        '7-nts' => [
                            'key' => '7-nts',
                            'coreVersion' => '7.1.2',
                            'isZts' => false,
                            'coreNameSuffix' => 'cns',
                            'extensionSetNameSuffix' => 'esns',
                        ],
                        '8-hidden' => [
                            'key' => '8-hidden',
                            'coreVersion' => '8.5.8',
                            'hidden' => true,
                        ],
                        '8-zts' => [
                            'key' => '8-zts',
                            'coreVersion' => '8.3.4',
                            'isZts' => true,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array<string, string> $expected
     * @param array<string, mixed> $configLayer
     *
     * @dataProvider casesIoInstanceOptions
     */
    public function testIoInstanceOptions(array $expected, array $configLayer): void
    {
        $utils = $this->createUtils(null, $configLayer);
        $config = $utils->getConfig();
        $pmkr = PmkrConfig::__set_state([
            'config' => $config,
            'configPath' => [],
        ]);
        $instances = $pmkr->instances;
        $this->tester->assertSame($expected, $utils->ioInstanceOptions($instances));
    }

    public function testGetInputValue(): void
    {
        $utils = $this->createUtils();
        $parameters = [
            'my_arg_1' => 'my_arg_1_value',
            '--my-option-1' => 'my_option_1_value',
        ];
        $inputDefinition = new InputDefinition();
        $inputDefinition->addOption(new InputOption(
            'my-option-1',
            null,
            InputOption::VALUE_REQUIRED,
        ));
        $inputDefinition->addArgument(new InputArgument(
            'my_arg_1',
            InputArgument::REQUIRED,
        ));
        $input = new ArrayInput($parameters, $inputDefinition);

        $locator = 'arg.my_arg_1';
        $this->tester->assertSame('my_arg_1_value', $utils->getInputValue($input, $locator));
        $utils->setInputValue($input, $locator, 'my_arg_1_new');
        $this->tester->assertSame('my_arg_1_new', $utils->getInputValue($input, $locator));

        $locator = 'option.my-option-1';
        $this->tester->assertSame('my_option_1_value', $utils->getInputValue($input, $locator));
        $utils->setInputValue($input, $locator, 'my_option_1_new');
        $this->tester->assertSame('my_option_1_new', $utils->getInputValue($input, $locator));
    }

    public function testGetProcessCallback(): void
    {
        $stdError = new BufferedOutput();
        $output = new ConsoleOutput();
        $output->setErrorOutput($stdError);

        $utils = $this->createUtils();
        $callback = $utils->getProcessCallback($output);
        $callback(Process::OUT, "stdOutput line 1\n");
        $callback(Process::ERR, "stdError line 1\n");

        $this->tester->assertSame("stdOutput line 1\n", $output->fetch());
        $this->tester->assertSame("stdError line 1\n", $output->getErrorOutput()->fetch());
    }

    public function testReplaceFileExtension(): void
    {
        $utils = $this->createUtils();
        $this->tester->assertSame('composer.lock', $utils->replaceFileExtension('composer.json', 'lock'));
    }

    public function testBoolToCompareDirection(): void
    {
        $utils = $this->createUtils();
        $this->tester->assertSame(
            ComparerInterface::DIR_ASCENDING,
            $utils->boolToCompareDirection(false),
        );
        $this->tester->assertSame(
            ComparerInterface::DIR_DESCENDING,
            $utils->boolToCompareDirection(true),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function casesSplitLines(): array
    {
        return [
            'empty' => [
                [],
                '',
            ],
            'only space' => [
                [
                    '  ',
                ],
                '  ',
            ],
            'one empty line \n' => [
                [
                    '',
                ],
                "\n",
            ],
            'multiple empty lines \n' => [
                [
                    '',
                    '',
                    '',
                ],
                "\n\n\n",
            ],
            'one empty line \r' => [
                [
                    '',
                ],
                "\r",
            ],
            'multiple empty lines \r' => [
                [
                    '',
                    '',
                    '',
                ],
                "\r\r\r",
            ],
            'one empty line \r\n' => [
                [
                    '',
                ],
                "\r\n",
            ],
            'multiple empty lines \r\n' => [
                [
                    '',
                    '',
                    '',
                ],
                "\r\n\r\n\r\n",
            ],
            'multiple empty lines mixed 1' => [
                [
                    'a',
                    'b',
                    '',
                    'c',
                ],
                "a\nb\n\nc\n",
            ],
            'multiple empty lines mixed 2' => [
                [
                    'a',
                    'b',
                    'c',
                    '',
                ],
                "a\nb\nc\n\n",
            ],
        ];
    }

    /**
     * @param array<string> $expected
     *
     * @dataProvider casesSplitLines
     */
    public function testSplitLines(array $expected, string $lines): void
    {
        $utils = $this->createUtils();
        $this->tester->assertSame($expected, $utils->splitLines($lines));
    }

    public function testNormalizeBooleanMapping(): void
    {
        $utils = $this->createUtils();

        $expected = [];
        $mapping = [];
        $this->tester->assertSame($expected, $utils->normalizeBooleanMapping($mapping));

        $expected = [
            'a' => true,
            'b' => false,
            'c-value' => true,
        ];
        $mapping = [
            'a' => true,
            'b' => false,
            'c-key' => 'c-value',
        ];
        $this->tester->assertSame($expected, $utils->normalizeBooleanMapping($mapping));
    }

    /**
     * @return array<string, mixed>
     */
    public function casesFetchPackageDependenciesFromInstanceCore(): array
    {
        $configLayer = [
            'cores' => [
                '0704-nts' => [
                    'key' => '0704-nts',
                    'dependencies' => [
                        'packages' => [
                            'opensuse-tumbleweed' => [
                                'autoconf' => true,
                                'bison' => true,
                                'vim' => false,
                                'cmake' => true,
                                'foo-bar' => 'foo.bar',
                            ],
                        ],
                    ],
                ],
            ],
            'extensions' => [],
            'extensionSets' => [
                '0704-nts' => [],
            ],
            'instances' => [
                'instance-01' => [
                    'key' => 'instance-01',
                    'coreVersion' => '7.4.27',
                ],
            ],
        ];

        return [
            'empty' => [
                [],
                $configLayer,
                'instance-01',
                'ubuntu-21-10',
            ],
            'basic' => [
                [
                    'autoconf' => true,
                    'bison' => true,
                    'cmake' => true,
                    'foo.bar' => true,
                ],
                $configLayer,
                'instance-01',
                'opensuse-tumbleweed',
            ],
        ];
    }

    /**
     * @param array<string, bool> $expected
     * @param array<string, mixed> $configLayer
     *
     * @dataProvider casesFetchPackageDependenciesFromInstanceCore
     */
    public function testFetchPackageDependenciesFromInstanceCore(
        array $expected,
        array $configLayer,
        string $instanceName,
        string $opSysIdentifier
    ): void {
        $utils = $this->createUtils(null, $configLayer);
        $pmkr = PmkrConfig::__set_state([
            'config' => $utils->getConfig(),
            'configPath' => [],
        ]);
        $instance = $pmkr->instances[$instanceName];
        $opSys = $this->tester->grabOpSys($opSysIdentifier);
        $actual = $utils->fetchPackageDependenciesFromInstanceCore($opSys, $instance);
        $this->tester->assertSame($expected, $actual);
    }

    /**
     * @return array<string, mixed>
     */
    public function casesFetchPackageDependenciesFromInstanceExtensions(): array
    {
        $configLayer = [
            'cores' => [
                '0704-nts' => [
                    'key' => '0704-nts',
                    'dependencies' => [
                        'packages' => [
                            'opensuse-tumbleweed' => [
                                'autoconf' => true,
                                'bison' => true,
                                'vim' => false,
                                'cmake' => true,
                                'foo-bar' => 'foo.bar',
                            ],
                        ],
                    ],
                ],
            ],
            'extensions' => [
                'dom-0704' => [
                    'dependencies' => [
                        'packages' => [
                            'opensuse-tumbleweed' => [
                                'libxml2-2' => true,
                                'libxml2-devel' => true,
                            ],
                        ],
                    ],
                ],
                '0704-nts' => [
                    'dependencies' => [
                        'packages' => [
                            'opensuse-tumbleweed' => [
                                'freetype2-devel' => true,
                            ],
                        ],
                    ],
                ],
            ],
            'extensionSets' => [
                '0704-nts' => [
                    'dom-0704' => [
                        'status' => 'enabled',
                    ],
                    'gd-0704' => [
                        'status' => 'optional',
                    ],
                ],
            ],
            'instances' => [
                'instance-01' => [
                    'key' => 'instance-01',
                    'coreVersion' => '7.4.27',
                ],
            ],
        ];

        return [
            'empty' => [
                [],
                $configLayer,
                'instance-01',
                'ubuntu-21-10',
                null,
            ],
            'basic' => [
                [
                    'libxml2-2' => true,
                    'libxml2-devel' => true,
                ],
                $configLayer,
                'instance-01',
                'opensuse-tumbleweed',
                [
                    'status' => ['enabled'],
                ],
            ],
        ];
    }

    /**
     * @param array<string, bool> $expected
     * @param array<string, mixed> $configLayer
     * @param ?array<string, mixed> $filterOptions
     *
     * @dataProvider casesFetchPackageDependenciesFromInstanceExtensions
     */
    public function testFetchPackageDependenciesFromInstanceExtensions(
        array $expected,
        array $configLayer,
        string $instanceName,
        string $opSysIdentifier,
        ?array $filterOptions
    ): void {
        $utils = $this->createUtils(null, $configLayer);
        $pmkr = PmkrConfig::__set_state([
            'config' => $utils->getConfig(),
            'configPath' => [],
        ]);
        $instance = $pmkr->instances[$instanceName];
        $opSys = $this->tester->grabOpSys($opSysIdentifier);
        $extensionFilter = null;
        if ($filterOptions !== null) {
            $extensionFilter = new PhpExtensionFilter();
            $extensionFilter->setOptions($filterOptions);
        }
        $actual = $utils->fetchPackageDependenciesFromInstanceExtensions(
            $opSys,
            $instance,
            $extensionFilter,
        );
        $this->tester->assertSame($expected, $actual);
    }

    /**
     * @return array<string, mixed>
     */
    public function casesFetchLibraryKeys(): array
    {
        return [
            'empty' => [
                [],
                'opensuse-tumbleweed',
                [
                    'ubuntu-21-10' => [
                        'icu4c-59_1' => true,
                    ],
                ],
            ],
            'basic' => [
                [
                    'icu4c-59_1' => true,
                    'bar' => true,
                ],
                'opensuse-tumbleweed',
                [
                    'ubuntu-21-10' => [
                        'other' => true,
                    ],
                    'opensuse-tumbleweed' => [
                        'icu4c-59_1' => true,
                        'foo' => false,
                        'bar' => true,
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array<string, bool> $expected
     * @param array<string, array<string, bool>> $libraryReferences
     *
     * @dataProvider casesFetchLibraryKeys
     */
    public function testFetchLibraryKeys(
        array $expected,
        string $opSysIdentifier,
        array $libraryReferences
    ): void {
        $utils = $this->createUtils();
        $opSys = $this->tester->grabOpSys($opSysIdentifier);
        $actual = $utils->fetchLibraryKeys($opSys, $libraryReferences);
        $this->tester->assertSame($expected, $actual);
    }

    /**
     * @return array<string, mixed>
     */
    public function casesValidateInstanceBinary(): array
    {
        return [
            'empty' => [
                [
                    'Must contain at least one alpha-numeric character.'
                ],
                '',
            ],
            'whitespace' => [
                [
                    'Must contain at least one alpha-numeric character.'
                ],
                ' ',
            ],
            'invalid /' => [
                [
                    'Must not contain any "/" or "\" characters.',
                ],
                '/',
            ],
            'invalid \\' => [
                [
                    'Must not contain any "/" or "\" characters.',
                ],
                '\\',
            ],
            'invalid foo/../bar' => [
                [
                    'Must not contain any "/" or "\" characters.',
                ],
                'foo/../bar',
            ],
            'valid php' => [
                [],
                'php',
            ],
        ];
    }

    /**
     * @param array<string> $expected
     *
     * @dataProvider casesValidateInstanceBinary
     */
    public function testValidateInstanceBinary(array $expected, string $binary): void
    {
        $utils = $this->createUtils();

        $this->tester->assertSame(
            $expected,
            $utils->validateInstanceBinary($binary),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function casesGetOnlyChildDir(): array
    {
        return [
            'empty' => [
                null,
                [
                    'foo' => [],
                ],
                'foo',
            ],
            '1 file' => [
                null,
                [
                    'foo' => [
                        'bar' => 'file content',
                    ],
                ],
                'foo',
            ],
            '1 dir; 1 file' => [
                null,
                [
                    'foo' => [
                        'my_dir_1' => [],
                        'my_file_1' => 'file content',
                    ],
                ],
                'foo',
            ],
            '1 dir' => [
                'my_dir_1',
                [
                    'foo' => [
                        'my_dir_1' => [],
                    ],
                ],
                'foo',
            ],
        ];
    }

    /**
     * @param array<string, string|array<string>> $vfsStructure
     *
     * @dataProvider casesGetOnlyChildDir
     */
    public function testGetOnlyChildDir(?string $expected, array $vfsStructure, string $dir): void
    {
        $vfs = vfsStream::setup(
            'root',
            0777,
            [
                __FUNCTION__ => $vfsStructure,
            ],
        );

        $dir = $vfs->url() . '/' . __FUNCTION__ . '/' . $dir;

        $utils = $this->createUtils();
        $actual = $utils->getOnlyChildDir($dir);
        if ($expected === null) {
            $this->tester->assertNull($actual);

            return;
        }

        $this->tester->assertNotNull($actual);
        $this->tester->assertSame($expected, $actual->getRelativePathname());
    }
}
