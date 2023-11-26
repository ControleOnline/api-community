<?php
namespace App\Tests\Functional;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\User;

class LessonResourceTest extends ApiTestCase
{
    public function testGetSchoolClass()
    {
        $client = self::createClient();
        $em = self::$container->get('doctrine')->getManager();
        /** @var User $user */
        $user = $em->getRepository(User::class)->find(1816);

        $client->request('GET', '/lessons', [
            'headers' => ['api-token' => $user->getApiKey()],
        ]);

        $this->assertResponseStatusCodeSame(200);
    }
}
