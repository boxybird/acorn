<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Model
 *
 * @property list<string> $encryptedPhi
 * @property list<string> $blindIndexed
 */
trait HasEncryptedPhi
{
    public function initializeHasEncryptedPhi(): void
    {
        foreach ($this->encryptedPhi ?? [] as $field) {
            $this->mergeCasts([$field => 'encrypted']);
        }
    }

    public static function bootHasEncryptedPhi(): void
    {
        static::saving(function (Model $model): void {
            /** @var Model&HasEncryptedPhi $model */
            /** @var list<string> $blindIndexed */
            $blindIndexed = $model->blindIndexed ?? [];

            foreach ($blindIndexed as $field) {
                /** @var string|null $value */
                $value = $model->getAttribute($field);
                $hashColumn = $field.'_hash';

                $model->setAttribute(
                    $hashColumn,
                    $value !== null && $value !== '' ? $model->computeBlindIndex($field) : null,
                );
            }
        });
    }

    public function computeBlindIndex(string $field): string
    {
        /** @var string $value */
        $value = $this->getAttribute($field);

        /** @var string $appKey */
        $appKey = config('app.key');

        return hash_hmac('sha256', mb_strtolower($value), $appKey);
    }

    /**
     * @param  Builder<static>  $builder
     * @return Builder<static>
     */
    public function scopeWhereBlindIndex(Builder $builder, string $field, string $value): Builder
    {
        /** @var string $appKey */
        $appKey = config('app.key');

        $hash = hash_hmac('sha256', mb_strtolower($value), $appKey);

        return $builder->where($field.'_hash', $hash);
    }
}
