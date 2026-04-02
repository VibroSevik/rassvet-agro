<?php

use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class JsonSerializer {

    private Serializer $serializer;

    public function __construct() {
        $encoders = [new JsonEncoder()];
        $normalizers = [
            new ArrayDenormalizer(),
            new ObjectNormalizer(null, null, null, new ReflectionExtractor())
        ];
        $this->serializer = new Serializer($normalizers, $encoders);
    }

    public function deserialize($data, string $type, array $context = []) {
        return $this->serializer->deserialize($data, $type, 'json', $context);
    }

    public function serialize($data, array $context = []): string {
        return $this->serializer->serialize($data, 'json', $context);
    }

    /**
     * @throws ExceptionInterface
     */
    public function normalize($data, array $context = []) {
        return $this->serializer->normalize($data, 'json', $context);
    }

    /**
     * @throws ExceptionInterface
     */
    public function denormalize($data, string $type, array $context = []) {
        return $this->serializer->denormalize($data, $type, 'json', $context);
    }
}