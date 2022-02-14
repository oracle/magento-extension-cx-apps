<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace OracleTest\Magento\Connector\Event;

use Oracle\M2\Connector\Event\Platform;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Oracle\M2\Connector\Event\Platform
 */
class PlatformTest extends TestCase
{

    /**
     * @return array
     */
    public static function baseUrlProvider()
    {
        $defaultValue = Platform::SARLACC;

        return [
            [false, $defaultValue],
            ['', $defaultValue],
            ['     ', $defaultValue],
            ['0', $defaultValue],
            ['  0 ', $defaultValue],
            ['foo', 'foo'],
            [' bar ', 'bar'],
        ];
    }

    /**
     * @covers ::getBaseUrl
     * @dataProvider baseUrlProvider
     * @group unit
     *
     * @param string|false $envValue
     * @param string $expectedResult
     */
    public function testGetBaseUrl($envValue, $expectedResult)
    {
        $platform = \Mockery::mock(Platform::class)
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
        $platform->shouldReceive('getEnvironmentVar')
            ->andReturn($envValue);

        self::assertEquals($expectedResult, $platform->getBaseUrl());
    }
}
