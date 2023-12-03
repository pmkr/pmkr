<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Commands;

use GuzzleHttp\ClientInterface as HttpClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Pmkr\Pmkr\CodeResult\CodeCommandResult;
use Pmkr\Pmkr\CodeResult\CodeResult;
use Pmkr\Pmkr\Util\ShellHelper;
use Pmkr\Pmkr\Util\TemplateHelper;
use Sweetchuck\Utils\StringUtils;
use Sweetchuck\Utils\VersionNumber;

class ExampleCommand extends CommandBase
{
    protected StringUtils $stringUtils;

    protected TemplateHelper $templateHelper;

    protected ShellHelper $shellHelper;

    protected HttpClientInterface $httpClient;

    protected function initDependencies(): static
    {
        if ($this->initialized) {
            return $this;
        }

        parent::initDependencies();
        $container = $this->getContainer();
        $this->stringUtils = $container->get('sweetchuck.string_utils');
        $this->templateHelper = $container->get('pmkr.template_helper');
        $this->shellHelper = $container->get('pmkr.shell_helper');
        $this->httpClient = $container->get('http_client');

        return $this;
    }

    /**
     * @param mixed[] $options
     *
     * @command example:pmkr
     */
    public function cmdExamplePmkrExecute(
        array $options = [
            'format' => 'code',
        ],
    ): CodeCommandResult {
        $context = [
            'envVars' => $this->shellHelper->collectPhpIniPaths(\PHP_BINARY),
        ];

        $result = new CodeResult();
        $result->language = 'zsh';
        $result->code = $this->templateHelper->renderExamplePmkr($context);

        return CodeCommandResult::data($result);
    }

    /**
     * @param mixed[] $options
     *
     * @command example:zplug-plugin-pmkrrc
     */
    public function cmdExampleZplugPluginPmkrRcExecute(
        array $options = [
            'format' => 'code',
        ],
    ): CodeCommandResult {
        $result = new CodeResult();
        $result->language = 'zsh';
        $result->code = $this->templateHelper->renderExampleZplugPluginPmkrRc();

        return CodeCommandResult::data($result);
    }

    /**
     * Outputs an example code to add to your `~/.zshrc`.
     *
     * @param mixed[] $options
     *
     * @command example:zplug-entry
     */
    public function cmdExampleZplugEntryExecute(
        string $reposDir = '',
        array $options = [
            'format' => 'code',
        ],
    ): CodeCommandResult {
        $context = [];
        if ($reposDir !== '') {
            $context['reposDir'] = $reposDir;
        }

        $result = new CodeResult();
        $result->language = 'zsh';
        $result->code = $this->templateHelper->renderExampleZplugEntry($context);

        return CodeCommandResult::data($result);
    }

    /**
     * @command example:rc
     *
     * @option string $format
     *   Default: code
     */
    public function cmdExamplePmkrRcExecute(): CodeCommandResult
    {
        $result = new CodeResult();
        $result->language = 'zsh';
        $result->code = $this->templateHelper->renderExamplePmkrRc();

        return CodeCommandResult::data($result);
    }

    /**
     * @command example:instance
     *
     * @option string $format
     *   Default: code
     *
     * @usage 8.1.4
     */
    public function cmdExampleInstanceExecute(string $coreVersion): CodeCommandResult
    {
        $releaseInfo = $this->getPhpNetReleaseInfo($coreVersion);

        $context = [
            'coreVersion' => VersionNumber::createFromString($coreVersion),
            'hashChecksum' => $releaseInfo['source']['tar.bz2']['sha256'] ?? '',
        ];

        $result = new CodeResult();
        $result->language = 'yaml';
        $result->code = $this->templateHelper->renderExampleInstance($context);

        return CodeCommandResult::data($result);
    }

    /**
     * @return null|php-net-release-info
     */
    protected function getPhpNetReleaseInfo(string $coreVersion): ?array
    {
        $uri = $this->stringUtils->buildUri([
            'scheme' => 'https',
            'host' => 'www.php.net',
            'path' => '/releases',
            'query' => [
                'json' => '',
                'version' => $coreVersion,
            ],
        ]);
        try {
            $response = $this->httpClient->request('GET', $uri);
            $info = json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            return null;
        }

        return $this->processPhpNetReleaseInfo($info);
    }

    /**
     * @param array<string, mixed> $info
     *
     * @return php-net-release-info
     */
    protected function processPhpNetReleaseInfo(array $info): array
    {
        $prefixLength = mb_strlen("php-{$info['version']}.");
        foreach (array_keys($info['source']) as $key) {
            $source = $info['source'][$key];
            unset($info['source'][$key]);
            $type = mb_substr($source['filename'], $prefixLength);
            $info['source'][$type] = $source;
        }

        /** @phpstan-var php-net-release-info $info */
        return $info;
    }
}
