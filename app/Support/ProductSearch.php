<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ProductSearch
{
    public static function normalize(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        return mb_strtolower(Str::ascii($value), 'UTF-8');
    }

    public static function tokenize(?string $search): array
    {
        $search = self::normalize($search);
        if ($search === '') {
            return [];
        }

        return array_values(array_filter(
            preg_split('/\s+/u', $search) ?: [],
            static fn ($token) => $token !== ''
        ));
    }

    public static function normalizedExpression(string $column): string
    {
        $expr = "LOWER(COALESCE({$column}, ''))";
        $replacements = [
            'á' => 'a',
            'à' => 'a',
            'ä' => 'a',
            'â' => 'a',
            'ã' => 'a',
            'é' => 'e',
            'è' => 'e',
            'ë' => 'e',
            'ê' => 'e',
            'í' => 'i',
            'ì' => 'i',
            'ï' => 'i',
            'î' => 'i',
            'ó' => 'o',
            'ò' => 'o',
            'ö' => 'o',
            'ô' => 'o',
            'õ' => 'o',
            'ú' => 'u',
            'ù' => 'u',
            'ü' => 'u',
            'û' => 'u',
            'ñ' => 'n',
        ];

        foreach ($replacements as $from => $to) {
            $expr = "REPLACE({$expr}, '{$from}', '{$to}')";
        }

        return $expr;
    }

    protected static function applyNormalizedLike(
        Builder $query,
        string $column,
        string $term,
        string $boolean = 'or'
    ): void {
        $method = $boolean === 'and' ? 'whereRaw' : 'orWhereRaw';

        $query->{$method}(
            self::normalizedExpression($column) . ' LIKE ?',
            ['%' . self::normalize($term) . '%']
        );
    }

    public static function apply($query, string $search, string $nameColumn = 'nombre', array $codeColumns = ['codigo_interno', 'codigo_barras']): void
    {
        $search = trim($search);
        $normalizedSearch = self::normalize($search);

        if ($search === '' || $normalizedSearch === '') {
            return;
        }

        $tokens = self::tokenize($normalizedSearch);

        $query->where(function ($subQuery) use ($search, $tokens, $nameColumn, $codeColumns) {
            self::applyNormalizedLike($subQuery, $nameColumn, $search, 'and');

            foreach ($codeColumns as $column) {
                self::applyNormalizedLike($subQuery, $column, $search);
            }

            if (count($tokens) > 1) {
                $subQuery->orWhere(function ($tokenQuery) use ($tokens, $nameColumn, $codeColumns) {
                    foreach ($tokens as $token) {
                        $tokenQuery->where(function ($matchQuery) use ($token, $nameColumn, $codeColumns) {
                            self::applyNormalizedLike($matchQuery, $nameColumn, $token, 'and');

                            foreach ($codeColumns as $column) {
                                self::applyNormalizedLike($matchQuery, $column, $token);
                            }
                        });
                    }
                });
            }
        });
    }
}
