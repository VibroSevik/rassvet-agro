<?php

namespace Packagist\Jwt\Tests;

use PHPUnit\Framework\TestCase;
use YandexPayAndSplit\JWT\JWK;
use InvalidArgumentException;

class JWKTest extends TestCase
{
    private $validJwkSet;

    protected function setUp(): void
    {
        $this->validJwkSet = [
            "keys" => [
                [
                    "alg" => "ES256",
                    "kty" => "EC",
                    "crv" => "P-256",
                    "x" => "LEBfQpwTDXJtLFiPcnYvGv-WaFXZGBnFP_yGhLL9MGc",
                    "y" => "a1Or3ovkpH12b0o3ruZUtm_z8bg3xQtHXi-uPC7UJT0",
                    "kid" => "test-key",
                ],
            ],
        ];
    }

    public function testParseJWKSetSuccess()
    {
        $result = JWK::parseJWKSet($this->validJwkSet);

        $this->assertIsArray($result);
        $this->assertArrayHasKey("test-key", $result);
        $this->assertIsString($result["test-key"]);
        $this->assertStringContainsString("-----BEGIN PUBLIC KEY-----", $result["test-key"]);
        $this->assertStringContainsString("-----END PUBLIC KEY-----", $result["test-key"]);
    }

    public function testParseJWKSetMissingKeys()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Required member 'keys' is missing in JWK set");

        $invalidJwkSet = ["some_other_field" => "value"];
        JWK::parseJWKSet($invalidJwkSet);
    }

    public function testParseJWKSetEmptyKeys()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("The list of keys in the JWK set must not be empty");

        $invalidJwkSet = ["keys" => []];
        JWK::parseJWKSet($invalidJwkSet);
    }

    public function testParseJWKSetWithMultipleKeys()
    {
        $multipleKeysJwkSet = [
            "keys" => [
                [
                    "alg" => "ES256",
                    "kty" => "EC",
                    "crv" => "P-256",
                    "x" => "LEBfQpwTDXJtLFiPcnYvGv-WaFXZGBnFP_yGhLL9MGc",
                    "y" => "a1Or3ovkpH12b0o3ruZUtm_z8bg3xQtHXi-uPC7UJT0",
                    "kid" => "key-1",
                ],
                [
                    "alg" => "ES256",
                    "kty" => "EC",
                    "crv" => "P-256",
                    "x" => "LEBfQpwTDXJtLFiPcnYvGv-WaFXZGBnFP_yGhLL9MGc",
                    "y" => "a1Or3ovkpH12b0o3ruZUtm_z8bg3xQtHXi-uPC7UJT0",
                    "kid" => "key-2",
                ],
            ],
        ];

        $result = JWK::parseJWKSet($multipleKeysJwkSet);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertArrayHasKey("key-1", $result);
        $this->assertArrayHasKey("key-2", $result);
    }

    public function testParseJWKSetWithoutKid()
    {
        $jwkSetWithoutKid = [
            "keys" => [
                [
                    "alg" => "ES256",
                    "kty" => "EC",
                    "crv" => "P-256",
                    "x" => "LEBfQpwTDXJtLFiPcnYvGv-WaFXZGBnFP_yGhLL9MGc",
                    "y" => "a1Or3ovkpH12b0o3ruZUtm_z8bg3xQtHXi-uPC7UJT0",
                    // Отсутствует kid
                ],
            ],
        ];

        $result = JWK::parseJWKSet($jwkSetWithoutKid);

        $this->assertIsArray($result);
        $this->assertArrayHasKey("0", $result);
    }
}
