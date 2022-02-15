<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Tests\Helper;

use Codeception\Module;
use Consolidation\Config\ConfigInterface;
use Consolidation\Config\Loader\ConfigProcessor;
use Pmkr\Pmkr\OpSys\OpSys;
use Robo\Config\Config;
use Twig\Cache\NullCache;
use Twig\Environment as TwigEnvironment;
use Twig\Loader\FilesystemLoader as TwigFilesystemLoader;

class Unit extends Module
{

    /**
     * @return array<string, mixed>
     */
    public function grabConfigLayerDefault(): array
    {
        return [
            'env' => [
                'USER' => 'me',
                'HOME' => '/home/me',
                'SHELL' => '/bin/tesh',
            ],
            'app' => [
                'name' => 'pmkr',
            ],
            'dir' => [
                'bin' => '${dir.usr}/bin',
                'sbin' => '${dir.usr}/sbin',
                'share' => '${dir.usr}/share',
                'src' => '${dir.usr}/src',
                'usr' => '${env.HOME}/slash/usr',
                'cache' => '${env.HOME}/.cache/${app.name}',
            ],
        ];
    }

    /**
     * @param null|array<string, mixed> $defaults
     * @param array<string, mixed> ...$configLayers
     *
     * @return \Consolidation\Config\ConfigInterface
     */
    public function grabConfig(?array $defaults = null, ...$configLayers): ConfigInterface
    {
        if ($defaults === null) {
            array_unshift($configLayers, $this->grabConfigLayerDefault());
        }

        $configProcessor = new ConfigProcessor();
        foreach ($configLayers as $configLayer) {
            $configProcessor->add($configLayer);
        }

        return new Config($configProcessor->export());
    }

    public function grabOpSys(string $identifier): OpSys
    {
        switch ($identifier) {
            case 'opensuse-tumbleweed':
                $state = [
                    'NAME' => 'openSUSE Tumbleweed',
                    '# VERSION' => '20211222',
                    'ID' => 'opensuse-tumbleweed',
                    'ID_LIKE' => 'opensuse suse',
                    'VERSION_ID' => '20211222',
                    'PRETTY_NAME' => 'openSUSE Tumbleweed',
                    'ANSI_COLOR' => '0;32',
                    'CPE_NAME' => 'cpe:/o:opensuse:tumbleweed:20211222',
                    'BUG_REPORT_URL' => 'https://bugs.opensuse.org',
                    'HOME_URL' => 'https://www.opensuse.org/',
                    'DOCUMENTATION_URL' => 'https://en.opensuse.org/Portal:Tumbleweed',
                    'LOGO' => 'distributor-logo-Tumbleweed',
                ];
                break;

            case 'ubuntu-21-10':
                $state = [
                    'PRETTY_NAME' => 'Ubuntu 21.10',
                    'NAME' => 'Ubuntu',
                    'VERSION_ID' => '21.10',
                    'VERSION' => '21.10 (Impish Indri)',
                    'VERSION_CODENAME' => 'impish',
                    'ID' => 'ubuntu',
                    'ID_LIKE' => 'debian',
                    'HOME_URL' => 'https://www.ubuntu.com/',
                    'SUPPORT_URL' => 'https://help.ubuntu.com/',
                    'BUG_REPORT_URL' => 'https://bugs.launchpad.net/ubuntu/',
                    'PRIVACY_POLICY_URL' => 'https://www.ubuntu.com/legal/terms-and-policies/privacy-policy',
                    'UBUNTU_CODENAME' => 'impish',
                ];
                break;

            default:
                $state = [];
                break;
        }

        return OpSys::__set_state($state);
    }

    public function grabSelfRoot(): string
    {
        return dirname(__DIR__, 3);
    }

    public function grabTwig(): TwigEnvironment
    {
        $root = $this->grabSelfRoot();

        $pmkrEtcTemplatesDir = "$root/resources/home/templates";
        $twigLoader = new TwigFilesystemLoader(
            [
                $pmkrEtcTemplatesDir,
            ],
            $pmkrEtcTemplatesDir,
        );

        $twig = new TwigEnvironment($twigLoader);
        $twig->setCache(new NullCache());

        return $twig;
    }
}
