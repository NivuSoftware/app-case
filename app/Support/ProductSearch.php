<?php

namespace App\Support;

class ProductSearch
{
    public static function tokenize(?string $search): array
    {
        $search = trim((string) $search);
        if ($search === '') {
            return [];
        }

        return array_values(array_filter(
            preg_split('/\s+/u', $search) ?: [],
            static fn ($token) => $token !== ''
        ));
    }

    public static function apply($query, string $search, string $nameColumn = 'nombre', array $codeColumns = ['codigo_interno', 'codigo_barras']): void
    {
        $search = trim($search);
        if ($search === '') {
            return;
        }

        $tokens = self::tokenize($search);

        $query->where(function ($subQuery) use ($search, $tokens, $nameColumn, $codeColumns) {
            $subQuery->where($nameColumn, 'like', '%' . $search . '%');

            foreach ($codeColumns as $column) {
                $subQuery->orWhere($column, 'like', '%' . $search . '%');
            }

            if (count($tokens) > 1) {
                $subQuery->orWhere(function ($tokenQuery) use ($tokens, $nameColumn, $codeColumns) {
                    foreach ($tokens as $token) {
                        $tokenQuery->where(function ($matchQuery) use ($token, $nameColumn, $codeColumns) {
                            $matchQuery->where($nameColumn, 'like', '%' . $token . '%');

                            foreach ($codeColumns as $column) {
                                $matchQuery->orWhere($column, 'like', '%' . $token . '%');
                            }
                        });
                    }
                });
            }
        });
    }
}
