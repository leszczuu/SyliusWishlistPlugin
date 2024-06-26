<?php

/*
 * This file was created by developers working at BitBag
 * Do you need more information about us and what we do? Visit our https://bitbag.io website!
 * We are hiring developers from all over the world. Join us and start your new, exciting adventure and become part of us: https://bitbag.io/career
*/

declare(strict_types=1);

namespace BitBag\SyliusWishlistPlugin\Resolver;

use BitBag\SyliusWishlistPlugin\Entity\WishlistInterface;
use BitBag\SyliusWishlistPlugin\Factory\WishlistFactoryInterface;
use BitBag\SyliusWishlistPlugin\Repository\WishlistRepositoryInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Context\ChannelNotFoundException;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ShopUserInterface;

final class ShopUserWishlistResolver implements ShopUserWishlistResolverInterface
{
    public function __construct(
        private WishlistRepositoryInterface $wishlistRepository,
        private WishlistFactoryInterface $wishlistFactory,
        private ChannelContextInterface $channelContext
    ) {
    }

    public function resolve(ShopUserInterface $user): WishlistInterface
    {
        try {
            $channel = $this->channelContext->getChannel();
        } catch (ChannelNotFoundException $exception) {
            $channel = null;
        }

        if ($channel instanceof ChannelInterface) {
            return $this->wishlistRepository->findOneByShopUserAndChannel($user, $channel) ?? $this->wishlistFactory->createForUserAndChannel($user, $channel);
        }

        return $this->wishlistRepository->findOneByShopUser($user) ?? $this->wishlistFactory->createForUser($user);
    }
}
