<?php

use CodeIgniter\Test\CIUnitTestCase;
use App\Services\AuthService;
use App\Controllers\Api\AuthController;
use App\Controllers\Api\AdminController;

/**
 * @internal
 */
final class AuthTest extends CIUnitTestCase
{
    public function testAuthServiceInstantiates(): void
    {
        $service = new AuthService();
        $this->assertInstanceOf(AuthService::class, $service);
    }

    public function testAuthControllerInstantiates(): void
    {
        $controller = new AuthController();
        $this->assertInstanceOf(AuthController::class, $controller);
    }

    public function testAdminControllerInstantiates(): void
    {
        $controller = new AdminController();
        $this->assertInstanceOf(AdminController::class, $controller);
    }

    public function testPasswordArgon2idOrBcryptHashing(): void
    {
        $password = 'SecretAdminPass123!';
        $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
        $hash = password_hash($password, $algo);

        $this->assertNotEmpty($hash);
        $this->assertTrue(password_verify($password, $hash));
        $this->assertFalse(password_verify('WrongPassword', $hash));
    }

    public function testTokenHashingEntropy(): void
    {
        $rawToken1 = bin2hex(random_bytes(32));
        $rawToken2 = bin2hex(random_bytes(32));

        $this->assertEquals(64, strlen($rawToken1));
        $this->assertNotEquals($rawToken1, $rawToken2);

        $hash1 = hash('sha256', $rawToken1);
        $hash2 = hash('sha256', $rawToken2);

        $this->assertEquals(64, strlen($hash1));
        $this->assertNotEquals($hash1, $hash2);
    }
}
