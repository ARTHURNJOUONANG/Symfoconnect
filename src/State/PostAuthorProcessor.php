<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Post;
use App\Entity\User;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Force l'auteur d'un post API au user connecte.
 */
final readonly class PostAuthorProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private Security $security,
        private CacheItemPoolInterface $cachePool
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof Post) {
            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Authentication required.');
        }

        $data->setAuthor($user);

        $result = $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        $this->cachePool->deleteItem(sprintf('feed_user_%d', $user->getId()));
        foreach ($user->getFollowers() as $follower) {
            $this->cachePool->deleteItem(sprintf('feed_user_%d', $follower->getId()));
        }

        return $result;
    }
}
