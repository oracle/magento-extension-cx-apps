<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace OracleTest\Magento\Order;

/**
 * Class ExtensionAbstractTest
 * @coversDefaultClass \Oracle\M2\Order\ExtensionAbstract
 */
class ExtensionAbstractTest extends \PHPUnit\Framework\TestCase
{
    /** @var \Oracle\M2\Order\Settings mocked */
    protected $extensionAbstract;

    public function setUp()
    {
        $this->extensionAbstract = $this->getMockBuilder(\Oracle\Order\Model\Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getHelper'])
            ->getMock();
    }

    /**
     * @group unit
     * @covers ::apply
     */
    public function testApply()
    {
        $helper = $this->getMockBuilder(\Oracle\M2\Order\Settings::class)
            ->disableOriginalConstructor()
            ->setMethods(['setCurrency', 'getItemPrice', 'getItemRowTotal', 'getItemDescription', 'formatPrice'])
            ->getMock();
        $helper->expects($this->any())
            ->method('setCurrency')
            ->willReturn(null);
        $helper->expects($this->any())
            ->method('getItemPrice')
            ->willReturn(1.99);
        $helper->expects($this->any())
            ->method('getItemRowTotal')
            ->willReturn(1.99);
        $helper->expects($this->any())
            ->method('getItemDescription')
            ->willReturn('test');
        $helper->expects($this->any())
            ->method('formatPrice')
            ->willReturn(1.99);

        $order = $this->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAllVisibleItems'])
            ->getMock();

        $lineItems = [];
        $lineItem = $this->getMockBuilder(\Magento\Sales\Model\Order\Item::class)
            ->disableOriginalConstructor()
            ->setMethods(['getProduct', 'getProductId', 'getSku', 'getQtyOrdered'])
            ->getMock();
        $product = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['getName', 'getProductUrl'])
            ->getMock();
        $product->expects($this->any())
            ->method('getName')
            ->willReturn('Product Name Here');
        $product->expects($this->any())
            ->method('getProductUrl')
            ->willReturn('http://www.productsRus.com');
        $lineItem->expects($this->any())
            ->method('getProduct')
            ->willReturn($product);
        $lineItem->expects($this->any())
            ->method('getProductId')
            ->willReturn(1);
        $lineItem->expects($this->any())
            ->method('getSku')
            ->willReturn('sku-d-view');
        $lineItem->expects($this->any())
            ->method('getQtyOrdered')
            ->willReturn(15);
        $lineItems[] = clone $lineItem;
        $lineItems[] = clone $lineItem;

        $order->expects($this->any())
            ->method('getAllVisibleItems')
            ->willreturn($lineItems);

        $this->extensionAbstract->expects($this->any())
            ->method('getHelper')
            ->willReturn($helper);

        $templateVars = ['order' => $order];

        $expected = [
            'subtotal' => 1.99,
            'shippingAmt' => 1.99,
            'discount' => 1.99,
            'tax' => 1.99,
            'grandTotal' => 1.99,
            'productId_1' => 1,
            'productName_1' => 'Product Name Here',
            'productSku_1' => 'sku-d-view',
            'productPrice_1' => 1.99,
            'productTotal_1' => 1.99,
            'productQty_1' => 15,
            'productUrl_1' => 'http://www.productsRus.com',
            'productDescription_1' => 'test',
            'productId_2' => 1,
            'productName_2' => 'Product Name Here',
            'productSku_2' => 'sku-d-view',
            'productPrice_2' => 1.99,
            'productTotal_2' => 1.99,
            'productQty_2' => 15,
            'productUrl_2' => 'http://www.productsRus.com',
            'productDescription_2' => 'test',
        ];

        $this->assertEquals($expected, $this->extensionAbstract->apply([], $templateVars, false));
    }
}
