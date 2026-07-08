<?php

namespace Application\Filter;

use Application\Model\AppMessage;
use Application\Model\DateModel;
use Laminas\InputFilter\InputFilter;
use Laminas\Validator\NotEmpty;
use Psr\Container\ContainerInterface;

class AppFilter extends InputFilter
{

    protected ?ContainerInterface $container = null;

    // $messageArr là mảng lỗi define thêm để validate thêm các điều kiện khác,
    // sau khi validate dữ liệu mặc định của inputFilter. VD: check trùng tên, check quản lý doanh nghiệp ...
    protected array $messageArr = [];

    protected array $options = [];

    protected bool $isInitPaging = false;

    protected $pageDefault = null;

    protected $pageSizeDefault = null;

    protected bool $isInitSorting = false;

    protected $defaultSort = null;

    protected $defaultDir = null;

    protected $sortKeys = null;

    public function setContainer($container)
    {
        $this->container = $container;
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
            return $this->container->get($entryName);
        } catch (\Exception $e) {
            return null;
        }
    }


    public function __construct($container, $options = [])
    {
        $this->setContainer($container);
        $this->options = $options;
    }


    /**
     * - Khởi tạo các input filter dạng number
     * - Dùng cho các API mà POST lên ít params, và params là dạng number
     *   => Không phải tạo ra nhiều file ...Filter mà chỉ có các input filter ở trên
     */
    public function initRequiredFilterNumbers($options)
    {
        if (!is_array($options) || !count($options)) {
            return false;
        }

        foreach ($options as $inputName) {
            $this->add([
                'name'       => $inputName,
                'required'   => true,
                'filters'    => [
                    ['name' => 'StringTrim'],
                    ['name' => 'Digits']
                ],
                'validators' => [
                    [
                        'name'                   => NotEmpty::class,
                        'break_chain_on_failure' => true,
                        'options'                => [
                            'messages' => [
                                'isEmpty' => AppMessage::VALIDATOR_REQUIRED
                            ]
                        ]
                    ],
                ],
            ]);
        }
    }

    /**
     * - Thêm các input không bắt buộc phải truyền
     * - Cho phép truyền loại dữ liệu của input đó
     */
    public function addInputFilters($options)
    {
        if (!is_array($options) || !count($options)) {
            return false;
        }

        foreach ($options as $inputName => $values) {
            $filters = [
                ['name' => 'StringTrim']
            ];
            if (empty($values['filterText'])) {
                $filters[] = ['name' => 'Digits'];
            }
            $this->add([
                'name'       => $inputName,
                'required'   => $values['required'] ?? false,
                'filters'    => $filters,
                'validators' => [
                    [
                        'name'                   => NotEmpty::class,
                        'break_chain_on_failure' => true,
                        'options'                => [
                            'messages' => [
                                'isEmpty' => AppMessage::VALIDATOR_REQUIRED
                            ]
                        ]
                    ]
                ]
            ]);
        }
    }

    /**
     * Hàm khởi tạo filter dạng date
     */
    public function createCommonInputFilterDate($fName = '', $required = false, $message = '', $format = ''): void
    {
        if (!$fName) {
            return;
        }
        $message = $message ?: AppMessage::DATE_FORMAT_INVALID;
        $format = $format ?: DateModel::COMMON_DATE_FORMAT;

        $this->add([
            'name'       => $fName,
            'required'   => $required,
            'filters'    => [
                ['name' => 'StringTrim'],
            ],
            'validators' => [
                [
                    'name'                   => 'NotEmpty',
                    'break_chain_on_failure' => true,
                    'options'                => [
                        'messages' => [
                            'isEmpty' => AppMessage::VALIDATOR_REQUIRED
                        ]
                    ]
                ],
                [
                    'name'                   => 'Date',
                    'break_chain_on_failure' => true,
                    'options'                => [
                        'format'   => $format,
                        'messages' => [
                            'dateInvalid'     => $message,
                            'dateInvalidDate' => $message,
                            'dateFalseFormat' => $message,
                        ]
                    ]
                ],
            ]
        ]);
    }

    /**
     * Hàm khởi tạo filter dạng datetime
     */
    public function createCommonInputFilterDateTime($fName = '', $required = false, $message = ''): void
    {
        if (!$fName) {
            return;
        }
        $message = $message ?: AppMessage::DATE_FORMAT_INVALID;

        $this->add([
            'name'       => $fName,
            'required'   => $required,
            'filters'    => [
                ['name' => 'StringTrim'],
            ],
            'validators' => [
                [
                    'name'                   => 'NotEmpty',
                    'break_chain_on_failure' => true,
                    'options'                => [
                        'messages' => [
                            'isEmpty' => AppMessage::VALIDATOR_REQUIRED
                        ]
                    ]
                ],
                [
                    'name'                   => 'Date',
                    'break_chain_on_failure' => true,
                    'options'                => [
                        'format'   => DateModel::COMMON_DATETIME_FORMAT,
                        'messages' => [
                            'dateInvalid'     => $message,
                            'dateInvalidDate' => $message,
                            'dateFalseFormat' => $message,
                        ]
                    ]
                ],
            ]
        ]);
    }

    /**
     * Trong hàm isValid thường cần gắn thêm lỗi
     * Hàm sẽ lưu lỗi custom theo dạng mảng chứa key là filedName inputFilter
     * @param $key string
     * @param $value string
     */
    public function setError($key, $value): void
    {
        if (!$key || is_array($key) || !$value || is_array($value)) {
            return;
        }
        $this->messageArr[$key] = $value;
    }

    /**
     * @return array
     * Hàm chung trả về dữ liệu sau khi filter dữ liệu
     */
    public function getData()
    {
        $data = [];
        foreach ($this->getRawValues() as $keyFilter => $valueFilter) {
            $data[$keyFilter] = $this->getValue($keyFilter);
        }

        if ($this->isInitPaging) {
            $paging = $this->getPaging($this->pageDefault, $this->pageSizeDefault);
            $data['page'] = $paging['page'];
            $data['pageSize'] = $paging['pageSize'];
            unset($data['icpp']);
        }

        if ($this->isInitSorting) {
            unset($data['sort'], $data['dir']);
            $data = array_merge($data, $this->getSorting($this->defaultSort, $this->defaultDir, $this->sortKeys));
        }

        return $data;
    }


    /**
     * Trả về lỗi filter dạng mảng
     * $options[isReturnOnlyValue] => chỉ trả về mảng lỗi, không trả về key Element
     */
    public function getMessagesArr($options = [])
    {
        $results = [];
        $errorMessages = array_merge($this->getMessages(), $this->messageArr);
        foreach ($errorMessages as $elementName => $errorMessages) {
            $results[$elementName] = is_array($errorMessages) && count($errorMessages) ? current($errorMessages) : $errorMessages;
        }
        return !empty($options['isReturnOnlyValue']) ? array_values($results) : $results;
    }


    /**
     * Hàm khởi tạo filter phân trang
     * */
    public function initInputPaging($pageDefault = null, $pageSizeDefault = null): void
    {
        $this->isInitPaging = true;
        $this->pageDefault = $pageDefault;
        $this->pageSizeDefault = $pageSizeDefault;

        if (!$this->has('page')) {
            $this->add([
                'name'     => 'page',
                'required' => false,
                'filters'  => [
                    ['name' => 'StringTrim'],
                    ['name' => 'Digits']
                ]
            ]);
        }

        if (!$this->has('pageSize')) {
            $this->add([
                'name'     => 'pageSize',
                'required' => false,
                'filters'  => [
                    ['name' => 'StringTrim'],
                    ['name' => 'Digits']
                ]
            ]);
        }
    }

    /**
     * Hàm trả kết quả sau khi filter param phân trang
     * page = 0 => Mặc định = 1
     * pageSize = 0 => Mặc định = 50
     * pageSize > 200 => Mặc định = 200
     * */
    public function getPaging($pageDefault = null, $pageSizeDefault = null)
    {
        $page = (int)($this->has('page') ? $this->getValue('page') : 0);
        $pageSize = (int)($this->has('pageSize') ? $this->getValue('pageSize') : 0);
        if (!$pageSize && $this->has('icpp')) {
            $pageSize = (int)$this->getValue('icpp');
        }
        $page = $page ?: $pageDefault;
        $pageSize = $pageSize ?: $pageSizeDefault;
        return [
            'page'     => ($page > 0 ? $page : 1),
            'pageSize' => ($pageSize > 0 ? min($pageSize, 200) : 50)
        ];
    }


    /**
     * Hàm khởi tạo filter sắp xếp
     * */
    public function initSorting($defaultSort = null, $defaultDir = null, $sortKeys = null): void
    {
        $this->isInitSorting = true;
        $this->defaultSort = $defaultSort;
        $this->defaultDir = $defaultDir;
        $this->sortKeys = $sortKeys;

        foreach (['sort', 'dir'] as $inputName) {
            if ($this->has($inputName)) {
                continue;
            }

            $this->add([
                'name'     => $inputName,
                'required' => false,
                'filters'  => [
                    ['name' => 'StringTrim'],
                ]
            ]);
        }
    }


    /**
     * Hàm lấy kết quả filter param sắp xếp
     * */
    public function getSorting($defaultSort = null, $defaultDir = null, $sortKeys = null)
    {
        $sort = $this->has('sort') && $this->getValue('sort') ? $this->getValue('sort') : $defaultSort;
        if (is_array($sortKeys) && !in_array($sort, $sortKeys)) {
            $sort = $defaultSort;
        } elseif ($sort) {
            $sort = str_replace(',', ' ', $sort);
        }

        $defaultDir = $defaultDir ? strtolower($defaultDir) : $defaultDir;
        $dir = $this->has('dir') && $this->getValue('dir') ? strtolower($this->getValue('dir')) : $defaultDir;
        if (!in_array($dir, ['asc', 'desc'], true)) {
            $dir = $defaultDir;
        }
        if (!$sort) {
            return [];
        }

        return [
            'sort' => $sort,
            'dir'  => $dir
        ];
    }
}
