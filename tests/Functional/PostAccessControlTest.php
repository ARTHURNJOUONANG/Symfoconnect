<?php

namespace App\Tests\Functional;

class PostAccessControlTest extends DatabaseWebTestCase
{
    public function testPostCreationRedirectsToLoginWhenAnonymous(): void
    {
        $this->resetDatabase();
        $client = static::createClient();
        $client->request('GET', '/post/nouveau');

        self::assertResponseRedirects('/login');
    }

    public function testConnectedUserCanAccessPostForm(): void
    {
        $this->resetDatabase();
        $client = static::createClient();
        $user = $this->createUser();
        $client->loginUser($user);

        $client->request('GET', '/post/nouveau');
        self::assertResponseIsSuccessful();
    }
}
