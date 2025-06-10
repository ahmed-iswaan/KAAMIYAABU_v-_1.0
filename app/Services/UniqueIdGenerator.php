<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UniqueIdGenerator
{
    /**
     * Generate a new unique ID for a given table/column with a prefix.
     *
     * e.g. generate('invoices','number','INV-',8) → INV-00000042
     */
    public static function generate(string $table, string $column, string $prefix, int $padLength = 8): string
    {
        // Find the latest existing number that matches the prefix
        $last = DB::table($table)
            ->where($column, 'like', $prefix.'%')
            ->orderBy($column, 'desc')
            ->value($column);

        $next = $last
            ? (int) Str::after($last, $prefix) + 1
            : 1;

        return $prefix . str_pad((string)$next, $padLength, '0', STR_PAD_LEFT);
    }
}
