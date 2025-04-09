<?php

namespace Tests\Functional;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class CustomWebTestCase extends WebTestCase
{
    protected function createAuthenticatedClient(string $role = 'ROLE_ADMIN'): KernelBrowser
    {
        $client = static::createClient();
        
        assert($client instanceof KernelBrowser);

        $token = 'FAKE_TOKEN_FOR_ROLE_' . $role;

        /** @var KernelBrowser $client */
        $client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer ' . $token);

        return $client;
    }
}
