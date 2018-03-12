<?php

namespace Dpsoft\psr7PartialDownload;

use GuzzleHttp\Psr7\LazyOpenStream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use function GuzzleHttp\Psr7\stream_for;

class Serve
{
    /**
     * @var ServerRequestInterface
     */
    private $request;
    /**
     * @var ResponseInterface
     */
    private $response;

    public function __construct(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * @param string $filePath
     * @param string $fileName
     *
     * @return ResponseInterface
     */
    public function download(string $filePath, string $fileName): ResponseInterface
    {
        if (!file_exists($filePath)) {
            return $this->response->withStatus(404, "File not found: $fileName");
        }
        if (!is_readable($filePath)) {
            return $this->response->withStatus(405, "File not readable: $fileName");
        }
        $mimeType = mime_content_type($filePath);
        /** @var int $fileSize */
        $fileSize = filesize($filePath);
        $this->response = $this->response->withHeader(
            'Accept-Ranges', 'byte'
        )->withHeader('Content-Type', $mimeType)->withHeader(
            'Content-Disposition', "attachment; filename=\"{$fileName}\""
        );
        $rangeRequest = $this->readRangeHeader($this->request, $fileSize);
        if (empty($rangeRequest)) {
            $this->response = $this->response->withHeader('Content-Length', $fileSize);
            return $this->response->withBody(new LazyOpenStream($filePath, 'r'));
        }
        $start = $rangeRequest['start'];
        $end = $rangeRequest['end'];
        // If the range can't be fulfilled.
        if ($start >= $fileSize OR $end >= $fileSize) {
            // Indicate the acceptable range.
            // Return the 416 'Requested Range Not Satisfiable'.
            return $this->response->withHeader('Content-Range', 'bytes */' . $fileSize)->withStatus(416);
        }
        // Indicate the current range.
        $this->response = $this->response->withHeader(
            'Content-Range', 'bytes ' . $start . '-' . $end . '/' . $fileSize
        );
        $this->response = $this->response->withHeader(
            'Content-Length', $start == $end ? 0 : ($end - $start + 1)
        );
        $this->response = $this->response->withHeader('Cache-Control', 'no-cache');
        $stream = new LazyOpenStream($filePath, 'r');
        $stream->seek($start);
        return $this->response->withBody(stream_for($stream->read($end - $start + 1)));
    }

    /**
     * Read request range in byte
     *
     * @param ServerRequestInterface $request
     * @param                        $fileSize
     *
     * @return array sample:['start'=>0,'end'=>1024] .if range not exists, an empty array returned
     */
    public function readRangeHeader(ServerRequestInterface $request, int $fileSize)
    {
        $range = $request->getHeaderLine('range');
        if (empty($range)) {
            return [];
        }
        $match_res = preg_match('/bytes=([0-9]*)-([0-9]*)/', $range, $matches);
        if (!$match_res) {
            return [];
        }
        $start = intval($matches[1]);
        $end = intval($matches[2]);
        $result = [
            'start' => empty($start) ? 0 : $start,
            'end'   => empty($end) ? ($fileSize - 1) : $end
        ];

        if (!empty($matches[1]) && empty($matches[2])) {
            $result['start'] = $start;
            $result['end'] = $fileSize - 1;
        }

        if ((empty($matches[1]) and $matches[1]!='0') && !empty($matches[2])) {
            $result['start'] = $fileSize - $end;
            $result['end'] = $fileSize - 1;
        }

        return $result;

    }
}