<?php
declare(strict_types=1);

namespace Posting\Service;

use Application\Factory\AppServiceFactory;
use Facebook\Model\Fanpage\FanpageMapper;
use Facebook\Model\Fanpage\FanpageModel;
use Facebook\Service\GraphApiClient;
use Posting\Model\Post\PostConst;
use Posting\Model\Post\PostModel;

/**
 * Đăng bài lên fanpage qua Meta Graph API (kênh CHANNEL_GRAPH_API).
 * - Text/link  -> POST /{pageId}/feed {message}
 * - Ảnh        -> upload từng ảnh /{pageId}/photos {url, published:false} rồi /feed + attached_media
 * - Video (1)  -> POST /{pageId}/videos {file_url, description}
 *
 * Trả về cùng shape với BrowserAgentClient::publish để PostExecutor xử lý đồng nhất:
 * @return array{success: bool, fbPostId: ?string, error: ?string, errorType: ?string}
 */
class GraphPublisher extends AppServiceFactory
{
    private const SCHEDULE_MIN_LEAD_SEC = 600;
    private const SCHEDULE_MAX_LEAD_SEC = 6480000; // 75 ngày

    public function publish(PostModel $post, array $media): array
    {
        return $this->send($post, $media);
    }

    /**
     * Lên lịch trực tiếp trên Facebook Page. Graph API yêu cầu lịch nằm trong khoảng
     * 10 phút đến 75 ngày; ngoài khoảng đó thì để queue nội bộ xử lý.
     */
    public function schedule(PostModel $post, array $media, string $scheduledAt): array
    {
        $timestamp = strtotime($scheduledAt);
        if ($timestamp === false) {
            return $this->fail('Thời gian lên lịch không hợp lệ', BrowserAgentClient::ERROR_PERMANENT);
        }

        $leadSec = $timestamp - time();
        if ($leadSec < self::SCHEDULE_MIN_LEAD_SEC || $leadSec > self::SCHEDULE_MAX_LEAD_SEC) {
            return $this->fail('Lịch không nằm trong khoảng Graph API cho phép', BrowserAgentClient::ERROR_PERMANENT);
        }

        return $this->send($post, $media, [
            'published'              => 'false',
            'scheduled_publish_time' => (string)$timestamp,
        ]);
    }

    /** Hủy bài đã lên lịch native trên Facebook khi còn ở trạng thái scheduled. */
    public function delete(PostModel $post): array
    {
        $loaded = $this->loadFanpage($post);
        if (! $loaded['success']) {
            return $this->fail($loaded['error'], $loaded['errorType']);
        }

        $fbPostId = (string)$post->getFbPostId();
        if ($fbPostId === '') {
            return ['success' => true, 'fbPostId' => null, 'error' => null, 'errorType' => null];
        }

        $client = $this->getContainerEntry(GraphApiClient::class);
        $res = $client->delete($fbPostId, ['access_token' => $loaded['token']]);
        if (! empty($res['error'])) {
            $err = is_array($res['error']) ? $res['error'] : ['message' => (string)$res['error']];
            return $this->fail($this->graphErrorMessage($err, 'Không hủy được lịch Facebook'), $this->classifyGraphError($err));
        }

        return ['success' => true, 'fbPostId' => null, 'error' => null, 'errorType' => null];
    }

    private function send(PostModel $post, array $media, array $publishParams = []): array
    {
        $loaded = $this->loadFanpage($post);
        if (! $loaded['success']) {
            return $this->fail($loaded['error'], $loaded['errorType']);
        }

        $pageId = $loaded['pageId'];
        $token  = $loaded['token'];
        $message = (string)$post->getContent();
        $images  = array_values(array_filter($media, fn($m) => (int)($m['type'] ?? 0) === PostConst::MEDIA_TYPE_IMAGE && ! empty($m['url'])));
        $videos  = array_values(array_filter($media, fn($m) => (int)($m['type'] ?? 0) === PostConst::MEDIA_TYPE_VIDEO && ! empty($m['url'])));

        $client = $this->getContainerEntry(GraphApiClient::class);

        // Video: Graph API tạo video post riêng, không đi qua /feed.
        if (! empty($videos)) {
            $res = $client->post($pageId . '/videos', array_merge([
                'file_url'     => (string)$videos[0]['url'],
                'description'  => $message,
                'access_token' => $token,
            ], $publishParams));
            return $this->toResult($res);
        }

        // Ảnh: upload unpublished từng ảnh, gom id để attach vào /feed.
        $attached = [];
        foreach ($images as $img) {
            $res = $client->post($pageId . '/photos', [
                'url'          => (string)$img['url'],
                'published'    => 'false',
                'access_token' => $token,
            ]);
            if (! empty($res['error']) || empty($res['id'])) {
                return $this->toResult($res, 'Upload ảnh thất bại');
            }
            $attached[] = ['media_fbid' => (string)$res['id']];
        }

        $params = array_merge(['message' => $message, 'access_token' => $token], $publishParams);
        foreach ($attached as $i => $mediaFbid) {
            $params['attached_media[' . $i . ']'] = json_encode($mediaFbid);
        }

        $res = $client->post($pageId . '/feed', $params);
        return $this->toResult($res);
    }

    // -------------------------------------------------------------------------

    private function loadFanpage(PostModel $post): array
    {
        $fanpage = new FanpageModel();
        $fanpage->setId((int)$post->getFanpageId());
        $fanpageMapper = $this->getContainerEntry(FanpageMapper::class);
        if (! $fanpageMapper->getFanpage($fanpage)) {
            return ['success' => false, 'error' => 'Không tìm thấy fanpage', 'errorType' => BrowserAgentClient::ERROR_PERMANENT];
        }

        $pageId = (string)$fanpage->getFbPageId();
        $token  = (string)$fanpage->getPageAccessToken();
        if ($pageId === '' || $token === '') {
            return ['success' => false, 'error' => 'Fanpage thiếu fbPageId hoặc page access token', 'errorType' => BrowserAgentClient::ERROR_PERMANENT];
        }

        return ['success' => true, 'pageId' => $pageId, 'token' => $token, 'error' => null, 'errorType' => null];
    }

    /** Chuẩn hóa response Graph API -> shape kết quả publish. */
    private function toResult(array $res, string $prefix = ''): array
    {
        if (! empty($res['error'])) {
            $err  = is_array($res['error']) ? $res['error'] : ['message' => (string)$res['error']];
            $msg  = ($prefix !== '' ? $prefix . ': ' : '') . $this->graphErrorMessage($err, 'Lỗi Graph API');
            return $this->fail($msg, $this->classifyGraphError($err));
        }

        // /feed trả {id: "pageId_postId"}; /photos, /videos trả {id: ...} (+ post_id với photos published).
        $fbPostId = (string)($res['post_id'] ?? $res['id'] ?? '');
        if ($fbPostId === '') {
            return $this->fail('Graph API không trả về id bài viết', BrowserAgentClient::ERROR_TRANSIENT);
        }

        return ['success' => true, 'fbPostId' => $fbPostId, 'error' => null, 'errorType' => null];
    }

    private function graphErrorMessage(array $error, string $fallback): string
    {
        $message = (string)($error['message'] ?? $fallback);
        $details = [];
        if (isset($error['code']) && $error['code'] !== '') {
            $details[] = 'Graph code ' . (int)$error['code'];
        }
        if (isset($error['error_subcode']) && $error['error_subcode'] !== '') {
            $details[] = 'subcode ' . (int)$error['error_subcode'];
        }
        if ($details) {
            $message .= ' (' . implode(', ', $details) . ')';
        }
        return $message;
    }

    /**
     * Phân loại lỗi Graph theo code (https://developers.facebook.com/docs/graph-api/guides/error-handling):
     * - 190 (token hỏng/hết hạn), 200-299 (thiếu permission), 100 (tham số sai) -> permanent
     * - 4, 17, 32, 613 (throttling)                                           -> rate_limit
     * - 1, 2 (lỗi tạm phía Facebook) và còn lại                               -> transient
     */
    private function classifyGraphError(array $error): string
    {
        $code = (int)($error['code'] ?? 0);
        if ($code === 190 || $code === 100 || ($code >= 200 && $code <= 299)) {
            return BrowserAgentClient::ERROR_PERMANENT;
        }
        if (in_array($code, [4, 17, 32, 613], true)) {
            return BrowserAgentClient::ERROR_RATE_LIMIT;
        }
        return BrowserAgentClient::ERROR_TRANSIENT;
    }

    private function fail(string $error, string $errorType): array
    {
        return ['success' => false, 'fbPostId' => null, 'error' => $error, 'errorType' => $errorType];
    }
}
