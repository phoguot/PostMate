<?php

/**
 * App controller
 */

namespace Application\Controller;

use Application\Model\ApiResultModel;
use Application\Model\JsonResponse;
use Exception;
use Interop\Container\ContainerInterface;
use Laminas\Http\Request;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\MvcEvent;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\View\Model\ViewModel;
use User\Service\UserService;

class AppController extends AbstractActionController implements FactoryInterface
{
    /**
     * Chuẩn hóa response lỗi dạng JSON và set HTTP status code.
     */
    public function responseError(MvcEvent $e, string $message, ?string $errorCode = null, int $statusCode = 403): JsonResponse
    {
        $response = (new ApiResultModel())->errorCodeResponse([
            'errorCode' => $errorCode,
            'messages'  => [$message],
        ]);
        $response->setStatusCode($statusCode);
        $e->setResponse($response);
        return $response;
    }

    public ?ViewModel $viewModel = null;
/**
     *
     * @var ContainerInterface
     */
    protected $container;
/**
     *
     * @return ViewModel
     */
    public function getViewModel(): ViewModel
    {
        if ($this->viewModel === null) {
            $this->viewModel = new ViewModel();
        }
        return $this->viewModel;
    }

    /**
     *
     * @return Request
     */
    public function getRequest()
    {
        return parent::getRequest();
    }


    protected function getUserService()
    {
        return $this->getContainerEntry(UserService::class);
    }

    /**
     *
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Lấy đối tượng từ container theo tên hoặc class.
     * @template T
     * @param class-string<T> $entryName
     * @return T
     */
    public function getContainerEntry(string $entryName)
    {
        try {
            return $this->getContainer()->get($entryName);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     *
     * @param ContainerInterface $container
     */
    public function setContainer($container)
    {
        $this->container = $container;
        return $this;
    }


    public function __invoke(\Psr\Container\ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $this->setContainer($container);
    }

    /**
     * get all $_POST params
     * @return array
     */
    public function getAllPostParams()
    {
        return array_merge_recursive($this->getRequest()->getPost()->toArray(), $this->getRequest()->getFiles()->toArray());
    }

    /**
     * Hàm lấy allPostParams call API từ FE
     * - Gắn thêm csrfToken từ headers vào để AppFilter có giá trị validate
     */
    public function getPostParamsApi(): array
    {
        $request = $this->getRequest();
        $params = $this->getAllPostParams();
        $contentType = $request->getHeaders()->get('Content-Type');
        $ct = $contentType ? strtolower($contentType->getFieldValue()) : '';
        if (strpos($ct, 'application/json') !== false) {
            $raw = (string)$request->getContent();
            $json = json_decode($raw, true);
            if (is_array($json)) {
        // JSON override key trùng
                $params = array_replace_recursive($params, $json);
            }
        }


        return $params;
    }

    /**
     * lấy param post or get
     *
     * @param
     *            $param
     * @return mixed
     */
    protected function getParam($param, $defaultValue = null)
    {
        $value = $this->getRequest()->getPost($param, $this->getRequest()
            ->getQuery($param));
        if (! $value && $defaultValue) {
            $value = $defaultValue;
        }
        return $value;
    }

    /**
     * get all $_GET params
     * @return array
     */
    protected function getAllQueryParams()
    {
        return $this->getRequest()->getQuery()->toArray();
    }
}
