<?php

namespace YandexPayAndSplit\JWT;

use stdClass;
use UnexpectedValueException;

class JWT
{
    const ASN1_SEQUENCE = 0x10;
    const ASN1_INTEGER = 0x02;

    public static $timestamp = null;

    public static function decode($jwt, array $keys)
    {
        $timestamp = is_null(static::$timestamp) ? time() : static::$timestamp;

        $segments = explode(".", $jwt);

        if (count($segments) !== 3) {
            throw new UnexpectedValueException("Wrong number of segments in JWT");
        }

        list($head, $body, $sign) = $segments;

        $header_json = static::segmentDecode($head);
        $header = static::jsonDecode($header_json);

        if ($header === null) {
            throw new UnexpectedValueException("Invalid JWT header encoding");
        }

        if (!property_exists($header, "alg")) {
            throw new UnexpectedValueException("Required member 'alt' is missing in header of JWT");
        }

        if ($header->alg !== "ES256") {
            throw new UnexpectedValueException("JWT algorithm are not supported");
        }

        if (!property_exists($header, "kid")) {
            throw new UnexpectedValueException("Required member 'kid' is missing in header of JWT");
        }

        if (empty($header->kid)) {
            throw new UnexpectedValueException('JWT header member "kid" must not be empty');
        }

        if (!isset($keys[$header->kid])) {
            throw new UnexpectedValueException('JWT header member "kid" invalid');
        }

        $key = $keys[$header->kid];

        $signature_json = static::segmentDecode($sign);
        $signature = self::signatureToDER($signature_json);

        if (!self::verifySignature($head . "." . $body, $signature, $key)) {
            throw new UnexpectedValueException("Incorrect JWT signature");
        }

        $payload_json = static::segmentDecode($body);
        $payload = static::jsonDecode($payload_json);

        if ($payload === null) {
            throw new UnexpectedValueException("Invalid JWT payload encoding");
        }

        if (is_array($payload)) {
            $payload = (object) $payload;
        }

        if (!$payload instanceof stdClass) {
            throw new UnexpectedValueException("JWT payload must be a JSON object");
        }

        if (isset($payload->nbf) && $payload->nbf > $timestamp) {
            throw new UnexpectedValueException(
                "The JWT token cannot be used until " . date(DATE_ATOM, $payload->nbf)
            );
        }

        if (isset($payload->iat) && $payload->iat > $timestamp) {
            throw new UnexpectedValueException(
                "The JWT token contains a future issue date: " . date(DATE_ATOM, $payload->iat)
            );
        }

        if (isset($payload->exp) && $payload->exp <= $timestamp) {
            throw new UnexpectedValueException("The validity period of the JWT token has expired");
        }

        return $payload;
    }

    private static function verifySignature($message, $signature, $key)
    {
        $result = openssl_verify($message, $signature, $key, "SHA256");

        if ($result === 1) {
            return true;
        }
        if ($result === 0) {
            return false;
        }

        throw new UnexpectedValueException(
            "Signature verification error — " . openssl_error_string()
        );
    }

    public static function segmentDecode($input)
    {
        $remainder = strlen($input) % 4;

        if ($remainder) {
            $padlen = 4 - $remainder;
            $input .= str_repeat("=", $padlen);
        }

        return base64_decode(strtr($input, "-_", "+/"));
    }

    private static function jsonDecode($json)
    {
        $obj = json_decode($json, false, 512);

        $err_num = json_last_error();

        // phpcs:disable
        $messages = array(
            JSON_ERROR_NONE => "No error has occurred",
            JSON_ERROR_DEPTH => "The maximum stack depth has been exceeded",
            JSON_ERROR_STATE_MISMATCH => "Invalid or malformed JSON",
            JSON_ERROR_CTRL_CHAR => "Control character error, possibly incorrectly encoded",
            JSON_ERROR_SYNTAX => "Syntax error",
            JSON_ERROR_UTF8 => "Malformed UTF-8 characters, possibly incorrectly encoded", // PHP >= 5.3.3
            JSON_ERROR_RECURSION => "One or more recursive references in the value to be encoded", // PHP >= 5.5.0
            JSON_ERROR_INF_OR_NAN => "One or more NAN or INF values in the value to be encoded", // PHP >= 5.5.0
            JSON_ERROR_UNSUPPORTED_TYPE => "A value of a type that cannot be encoded was given", // PHP >= 5.5.0
            JSON_ERROR_INVALID_PROPERTY_NAME => "A property name that cannot be encoded was given", // PHP >= 7.0.0
            JSON_ERROR_UTF16 => "Malformed UTF-16 characters, possibly incorrectly encoded", // PHP >= 7.0.0
        );
        // phpcs:enable

        if ($err_num) {
            throw new UnexpectedValueException(
                isset($messages[$err_num]) ? $messages[$err_num] : "Unknown JSON error: " . $err_num
            );
        }

        return $obj;
    }

    private static function signatureToDER($sign)
    {
        $length = max(1, (int) (strlen($sign) / 2));

        list($r, $s) = str_split($sign, $length);

        $r = ltrim($r, "\x00");
        $s = ltrim($s, "\x00");

        if (ord($r[0]) > 0x7f) {
            $r = "\x00" . $r;
        }
        if (ord($s[0]) > 0x7f) {
            $s = "\x00" . $s;
        }

        return self::encodeDER(
            self::ASN1_SEQUENCE,
            self::encodeDER(self::ASN1_INTEGER, $r, self::ASN1_SEQUENCE) .
                self::encodeDER(self::ASN1_INTEGER, $s, self::ASN1_SEQUENCE),
            self::ASN1_SEQUENCE
        );
    }

    public static function encodeDER($type, $value, $asn1_sequence)
    {
        $tag_header = 0;
        if ($type === $asn1_sequence) {
            $tag_header |= 0x20;
        }

        $der = chr($tag_header | $type);

        $der .= chr(strlen($value));

        return $der . $value;
    }
}
