<?php

namespace App\Tests\Unit;

use App\Entity\Post;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class PostEntityTest extends TestCase
{
    public function testLikeAndUnlikeRules(): void
    {
        $post = new Post();
        $user = (new User())
            ->setEmail('unit@example.com')
            ->setUsername('unit_user')
            ->setPassword('hash');

        $post->likeBy($user);
        $post->likeBy($user);
        self::assertSame(1, $post->getLikesCount());
        self::assertTrue($post->isLikedBy($user));

        $post->unlikeBy($user);
        self::assertSame(0, $post->getLikesCount());
        self::assertFalse($post->isLikedBy($user));
    }
}
