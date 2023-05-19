<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Tests\Unit\Util;

use Codeception\Attribute\DataProvider;
use org\bovigo\vfs\vfsStream;
use Pmkr\Pmkr\Util\InstanceCollector;
use Codeception\Test\Unit;
use Pmkr\Pmkr\Tests\UnitTester;
use Symfony\Component\Finder\SplFileInfo;

/**
 * @covers \Pmkr\Pmkr\Util\InstanceCollector
 */
class InstanceCollectorTest extends Unit
{

    protected UnitTester $tester;

    public function testCollect(): void
    {
        $vfsStructure = [
            'share' => [
                'other-1' => [],
                'pmkr-php-a-b' => [],
                'pmkr-php-c-d' => [],
                'other-2' => [],
            ],
        ];
        $vfsRoot = vfsStream::setup(
            __FUNCTION__,
            null,
            $vfsStructure,
        );
        $parentDir = $vfsRoot->url() . '/share';

        $ic = new InstanceCollector();
        $expected = [
            'a-b' => new SplFileInfo("$parentDir/pmkr-php-a-b", '', 'pmkr-php-a-b'),
            'c-d' => new SplFileInfo("$parentDir/pmkr-php-c-d", '', 'pmkr-php-c-d'),
        ];
        $this->tester->assertEquals(
            $expected,
            $ic->collect($parentDir),
        );
    }

    public function testCollectOrphans(): void
    {
        $vfsStructure = [
            'usr' => [
                'src' => [
                    'other-1' => [],
                    'pmkr-php-a' => [],
                    'pmkr-php-c' => [],
                    'other-2' => [],
                ],
                'share' => [
                    'other-1' => [],
                    'pmkr-php-a' => [],
                    'pmkr-php-d' => [],
                    'other-2' => [],
                ],
            ],
        ];
        $vfsRoot = vfsStream::setup(
            __FUNCTION__,
            null,
            $vfsStructure,
        );
        $rootDir = $vfsRoot->url();

        $configLayer = [
            'dir' => [
                'bin' => "$rootDir/usr/bin",
                'sbin' => "$rootDir/usr/sbin",
                'share' => "$rootDir/usr/share",
                'src' => "$rootDir/usr/src",
                'usr' => "$rootDir/usr",
                'cache' => '${env.HOME}/.cache/${app.name}',
            ],
            'instances' => [
                'a' => [
                    'key' => 'a',
                ],
            ],
        ];
        $config = $this->tester->grabConfig(null, $configLayer);

        $ic = new InstanceCollector();
        $expected = [
            'c' => [
                'src' => "$rootDir/usr/src/pmkr-php-c",
            ],
            'd' => [
                'share' => "$rootDir/usr/share/pmkr-php-d",
            ],
        ];

        $this->tester->assertSame(
            $expected,
            $ic->collectOrphans($config),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function casesFlattenOrphanDirs(): array
    {
        return [
            'empty' => [
                [],
                [],
            ],
            'basic' => [
                [
                    'a/src',
                    'a/share',
                    'b/src',
                    'c/share',
                ],
                [
                    'a' => [
                        'src' => 'a/src',
                        'share' => 'a/share',
                    ],
                    'b' => [
                        'src' => 'b/src',
                    ],
                    'c' => [
                        'share' => 'c/share',
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array<string> $expected
     * @param array<string, mixed> $orphans
     */
    #[DataProvider('casesFlattenOrphanDirs')]
    public function testFlattenOrphanDirs(array $expected, array $orphans): void
    {
        $ic = new InstanceCollector();
        $this->tester->assertSame(
            $expected,
            $ic->flattenOrphanDirs($orphans),
        );
    }
}
