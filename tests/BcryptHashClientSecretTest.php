<?php

use Drewlabs\Oauth\Clients\BcryptHashClientSecret;
use Drewlabs\Oauth\Clients\Contracts\SecretClientInterface;
use Drewlabs\Oauth\Clients\PasswordVerifyClientSecretEngine;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class BcryptHashClientSecretTest extends TestCase
{
    public function test_bcrypt_hash_client_secret_returns_string_hashed_value()
    {
        $secretHash = new BcryptHashClientSecret;
        $is_string  = is_string($secretHash->hashSecret('MySuperSecretPassword'));
        $this->assertTrue($is_string);
    }

    public function test_verify_client_secret_returns_true_on_result_of_bcrypt_hash_if_password_equals_original()
    {
        /**
         * @var SecretClientInterface&MockObject
         */
        $client = $this->createMock(SecretClientInterface::class);

        $client->method('getHashedSecret')
            ->willReturn((new BcryptHashClientSecret)->hashSecret('MyPassword'));

        // Assert
        $this->assertTrue((new PasswordVerifyClientSecretEngine)->verify($client, 'MyPassword'));
    }

    public function test_verify_client_secret_returns_false_on_result_of_bcrypt_hash_if_password_does_not_equals_original()
    {
        /**
         * @var SecretClientInterface&MockObject
         */
        $client = $this->createMock(SecretClientInterface::class);

        $client->method('getHashedSecret')
            ->willReturn((new BcryptHashClientSecret)->hashSecret('FakePassword'));

        // Assert
        $this->assertFalse((new PasswordVerifyClientSecretEngine)->verify($client, 'MyPassword'));
    }
}