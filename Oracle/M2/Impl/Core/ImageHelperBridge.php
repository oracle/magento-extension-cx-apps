<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\M2\Impl\Core;

class ImageHelperBridge implements \Oracle\M2\Core\Catalog\ImageHelperInterface
{
    private $_imageHelper;
    private $_logger;

    /**
     * @param \Magento\Catalog\Helper\Image $imageHelper
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Catalog\Helper\Image $imageHelper,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->_imageHelper = $imageHelper;
        $this->_logger = $logger;
    }

    /**
     * @see parent
     */
    public function getImageUrl($product, $attribute)
    {
        try {
            $imageId = null;
            switch ($attribute) {
                case 'small_image':
                    $imageId = 'product_page_image_medium';
                    break;
                case 'thumbnail':
                    $imageId = 'product_page_image_small';
                    break;
                default:
                    $imageId = 'product_page_image_large';
                    break;
            }
            return (string)$this->_imageHelper->init($product, $imageId)
                ->constrainOnly(true)->keepAspectRatio(true)->keepFrame(false)
                ->setImageFile($product->getImage())
                ->getUrl();
        } catch (\Exception $e) {
            $this->_logger->critical($e);
            return '';
        }
    }

    /**
     * @see parent
     */
    public function getDefaultPlaceHolderUrl()
    {
        try {
            return (string)$this->_imageHelper->getDefaultPlaceHolderUrl('image');
        } catch (\Exception $e) {
            $this->_logger->critical($e);
            return '';
        }
    }
}
