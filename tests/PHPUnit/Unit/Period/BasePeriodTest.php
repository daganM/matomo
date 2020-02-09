<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Tests\Unit\Period;

use Piwik\Translate;

abstract class BasePeriodTest extends \PHPUnit\Framework\TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Translate::loadAllTranslations();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        Translate::reset();
    }
}