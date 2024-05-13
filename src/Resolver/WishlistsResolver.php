<?php

/*
 * This file was created by developers working at BitBag
 * Do you need more information about us and what we do? Visit our https://bitbag.io website!
 * We are hiring developers from all over the world. Join us and start your new, exciting adventure and become part of us: https://bitbag.io/career
*/

declare(strict_types=1);

namespace BitBag\SyliusWishlistPlugin\Resolver;

use BitBag\SyliusWishlistPlugin\Command\Wishlist\CreateWishlist;
use BitBag\SyliusWishlistPlugin\Entity\WishlistInterface;
use BitBag\SyliusWishlistPlugin\Repository\WishlistRepositoryInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Context\ChannelNotFoundException;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ShopUserInterface;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class WishlistsResolver implements WishlistsResolverInterface
{
    use HandleTrait;

    public function __construct(
        private WishlistRepositoryInterface $wishlistRepository,
        private TokenStorageInterface $tokenStorage,
        private WishlistCookieTokenResolverInterface $wishlistCookieTokenResolver,
        private ChannelContextInterface $channelContext,
        private TokenUserResolverInterface $tokenUserResolver,
        MessageBusInterface $messageBus
    ) {
    }

    public function resolve(): array
    {
        $token = $this->tokenStorage->getToken();
        $user = $this->tokenUserResolver->resolve($token);

        $wishlistCookieToken = $this->wishlistCookieTokenResolver->resolve();

        try {
            $channel = $this->channelContext->getChannel();
        } catch (ChannelNotFoundException $foundException) {
            $channel = null;
        }

        if ($user instanceof ShopUserInterface) {
            return $this->wishlistRepository->findAllByShopUserAndToken($user->getId(), $wishlistCookieToken);
        }

        if ($channel instanceof ChannelInterface) {
            return $this->wishlistRepository->findAllByAnonymousAndChannel($wishlistCookieToken, $channel);
        }

        return $this->wishlistRepository->findAllByAnonymous($wishlistCookieToken);
    }

    public function resolveAndCreate(): array
    {
        $wishlists = $this->resolve();

        $wishlistCookieToken = $this->wishlistCookieTokenResolver->resolve();

        try {
            $channel = $this->channelContext->getChannel();
        } catch (ChannelNotFoundException $foundException) {
            $channel = null;
        }

        if (0 === count($wishlists)) {
            $createWishlist = new CreateWishlist($wishlistCookieToken, $channel?->getCode());
            /** @var WishlistInterface $wishlist */
            $wishlist = $this->handle($createWishlist);

            $wishlists = [$wishlist];
        }

        return $wishlists;
    }
}
