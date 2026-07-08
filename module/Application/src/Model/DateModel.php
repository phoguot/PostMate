<?php

namespace Application\Model;

use DateTime;

class DateModel extends DateTime
{
    // Định dạng format ngày dùng truy vấn trong ES
    public const COMMON_DATE_ELASTIC_FORMAT = 'yyyy-MM-dd';
    public const COMMON_DATE_FORMAT = 'Y-m-d';

    public const COMMON_DATETIME_FORMAT  = 'Y-m-d H:i:s';
    public const DISPLAY_DATETIME_FORMAT = 'd/m/Y H:i:s';
    public const FILEPATH_DATE_FORMAT = 'YmdHis';

    public static function getCurrentDate(): string
    {
        return date(self::COMMON_DATE_FORMAT);
    }

    public static function getCurrentDateTime(): string
    {
        return date(self::COMMON_DATETIME_FORMAT);
    }


    /**
     * convert display date to common datetime format
     */
    public static function toCommonDateTime($d): string
    {
        if ($d) {
            $date = DateTime::createFromFormat(self::DISPLAY_DATETIME_FORMAT, $d);
            if ($date) {
                return $date->format(self::COMMON_DATETIME_FORMAT);
            }
        }
        return '';
    }

    public function addMinutes($minutes): string
    {
        $newDateTime = strtotime('+' . $minutes . ' minutes');
        return date('Y-m-d H:i:s', $newDateTime);
    }


    /**
     * Lấy thời gian hiện tại theo timestamps
     * @TODO NVN: hàm này tạo nhằm mục đích muôn sử thêm chung thì sửa 1 chỗ
     */
    public static function getTimeStampsCurrent(): int
    {
        return time();
    }

    /**
     * Hàm kiểm tra giá trị có phải timestamps hợp lệ không
     */
    public static function validateTimestamp(mixed $time): int|null
    {
        return is_numeric($time) && (int)$time == $time ? (int)$time : null;
    }
    public static function getCurrentDateUpload()
    {
        return date(self::FILEPATH_DATE_FORMAT);
    }
    public static function subtractMonth($months)
    {
        if (! $months) {
            return false;
        }

        return strtotime("-" . $months . " months");
    }

    public static function addMonth($months)
    {
        if (! $months) {
            return false;
        }

        return strtotime("+" . $months . " months");
    }

    /**
     * Chuyển định dạng yyyy-MM-dd sang timestamps epoch (giây)
     */
    public static function fromElasticDateToTimestamp(string $date): ?int
    {
        $dateTime = DateTime::createFromFormat('Y-m-d', $date);
        if ($dateTime) {
            return $dateTime->getTimestamp();
        }
        return null;
    }

    /**
     * Chuyển timestamp (epoch giây) thành chuỗi yyyy-MM-dd.
     *
     * @param int|string $timestamp Epoch-second (hợp lệ)
     * @return string Chuỗi ngày theo định dạng COMMON_DATE_FORMAT; trả '' nếu sai.
     */
    public static function fromTimestampToCommonDate(int|string $timestamp): string
    {
        if (! self::validateTimestamp($timestamp)) {
            return '';
        }
        return date(self::COMMON_DATE_FORMAT, (int) $timestamp);
    }
    public static function subtractDay($day, $fromDate = null)
    {
        if ($fromDate) {
            $numberDayStrToTime = $day * (60 * 60 * 24);
            return date(self::COMMON_DATE_FORMAT, strtotime($fromDate) - $numberDayStrToTime);
        } else {
            return date(self::COMMON_DATE_FORMAT, strtotime("-" . $day . " days"));
        }
    }
}
