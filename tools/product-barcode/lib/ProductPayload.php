<?php

declare(strict_types=1);

final class ProductPayload
{
    private const MAX_JSON = 80;

    /**
     * @param array<string, mixed> $input
     * @return array{ok:true, data:array<string, mixed>, json:string}|array{ok:false, errors:list<string>}
     */
    public static function fromForm(array $input): array
    {
        $errors = [];
        $name = self::clip((string) ($input['name'] ?? ''), 80);
        $sku = self::clip((string) ($input['sku'] ?? ''), 40);
        $category = self::clip((string) ($input['category'] ?? ''), 40);
        $notes = self::clip((string) ($input['notes'] ?? ''), 80);
        $priceRaw = trim((string) ($input['price'] ?? ''));
        $qtyRaw = trim((string) ($input['qty'] ?? ''));

        if ($name === '') {
            $errors[] = 'Product name is required.';
        }
        if ($sku === '') {
            $errors[] = 'SKU is required.';
        }
        if ($priceRaw === '' || !is_numeric($priceRaw) || (float) $priceRaw < 0) {
            $errors[] = 'Enter a valid price.';
        }
        if ($qtyRaw === '' || !preg_match('/^\d+$/', $qtyRaw)) {
            $errors[] = 'Enter a valid quantity.';
        }

        if ($errors) {
            return ['ok' => false, 'errors' => $errors];
        }

        $data = [
            'name' => $name,
            'sku' => $sku,
            'price' => round((float) $priceRaw, 2),
            'qty' => (int) $qtyRaw,
            'category' => $category,
            'notes' => $notes,
        ];
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($json)) {
            return ['ok' => false, 'errors' => ['Could not encode product data.']];
        }
        if (strlen($json) > self::MAX_JSON) {
            return [
                'ok' => false,
                'errors' => ['Details are too long for a 1D barcode. Shorten name or notes.'],
            ];
        }

        return ['ok' => true, 'data' => $data, 'json' => $json];
    }

    /**
     * @return array{ok:true, data:mixed}|array{ok:false, error:string}
     */
    public static function fromScan(string $text): array
    {
        $text = trim($text);
        if ($text === '') {
            return ['ok' => false, 'error' => 'Empty scan.'];
        }

        $decoded = json_decode($text, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return ['ok' => true, 'data' => $decoded];
        }

        return ['ok' => true, 'data' => ['raw' => $text]];
    }

    private static function clip(string $value, int $max): string
    {
        $value = trim($value);
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $max);
        }
        return substr($value, 0, $max);
    }
}
