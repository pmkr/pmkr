<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Task;

use Robo\Common\ConfigAwareTrait;
use Robo\Contract\ConfigAwareInterface;
use Pmkr\Pmkr\Util\ConfigNormalizer;

class ConfigNormalizerTask extends BaseTask implements ConfigAwareInterface
{
    use ConfigAwareTrait;

    protected string $taskName = 'PMKR - Config normalizer';

    protected ConfigNormalizer $configNormalizer;

    public function __construct(ConfigNormalizer $configNormalizer)
    {
        $this->configNormalizer = $configNormalizer;
    }

    /**
     * {@inheritdoc}
     */
    protected function runDoIt()
    {
        $this->configNormalizer->normalizeConfig($this->getConfig());

        return $this;
    }
}
