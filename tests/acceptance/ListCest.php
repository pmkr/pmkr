<?php

declare(strict_types = 1);

namespace Pmkr\Pmkr\Tests\Acceptance;

use Pmkr\Pmkr\Tests\AcceptanceTester;

class ListCest
{
    public function listCommandTest(AcceptanceTester $tester): void
    {
        $pmkr = $tester->grabPmkrExecutable();
        $tester->expectTo('see all the available pmkr commands');
        $tester->runShellCommand(sprintf('%s list', $pmkr));
    }
}
