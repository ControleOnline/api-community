<?php

namespace App\Tests\Functional;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\SchoolClass;
use App\Entity\User;

class SchoolClassResourceTest extends ApiTestCase
{
    public function testGetSchoolClass()
    {
        $client = self::createClient();
        $em = self::$container->get('doctrine')->getManager();
        $schoolClass = $em->getRepository(SchoolClass::class)->find(1);
        /** @var User $user */
        $user = $em->getRepository(User::class)->find(1816);

        $client->request('GET', '/school_classes/'.$schoolClass->getId(), [
            'headers' => ['api-token' => $user->getApiKey()],
        ]);

        $this->assertResponseStatusCodeSame(200);
    }

    public function testUpdateSchoolClass()
    {
        $client = self::createClient();
        $em = self::$container->get('doctrine')->getManager();
        $schoolClass = $em->getRepository(SchoolClass::class)->find(1);
        /** @var User $user */
        $user = $em->getRepository(User::class)->find(1816);

        $client->request('PUT', '/school_classes/'.$schoolClass->getId(), [
            'headers' => ['api-token' => $user->getApiKey()],
            'json' => ['homework' => 'The first homework'],
        ]);

        $this->assertResponseStatusCodeSame(200);
    }
}
