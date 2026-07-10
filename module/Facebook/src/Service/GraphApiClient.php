<?php
declare(strict_types=1);

namespace Facebook\Service;

use Application\Factory\AppServiceFactory;

/**
 * HTTP client tối giản cho Facebook Graph API (kênh Graph API, docs mục 6.5).
 * - Chỉ GET (đọc token/metadata); đăng bài qua Graph API sẽ bổ sung sau.
 * - Lỗi mạng/HTTP/JSON đều trả về dạng ['error' => ['message' => ...]] giống format lỗi
 *   của chính Graph API, nên caller chỉ cần kiểm tra key 'error'.
 */
class GraphApiClient extends AppServiceFactory
{
    public const GRAPH_BASE = 'https://graph.facebook.com/v21.0';

    public function get(string $path, array $params = []): array
    {
        $url = self::GRAPH_BASE . '/' . ltrim($path, '/') . '?' . http_build_query($params);

        [$body, $error] = $this->request($url);
        if ($error !== null) {
            return ['error' => ['message' => $error]];
        }

        $decoded = json_decode((string)$body, true);
        if (! is_array($decoded)) {
            return ['error' => ['message' => 'Graph API trả về dữ liệu không hợp lệ']];
        }
        return $decoded;
    }

    /** @return array{0: ?string, 1: ?string} [body, error] */
    private function request(string $url): array
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT        => 20,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            $caInfo = $this->resolveCaInfo();
            if ($caInfo !== null) {
                curl_setopt($ch, CURLOPT_CAINFO, $caInfo);
            }
            $body  = curl_exec($ch);
            $error = $body === false ? ('Lỗi kết nối Graph API: ' . curl_error($ch)) : null;
            curl_close($ch);
            return [$body === false ? null : (string)$body, $error];
        }

        // InfinityFree/host không có ext-curl: fallback qua stream, ignore_errors để
        // vẫn đọc được body JSON khi Graph API trả HTTP 4xx.
        $context = stream_context_create(['http' => ['timeout' => 20, 'ignore_errors' => true]]);
        $body = @file_get_contents($url, false, $context);
        if ($body === false) {
            return [null, 'Không gọi được Graph API (thiếu ext-curl và allow_url_fopen tắt)'];
        }
        return [$body, null];
    }

    /**
     * CA bundle cho verify SSL: ưu tiên config['graph_api']['cainfo'] (WAMP local không
     * set curl.cainfo trong php.ini — khai báo ở config/autoload/local.php để khỏi sửa
     * php.ini + restart Apache), sau đó tới các giá trị ini chuẩn. Null = dùng mặc định hệ thống.
     */
    private function resolveCaInfo(): ?string
    {
        $config = $this->getContainerEntry('config')['graph_api'] ?? [];
        foreach ([(string)($config['cainfo'] ?? ''), (string)ini_get('curl.cainfo'), (string)ini_get('openssl.cafile')] as $path) {
            if ($path !== '' && is_file($path)) {
                return $path;
            }
        }
        return null;
    }
}
