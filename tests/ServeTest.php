<?php
/**
 * Created by PhpStorm.
 * User: sadeghpm
 * Date: 3/12/18
 * Time: 2:43 PM
 */

namespace Tests;


use Dpsoft\psr7PartialDownload\Serve;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class ServeTest extends TestCase
{
    /**
     * @var ServerRequestInterface
     */
    private $request;
    /**
     * @var Serve
     */
    private $download;
    /**
     * @var
     */
    private $test_file = 'test_file.txt';

    protected function setUp()
    {
        $this->request = ServerRequest::fromGlobals();
        $this->download = new Serve($this->request, new Response());
    }

    public function testReadRangeHeader()
    {
        $request = $this->request->withHeader('range', 'bytes=200-1000');
        $requestRange = $this->download->readRangeHeader($request, 1001);
        self::assertArrayHasKey('start', $requestRange);
        self::assertEquals(200, $requestRange['start']);
        self::assertArrayHasKey('end', $requestRange);
        self::assertEquals(1000, $requestRange['end']);
    }

    public function testReadRangeWithZeroStart()
    {
        $request = $this->request->withHeader('range', 'bytes=0-1023');
        $requestRange = $this->download->readRangeHeader($request, 1024);
        self::assertArrayHasKey('start', $requestRange);
        self::assertEquals(0, $requestRange['start']);
        self::assertArrayHasKey('end', $requestRange);
        self::assertEquals(1023, $requestRange['end']);
    }

    public function testReadRangeHeaderWithNoEndRange()
    {
        $request = $this->request->withHeader('range', 'bytes=200-');
        $requestRange = $this->download->readRangeHeader($request, 1001);
        self::assertArrayHasKey('start', $requestRange);
        self::assertEquals(200, $requestRange['start']);
        self::assertArrayHasKey('end', $requestRange);
        self::assertEquals(1000, $requestRange['end']);
    }

    public function testReadRangeHeaderWithNoStartRange()
    {
        $request = $this->request->withHeader('range', 'bytes=-900');
        $requestRange = $this->download->readRangeHeader($request, 1001);
        self::assertArrayHasKey('start', $requestRange);
        self::assertEquals(101, $requestRange['start']);
        self::assertArrayHasKey('end', $requestRange);
        self::assertEquals(1000, $requestRange['end']);
    }

    public function testDownload()
    {
        $response = $this->download->download($this->test_file, $this->test_file);
        self::assertEquals('text/plain', $response->getHeaderLine('Content-Type'));
        self::assertEquals(filesize($this->test_file), $response->getHeaderLine('Content-Length'));
    }

    public function testDownloadWithRange()
    {
        $this->request = ServerRequest::fromGlobals()->withHeader('range', 'bytes=1-10');
        $this->download = new Serve($this->request, new Response());
        $response = $this->download->download($this->test_file, $this->test_file);
        self::assertEquals(10 - 0, $response->getHeaderLine('Content-Length'));
        self::assertEquals('est_file_c', (string)$response->getBody());

    }

    public function testDownloadWithZeroRange()
    {
        $this->request = ServerRequest::fromGlobals()->withHeader('range', 'bytes=0-10');
        $this->download = new Serve($this->request, new Response());
        $response = $this->download->download($this->test_file, $this->test_file);
        self::assertEquals(11, $response->getHeaderLine('Content-Length'));
        self::assertEquals(
            'bytes 0-10/' . filesize($this->test_file), $response->getHeaderLine('Content-Range')
        );
        self::assertEquals('test_file_c', (string)$response->getBody());

    }
    public function testDownloadWithNoEndRange()
    {
        $this->request = ServerRequest::fromGlobals()->withHeader('range', 'bytes=10-');
        $this->download = new Serve($this->request, new Response());
        $response = $this->download->download($this->test_file, $this->test_file);
        self::assertEquals(7, $response->getHeaderLine('Content-Length'));
        self::assertEquals(
            'bytes 10-16/' . filesize($this->test_file), $response->getHeaderLine('Content-Range')
        );
        self::assertEquals('content', (string)$response->getBody());

    }
    public function testDownloadWithNoStartRange()
    {
        $this->request = ServerRequest::fromGlobals()->withHeader('range', 'bytes=-10');
        $this->download = new Serve($this->request, new Response());
        $response = $this->download->download($this->test_file, $this->test_file);
        self::assertEquals(10, $response->getHeaderLine('Content-Length'));
        self::assertEquals(
            'bytes 7-16/' . filesize($this->test_file), $response->getHeaderLine('Content-Range')
        );
        self::assertEquals('le_content', (string)$response->getBody());

    }
    public function testDownloadWithInvalidRange()
    {
        $this->request = ServerRequest::fromGlobals()->withHeader('range', 'bytes=12-32');
        $this->download = new Serve($this->request, new Response());
        $response = $this->download->download($this->test_file, $this->test_file);
        self::assertEquals(416, $response->getStatusCode());
        self::assertEquals(
            'bytes */' . filesize($this->test_file), $response->getHeaderLine('Content-Range')
        );
    }
}
