<?php

namespace App\Tests\Functional;

class ApiPostsTest extends DatabaseWebTestCase
{
    public function testApiPostsReturnsValidJson(): void
    {
        $this->resetDatabase();
        $client = static::createClient();
        $client->request('GET', '/api/posts', server: ['HTTP_ACCEPT' => 'application/ld+json']);

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertIsArray($data);
    }
}
