<?php

namespace JsonRpcBundle\Tests;

use JsonRpcBundle\Tests\Stubs\TestKernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiDocTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    public function testApiDocGeneration()
    {
        self::bootKernel();
        $request = Request::create('/api/doc.json', Request::METHOD_GET);
        $request->headers->set('Content-Type', 'application/json');
        $response = self::$kernel?->handle($request);
        if (!$response instanceof Response) {
            self::fail();
        }

//        file_put_contents(__DIR__.'/api.json', $response->getContent());
//        dd($response->getContent());
        self::assertJson($response->getContent());
    }
}
