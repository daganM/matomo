<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link    http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Tests\System;

use Piwik\Http;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\TestCase\SystemTestCase;

/**
 * @group TrackerTest
 * @group Plugins
 */
class TrackerResponseTest extends SystemTestCase
{
    public static $fixture = null;

    /**
     * @var \MatomoTracker
     */
    private $tracker;

    public function setUp(): void
    {
        parent::setUp();

        $idSite = 1;
        $dateTime = '2014-01-01 00:00:01';

        if (!Fixture::siteCreated($idSite)) {
            Fixture::createWebsite($dateTime);
        }

        $this->tracker = Fixture::getTracker($idSite, $dateTime, $defaultInit = true);
    }

    public function test_response_ShouldContainAnImage()
    {
        $response = $this->tracker->doTrackPageView('Test');

        Fixture::checkResponse($response);
        $this->assertNotEmpty($response);
    }

    public function test_response_ShouldBeEmpty_IfImageIsDisabled()
    {
        $this->tracker->disableSendImageResponse();

        $response = $this->tracker->doTrackPageView('Test');

        $this->assertSame('', $response);
    }

    public function test_response_ShouldSend200ResponseCode_IfImageIsEnabled()
    {
        $url = $this->tracker->getUrlTrackPageView('Test');

        $response = $this->sendHttpRequest($url);
        $this->assertEquals(200, $response['status']);
        $this->assertArrayHasKey('Cache-Control', $response['headers']);
        $this->assertEquals('no-store', $response['headers']['Cache-Control']);
    }

    public function test_response_ShouldSend204ResponseCode_IfImageIsDisabled()
    {
        $url = $this->tracker->getUrlTrackPageView('Test');
        $url .= '&send_image=0';

        $response = $this->sendHttpRequest($url);
        $this->assertEquals(204, $response['status']);
        $this->assertArrayHasKey('Cache-Control', $response['headers']);
        $this->assertEquals('no-store', $response['headers']['Cache-Control']);
    }

    public function test_response_ShouldSend400ResponseCode_IfSiteIdIsInvalid()
    {
        $url = $this->tracker->getUrlTrackPageView('Test');
        $url .= '&idsite=100';

        $response = $this->sendHttpRequest($url);
        $this->assertEquals(400, $response['status']);
    }

    public function test_response_ShouldSend400ResponseCode_IfSiteIdIsNegative()
    {
        $url = $this->tracker->getUrlTrackPageView('Test');
        $url .= '&idsite=-1';

        $response = $this->sendHttpRequest($url);
        $this->assertEquals(400, $response['status']);
    }

    public function test_response_ShouldSend400ResponseCode_IfSiteIdIsZero()
    {
        $url = $this->tracker->getUrlTrackPageView('Test');
        $url .= '&idsite=0';

        $response = $this->sendHttpRequest($url);
        $this->assertEquals(400, $response['status']);
    }

    public function test_response_ShouldSend400ResponseCode_IfInvalidRequestParameterIsGiven()
    {
        $url = $this->tracker->getUrlTrackPageView('Test');
        $url .= '&cid=' . str_pad('1', 16, '1');

        $response = $this->sendHttpRequest($url);
        $this->assertArrayHasKey('Cache-Control', $response['headers']);
        $this->assertEquals('no-store', $response['headers']['Cache-Control']);
        $this->assertEquals(200, $response['status']);

        $response = $this->sendHttpRequest($url . '1'); // has to be 16 char, but is 17 now
        $this->assertEquals(400, $response['status']);
    }

    // See https://github.com/piwik/piwik/issues/7850 piwik.php is used by plugins and monitoring systems to test for Piwik installation.
    // it is important to return a 200 if someone does a GET request with no parameters
    public function test_response_ShouldReturnPiwikMessageWithHttp200_InCaseOfEmptyGETRequest()
    {
        $url = Fixture::getTrackerUrl();
        $response = Http::sendHttpRequest($url, 10, null, null, 0, false, false, true);
        $this->assertEquals(200, $response['status']);

        $expected = "This resource is part of Matomo. Keep full control of your data with the leading free and open source <a href='https://matomo.org' target='_blank' rel='noopener noreferrer nofollow'>web analytics & conversion optimisation platform</a>.";
        $this->assertEquals($expected, $response['data']);
    }

    public function test_response_ShouldReturnPiwikMessageWithHttp400_InCaseOfInvalidRequestOrIfNothingIsTracked()
    {
        $url = Fixture::getTrackerUrl();
        $response = $this->sendHttpRequest($url . '?rec=1');
        $this->assertEquals(400, $response['status']);

        $response = $this->sendHttpRequest($url);
        $expected = "This resource is part of Matomo. Keep full control of your data with the leading free and open source <a href='https://matomo.org' target='_blank' rel='noopener noreferrer nofollow'>web analytics & conversion optimisation platform</a>.";
        $this->assertEquals($expected, $response['data']);
    }

    public function test_response_ShouldReturnPiwikMessageWithHttp503_InCaseOfMaintenanceMode()
    {
        $url = $this->tracker->getUrlTrackPageView('Test');
        $response = $this->sendHttpRequest($url);
        $this->assertEquals(200, $response['status']);

        $url = $url . "&forceEnableTrackerMaintenanceMode=1";
        $response = $this->sendHttpRequest($url);
        $this->assertEquals(503, $response['status']);
    }

    protected function sendHttpRequest($url)
    {
        return Http::sendHttpRequest($url, 10, null, null, 0, false, false, true);
    }
}
