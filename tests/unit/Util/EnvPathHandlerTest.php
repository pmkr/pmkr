<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Tests\Unit\Util;

use Codeception\Attribute\DataProvider;
use Pmkr\Pmkr\Model\Instance;
use Pmkr\Pmkr\Tests\UnitTester;
use Pmkr\Pmkr\Util\EnvPathHandler;
use Codeception\Test\Unit;

/**
 * @covers \Pmkr\Pmkr\Util\EnvPathHandler
 */
class EnvPathHandlerTest extends Unit
{
    protected UnitTester $tester;

    public function testPathSeparator(): void
    {
        $config = $this->tester->grabConfig(null);
        $handler = new EnvPathHandler($config);
        $this->tester->assertSame(\PATH_SEPARATOR, $handler->getPathSeparator());
    }

    /**
     * @return array<string, mixed>
     */
    public function casesRemove(): array
    {
        return [
            'empty' => [
                '',
                '',
            ],
            'basic' => [
                '/b:/a',
                implode(':', [
                    '/b',
                    '/home/me/slash/usr/share/pmkr-php-foo/sbin',
                    '/home/me/slash/usr/share/pmkr-php-foo/bin',
                    '/a',
                ]),
            ],
        ];
    }

    #[DataProvider('casesRemove')]
    public function testRemove(string $expected, string $envPath): void
    {
        $config = $this->tester->grabConfig(null);
        $handler = new EnvPathHandler($config);
        $handler->setPathSeparator(':');
        $this->tester->assertEquals($expected, $handler->remove($envPath));
    }

    /**
     * @return array<string, mixed>
     */
    public function casesOverride(): array
    {
        return [
            'empty' => [
                implode(':', [
                    '/home/me/slash/usr/share/pmkr-php-foo/bin',
                    '/home/me/slash/usr/share/pmkr-php-foo/sbin',
                ]),
                '',
            ],
            'basic' => [
                implode(':', [
                    '/a',
                    '/home/me/slash/usr/share/pmkr-php-foo/bin',
                    '/home/me/slash/usr/share/pmkr-php-foo/sbin',
                    '/b',
                    '/c',
                ]),
                implode(':', [
                    '/a',
                    '/home/me/slash/usr/share/pmkr-php-05/bin',
                    '/home/me/slash/usr/share/pmkr-php-05/sbin',
                    '/b',
                    '/home/me/slash/usr/share/pmkr-php-07/bin',
                    '/home/me/slash/usr/share/pmkr-php-07/sbin',
                    '/c',
                ]),
            ],
        ];
    }

    #[DataProvider('casesOverride')]
    public function testOverride(string $expected, string $envPath): void
    {
        $config = $this->tester->grabConfig(
            null,
            [
                'instances' => [
                    'foo' => [
                        'key' => 'foo',
                    ],
                ],
            ],
        );
        $instance = Instance::__set_state([
            'config' => $config,
            'configPath' => ['instances', 'foo'],
        ]);
        $handler = new EnvPathHandler($config);
        $handler->setPathSeparator(':');
        $this->tester->assertSame($expected, $handler->override($envPath, $instance));
    }

    /**
     * @return array<string, mixed>
     */
    public function casesGetCurrentInstanceName(): array
    {
        return [
            'not-found' => [
                null,
                '/a:/b',
            ],
            'first' => [
                'first',
                implode(':', [
                    '/a',
                    '/home/me/slash/usr/share/pmkr-php-first/bin',
                    '/home/me/slash/usr/share/pmkr-php-second/bin',
                ]),
            ],
        ];
    }

    #[DataProvider('casesGetCurrentInstanceName')]
    public function testGetCurrentInstanceName(?string $expected, string $envPath): void
    {
        $config = $this->tester->grabConfig(null, []);
        $handler = new EnvPathHandler($config);
        $handler->setPathSeparator(':');
        $this->tester->assertSame($expected, $handler->getCurrentInstanceName($envPath));
    }
}
