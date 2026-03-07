# yandex_pay_and_split/jwt

- Пакет для декодирования JWT-токена
- Используем repositories path: https://getcomposer.org/doc/05-repositories.md#path

### Сущности

- `JWK` – для парсинга набора ключей JWK
- `JWT` - для декодирования JWT токена

### Пример использования

```php
use YandexPayAndSplit\JWT\JWK;
use YandexPayAndSplit\JWT\JWT;

$JWT_TOKEN =
    'eyJhbGciOiJFUzI1NiIsImlhdCI6MTY1MDE5Njc1OCwia2lkIjoidGVzdC1rZXkiLCJ0eXAiOiJKV1QifQ.eyJjYXJ0Ijp7Iml0ZW1zIjpbeyJwcm9kdWN' .
    '0SWQiOiJwMSIsInF1YW50aXR5Ijp7ImNvdW50IjoiMSJ9fV19LCJjdXJyZW5jeUNvZGUiOiJSVUIiLCJtZXJjaGFudElkIjoiMjc2Y2YxZjEtZjhlZC00N' .
    'GZlLTg5ZTMtNWU0MTEzNDZkYThkIn0.YmQjHlh3ddLWgBexQ3QrwtbgAA3u1TVnBl1qnfMIvToBwinH3uH92KGB15m4NAQXdz5nhkjPZZu7RUStJt40PQ';

$keys_json =
    "{\"keys\": [{\"alg\": \"ES256\", \"kty\": \"EC\", \"crv\": \"P-256\", \"x\": \"LEBfQpwTDXJtLFiPcnYvGv-WaFXZGBnFP_yGhLL9MGc\", \"y\": \"a1Or3ovkpH12b0o3ruZUtm_z8bg3xQtHXi-uPC7UJT0\", \"kid\": \"test-key\"}]}";
$keys_data = json_decode($keys_json, true);

$keys = JWK::parseJWKSet($keys_data);
$payload = JWT::decode($JWT_TOKEN, $keys);

print_r($payload);
```

### Установка

Для начала добавляем в целевой `composer.json` проекта:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "relative/path/to/package/folder",
            "options": {
                "symlink": false
            }
        }
    ]
}
```

Затем можем выполнять установку в проект:

```bash
composer require yandex_pay_and_split/jwt -d path/to/project
```

P.S. Этим же скриптом обновляем пакет без бампа версии во время разработки

## Источник

Нам требовалась поддержка PHP версии 5.3 и выше, а также библиотека для декодирования JWT, зашифрованных с помощью ES256.
Была выбрана библиотека firebase/php-jwt версии 6.0.0, в которой была поддержка нужных версий PHP. Но в данной версии нет декодирования ES256 токенов.
В связи с этим, был сделан форк выбранной библиотеки firebase/php-jwt и были внесены изменения под наши нужны.

Этот код взят из firebase/php-jwt 6.3.0 (лицензия BSD с 3 пунктами)
Оригинальный источник: https://github.com/firebase/php-jwt/tree/v6.3.0

### Изменения

- Удалены неиспользуемые классы (BeforeValidException, CachedKeySet, ExpiredException, Key и SignatureInvalidException) и методы классов JWK и JWT
- Изменены методы классов JWK и JWT для работы только с ES256

### Лицензия

[3-Clause BSD](http://opensource.org/licenses/BSD-3-Clause)
