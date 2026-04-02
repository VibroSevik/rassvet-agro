<?php

namespace ResponseUtils;

class ResponseStatuses {
    public const HTTP_OK = [
        'text' => 'OK',
        'code' => 200
    ];

    public const HTTP_BAD_REQUEST = [
        'text' => 'Bad Request',
        'code' => 400
    ];

    public const HTTP_METHOD_NOT_ALLOWED = [
        'text' => 'Method Not Allowed',
        'code' => 405
    ];
}