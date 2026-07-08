<?php

/**
 * App Result API model
 */

namespace Application\Model;

class ApiResultModel
{
    public const RESPONSE_CODE_FAILED = 0;
    public const RESPONSE_CODE_SUCCESS = 1;
    public const RESPONSE_CODE_SERVER_ERROR = 10;
    protected ?int $code = null;
    protected mixed $errorCode = null;
    protected mixed $container;
    protected array $messages = [];
    protected array $data = [];
    protected array $dataResPaginator = [
        'totalPages' => 0,
        'totalItems' => 0,
        'page'       => 0,
        'result'     => [],
    ];
    public function setData($data)
    {
        return $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }

    private function toJsonResponse(): JsonResponse
    {
        $jsonModel = new JsonResponse();
        $jsonModel->setVariables([
            'code'      => $this->code,
            'errorCode' => $this->errorCode,
            'messages'  => $this->messages ?: [],
            'data'      => $this->data,
        ]);
        return $jsonModel;
    }

    public function errorCodeResponse($options = []): JsonResponse
    {
        $this->code      = self::RESPONSE_CODE_FAILED;
        $this->errorCode = $options['errorCode'] ?? '';
        $this->messages  = $options['messages'] ?? [];
        return $this->toJsonResponse();
    }

    // Client post value không hợp lệ (sai kiểu, thiếu field...)
    public function errorInvalidFormResponse($messages = []): JsonResponse
    {
        $this->errorCode = AppConst::ERR_INVALID_FORM_FIELDS;
        return $this->errorResponse($messages);
    }

    // Không có quyền truy cập dữ liệu
    public function errorData403Response($messages = []): JsonResponse
    {
        $this->errorCode = AppConst::ERR_DATA_403;
        return $this->errorResponse($messages);
    }

    // User chưa login
    public function errorPage401Response($messages = []): JsonResponse
    {
        $this->errorCode = AppConst::ERR_401;
        return $this->errorResponse($messages);
    }

    // Server không trả về response
    public function errorData404Response($messages = []): JsonResponse
    {
        $this->errorCode = AppConst::ERR_DATA_404;
        return $this->errorResponse($messages);
    }

    public function errorResponse($messages = [], $data = []): JsonResponse
    {
        $this->code = self::RESPONSE_CODE_FAILED;
        if (! empty($messages) && is_array($messages)) {
            $this->messages = $messages;
        }
        if (! empty($data) && is_array($data)) {
            $this->data = $data;
        }
        return $this->toJsonResponse();
    }

    public function successResponse($data = [], $messages = []): JsonResponse
    {
        $this->code = self::RESPONSE_CODE_SUCCESS;
        if (! empty($data) && is_array($data)) {
            $this->data = $data;
        }
        if (! empty($messages) && is_array($messages)) {
            $this->messages = $messages;
        }
        return $this->toJsonResponse();
    }
}
