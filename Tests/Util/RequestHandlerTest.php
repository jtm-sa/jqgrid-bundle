<?php

namespace himiklab\JqGridBundle\Tests\Utils;

use himiklab\JqGridBundle\Util\POSTDataFetcher;
use himiklab\JqGridBundle\Util\RequestHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class RequestHandlerTest extends TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $postFetcher;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $request;

    /** @var RequestHandler */
    private $handler;

    public function setUp()
    {
        parent::setUp();

        $this->postFetcher = $this->createMock(POSTDataFetcher::class);
        $this->request = $this->createMock(Request::class);

        $this->handler = new RequestHandler($this->postFetcher);
    }

    public function testPOSTData()
    {
        $this->postFetcher
            ->expects($this->once())
            ->method('getPOSTData')
            ->willReturn('param1=test1&param2=test%202&param3[]=test3&param3[]=test4');

        $this->request
            ->expects($this->any())
            ->method('isMethod')
            ->willReturnMap([['post', true], ['get', false]]);

        $this->assertEquals(
            ['param1' => 'test1', 'param2' => 'test 2', 'param3' => ['test3', 'test4']],
            $this->handler->getRequestData($this->request)
        );
    }

    public function testGETData()
    {
        $this->request
            ->expects($this->any())
            ->method('isMethod')
            ->willReturnMap([['get', true], ['post', false]]);

        $this->request->query = $this->createMock(ParameterBag::class);
        $this->request->query
            ->expects($this->once())
            ->method('all')
            ->willReturn(['param' => 'test']);

        $this->assertEquals(
            ['param' => 'test'],
            $this->handler->getRequestData($this->request)
        );
    }

    public function testUnsupportedRequestException()
    {
        $this->request
            ->expects($this->any())
            ->method('isMethod')
            ->willReturn(false);

        $this->expectException(\LogicException::class);
        $this->handler->getRequestData($this->request);
    }
}
