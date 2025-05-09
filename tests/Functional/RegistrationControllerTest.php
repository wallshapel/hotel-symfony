<?php

namespace App\Tests\Functional\Controller;

use App\Contract\UserRegistrationInterface;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\CustomWebTestCase;

class RegistrationControllerTest extends CustomWebTestCase
{
    public function testRegisterReturnsCreatedResponse(): void
    {
        $client = static::createClient();

        // Mock del servicio con retorno exitoso
        self::getContainer()->set(UserRegistrationInterface::class, new class implements UserRegistrationInterface {
            public function registerUser(array $data): array
            {
                return [
                    'message' => 'Registered successfully',
                    'status' => Response::HTTP_CREATED
                ];
            }
        });

        $payload = [
            'email' => 'test@example.com',
            'username' => 'tester',
            'password' => 'secure123'
        ];

        $client->request(
            'POST',
            '/api/v1/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Registered successfully', $responseData['message']);
    }

    public function testRegisterReturnsBadRequestIfFieldsAreMissing(): void
    {
        $client = static::createClient();

        self::getContainer()->set(UserRegistrationInterface::class, new class implements UserRegistrationInterface {
            public function registerUser(array $data): array
            {
                return [
                    'message' => 'Missing required fields: email, username, password',
                    'status' => Response::HTTP_BAD_REQUEST
                ];
            }
        });

        $client->request(
            'POST',
            '/api/v1/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([]) // vacío
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertStringContainsString('Missing required fields', $responseData['message']);
    }

    public function testRegisterReturnsConflictIfEmailExists(): void
    {
        $client = static::createClient();

        self::getContainer()->set(UserRegistrationInterface::class, new class implements UserRegistrationInterface {
            public function registerUser(array $data): array
            {
                return [
                    'message' => 'A user with this email already exists.',
                    'status' => Response::HTTP_CONFLICT
                ];
            }
        });

        $client->request(
            'POST',
            '/api/v1/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'existing@example.com',
                'username' => 'anyuser',
                'password' => 'pass123'
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('A user with this email already exists.', $responseData['message']);
    }

    public function testRegisterReturnsConflictIfUsernameExists(): void
    {
        $client = static::createClient();

        self::getContainer()->set(UserRegistrationInterface::class, new class implements UserRegistrationInterface {
            public function registerUser(array $data): array
            {
                return [
                    'message' => 'Username already exists.',
                    'status' => Response::HTTP_CONFLICT
                ];
            }
        });

        $client->request(
            'POST',
            '/api/v1/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'unique@example.com',
                'username' => 'takenuser',
                'password' => 'pass123'
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Username already exists.', $responseData['message']);
    }

    public function testRegisterReturnsBadRequestIfValidationFails(): void
    {
        $client = static::createClient();

        self::getContainer()->set(UserRegistrationInterface::class, new class implements UserRegistrationInterface {
            public function registerUser(array $data): array
            {
                return [
                    'errors' => ['email' => 'Invalid email format.'],
                    'status' => Response::HTTP_BAD_REQUEST
                ];
            }
        });

        $client->request(
            'POST',
            '/api/v1/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'invalid-email',
                'username' => 'user',
                'password' => 'short'
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('errors', $responseData);
        $this->assertArrayHasKey('email', $responseData['errors']);
    }

    public function testRegisterReturnsBadRequestIfInvalidJson(): void
    {
        $client = static::createClient();

        self::getContainer()->set(UserRegistrationInterface::class, new class implements UserRegistrationInterface {
            public function registerUser(array $data): array
            {
                return [
                    'message' => 'Invalid JSON format.',
                    'status' => Response::HTTP_BAD_REQUEST
                ];
            }
        });

        // Simulamos JSON mal formado
        $client->request(
            'POST',
            '/api/v1/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{invalid_json' // <-- sin cerrar
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Invalid JSON format.', $responseData['message']);
    }
}
