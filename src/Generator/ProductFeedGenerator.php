<?php

namespace LilianBellini\SyliusGoogleMerchantCenter\Generator;

use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use LilianBellini\SyliusGoogleMerchantCenter\Services\StockService;

class ProductFeedGenerator
{
    private ProductRepositoryInterface $productRepository;
    private RouterInterface            $router;
    private string                     $url;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        RouterInterface            $router,
        string                     $url
    ) {
        $this->productRepository = $productRepository;
        $this->router            = $router;
        $this->url               = rtrim($url, '/');
    }

    public function generateFeed(): Response
    {
        $products = $this->productRepository->findAll();

        $xml     = new \SimpleXMLElement(
            '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0"></rss>'
        );
        $channel = $xml->addChild('channel');
        $channel->addChild('title', 'Ma Pépinière');
        $channel->addChild('link',  $this->url);
        $channel->addChild('description', 'Découvrez notre collection de plantes et accessoires.');

        foreach ($products as $product) {
            if (!$product->isEnabled() || !StockService::getStockAvailability($product)) {
                continue;
            }

            /* ------------------------------
             * Variant : premier variant activé
             * -----------------------------*/
            $variant = null;
            foreach ($product->getVariants() as $v) {
                if (method_exists($v, 'isEnabled') ? $v->isEnabled() : true) {
                    $variant = $v;
                    break;
                }
            }
            if ($variant === null) {
                continue; // aucun variant actif → on ignore le produit
            }

            /* -------------------
             * Prix du premier ChannelPricing
             * ------------------*/
            $price = null;
            if (($channelPricing = $variant->getChannelPricings()->first()) !== false) {
                $price = $channelPricing->getPrice() / 100; // centimes → €
            }

            /* ---------------------------
             * Création du node <item>
             * --------------------------*/
            $item = $channel->addChild('item');
            $item->addChild('g:id', (string) $product->getId(), 'http://base.google.com/ns/1.0');
            $item->addChild('title',        htmlspecialchars($product->getName()),        'http://base.google.com/ns/1.0');
            $item->addChild('description',  htmlspecialchars($product->getDescription()), 'http://base.google.com/ns/1.0');

            // Lien produit
            $locale = $product->getTranslation()->getLocale();
            $item->addChild(
                'link',
                $this->url . '/' .$this->router->generate(
                    'sylius_shop_product_show',
                    ['slug' => $product->getSlug(), '_locale' => $locale],
                    RouterInterface::RELATIVE_PATH
                )
            );

            // Image principale
            if (($image = $product->getImages()->first())) {
                $item->addChild(
                    'g:image_link',
                    $this->url . '/media/image/' . $image->getPath(),
                    'http://base.google.com/ns/1.0'
                );
            }

            // Prix, disponibilité, group id
            if ($price !== null) {
                $item->addChild(
                    'g:price',
                    number_format($price, 2, '.', '') . ' EUR',
                    'http://base.google.com/ns/1.0'
                );
            }

            $availability = $variant->isInStock() ? 'in stock' : 'preorder';
            $item->addChild('g:availability', $availability, 'http://base.google.com/ns/1.0');

            if (!$variant->isInStock()) {
                $item->addChild(
                    'g:availability_date',
                    (new \DateTime('+1 month'))->format('Y-m-d\TH:i:s\Z'),
                    'http://base.google.com/ns/1.0'
                );
            }

            $item->addChild('g:item_group_id', $variant->getCode(), 'http://base.google.com/ns/1.0');

            // Marque & condition
            $item->addChild('g:brand',     'Ma Pépinière', 'http://base.google.com/ns/1.0');
            $item->addChild('g:condition', 'new',          'http://base.google.com/ns/1.0');

            /* -----------------
             * Frais de port
             * ----------------*/
            $shipping = $item->addChild('g:shipping', '', 'http://base.google.com/ns/1.0');
            $shipping->addChild('g:country', 'FR', 'http://base.google.com/ns/1.0');
            $shippingCost = ($price !== null && $price < 80) ? 8.99 : 0;
            $shipping->addChild(
                'g:price',
                number_format($shippingCost, 2, '.', '') . ' EUR',
                'http://base.google.com/ns/1.0'
            );
        }

        $response = new Response($xml->asXML());
        $response->headers->set('Content-Type', 'text/xml; charset=UTF-8');

        return $response;
    }
}
