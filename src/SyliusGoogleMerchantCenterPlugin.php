<?php

namespace LilianBellini\SyliusGoogleMerchantCenter;

use Sylius\Bundle\CoreBundle\Application\SyliusPluginTrait;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use LilianBellini\SyliusGoogleMerchantCenter\DependencyInjection\SyliusGoogleMerchantCenterExtension;

class SyliusGoogleMerchantCenterPlugin extends Bundle
{
    use SyliusPluginTrait;

    protected function getContainerExtensionClass(): string
    {
        return SyliusGoogleMerchantCenterExtension::class;
    }
}
