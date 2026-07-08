<?php

namespace Application\Model;

class AppFormat
{
    public static function encodeData(mixed $value): ?string
    {
        if (is_array($value)) {
            $encodedValue = json_encode($value);

            // Kiểm tra lỗi encode
            if (json_last_error() !== JSON_ERROR_NONE) {
                return null;
            }
            return $encodedValue;
        }

        return $value;
    }

    /**
     * Decode chuỗi JSON thành mảng hoặc giá trị
     *
     * @param mixed $value Chuỗi JSON cần decode
     *
     * @return string|array|null Mảng hoặc giá trị gốc nếu decode thành công, null nếu thất bại
     */
    public static function decodeData(mixed $value): string|array|null
    {
        // Kiểm tra nếu giá trị là string và không rỗng
        if (! is_string($value) || empty($value)) {
            return null;
        }

        // Decode chuỗi JSON
        $decodedValue = json_decode($value, true);

        // Kiểm tra lỗi decode
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        // Kiểm tra nếu kết quả là mảng hoặc null (JSON có thể là null hợp lệ)
        if (is_array($decodedValue) || $decodedValue === null) {
            return $decodedValue;
        }

        return $value;
    }


    public static function castDoubleOrNull(mixed $value): ?float
    {
        if (! $value) {
            return null;
        }

        return (float)$value;
    }

    public static function castIntOrNull(mixed $value): ?int
    {
        if (! $value) {
            return null;
        }

        return (int)$value;
    }

    public static function castStringOrNull(mixed $value): ?string
    {
        if (! $value) {
            return null;
        }

        return (string)$value;
    }
    public static function removeSigns($text)
    {
        $vnSigns = [
            'à','á','ạ','ả','ã','â','ầ','ấ','ậ','ẩ','ẫ','ă','ằ','ắ','ặ','ẳ','ẵ',
            'đ',
            'è','é','ẹ','ẻ','ẽ','ê','ề','ế','ệ','ể','ễ',
            'ì','í','ị','ỉ','ĩ',
            'ò','ó','ọ','ỏ','õ','ô','ồ','ố','ộ','ổ','ỗ','ơ','ờ','ớ','ợ','ở','ỡ',
            'ù','ú','ụ','ủ','ũ','ư','ừ','ứ','ự','ử','ữ',
            'ỳ','ý','ỵ','ỷ','ỹ',
            'À','Á','Ạ','Ả','Ã','Â','Ầ','Ấ','Ậ','Ẩ','Ẫ','Ă','Ằ','Ắ','Ặ','Ẳ','Ẵ',
            'Đ',
            'È','É','Ẹ','Ẻ','Ẽ','Ê','Ề','Ế','Ệ','Ể','Ễ',
            'Ì','Í','Ị','Ỉ','Ĩ',
            'Ò','Ó','Ọ','Ỏ','Õ','Ô','Ồ','Ố','Ộ','Ổ','Ỗ','Ơ','Ờ','Ớ','Ợ','Ở','Ỡ',
            'Ù','Ú','Ụ','Ủ','Ũ','Ư','Ừ','Ứ','Ự','Ử','Ữ',
            'Ỳ','Ý','Ỵ','Ỷ','Ỹ'
        ];
        $vnUnsigns = [
            'a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a',
            'd',
            'e','e','e','e','e','e','e','e','e','e','e',
            'i','i','i','i','i',
            'o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o',
            'u','u','u','u','u','u','u','u','u','u','u',
            'y','y','y','y','y',
            'A','A','A','A','A','A','A','A','A','A','A','A','A','A','A','A','A',
            'D',
            'E','E','E','E','E','E','E','E','E','E','E',
            'I','I','I','I','I',
            'O','O','O','O','O','O','O','O','O','O','O','O','O','O','O','O','O',
            'U','U','U','U','U','U','U','U','U','U','U',
            'Y','Y','Y','Y','Y'
        ];
        return str_replace($vnSigns, $vnUnsigns, $text);
    }
}
