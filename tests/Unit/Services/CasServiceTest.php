<?php

namespace Tests\Unit\Services;

use App\Services\CasService;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use ReflectionProperty;

class CasServiceTest extends TestCase
{
    /**
     * CasService's constructor always builds a real Guzzle Client (no network
     * call happens at construction time) but never performs I/O until
     * authenticate() runs, so the real constructor is safe to call directly;
     * only the httpClient property is swapped for a mock afterward.
     */
    private function serviceWithMockedClient(Client $client): CasService
    {
        $service = new CasService();

        $property = new ReflectionProperty(CasService::class, 'httpClient');
        $property->setAccessible(true);
        $property->setValue($service, $client);

        return $service;
    }

    private function mockResponseWithBody(string $body): ResponseInterface
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getContents')->willReturn($body);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($stream);

        return $response;
    }

    public function testAuthenticateReturnsTrueWhenCasServerRespondsYes(): void
    {
        $client = $this->createMock(Client::class);
        $client->method('get')->willReturn($this->mockResponseWithBody("yes\nnikitas\n"));

        $this->assertTrue($this->serviceWithMockedClient($client)->authenticate('ST-123', 'http://app.test'));
    }

    public function testAuthenticateReturnsFalseWhenCasServerRespondsNo(): void
    {
        $client = $this->createMock(Client::class);
        $client->method('get')->willReturn($this->mockResponseWithBody("no\n"));

        $this->assertFalse($this->serviceWithMockedClient($client)->authenticate('ST-bad', 'http://app.test'));
    }

    public function testAuthenticateReturnsFalseWhenClientThrows(): void
    {
        $client = $this->createMock(Client::class);
        $client->method('get')->willThrowException(new \RuntimeException('connection refused'));

        $this->assertFalse($this->serviceWithMockedClient($client)->authenticate('ST-123', 'http://app.test'));
    }

    public function testAuthenticateIsCaseInsensitiveOnYes(): void
    {
        $client = $this->createMock(Client::class);
        $client->method('get')->willReturn($this->mockResponseWithBody("YES\r\nnikitas\r\n"));

        $this->assertTrue($this->serviceWithMockedClient($client)->authenticate('ST-123', 'http://app.test'));
    }
}
