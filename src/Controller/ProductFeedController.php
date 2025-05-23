<?php

namespace LilianBellini\SyliusGoogleMerchantCenter\Controller;

use LilianBellini\SyliusGoogleMerchantCenter\Generator\ProductFeedGenerator;
use Symfony\Component\HttpFoundation\Response;

class ProductFeedController
{
    private $productFeedGenerator;

    public function __construct(ProductFeedGenerator $productFeedGenerator)
    {
        $this->productFeedGenerator = $productFeedGenerator;
    }

    public function feedAction(): Response
    {
        return $this->productFeedGenerator->generateFeed();
    }
}
