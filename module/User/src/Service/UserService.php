<?php
declare(strict_types=1);

namespace User\Service;

use Application\Factory\AppServiceFactory;
use Application\Model\ActivityLog\ActivityLogConst;
use Application\Model\ActivityLog\ActivityLogMapper;
use Application\Model\ApiResultModel;
use Application\Model\AppMessage;
use Application\Model\JsonResponse;
use Laminas\Session\Container as SessionContainer;
use User\Filter\Profile\ChangePasswordFilter;
use User\Filter\Profile\ProfileUpdateFilter;
use User\Model\User\UserConst;
use User\Model\User\UserMapper;
use User\Model\User\UserModel;

/**
 * Dịch vụ xác thực & phiên đăng nhập của user.
 *
 * - Danh tính (identity) lưu trong Laminas Session Container 'auth', key 'user_id'.
 * - Toàn bộ hệ thống scope dữ liệu theo user đăng nhập qua getIdentity().
 */
class UserService extends AppServiceFactory
{
    const SESSION_NAMESPACE   = 'auth';
    const SESSION_KEY_USER_ID = 'user_id';

    private ?SessionContainer $session = null;
    private ?UserModel $currentUser = null;

    private function session(): SessionContainer
    {
        if ($this->session === null) {
            $this->session = new SessionContainer(self::SESSION_NAMESPACE);
        }
        return $this->session;
    }

    // -------------------------------------------------------------------------
    // Identity

    /** Trả về id user đang đăng nhập, hoặc null nếu chưa đăng nhập. */
    public function getIdentity(): ?int
    {
        $session = $this->session();
        $userId  = $session->offsetExists(self::SESSION_KEY_USER_ID)
            ? $session->offsetGet(self::SESSION_KEY_USER_ID)
            : null;
        return $userId ? (int)$userId : null;
    }

    public function setIdentity(int $userId): void
    {
        $this->session()->offsetSet(self::SESSION_KEY_USER_ID, $userId);
        $this->currentUser = null;
    }

    public function clearIdentity(): void
    {
        if ($this->session()->offsetExists(self::SESSION_KEY_USER_ID)) {
            $this->session()->offsetUnset(self::SESSION_KEY_USER_ID);
        }
        $this->currentUser = null;
    }

    public function isLoggedIn(): bool
    {
        return $this->getIdentity() !== null;
    }

    /**
     * Guard quyền — hiện chỉ kiểm đăng nhập (chưa có ACL theo resource/action).
     */
    public function isAllowed($resource = null, $action = null): bool
    {
        return $this->isLoggedIn();
    }

    // -------------------------------------------------------------------------
    // Current user

    /** Lấy bản ghi user đang đăng nhập (null nếu chưa đăng nhập / không tồn tại). */
    public function getUser(): ?UserModel
    {
        if ($this->currentUser instanceof UserModel) {
            return $this->currentUser;
        }
        $id = $this->getIdentity();
        if (!$id) {
            return null;
        }
        $model = new UserModel();
        $model->setId($id);

        $mapper = $this->getContainerEntry(UserMapper::class);
        $user   = $mapper->getUser($model);
        $this->currentUser = $user ?: null;
        return $this->currentUser;
    }

    // -------------------------------------------------------------------------
    // Login / Logout

    /**
     * Đăng nhập bằng username (hoặc email) + password.
     * Trả về UserModel nếu thành công, null nếu sai thông tin / tài khoản không hợp lệ.
     */
    public function login(string $username, string $password): ?UserModel
    {
        if ($username === '' || $password === '') {
            return null;
        }

        $mapper = $this->getContainerEntry(UserMapper::class);
        $probe  = new UserModel();
        $probe->setUsername($username);
        $user = $mapper->getUserByUsername($probe);
        if (!$user instanceof UserModel) {
            return null;
        }
        if ($user->getStatus() !== UserConst::STATUS_ACTIVE) {
            return null;
        }
        $hash = $user->getPasswordHash();
        if (!$hash || !password_verify($password, $hash)) {
            return null;
        }

        $this->setIdentity((int)$user->getId());
        $this->currentUser = $user;
        return $user;
    }

    public function logout(): void
    {
        $this->clearIdentity();
    }

    // -------------------------------------------------------------------------
    // Hồ sơ / Bảo mật (màn Cài đặt > Tài khoản — profile.png)

    /** Cập nhật hồ sơ (họ tên, ảnh đại diện) của user đang đăng nhập. */
    public function updateProfile(array $payload = []): JsonResponse
    {
        $apiResult = new ApiResultModel();

        $userId = $this->getIdentity();
        if (! $userId) {
            return $apiResult->errorPage401Response([AppMessage::COMMON_401]);
        }

        $filter = new ProfileUpdateFilter($this->getContainer());
        $filter->setData($payload);
        if (! $filter->isValid()) {
            return $apiResult->errorInvalidFormResponse($filter->getMessagesArr());
        }
        $formData = $filter->getData();

        $data = [];
        foreach (['fullName', 'avatarUrl'] as $key) {
            if (array_key_exists($key, $formData) && $formData[$key] !== null && $formData[$key] !== '') {
                $data[$key] = $formData[$key];
            }
        }
        if (empty($data)) {
            return $apiResult->errorInvalidFormResponse(['fullName' => AppMessage::INVALID_DATA]);
        }

        $this->getContainerEntry(UserMapper::class)->updateAttrs($userId, $data);
        $this->currentUser = null;

        $this->getContainerEntry(ActivityLogMapper::class)->log(
            $userId,
            'user:' . $userId,
            'Cập nhật hồ sơ',
            'Cập nhật thông tin tài khoản',
            ActivityLogConst::LEVEL_INFO
        );

        $user = $this->getUser();
        return $apiResult->successResponse($user ? $user->getRespUser() : [], [AppMessage::UPDATE_SUCCESSFULLY]);
    }

    /** Đổi mật khẩu — xác thực mật khẩu hiện tại rồi lưu hash mới. */
    public function changePassword(array $payload = []): JsonResponse
    {
        $apiResult = new ApiResultModel();

        $userId = $this->getIdentity();
        if (! $userId) {
            return $apiResult->errorPage401Response([AppMessage::COMMON_401]);
        }

        $filter = new ChangePasswordFilter($this->getContainer());
        $filter->setData($payload);
        if (! $filter->isValid()) {
            return $apiResult->errorInvalidFormResponse($filter->getMessagesArr());
        }
        $formData = $filter->getData();

        $currentPassword = (string)$formData['currentPassword'];
        $newPassword     = (string)$formData['newPassword'];
        $confirmPassword = (string)$formData['confirmPassword'];

        if ($newPassword !== $confirmPassword) {
            return $apiResult->errorInvalidFormResponse(['confirmPassword' => 'Xác nhận mật khẩu không khớp']);
        }
        if (strlen($newPassword) < 8) {
            return $apiResult->errorInvalidFormResponse(['newPassword' => AppMessage::VALIDATOR_PASSWORD_TOO_SHORT]);
        }

        $user = $this->getUser();
        if (! $user) {
            return $apiResult->errorData404Response([AppMessage::USER_NOT_FOUND]);
        }
        $hash = $user->getPasswordHash();
        if (! $hash || ! password_verify($currentPassword, $hash)) {
            return $apiResult->errorInvalidFormResponse(['currentPassword' => 'Mật khẩu hiện tại không đúng']);
        }

        $this->getContainerEntry(UserMapper::class)->updateAttrs($userId, [
            'passwordHash' => password_hash($newPassword, PASSWORD_BCRYPT),
        ]);
        $this->currentUser = null;

        $this->getContainerEntry(ActivityLogMapper::class)->log(
            $userId,
            'user:' . $userId,
            'Đổi mật khẩu',
            'Đổi mật khẩu tài khoản',
            ActivityLogConst::LEVEL_SUCCESS
        );

        return $apiResult->successResponse([], [AppMessage::UPDATE_SUCCESSFULLY]);
    }

    /**
     * Bật/tắt xác thực 2 lớp (2FA).
     * Hook: sinh secret TOTP + QR + xác minh mã — chưa tích hợp thư viện TOTP,
     * hiện chỉ phản hồi trạng thái để luồng UI hoạt động.
     */
    public function toggleTwoFactor(array $payload = []): JsonResponse
    {
        $apiResult = new ApiResultModel();

        $userId = $this->getIdentity();
        if (! $userId) {
            return $apiResult->errorPage401Response([AppMessage::COMMON_401]);
        }

        $enabled = filter_var($payload['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $this->getContainerEntry(ActivityLogMapper::class)->log(
            $userId,
            'user:' . $userId,
            'Xác thực 2 lớp',
            $enabled ? 'Bật xác thực 2 lớp' : 'Tắt xác thực 2 lớp',
            ActivityLogConst::LEVEL_INFO
        );

        return $apiResult->successResponse(['twoFactorEnabled' => $enabled], [AppMessage::UPDATE_SUCCESSFULLY]);
    }
}
