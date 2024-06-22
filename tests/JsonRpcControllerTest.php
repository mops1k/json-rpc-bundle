<?php

namespace JsonRpcBundle\Tests;

use JsonRpcBundle\Tests\Stubs\TestKernel;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class JsonRpcControllerTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    #[DataProvider('dataProvider')]
    public function testRpcRequest(array $content, callable $assertionCallable): void
    {
        self::bootKernel();
        $rpcRequest = Request::create('/rpc', Request::METHOD_POST, content: \json_encode($content));
        $rpcRequest->headers->set('Content-Type', 'application/json');
        $response = self::$kernel?->handle($rpcRequest);
        if (!$response instanceof Response) {
            self::fail();
        }

        self::assertEquals(200, $response->getStatusCode());

        $assertionCallable($response);
    }

    public function testRpcPreconditionFailed()
    {

        self::bootKernel();
        $rpcRequest = Request::create('/rpc', Request::METHOD_POST, content: [
            'jsonrpc' => '2.0',
            'method' => 'testMethodWithoutContract',
            'params' => [5],
            'id' => 1,
        ]);
        $response = self::$kernel?->handle($rpcRequest);
        if (!$response instanceof Response) {
            self::fail();
        }

        self::assertEquals(200, $response->getStatusCode());
        $content = \json_decode($response->getContent(), true);
        self::assertArrayHasKey('error', $content);
        self::assertEquals(0, $content['error']['code']);
        self::assertEquals('Content-Type must be application/json', $content['error']['message']);
        self::assertEquals(null, $content['id']);
    }

    public function testExceptionListenerNotCalled()
    {

        self::bootKernel();
        $rpcRequest = Request::create('/bad_path');
        $response = self::$kernel?->handle($rpcRequest);
        if (!$response instanceof Response) {
            self::fail();
        }

        self::assertEquals(404, $response->getStatusCode());
    }

    public static function dataProvider(): iterable
    {
        yield 'no contract, no hints' => [
            'content' => [
                'jsonrpc' => '2.0',
                'method' => 'testMethodWithoutContract',
                'params' => [5],
                'id' => 1,
            ],
            'assertionCallable' => function (Response $response) {
                self::assertJson($response->getContent() ?: null);
                $content = \json_decode($response->getContent(), true);
                self::assertEquals(5, $content['result']);
                self::assertEquals(1, $content['id']);
            },
        ];
        yield 'no contract, with hints' => [
            'content' => [
                'jsonrpc' => '2.0',
                'method' => 'testMethodWithoutContract',
                'params' => ['id' => 5],
                'id' => 1,
            ],
            'assertionCallable' => function (Response $response) {
                self::assertJson($response->getContent() ?: null);
                $content = \json_decode($response->getContent(), true);
                self::assertEquals(5, $content['result']);
                self::assertEquals(1, $content['id']);
            },
        ];
        yield 'with contract, no hints' => [
            'content' => [
                'jsonrpc' => '2.0',
                'method' => 'testMethodWithContract',
                'params' => [82, 'test text'],
                'id' => 1,
            ],
            'assertionCallable' => function (Response $response) {
                self::assertJson($response->getContent() ?: null);
                $content = \json_decode($response->getContent(), true);
                self::assertEquals(['id' => 82, 'text' => 'test text'], $content['result']);
                self::assertEquals(1, $content['id']);
            },
        ];
        yield 'with contract, mixed hints' => [
            'content' => [
                'jsonrpc' => '2.0',
                'method' => 'testMethodWithContract',
                'params' => [82, 'text' => 'test text'],
                'id' => 1,
            ],
            'assertionCallable' => function (Response $response) {
                self::assertJson($response->getContent() ?: null);
                $content = \json_decode($response->getContent(), true);
                self::assertEquals(['id' => 82, 'text' => 'test text'], $content['result']);
                self::assertEquals(1, $content['id']);
            },
        ];
        yield 'with contract, with hints' => [
            'content' => [
                'jsonrpc' => '2.0',
                'method' => 'testMethodWithContract',
                'params' => ['id' => 82, 'text' => 'test text'],
                'id' => 1,
            ],
            'assertionCallable' => function (Response $response) {
                self::assertJson($response->getContent() ?: null);
                $content = \json_decode($response->getContent(), true);
                self::assertEquals(['id' => 82, 'text' => 'test text'], $content['result']);
                self::assertEquals(1, $content['id']);
            },
        ];
        yield 'multiple content responses' => [
            'content' => [
                [
                    'jsonrpc' => '2.0',
                    'method' => 'testMethodWithContract',
                    'params' => ['id' => 82, 'text' => 'test text'],
                    'id' => 1,
                ],
                [
                    'jsonrpc' => '2.0',
                    'method' => 'testMethodWithoutContract',
                    'params' => ['id' => 5],
                    'id' => 2,
                ],
            ],
            'assertionCallable' => function (Response $response) {
                self::assertJson($response->getContent() ?: null);
                $content = \json_decode($response->getContent(), true);
                self::assertEquals('test text', $content[0]['result']['text']);
                self::assertEquals(82, $content[0]['result']['id']);
                self::assertEquals(1, $content[0]['id']);
                self::assertEquals(5, $content[1]['result']);
                self::assertEquals(2, $content[1]['id']);
            },
        ];
        yield 'contract validation fail' => [
            'content' => [
                'jsonrpc' => '2.0',
                'method' => 'testMethodWithContract',
                'params' => ['id' => -82, 'text' => ''],
                'id' => 1,
            ],
            'assertionCallable' => function (Response $response) {
                self::assertJson($response->getContent() ?: null);
                $content = \json_decode($response->getContent(), true);
                self::assertArrayHasKey('error', $content);
                self::assertEquals(-32602, $content['error']['code']);
                self::assertEquals('Invalid method parameters.', $content['error']['message']);
                self::assertArrayHasKey('id', $content['error']['data']);
                self::assertArrayHasKey('text', $content['error']['data']);
                self::assertEquals(1, $content['id']);
            },
        ];
        yield 'no contract validation fail' => [
            'content' => [
                'jsonrpc' => '2.0',
                'method' => 'testMethodWithoutContract',
                'params' => ['id' => -8],
                'id' => 1,
            ],
            'assertionCallable' => function (Response $response) {
                self::assertJson($response->getContent() ?: null);
                $content = \json_decode($response->getContent(), true);
                self::assertArrayHasKey('error', $content);
                self::assertEquals(-32602, $content['error']['code']);
                self::assertEquals('Invalid method parameters.', $content['error']['message']);
                self::assertArrayHasKey('[id]', $content['error']['data']);
                self::assertEquals(1, $content['id']);
            },
        ];
        yield 'bad method parameter' => [
            'content' => [
                'jsonrpc' => '2.0',
                'method' => 'testMethodWithoutContract',
                'params' => ['id' => 'text'],
                'id' => 1,
            ],
            'assertionCallable' => function (Response $response) {
                self::assertJson($response->getContent() ?: null);
                $content = \json_decode($response->getContent(), true);
                self::assertArrayHasKey('error', $content);
                self::assertEquals(-32603, $content['error']['code']);
                self::assertEquals('Internal JSON-RPC error.', $content['error']['message']);
                self::assertStringContainsString(
                    'JsonRpcBundle\Tests\Stubs\Method\TestMethodWithoutContract::__invoke(): Argument #1 ($id) must be of type int, string given',
                    $content['error']['data']
                );
                self::assertEquals(1, $content['id']);
            },
        ];
        yield 'bad method name' => [
            'content' => [
                'jsonrpc' => '2.0',
                'method' => 'nonExistenceMethod',
                'params' => null,
                'id' => 1,
            ],
            'assertionCallable' => function (Response $response) {
                self::assertJson($response->getContent() ?: null);
                $content = \json_decode($response->getContent(), true);
                self::assertArrayHasKey('error', $content);
                self::assertEquals(-32601, $content['error']['code']);
                self::assertEquals('The requested method was not found.', $content['error']['message']);
                self::assertStringContainsString(
                    'Method "nonExistenceMethod" does not exists',
                    $content['error']['data']
                );
                self::assertEquals(1, $content['id']);
            },
        ];
        yield 'notification method' => [
            'content' => [
                'jsonrpc' => '2.0',
                'method' => 'testNotificationMethod',
                'params' => null,
                'id' => 8,
            ],
            'assertionCallable' => function (Response $response) {
                self::assertFalse(json_validate($response->getContent()));
            },
        ];
        //        yield 'notification and method without contract' => [
        //            'content' => [
        //                'jsonrpc' => '2.0',
        //                'method' => 'testNotificationMethod',
        //                'params' => [],
        //                'id' => 8,
        //            ],
        //            'assertionCallable' => function (Response $response) {
        //                self::assertFalse(json_validate($response->getContent()));
        //            },
        //        ];
        yield 'parse error' => [
            'content' => [1],
            'assertionCallable' => function (Response $response) {
                self::assertJson($response->getContent() ?: null);
                $content = \json_decode($response->getContent(), true);
                self::assertArrayHasKey('error', $content);
                self::assertEquals(-32700, $content['error']['code']);
                self::assertEquals('Invalid JSON was received by the server.', $content['error']['message']);
                self::assertEquals(
                    'The JSON sent is not a valid Request object.',
                    $content['error']['data']
                );
                self::assertEquals(null, $content['id']);
            },
        ];
    }
}
