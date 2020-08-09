<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Commands;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

class ExtensionOptionCommand extends CommandBase
{

    /**
     * @command extension:option:list
     *
     * @pmkrInitNormalizeConfig
     * @pmkrInteractInstanceName
     *   arg.instanceName:
     *      hasSrcDir: true
     *      hasShareDir: true
     * @pmkrValidateInstanceName arg.instanceName
     * @pmkrValidateInstance
     *   arg.instanceName:
     *      hasSrcDir: true
     *      hasShareDir: true
     *
     * @todo Use \Consolidation\AnnotatedCommand\CommandResult as return.
     */
    public function cmdExtensionOptionListExecute(
        string $instanceName,
        array $extensionNames
    ) {
        $instance = $this->getPmkr()->instances[$instanceName];
        $extDirRoot = $instance->srcDir . '/ext';
        $extDirs = (new Finder())
            ->in($extDirRoot)
            ->directories()
            ->depth(0);

        $phpize = "$instance->shareDir/bin/phpize";
        foreach ($extDirs as $extDir) {
            $extName = $extDir->getBasename();

            if ($extensionNames && !in_array($extName, $extensionNames)) {
                continue;
            }

            $process = Process::fromShellCommandline(escapeshellcmd($phpize), $extDir->getPathname());
            $exitCode = $process->run();
            if ($exitCode !== 0) {
                $this->logger->error(
                    "phpize: {dir} exitCode:{exitCode} stdOutput:{stdOutput} stdError:{stdError}",
                    [
                        'dir' => $extDir->getPathname(),
                        'exitCode' => (string) $exitCode,
                        'stdOutput' => $process->getOutput(),
                        'stdError' => $process->getErrorOutput(),
                    ],
                );

                continue;
            }

            $process = Process::fromShellCommandline('./configure --help', $extDir->getPathname());
            $exitCode = $process->run();
            if ($exitCode !== 0) {
                $this->logger->error('./configure --help', ['dir' => $extDir->getPathname()]);
            }

            $stdOutput = $process->getOutput();
            $startPos = strpos($stdOutput, '--with-php-config');
            $startPos = strpos($stdOutput, "\n", $startPos);
            $endPos = strpos($stdOutput, '--enable-shared');
            $value = substr($stdOutput, $startPos, $endPos - $startPos);
            $this->yell($extName);
            $this->output()->writeln($value);
        }
    }
}
