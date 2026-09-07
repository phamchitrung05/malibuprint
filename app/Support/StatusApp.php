<?php

namespace App\Support;

use InvalidArgumentException;

final class StatusApp
{
    /**
     * Trả về toàn bộ metadata của các trạng thái trong một nhóm cấu hình.
     *
     * @return array<string|int, array<string, mixed>>
     */
    public static function values(string $group): array
    {
        return config("status-app.{$group}.values", []);
    }

    /** @return array<string|int, string> */
    public static function options(string $group): array
    {
        $options = [];

        foreach (self::values($group) as $value => $metadata) {
            $options[$value] = $metadata['label'] ?? (string) $value;
        }

        return $options;
    }

    public static function default(string $group): string
    {
        $default = config("status-app.{$group}.default");

        if (! is_string($default) || ! array_key_exists($default, self::values($group))) {
            throw new InvalidArgumentException("Nhóm trạng thái [{$group}] chưa cấu hình giá trị mặc định hợp lệ.");
        }

        return $default;
    }

    /**
     * Lấy mã trạng thái theo key và báo lỗi sớm nếu code sử dụng trạng thái chưa được khai báo.
     */
    public static function value(string $group, string $key): string
    {
        if (! array_key_exists($key, self::values($group))) {
            throw new InvalidArgumentException("Trạng thái [{$key}] chưa được khai báo trong nhóm [{$group}].");
        }

        return $key;
    }

    public static function label(string $group, string|int|bool|null $value, string $fallback = 'Không xác định'): string
    {
        return (string) self::metadata($group, $value, 'label', $fallback);
    }

    public static function color(string $group, string|int|bool|null $value, string $fallback = 'gray'): string
    {
        return (string) self::metadata($group, $value, 'color', $fallback);
    }

    public static function metadata(
        string $group,
        string|int|bool|null $value,
        ?string $key = null,
        mixed $fallback = null,
    ): mixed {
        $normalizedValue = is_bool($value) ? (int) $value : $value;
        $metadata = self::values($group)[$normalizedValue] ?? null;

        if (! is_array($metadata)) {
            return $fallback;
        }

        return $key === null ? $metadata : ($metadata[$key] ?? $fallback);
    }

    public static function canTransition(string $group, string $from, string $to): bool
    {
        return in_array($to, config("status-app.{$group}.transitions.{$from}", []), true);
    }
}
