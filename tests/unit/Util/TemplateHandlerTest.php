<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Tests\Unit\Util;

use Pmkr\Pmkr\Tests\UnitTester;
use Codeception\Test\Unit;
use Pmkr\Pmkr\Util\TemplateHelper;
use Pmkr\Pmkr\Utils;
use Sweetchuck\Utils\VersionNumber;

/**
 * @covers \Pmkr\Pmkr\Util\TemplateHelper
 */
class TemplateHandlerTest extends Unit
{
    protected UnitTester $tester;

    public function casesRenderExamplePmkr(): array
    {
        $selfRoot = dirname(codecept_data_dir(), 2);
        $phpBinary = \PHP_BINARY;

        return [
            'basic' => [
                implode("\n", [
                    '#!/usr/bin/env my_shell',
                    '',
                    'PMKR_WRAPPER_PHPRC="${PHPRC}" \\',
                    'PMKR_WRAPPER_PHP_INI_SCAN_DIR="${PHP_INI_SCAN_DIR}" \\',
                    "'$phpBinary' \\",
                    "    '$selfRoot/bin/pmkr' \\",
                    '    $@',
                    '',
                ]),
                [
                    'shell' => 'my_shell',
                ],
            ],
            'with envVars' => [
                implode("\n", [
                    '#!/usr/bin/env my_shell',
                    '',
                    'PMKR_WRAPPER_PHPRC="${PHPRC}" \\',
                    'PMKR_WRAPPER_PHP_INI_SCAN_DIR="${PHP_INI_SCAN_DIR}" \\',
                    "PHPRC='/my/php.ini' \\",
                    "PHP_INI_SCAN_DIR='/my/php/etc/conf.d:/foo' \\",
                    "'my_php' \\",
                    "    '/my/path/to/pmkr-root/bin/pmkr' \\",
                    '    $@',
                    '',
                ]),
                [
                    'shell' => 'my_shell',
                    'envVars' => [
                        "PHPRC='/my/php.ini'",
                        "PHP_INI_SCAN_DIR='/my/php/etc/conf.d:/foo'",
                    ],
                    'phpBinary' => 'my_php',
                    'pmkrRoot' => '/my/path/to/pmkr-root',
                ],
            ],
        ];
    }

    /**
     * @dataProvider casesRenderExamplePmkr
     */
    public function testRenderExamplePmkr(string $expected, array $context)
    {
        $config = $this->tester->grabConfig();
        $this->tester->assertSame('/home/me', $config->get('env.HOME'));

        $utils = new Utils($config);
        $twig = $this->tester->grabTwig();
        $templateHandler = new TemplateHelper($config, $utils, $twig);

        $this->tester->assertSame(
            $expected,
            $templateHandler->renderExamplePmkr($context),
        );
    }

    public function testRenderExampleZplugEntry()
    {
        $config = $this->tester->grabConfig();
        $utils = new Utils($config);
        $twig = $this->tester->grabTwig();
        $templateHandler = new TemplateHelper($config, $utils, $twig);

        $this->tester->assertSame(
            implode(
                "\n",
                [
                    "zplug '/home/me/.zplug/repos/pmkrrc', \\",
                    '    from:local, \\',
                    "    use:'pmkrrc.zsh'",
                    '',
                ],
            ),
            $templateHandler->renderExampleZplugEntry(),
        );
    }

    public function testRenderExampleZplugPluginPmkrrc()
    {
        $config = $this->tester->grabConfig();
        $utils = new Utils($config);
        $twig = $this->tester->grabTwig();
        $templateHandler = new TemplateHelper($config, $utils, $twig);

        $this->tester->assertSame(
            implode(
                "\n",
                [
                    '#!/usr/bin/env zsh',
                    '',
                    'autoload -U add-zsh-hook',
                    '',
                    'eval "$(pmkr --no-ansi instance:pick:default --format=\'shell-var-setter\' --soft)"',
                    '',
                    'function _pmkr_on_hook_chpwd {',
                    "    if [[ -s './.pmkrrc.zsh' ]]; then",
                    "        . './.pmkrrc.zsh'",
                    '    fi',
                    '}',
                    '',
                    'add-zsh-hook chpwd _pmkr_on_hook_chpwd',
                    '',
                ],
            ),
            $templateHandler->renderExampleZplugPluginPmkrRc(),
        );
    }

    public function testRenderExamplePmkrRc()
    {
        $config = $this->tester->grabConfig();
        $utils = new Utils($config);
        $twig = $this->tester->grabTwig();
        $templateHandler = new TemplateHelper($config, $utils, $twig);

        $this->tester->assertSame(
            implode(
                "\n",
                [
                    '#!/usr/bin/env tesh',
                    '',
                    'eval "$(pmkr instance:pick:project --no-ansi --format=\'shell-var-setter\')"',
                    '',
                ],
            ),
            $templateHandler->renderExamplePmkrRc(),
        );
    }

    public function testRenderExampleInstance()
    {
        $config = $this->tester->grabConfig();
        $utils = new Utils($config);
        $twig = $this->tester->grabTwig();
        $templateHandler = new TemplateHelper($config, $utils, $twig);

        $context = [
            'coreVersion' => VersionNumber::createFromString('7.4.27'),
        ];

        $this->tester->assertSame(
            implode(
                "\n",
                [
                    'instances:',
                    "    '070427-nts': &instance_070427",
                    '        isZts: false',
                    "        coreVersion: '7.4.27'",
                    '        coreChecksum:',
                    "            hashAlgorithm: 'sha256'",
                    "            hashChecksum: '@todo'",
                    "    '070427-zts':",
                    '        isZts: true',
                    '        <<: *instance_070427',
                    "    '070427-nts-none':",
                    '        hidden: true',
                    "        extensionSetNameSuffix: 'none'",
                    '        <<: *instance_070427',
                    '',
                ],
            ),
            $templateHandler->renderExampleInstance($context),
        );
    }
}
