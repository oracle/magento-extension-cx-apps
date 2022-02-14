<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace OracleTest\Magento\Connector;

use Oracle\Connector\Model\Registration;
use Oracle\M2\Connector\Middleware;
use Oracle\M2\Connector\RegistrationInterface;
use Oracle\Serialize\BiDirectional;
use Oracle\Serialize\Json\Standard;
use Oracle\Transfer\Curl\Request;
use Oracle\Transfer\Curl\Response;
use Magento\Backend\Service\V1\ModuleService;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Oracle\M2\Connector\Middleware
 */
class MiddlewareTest extends TestCase
{

    /**
     * @return array
     */
    public static function baseUrlProvider()
    {
        $defaultValue = Middleware::BASE_URL;

        return [
            ['', $defaultValue],
            ['     ', $defaultValue],
            ['0', $defaultValue],
            ['  0 ', $defaultValue],
            ['foo', 'foo'],
            [' bar ', 'bar'],
        ];
    }

    /**
     * @covers ::getServiceBaseUrl
     * @dataProvider baseUrlProvider
     * @group integration
     *
     * @param string $envValue
     * @param string $expectedResult
     */
    public function testGetServiceBaseUrl($envValue, $expectedResult)
    {
        $envVar = Middleware::BASE_URL_OVERRIDE_KEY;
        putenv("$envVar=$envValue");

        self::assertEquals($expectedResult, Middleware::getServiceBaseUrl());
    }

    /**
     * @covers ::deregister
     * @group unit
     */
    public function testDeregister()
    {
        $registration = $this->initMock(Registration::class, true, [], ['getIsActive']);
        $registration->expects($this->any())->method('getIsActive')->willReturn(true);

        // Mock all dependencies and inject into constructor
        $client = $this->initMock(\Oracle\Transfer\Curl\Adapter::class, true, [], ['createRequest']);
        $response = $this->initMock(Response::class, true, [], ['code']);
        $response->expects($this->any())->method('code')->willReturn(200);
        $request = $this->initMock(Request::class, true, [] , ['respond']);
        $request->expects($this->any())->method('respond')->willReturn($response);
        $client->expects($this->any())->method('createRequest')->willReturn($request);

        $encoder = $this->initMock(Standard::class, true, [], ['encode', 'getMimeType']);
        $encoder->expects($this->any())->method('encode')->willReturn('stuff');
        $encoder->expects($this->any())->method('getMimeType')->willReturn('application/json');

        $logger = $this->initMock(\Oracle\M2\Impl\Core\Logger::class, true, [], []);
        $meta = $this->initMock(\Oracle\M2\Impl\Core\Meta::class, true, [], []);

        $encryptor = $this->initMock(\Oracle\M2\Impl\Core\Encryptor::class, true, [], ['encrypt']);
        $encryptor->expects($this->any())->method('encrypt')->willReturn('things');

        $eventManager = $this->initMock(\Oracle\M2\Impl\Core\Event::class, true, [], []);
        $storeManager = $this->initMock(\Oracle\M2\Impl\Core\Store::class, true, [], ['getStore']);
        $configManager = $this->initMock(\Oracle\M2\Impl\Core\Config::class, true, [], []);

        $settings = $this->initMock(\Oracle\M2\Connector\Settings::class, true, [], ['getCustomUrl']);
        $settings->expects($this->any())->method('getCustomUrl')->willReturn('more/stuff');

        $serializer = $this->getMockBuilder(\Magento\Framework\Serialize\SerializerInterface::class)->getMock();

        $store = $this->initMock(\Magento\Store\Model\Store::class, true, [], ['getConfig']);
        $store->expects($this->any())->method('getConfig')->willReturn(true);
        $storeManager->expects($this->any())->method('getStore')->willReturn($store);

        $middleware = $this->initMock(
            Middleware::class,
            false,
            [$client, $encoder, $logger, $meta, $encryptor, $eventManager, $storeManager, $configManager, $settings,
                $serializer],
            ['defaultStoreId', 'scopeTree']
        );
        $middleware->expects($this->any())
            ->method('defaultStoreId')
            ->willReturn('something');

        $tree = [
            'id' => 'default.0',
            'name' => 'Default',
            'children' => [
                [
                    'id' => 'website.1',
                    'name' => 'Website 1',
                    'children' => [
                        [
                            'store.1',
                            'name' => 'store 1',
                            'children' => []
                        ]
                    ]
                ],
                [
                    'id' => 'website.2',
                    'name' => 'Website 2',
                    'children' => [
                        [
                            'store.2',
                            'name' => 'store 2',
                            'children' => []
                        ],
                        [
                            'store.3',
                            'name' => 'store 3',
                            'children' => []
                        ]
                    ]
                ],
                [
                    'id' => 'website.3',
                    'name' => 'Website 3',
                    'children' => []
                ]
            ]
        ];
        $middleware->expects($this->any())
            ->method('scopeTree')
            ->willReturn($tree);

        $this->assertTrue($middleware->deregister($registration));
    }

    /**
     * Convenience function for initializing a mock object with disabled constructor
     *
     * @param string $className
     * @param bool $disableConstructor
     * @param array $constructorArgs
     * @param array|null $methods
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function initMock($className, $disableConstructor = true, array $constructorArgs = [], array $methods = null)
    {
        $builder = $this->getMockBuilder($className)
            ->setMethods($methods);
        if ($disableConstructor) {
            $builder->disableOriginalConstructor();
        } else {
            $builder->setConstructorArgs($constructorArgs);
        }
        return $builder->getMock();
    }
}
