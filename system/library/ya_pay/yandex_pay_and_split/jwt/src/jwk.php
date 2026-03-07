<?php

namespace YandexPayAndSplit\JWT;

use InvalidArgumentException;
use UnexpectedValueException;

class JWK
{
    // Используется для определения криптографического алгоритма Elliptic Curve Cryptography (ECC)
    const OID = "1.2.840.10045.2.1";
    // Определяет конкретную эллиптическую кривую - P-256. Она применяется в ES256
    const CRV = "1.2.840.10045.3.1.7";

    const ASN1_SEQUENCE = 0x10;
    const ASN1_OBJECT_IDENTIFIER = 0x06;
    const ASN1_BIT_STRING = 0x03;

    public static function parseJWKSet(array $jwk_set)
    {
        if (!isset($jwk_set["keys"])) {
            throw new InvalidArgumentException("Required member 'keys' is missing in JWK set");
        }

        if (empty($jwk_set["keys"])) {
            throw new InvalidArgumentException("The list of keys in the JWK set must not be empty");
        }

        $parsed_keys = array();

        foreach ($jwk_set["keys"] as $k => $v) {
            $kid = isset($v["kid"]) ? $v["kid"] : $k;

            $parsed_key = self::parseJWK($v);

            if (!empty($parsed_key)) {
                $parsed_keys[(string) $kid] = $parsed_key;
            }
        }

        return $parsed_keys;
    }

    private static function parseJWK(array $jwk)
    {
        if (empty($jwk)) {
            throw new InvalidArgumentException("JWK must not be empty");
        }

        if (!isset($jwk["kty"])) {
            throw new InvalidArgumentException("Required member 'kty' is missing in JWK");
        }

        if ($jwk["kty"] !== "EC") {
            throw new InvalidArgumentException("JWK type are not supported");
        }

        if (!isset($jwk["alg"])) {
            throw new InvalidArgumentException("Required member 'alg' is missing in JWK");
        }

        if ($jwk["alg"] !== "ES256") {
            throw new InvalidArgumentException("JWK algorithm are not supported");
        }

        if (isset($jwk["d"])) {
            throw new InvalidArgumentException("JWK must be public");
        }

        if (!isset($jwk["crv"])) {
            throw new InvalidArgumentException("Required member 'crv' is missing in JWK");
        }

        if ($jwk["crv"] !== "P-256") {
            throw new InvalidArgumentException("JWK curve are not supported");
        }

        if (empty($jwk["x"]) || empty($jwk["y"])) {
            throw new InvalidArgumentException(
                "The JWK does not contain the required member 'x' or 'y', or it's empty"
            );
        }

        $public_key = self::createPem($jwk["x"], $jwk["y"]);

        if (empty($public_key)) {
            throw new UnexpectedValueException("Public key must not be empty");
        }

        return $public_key;
    }

    private static function createPem($x, $y)
    {
        $pem = JWT::encodeDER(
            self::ASN1_SEQUENCE,
            JWT::encodeDER(
                self::ASN1_SEQUENCE,
                JWT::encodeDER(
                    self::ASN1_OBJECT_IDENTIFIER,
                    self::encodeOID(self::OID),
                    self::ASN1_SEQUENCE
                ) .
                    JWT::encodeDER(
                        self::ASN1_OBJECT_IDENTIFIER,
                        self::encodeOID(self::CRV),
                        self::ASN1_SEQUENCE
                    ),
                self::ASN1_SEQUENCE
            ) .
                JWT::encodeDER(
                    self::ASN1_BIT_STRING,
                    chr(0x00) . chr(0x04) . JWT::segmentDecode($x) . JWT::segmentDecode($y),
                    self::ASN1_SEQUENCE
                ),
            self::ASN1_SEQUENCE
        );

        return sprintf(
            "-----BEGIN PUBLIC KEY-----\n%s\n-----END PUBLIC KEY-----\n",
            wordwrap(base64_encode($pem), 64, "\n", true)
        );
    }

    private static function encodeOID($oid)
    {
        $octets = explode(".", $oid);

        $first = (int) array_shift($octets);
        $second = (int) array_shift($octets);

        $oid = chr($first * 40 + $second);

        foreach ($octets as $octet) {
            if ($octet == 0) {
                $oid .= chr(0x00);

                continue;
            }

            $bin = "";

            while ($octet) {
                $bin .= chr(0x80 | ($octet & 0x7f));

                $octet >>= 7;
            }

            $bin[0] = $bin[0] & chr(0x7f);

            if (pack("V", 65534) == pack("L", 65534)) {
                $oid .= strrev($bin);
            } else {
                $oid .= $bin;
            }
        }

        return $oid;
    }
}
