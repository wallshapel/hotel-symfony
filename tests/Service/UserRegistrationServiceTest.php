<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\UserRegistrationService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserRegistrationServiceTest extends TestCase
{
    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $passwordHasher;
    private ValidatorInterface $validator;
    private UserRegistrationService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);

        $this->service = new UserRegistrationService(
            $this->em,
            $this->passwordHasher,
            $this->validator
        );
    }

    public function testRegisterUserReturnsBadRequestWhenRequiredFieldsAreMissing(): void
    {
        $result = $this->service->registerUser([]);

        $this->assertEquals(JsonResponse::HTTP_BAD_REQUEST, $result['status']);
        $this->assertStringContainsString('Missing required fields', $result['message']);
        $this->assertStringContainsString('email', $result['message']);
        $this->assertStringContainsString('username', $result['message']);
        $this->assertStringContainsString('password', $result['message']);
    }

    public function testRegisterUserReturnsConflictIfEmailAlreadyExists(): void
    {
        // Simulamos un usuario existente
        $existingUser = $this->createMock(User::class);

        $userRepo = $this->createMock(EntityRepository::class);

        $userRepo
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'existing@example.com'])
            ->willReturn($this->createMock(User::class));

        $this->em
            ->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($userRepo);

        $data = [
            'email' => 'existing@example.com',
            'username' => 'newuser',
            'password' => 'password123'
        ];

        $result = $this->service->registerUser($data);

        $this->assertEquals(JsonResponse::HTTP_CONFLICT, $result['status']);
        $this->assertEquals('A user with this email already exists.', $result['message']);
    }

    public function testRegisterUserReturnsConflictIfUsernameAlreadyExists(): void
    {
        $userRepo = $this->createMock(EntityRepository::class);

        $userRepo
            ->method('findOneBy')
            ->willReturnCallback(function ($criteria) {
                if (isset($criteria['email']) && $criteria['email'] === 'new@example.com') {
                    return null; // Email no existe
                }

                if (isset($criteria['username']) && $criteria['username'] === 'existinguser') {
                    return $this->createMock(User::class); // Username ya existe
                }

                return null;
            });

        $this->em
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($userRepo);

        $data = [
            'email' => 'new@example.com',
            'username' => 'existinguser',
            'password' => 'password123'
        ];

        $result = $this->service->registerUser($data);

        $this->assertEquals(JsonResponse::HTTP_CONFLICT, $result['status']);
        $this->assertEquals('Username already exists.', $result['message']);
    }

    public function testRegisterUserReturnsBadRequestWhenValidationFails(): void
    {
        // Mock del repositorio que retorna null (email y username disponibles)
        $userRepo = $this->createMock(EntityRepository::class);
        $userRepo->method('findOneBy')->willReturn(null);

        $this->em
            ->method('getRepository')
            ->willReturn($userRepo);

        // Simulamos una violación de validación
        $violation = $this->createMock(ConstraintViolationInterface::class);
        $violation->method('getPropertyPath')->willReturn('email');
        $violation->method('getMessage')->willReturn('Invalid email format.');

        $violationList = new ConstraintViolationList([$violation]);

        $this->validator
            ->method('validate')
            ->willReturn($violationList);

        $data = [
            'email' => 'bad-email',
            'username' => 'validuser',
            'password' => 'password123'
        ];

        $result = $this->service->registerUser($data);

        $this->assertEquals(JsonResponse::HTTP_BAD_REQUEST, $result['status']);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('email', $result['errors']);
        $this->assertEquals('Invalid email format.', $result['errors']['email']);
    }

    public function testRegisterUserReturnsCreatedWhenDataIsValid(): void
    {
        // Repositorio que retorna null: ni email ni username existen
        $userRepo = $this->createMock(EntityRepository::class);
        $userRepo->method('findOneBy')->willReturn(null);

        $this->em
            ->method('getRepository')
            ->willReturn($userRepo);

        // Validación sin errores
        $this->validator
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        // Hash simulado
        $this->passwordHasher
            ->method('hashPassword')
            ->willReturn('hashed_password_123');

        // Esperamos que persist y flush se llamen 1 vez
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $data = [
            'email' => 'new@example.com',
            'username' => 'newuser',
            'password' => 'validpassword'
        ];

        $result = $this->service->registerUser($data);

        $this->assertEquals(JsonResponse::HTTP_CREATED, $result['status']);
        $this->assertEquals('Registered successfully', $result['message']);
    }
}
