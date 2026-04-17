<?php

namespace App\Tests\Functional;

class PublicPageTest extends DatabaseWebTestCase
{
    public function testHomepageRespondsWith200(): void
    {
        $this->resetDatabase();
        $client = static::createClient();
        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
    }
}
