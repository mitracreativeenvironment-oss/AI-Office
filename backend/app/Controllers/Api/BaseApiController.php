<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;

class BaseApiController extends ResourceController
{
    protected function respondSuccess($data = null, string $message = 'Success', int $code = ResponseInterface::HTTP_OK)
    {
        return $this->respond([
            'status'     => 'success',
            'code'       => $code,
            'message'    => $message,
            'data'       => $data,
            'timestamp'  => date('c'),
        ], $code);
    }

    protected function respondError(string $message = 'Bad Request', int $code = ResponseInterface::HTTP_BAD_REQUEST, $errors = null)
    {
        return $this->respond([
            'status'     => 'error',
            'code'       => $code,
            'message'    => $message,
            'errors'     => $errors,
            'timestamp'  => date('c'),
        ], $code);
    }

    protected function respondNotFound(string $message = 'Resource not found')
    {
        return $this->respondError($message, ResponseInterface::HTTP_NOT_FOUND);
    }
}
