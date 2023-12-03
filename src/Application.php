<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr;

use Consolidation\AnnotatedCommand\CommandFileDiscovery;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Robo\Common\ConfigAwareTrait;
use Sweetchuck\PearClient\PearClient;
use Pmkr\Pmkr\SyntaxHighlighter\SyntaxHighlighter;
use Symfony\Component\Console\Application as ApplicationBase;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;
use Twig\Cache\NullCache;
use Twig\Environment as TwigEnvironment;
use Twig\Loader\FilesystemLoader as TwigFilesystemLoader;

class Application extends ApplicationBase implements ContainerAwareInterface
{
    use ConfigAwareTrait;
    use ContainerAwareTrait;

    const NAME = 'pmkr';

    /**
     * @todo This should be configurable.
     */
    const INSTANCE_DIR_PREFIX = 'pmkr-php';

    /**
     * @return array<string>
     */
    public function getCommandClasses(string $projectRoot): array
    {
        return (new CommandFileDiscovery())
            ->setSearchPattern('*Command.php')
            ->discover(
                [
                    "$projectRoot/src/Commands",
                ],
                '\Pmkr\Pmkr\Commands',
            );
    }

    /**
     * @param array<string, string> $envVars
     *
     * @return string[]
     */
    public function getConfigFiles(string $projectRoot, array $envVars): array
    {
        // @todo DRY \Pmkr\Pmkr\Util\ConfigFileCollector::collect().
        $appName = $this->getName();
        $envVarNamePrefix = mb_strtoupper($appName);

        $files = [
            "$projectRoot/$appName.yml",
        ];

        $dirsRawDefault = [
            "$projectRoot/resources/home",
        ];

        $dirsRawConfig = array_filter(
            explode(
                \PATH_SEPARATOR,
                $envVars["{$envVarNamePrefix}_CONFIG"] ?? '',
            ),
            '\strlen',
        );

        $dirsRawConfigExtra = array_filter(
            explode(
                \PATH_SEPARATOR,
                $envVars["{$envVarNamePrefix}_CONFIG_EXTRA"] ?? '',
            ),
            '\strlen',
        );

        if (!$dirsRawConfigExtra) {
            $dirsRawConfigExtra[] = ($envVars['HOME'] ?? '') . "/.$appName";
        }

        $dirsRaw = array_merge(
            $dirsRawDefault,
            $dirsRawConfig,
            $dirsRawConfigExtra,
        );

        $filesList = new Finder();
        foreach ($dirsRaw as $dirRaw) {
            if (!$dirRaw || !is_dir($dirRaw)) {
                continue;
            }

            $filesList->append(
                (new Finder())
                    ->in($dirRaw)
                    ->depth(0)
                    ->files()
                    ->name('pmkr.*.yml')
                    ->sortByName()
            );
        }
        foreach ($filesList as $file) {
            $files[] = $file->getPathname();
        }

        return $files;
    }

    /**
     * @return $this
     */
    public function configureContainer()
    {
        $this
            ->configureContainerFromYaml(__DIR__ . '/../' . static::NAME . '.services.yml')
            ->configureContainerTwig()
            ->configureContainerPeclClient()
            ->configureContainerSyntaxHighlighters();

        $container = $this->getContainer();
        $formatterManager = $container->get('formatterManager');
        $formatterManager
            ->addFormatter('shell-var-setter', $container->get('pmkr.output_formatter.shell_var_setter'))
            ->addFormatter('shell-executable', $container->get('pmkr.output_formatter.shell_executable'))
            ->addFormatter('shell-arguments', $container->get('pmkr.output_formatter.shell_arguments'))
            ->addFormatter('json', $container->get('pmkr.output_formatter.json'))
            ->addFormatter('yaml', $container->get('pmkr.output_formatter.yaml'))
            ->addFormatter('code', $container->get('pmkr.output_formatter.code'));

        return $this;
    }

    protected function configureContainerFromYaml(string $fileName): static
    {
        $content = Yaml::parseFile($fileName);
        $content += ['services' => []];
        $this->configureContainerAddServices($content['services']);

        return $this;
    }

    protected function configureContainerTwig(): static
    {
        $config = $this->getConfig();
        /** @var \League\Container\Container $container */
        $container = $this->getContainer();
        $utils = $container->get('pmkr.utils');
        $fs = $container->get('filesystem');

        $pmkrEtcTemplatesDir = $utils->getPmkrRoot() . '/resources/home/templates';
        $homeEtcTemplatesDir = $config->get('dir.templates');
        $dirUmask = 0777 - umask();
        if (!$fs->exists($homeEtcTemplatesDir)) {
            $fs->mkdir($homeEtcTemplatesDir, $dirUmask);
        }

        $twigLoader = new TwigFilesystemLoader(
            [
                $homeEtcTemplatesDir,
                $pmkrEtcTemplatesDir,
            ],
            $homeEtcTemplatesDir,
        );

        $cacheDir = $config->get('dir.cache');
        if ($cacheDir) {
            if (!$fs->exists("$cacheDir/templates")) {
                $fs->mkdir("$cacheDir/templates", $dirUmask);
            }
        }

        $twigCache = new NullCache();

        $container->addShared('twig.loader.filesystem', $twigLoader);
        $container->addShared('twig.cache', $twigCache);
        $container
            ->addShared('twig.environment', TwigEnvironment::class)
            ->addArgument('twig.loader.filesystem')
            ->addMethodCall('setCache', ['twig.cache']);

        return $this;
    }

    protected function configureContainerPeclClient(): static
    {
        /** @var \League\Container\Container $container */
        $container = $this->getContainer();

        $httpClientPecl = PearClient::createHttpClient([
            'base_uri' => 'https://pecl.php.net/rest/',
        ]);
        $container->addShared('pecl.http_client', $httpClientPecl);

        $container
            ->addShared('pecl.client', PearClient::class)
            ->addArgument('pecl.http_client');

        return $this;
    }

    protected function configureContainerSyntaxHighlighters(): static
    {
        /** @var \League\Container\Container $container */
        $container = $this->getContainer();
        $config = $container->get('config');

        $container
            ->addShared('syntax_highlighter', SyntaxHighlighter::class)
            ->addArgument('pmkr.terminal_color_schema.detector')
            ->addMethodCall('setOptions', [$config->get('syntaxHighlighter')]);

        $handlerDefinitions = $config->get('syntaxHighlighter.handler') ?: [];
        foreach ($handlerDefinitions as $handlerName => $handlerDefinition) {
            $backendClass = 'Pmkr\Pmkr\SyntaxHighlighter\Backend\\' . ucfirst($handlerDefinition['backend']);
            $backendOptions = $handlerDefinition['options'] ?? [];
            $backendArgs = [];
            switch ($handlerDefinition['backend']) {
                case 'bat':
                    $backendArgs[] = 'pmkr.utils';
                    $backendArgs[] = 'pmkr.process_factory';
                    $backendArgs[] = 'pmkr.process_result_parser.bat_list_languages';
                    break;

                case 'jq':
                case 'kate':
                case 'yq':
                    $backendArgs[] = 'pmkr.utils';
                    $backendArgs[] = 'pmkr.process_factory';
                    break;
            }
            $container
                ->addShared("syntax_highlighter.handler.$handlerName", $backendClass)
                ->addArguments($backendArgs)
                ->addMethodCall('setOptions', [$backendOptions]);
        }

        return $this;
    }

    /**
     * @param array<string, mixed> $services
     */
    protected function configureContainerAddServices(array $services): static
    {
        foreach ($services as $id => $service) {
            $service['id'] = $id;
            $this->configureContainerAddService($service);
        }

        return $this;
    }

    /**
     * @param array<string, mixed> $service
     */
    protected function configureContainerAddService(array $service): static
    {
        $service += [
            'shared' => true,
            'arguments' => [],
        ];

        $container = $this->getContainer();

        $definition = $service['shared'] ?
            $container->addShared($service['id'], $service['class'])
            : $container->add($service['id'], $service['class']);

        foreach ($service['arguments'] as $argument) {
            switch (mb_substr($argument, 0, 1)) {
                case '@':
                    $definition->addArgument(mb_substr($argument, 1));
                    break;
            }
        }

        return $this;
    }
}
