<?php

namespace Packagist\Jwt\Tests;

use PHPUnit\Framework\TestCase;
use YandexPayAndSplit\JWT\JWT;
use YandexPayAndSplit\JWT\JWK;
use UnexpectedValueException;

class JWTTest extends TestCase
{
    private $keys;
    private $testData;
    private $successJwtTokens;
    private $failedJwtTokens;

    protected function setUp(): void
    {
        $jwkSet = [
            "keys" => [
                [
                    "alg" => "ES256",
                    "kty" => "EC",
                    "crv" => "P-256",
                    "x" => "LEBfQpwTDXJtLFiPcnYvGv-WaFXZGBnFP_yGhLL9MGc",
                    "y" => "a1Or3o vkpH12b0o3ruZUtm_z8bg3xQtHXi-uPC7UJT0",
                    "kid" => "test-key",
                ],
            ],
        ];
        $this->keys = JWK::parseJWKSet($jwkSet);

        $this->testData = [
            "captured" => [
                "event" => "ORDER_STATUS_UPDATED",
                "eventTime" => "2025-04-22T10:24:16.326092+00:00",
                "merchantId" => "349c2eae-9285-4096-a362-915f0a65e126",
                "order" => [
                    "orderId" => "3120",
                    "paymentStatus" => "CAPTURED",
                ],
            ],
            "refunded" => [
                "event" => "ORDER_STATUS_UPDATED",
                "eventTime" => "2025-04-22T10:42:38.820413+00:00",
                "merchantId" => "349c2eae-9285-4096-a362-915f0a65e126",
                "order" => [
                    "orderId" => "3120",
                    "paymentStatus" => "REFUNDED",
                ],
            ],
        ];

        $this->successJwtTokens = [
            "captured_order" =>
                "eyJhbGciOiJFUzI1NiIsImNydiI6IlAtMjU2IiwiZXhwIjoxNzQ1MzE3NzU2LCJpYXQiOjE3NDUzMTc0NTYsImtp" .
                "ZCI6InRlc3Qta2V5Iiwia3R5IjoiRUMiLCJ0eXAiOiJKV1QifQ.eyJldmVudCI6Ik9SREVSX1NUQVRVU19VUERB" .
                "VEVEIiwiZXZlbnRUaW1lIjoiMjAyNS0wNC0yMlQxMDoyNDoxNi4zMjYwOTIrMDA6MDAiLCJtZXJjaGFudElkIj" .
                "oiMzQ5YzJlYWUtOTI4NS00MDk2LWEzNjItOTE1ZjBhNjVlMTI2Iiwib3JkZXIiOnsib3JkZXJJZCI6IjMxMjAi" .
                "LCJwYXltZW50U3RhdHVzIjoiQ0FQVFVSRUQifX0.zumXHmKsYnhgUwCRQZNBtREYEjqYqsUk3eJTPQiUp3cR" .
                "zBavd4pad6acae26POpBv1_nnh1YJR_ZClozFOXudA",
            "refunded_order" =>
                "eyJhbGciOiJFUzI1NiIsImNydiI6IlAtMjU2IiwiZXhwIjoxNzQ1MzE4ODU5LCJpYXQiOjE3NDUzMTg1NTks" .
                "ImtpZCI6InRlc3Qta2V5Iiwia3R5IjoiRUMiLCJ0eXAiOiJKV1QifQ.eyJldmVudCI6Ik9SREVSX1NUQVRVU1" .
                "9VUERBVEVEIiwiZXZlbnRUaW1lIjoiMjAyNS0wNC0yMlQxMDo0MjozOC44MjA0MTMrMDA6MDAiLCJtZXJjaGFu" .
                "dElkIjoiMzQ5YzJlYWUtOTI4NS00MDk2LWEzNjItOTE1ZjBhNjVlMTI2Iiwib3JkZXIiOnsib3JkZXJJZCI6" .
                "IjMxMjAiLCJwYXltZW50U3RhdHVzIjoiUkVGVU5ERUQifX0.W9mSi_BpVoH5M2nsun0WSV9IcKkET_ZUsZ73" .
                "5evtZo7wXINEeqm1yk8axxrLFZ7HPuwdSbnCp54WK6DYyhe3zA",
            "broken_jwt" =>
                "eyJhbGciOiJFUzI1NiIsImNydiI6IlAtMjU2IiwiZXhwIjoxNzQ1MzE4ODU5LCJpYXQiOjE3NDUzMTg1NTks" .
                "ImtpZCI6InRlc3Qta2V5Iiwia3R5IjoiRUMiLCJ0eXAiOiJKV1QifQ.eyJldmVudCI6Ik9SREVSX1NUQVRVU1" .
                "9VUERBVEVEIiwiZXZlbnRUaW1lIjoiMjAyNS0wNC0yMlQxMDo0MjozOC44MjA0MTMrMDA6MDAiLCJtZXJjaGFu" .
                "dElkIjoiMzQ5YzJlYWUtOTI4NS00MDk2LWEzNjItOTE1ZjBhNjVlMTI2Iiwib3JkZXIiOnsib3JkZXJJZCI6" .
                "IjMxMjAiLCJwYXltZW50U3RhdHVzIjoiUkVGVU5ERUQifX0.W9mSi_BpVoH5M2nsun0WSV9IcKkET_ZUsZ73" .
                "5evtZo7wXINEeqm1yk8axxrLFZ7HPuwdSbnCp54WK6DYyhe3bz",
        ];

        $this->failedJwtTokens = [
            "unsupported_algorithm" => $this->createFakeJwt(
                [
                    "alg" => "RS256",
                    "typ" => "JWT",
                    "kid" => "test-key",
                ],
                [
                    "event" => "ORDER_STATUS_UPDATED",
                    "merchantId" => "test",
                ]
            ),
            "invalid_kid" => $this->createFakeJwt(
                [
                    "alg" => "ES256",
                    "typ" => "JWT",
                    "kid" => "non-existent-key",
                ],
                [
                    "event" => "ORDER_STATUS_UPDATED",
                    "merchantId" => "test",
                ]
            ),
            "missing_kid" => $this->createFakeJwt(
                [
                    "alg" => "ES256",
                    "typ" => "JWT",
                ],
                [
                    "event" => "ORDER_STATUS_UPDATED",
                    "merchantId" => "test",
                ]
            ),
        ];
    }

    protected function tearDown(): void
    {
        $this->keys = null;
        $this->testData = null;
        $this->successJwtTokens = null;
        $this->failedJwtTokens = null;
    }

    private function getKeys()
    {
        return $this->keys;
    }

    private function getExpectedObject(array $data): object
    {
        return json_decode(json_encode($data));
    }

    private function createFakeJwt(
        array $header,
        array $payload,
        string $signature = "fake_signature"
    ): string {
        $headerEncoded = base64_encode(json_encode($header));
        $payloadEncoded = base64_encode(json_encode($payload));

        return $headerEncoded . "." . $payloadEncoded . "." . $signature;
    }

    public function testJwtReturnsCapturedOrder()
    {
        $expectedObject = $this->getExpectedObject($this->testData["captured"]);
        $decoded = JWT::decode($this->successJwtTokens["captured_order"], $this->getKeys());

        $this->assertEquals($expectedObject, $decoded);
    }

    public function testJwtReturnsRefundedOrder()
    {
        $expectedObject = $this->getExpectedObject($this->testData["refunded"]);
        $decoded = JWT::decode($this->successJwtTokens["refunded_order"], $this->getKeys());

        $this->assertEquals($expectedObject, $decoded);
    }

    public function testJwtReturnsIncorrectJWTSignature()
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("Incorrect JWT signature");

        JWT::decode($this->successJwtTokens["broken_jwt"], $this->getKeys());
    }

    public function testJwtReturnsUnsupportedAlgorithm()
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("JWT algorithm are not supported");

        JWT::decode($this->failedJwtTokens["unsupported_algorithm"], $this->getKeys());
    }

    public function testJwtReturnsInvalidKid()
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('JWT header member "kid" invalid');

        JWT::decode($this->failedJwtTokens["invalid_kid"], $this->getKeys());
    }

    public function testJwtReturnsMissingKid()
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("Required member 'kid' is missing in header of JWT");

        JWT::decode($this->failedJwtTokens["missing_kid"], $this->getKeys());
    }
}
