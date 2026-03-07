# yandex_pay_and_split/opencart

- Пакет для формирования заказов "Yandex Pay и Split" для Opencart 2 и 3
- Используем repositories path: https://getcomposer.org/doc/05-repositories.md#path

### Сущности

- `AdminController` – для отрисовки административной части плагина
- `CatalogController` - для отрисовки кнопки Яндекс Пэй на чекауте
- `Validate` - для валидации JWT токена приходящего на webhook
- `Webhook` - для обработки запроса от бекенда Яндекс Пэй на смену статуса
- `YaPayOrder` - для работы с заказами на форме чекаута

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
composer require yandex_pay_and_split/opencart -d path/to/project
```

P.S. Этим же скриптом обновляем пакет без бампа версии во время разработки
