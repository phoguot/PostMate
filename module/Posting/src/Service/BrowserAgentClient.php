<?php
declare(strict_types=1);

namespace Posting\Service;

use Application\Factory\AppServiceFactory;
use Posting\Model\Post\PostModel;

/**
 * Cầu nối tới agent Chrome anti-detect (Node.js/Puppeteer) chạy trên máy worker —
 * TrinhDuyet/HAM_XU_LY.md::publishViaBrowser.
 *
 * Ranh giới đăng-thật: PHP chỉ gửi "nội dung + ngữ cảnh (cookie/proxy/profile)" và nhận
 * lại fbPostId. Toàn bộ điều khiển Chrome (mở profile, nạp cookie, gõ nội dung, click Đăng)
 * nằm ở agent, KHÔNG ở PHP.
 *
 * - Khi chưa cấu hình config['browser_agent']['endpoint']: trả stub thành công (fbPostId giả)
 *   để pipeline job/claim/retry chạy & test được đầu-cuối. Cắm agent thật vào không phải sửa
 *   nghiệp vụ phía trên.
 *
 * @return array{success: bool, fbPostId: ?string, error: ?string, errorType: ?string}
 */
class BrowserAgentClient extends AppServiceFactory
{
    /** Loại lỗi trả về (đồng bộ với PostExecutor::classify). */
    public const ERROR_TRANSIENT = 'transient';
    public const ERROR_PERMANENT = 'permanent';
    public const ERROR_CHECKPOINT = 'checkpoint';
    public const ERROR_RATE_LIMIT = 'rate_limit';

    public function publish(PostModel $post, array $context): array
    {
        $endpoint = $this->endpoint();
        if ($endpoint === null) {
            // Chưa có agent → stub thành công để luồng job hoạt động.
            return [
                'success'   => true,
                'fbPostId'  => 'stub_' . $post->getId() . '_' . $post->getAttemptCount(),
                'error'     => null,
                'errorType' => null,
            ];
        }

        return $this->callAgent($endpoint, [
            'postId'      => $post->getId(),
            'targetType'  => $post->getTargetType(),
            'content'     => $context['content'] ?? $post->getContent(),
            'media'       => $context['media'] ?? [],
            'account'     => $context['account'] ?? [],
            'cookie'      => $context['cookie'] ?? null,
            'proxy'       => $context['proxy'] ?? null,
            'profile'     => $context['profile'] ?? null,
            'fbPageId'    => $context['fbPageId'] ?? null,
            'idemKey'     => $context['idemKey'] ?? null,
        ]);
    }

    private function endpoint(): ?string
    {
        $config = $this->getContainerEntry('config')['browser_agent'] ?? [];
        $url    = (string)($config['endpoint'] ?? '');
        return $url !== '' ? $url : null;
    }

    /** POST JSON tới agent. Lỗi mạng → transient (worker sẽ retry). */
    private function callAgent(string $endpoint, array $payload): array
    {
        $config = $this->getContainerEntry('config')['browser_agent'] ?? [];
        $token  = (string)($config['token'] ?? '');

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => array_filter([
                'Content-Type: application/json',
                $token !== '' ? ('Authorization: Bearer ' . $token) : null,
            ]),
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => (int)($config['timeout'] ?? 120),
        ]);
        $body  = curl_exec($ch);
        $errno = curl_errno($ch);
        curl_close($ch);

        if ($errno || $body === false) {
            return ['success' => false, 'fbPostId' => null, 'error' => 'Không gọi được agent Chrome', 'errorType' => self::ERROR_TRANSIENT];
        }

        $data = json_decode((string)$body, true);
        if (! is_array($data)) {
            return ['success' => false, 'fbPostId' => null, 'error' => 'Agent trả dữ liệu không hợp lệ', 'errorType' => self::ERROR_TRANSIENT];
        }

        return [
            'success'   => (bool)($data['success'] ?? false),
            'fbPostId'  => $data['fbPostId'] ?? null,
            'error'     => $data['error'] ?? null,
            'errorType' => $data['errorType'] ?? self::ERROR_TRANSIENT,
        ];
    }
}
