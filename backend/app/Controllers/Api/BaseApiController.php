<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;

class BaseApiController extends ResourceController
{
    /**
     * Return standardized success JSON envelope.
     */
    protected function respondSuccess($data = null, string $message = 'Success', int $code = ResponseInterface::HTTP_OK, array $meta = [])
    {
        return $this->respond([
            'status'     => 'success',
            'code'       => $code,
            'message'    => $message,
            'data'       => $data,
            'meta'       => $meta,
            'timestamp'  => date('c'),
        ], $code);
    }

    /**
     * Return standardized error JSON envelope.
     */
    protected function respondError(string $message = 'Bad Request', int $code = ResponseInterface::HTTP_BAD_REQUEST, $errors = null, array $meta = [])
    {
        return $this->respond([
            'status'     => 'error',
            'code'       => $code,
            'message'    => $message,
            'errors'     => $errors,
            'meta'       => $meta,
            'timestamp'  => date('c'),
        ], $code);
    }

    /**
     * Return standardized 404 response.
     */
    protected function respondNotFound(string $message = 'Resource not found')
    {
        return $this->respondError($message, ResponseInterface::HTTP_NOT_FOUND);
    }
}

