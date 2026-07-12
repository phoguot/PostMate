<?php

declare(strict_types=1);

namespace Application\Model\Log;

use Throwable;

/**
 * Ghi log lỗi ra file dưới dạng structured JSON (mỗi dòng 1 sự kiện).
 *
 * - File log nằm trong thư mục `data/` (cùng chỗ với php-error.log), tên theo ngày:
 *   `data/error-YYYY-MM-DD.log` -> dễ soi và tự xoay vòng theo ngày.
 * - Mỗi request có một requestId để gom các dòng log của cùng 1 request.
 * - Không log secret/token: chỉ ghi các field đã được chọn lọc.
 */
class ErrorLogger
{
    /** Request id dùng chung cho cả vòng đời 1 request. */
    private static ?string $requestId = null;

    /** Ghi đè thư mục log (mặc định: getcwd()/data). */
    private static ?string $logDir = null;

    /**
     * Cho phép cấu hình thư mục log (gọi từ Module khi bootstrap nếu cần).
     */
    public static function setLogDir(?string $dir): void
    {
        self::$logDir = $dir !== null ? rtrim($dir, '/\\') : null;
    }

    /**
     * Log một exception/throwable kèm context bổ sung.
     *
     * @param array<string,mixed> $context
     */
    public static function logException(Throwable $e, array $context = []): void
    {
        self::write('error', 'app_exception', array_merge([
            'message' => $e->getMessage(),
            'type'    => $e::class,
            'code'    => $e->getCode(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
            'trace'   => self::truncateTrace($e),
        ], $context));
    }

    /**
     * Log một sự kiện tuỳ ý (level: error|warn|info).
     *
     * @param array<string,mixed> $fields
     */
    public static function write(string $level, string $event, array $fields = []): void
    {
        $record = array_merge([
            'time'      => date('c'),
            'level'     => $level,
            'event'     => $event,
            'requestId' => self::requestId(),
            'method'    => $_SERVER['REQUEST_METHOD'] ?? null,
            'uri'       => $_SERVER['REQUEST_URI'] ?? null,
        ], $fields);

        $line = json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($line === false) {
            $line = json_encode([
                'time'      => date('c'),
                'level'     => 'error',
                'event'     => 'log_encode_failed',
                'requestId' => self::requestId(),
            ]);
        }

        $file = self::logFilePath();
        // Silence lỗi ghi file (đầy đĩa / open_basedir) — không được làm sập request vì logging.
        @file_put_contents($file, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    private static function requestId(): string
    {
        if (self::$requestId === null) {
            // Ưu tiên request id do proxy/FE truyền vào để dễ trace xuyên hệ thống.
            $incoming = $_SERVER['HTTP_X_REQUEST_ID'] ?? '';
            self::$requestId = is_string($incoming) && $incoming !== ''
                ? substr($incoming, 0, 64)
                : bin2hex(random_bytes(8));
        }
        return self::$requestId;
    }

    private static function logFilePath(): string
    {
        $dir = self::$logDir ?? (getcwd() ?: __DIR__) . DIRECTORY_SEPARATOR . 'data';
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        return $dir . DIRECTORY_SEPARATOR . 'error-' . date('Y-m-d') . '.log';
    }

    /**
     * Cắt gọn stack trace: giữ tối đa 30 frame, mỗi frame chỉ file:line + hàm.
     *
     * @return list<string>
     */
    private static function truncateTrace(Throwable $e): array
    {
        $frames = [];
        foreach (array_slice($e->getTrace(), 0, 30) as $i => $f) {
            $where = ($f['file'] ?? '?') . ':' . ($f['line'] ?? '?');
            $fn    = ($f['class'] ?? '') . ($f['type'] ?? '') . ($f['function'] ?? '');
            $frames[] = "#{$i} {$where} {$fn}()";
        }
        return $frames;
    }
}
