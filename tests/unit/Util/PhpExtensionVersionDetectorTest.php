<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Tests\Unit\Util;

use Codeception\Test\Unit;
use org\bovigo\vfs\vfsStream;
use Pmkr\Pmkr\Tests\UnitTester;
use Pmkr\Pmkr\Util\PhpExtensionVersionDetector;
use Sweetchuck\Utils\VersionNumber;

class PhpExtensionVersionDetectorTest extends Unit
{
    protected UnitTester $tester;

    /**
     * @return array<string, mixed>
     */
    public function casesDetect(): array
    {
        return [
            'success PHP_VERSION lib.h' => [
                'PHP_VERSION',
                [
                    'ext' => [
                        'sodium' => [
                            'php_libsodium.h' => implode("\n", [
                                'foo',
                                '#define PHP_SODIUM_VERSION PHP_VERSION',
                                'bar',
                                '',
                            ]),
                        ],
                    ],
                ],
                '7.4.0',
                'ext/sodium',
                'sodium',
            ],
            'success PHP_VERSION normal.h' => [
                'PHP_VERSION',
                [
                    'ext' => [
                        'tidy' => [
                            'php_tidy.h' => implode("\n", [
                                'foo',
                                '#define PHP_TIDY_VERSION PHP_VERSION',
                                'bar',
                                '',
                            ]),
                        ],
                    ],
                ],
                '7.4.0',
                'ext/tidy',
                null,
            ],
            'success 1.2.3 normal.h' => [
                '1.2.3',
                [
                    'ext' => [
                        'normal' => [
                            'php_normal.h' => implode("\n", [
                                'foo',
                                '#define PHP_NORMAL_VERSION "1.2.3"',
                                'bar',
                                '',
                            ]),
                        ],
                    ],
                ],
                '7.4.0',
                'ext/normal',
                'normal',
            ],
            'success 1.2.3 zip.h' => [
                'PHP_VERSION',
                [
                    'ext' => [
                        'zip' => [
                            'php_zip.h' => implode("\n", [
                                'foo',
                                '#define PHP_ZIP_VERSION "1.2.3"',
                                'bar',
                                '',
                            ]),
                        ],
                    ],
                ],
                '7.4.0',
                'ext/zip',
                'zip',
            ],
            'success not found' => [
                null,
                [
                    'ext' => [
                        'my01' => [
                            'php_my01.h' => implode("\n", [
                                'foo',
                                'bar',
                                '',
                            ]),
                        ],
                    ],
                ],
                '7.4.0',
                'ext/my01',
                'my01',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $vfsStructure
     *
     * @dataProvider casesDetect
     */
    public function testDetect(
        ?string $expected,
        array $vfsStructure,
        string $coreVersion,
        string $dir,
        ?string $name
    ): void {
        $version = VersionNumber::createFromString($coreVersion);

        $vfsRoot = vfsStream::setup(
            __FUNCTION__,
            null,
            $vfsStructure,
        );
        $dir = $vfsRoot->url() . "/$dir";

        $detector = new PhpExtensionVersionDetector();
        $this->tester->assertSame($expected, $detector->detect($version, $dir, $name));
    }
}
