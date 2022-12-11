<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Util;

use Sweetchuck\Utils\VersionNumber;
use Symfony\Component\Filesystem\Filesystem;

class PhpExtensionVersionDetector
{
    protected Filesystem $filesystem;

    protected VersionNumber $coreVersion;

    /**
     * @var array<string, array<string, bool>>
     */
    protected array $coreExtensions = [
        '0506' => [
            'bcmath' => true,
            'bz2' => true,
            'calendar' => true,
            'com_dotnet' => true,
            'ctype' => true,
            'curl' => true,
            'date' => true,
            'dba' => true,
            'dom' => true,
            'embed' => true,
            'enchant' => true,
            'ereg' => true,
            'exif' => true,
            'fileinfo' => true,
            'filter' => true,
            'ftp' => true,
            'gd' => true,
            'gettext' => true,
            'gmp' => true,
            'hash' => true,
            'iconv' => true,
            'imap' => true,
            'interbase' => true,
            'intl' => true,
            'json' => true,
            'ldap' => true,
            'libxml' => true,
            'mbstring' => true,
            'mcrypt' => true,
            'mssql' => true,
            'mysql' => true,
            'mysqli' => true,
            'mysqlnd' => true,
            'oci8' => true,
            'odbc' => true,
            'opcache' => true,
            'openssl' => true,
            'pcntl' => true,
            'pcre' => true,
            'pdo' => true,
            'pdo_dblib' => true,
            'pdo_firebird' => true,
            'pdo_mysql' => true,
            'pdo_oci' => true,
            'pdo_odbc' => true,
            'pdo_pgsql' => true,
            'pdo_sqlite' => true,
            'pgsql' => true,
            'phar' => true,
            'phpdbg' => true,
            'posix' => true,
            'pspell' => true,
            'readline' => true,
            'recode' => true,
            'reflection' => true,
            'session' => true,
            'shmop' => true,
            'simplexml' => true,
            'skeleton' => true,
            'snmp' => true,
            'soap' => true,
            'sockets' => true,
            'spl' => true,
            'sqlite3' => true,
            'standard' => true,
            'sybase_ct' => true,
            'sysvmsg' => true,
            'sysvsem' => true,
            'sysvshm' => true,
            'tidy' => true,
            'tokenizer' => true,
            'wddx' => true,
            'xml' => true,
            'xmlreader' => true,
            'xmlrpc' => true,
            'xmlwriter' => true,
            'xsl' => true,
            'zip' => true,
            'zlib' => true,
        ],
        '0700' => [],
        '0701' => [],
        '0702' => [],
        '0703' => [],
        '0704' => [],
        '0800' => [],
        '0801' => [],
        '0802' => [],
    ];

    public function __construct(?Filesystem $filesystem = null)
    {
        $this->filesystem = $filesystem ?: new Filesystem();
        $this->initCoreExtensions();
    }

    /**
     * @return $this
     */
    protected function initCoreExtensions()
    {
        $this->coreExtensions['0700'] = $this->coreExtensions['0506'];
        $this->coreExtensions['0700']['ereg'] = false;
        $this->coreExtensions['0700']['mssql'] = false;
        $this->coreExtensions['0700']['mysql'] = false;
        $this->coreExtensions['0700']['sybase_ct'] = false;

        // Same.
        $this->coreExtensions['0701'] = $this->coreExtensions['0700'];

        $this->coreExtensions['0702'] = $this->coreExtensions['0701'];
        $this->coreExtensions['0702']['mcrypt'] = false;
        $this->coreExtensions['0702']['sodium'] = true;
        $this->coreExtensions['0702']['zend_test'] = true;

        // Same.
        $this->coreExtensions['0703'] = $this->coreExtensions['0702'];

        $this->coreExtensions['0704'] = $this->coreExtensions['0703'];
        $this->coreExtensions['0704']['ffi'] = true;
        $this->coreExtensions['0704']['interbase'] = false;
        $this->coreExtensions['0704']['recode'] = false;
        $this->coreExtensions['0704']['wddx'] = false;

        $this->coreExtensions['0800'] = $this->coreExtensions['0704'];
        $this->coreExtensions['0800']['xmlrpc'] = false;

        // Same.
        $this->coreExtensions['0801'] = $this->coreExtensions['0800'];

        // Same.
        $this->coreExtensions['0802'] = $this->coreExtensions['0801'];
        $this->coreExtensions['0802']['random'] = true;

        return $this;
    }

    public function detect(VersionNumber $coreVersion, string $dir, ?string $name = null): ?string
    {
        $this->coreVersion = $coreVersion;
        if (!$this->isCoreVersionSupported($coreVersion)) {
            throw new \InvalidArgumentException("core version $coreVersion is not supported");
        }

        if ($name === null || $name === '') {
            $name = basename($dir);
        }

        if ($this->isCoreExtension($name)) {
            return 'PHP_VERSION';
        }

        foreach ($this->headerFileNameCandidates($name) as $fileName) {
            $version = $this->parseVersionFromHeaderFile($name, "$dir/$fileName");
            if ($version !== null) {
                return $version;
            }
        }

        return null;
    }

    /**
     * @return array<string>
     */
    protected function headerFileNameCandidates(string $name): array
    {
        return [
            "php_$name.h",
            "php_lib$name.h",
            "$name.h",
            "{$name}_private.h",
        ];
    }

    protected function parseVersionFromHeaderFile(string $name, string $filePath): ?string
    {
        if (!$this->filesystem->exists($filePath)) {
            return null;
        }

        $nameUpperSafe = preg_quote(strtoupper($name));
        $matches = [];
        preg_match(
            "/^#define PHP_{$nameUpperSafe}_VERSION\s+(?P<version>PHP_VERSION|\"[^\"]+\")\$/m",
            (string) file_get_contents($filePath),
            $matches,
        );

        return isset($matches['version']) ? trim($matches['version'], '"') : null;
    }

    protected function isCoreExtension(string $name): bool
    {
        $coreVersionCandidates = [
            $this->coreVersion->formatMA2MI2P2,
            $this->coreVersion->formatMA2MI2,
            $this->coreVersion->formatMA2,
        ];
        foreach ($coreVersionCandidates as $coreVersion) {
            if (isset($this->coreExtensions[$coreVersion][$name])) {
                return $this->coreExtensions[$coreVersion][$name];
            }
        }

        return false;
    }

    protected function isCoreVersionSupported(VersionNumber $coreVersion): bool
    {
        return isset($this->coreExtensions[$coreVersion->formatMA2MI2P2])
            || isset($this->coreExtensions[$coreVersion->formatMA2MI2])
            || isset($this->coreExtensions[$coreVersion->formatMA2]);
    }
}
