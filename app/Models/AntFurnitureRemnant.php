<?php

namespace App\Models;

use App\Enums\AntRemnantOrigin;
use App\Enums\BoardColor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AntFurnitureRemnant extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'width_cm',
        'length_cm',
        'thickness_mm',
        'color',
        'status',
        'origin',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'width_cm' => 'decimal:2',
            'length_cm' => 'decimal:2',
            'thickness_mm' => 'integer',
            'color' => BoardColor::class,
            'origin' => AntRemnantOrigin::class,
            'is_active' => 'boolean',
        ];
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where('status', 'available');
    }

    public function getDimensionsLabelAttribute(): string
    {
        return sprintf(
            '%s × %s cm',
            rtrim(rtrim(number_format((float) $this->width_cm, 2, ',', ''), '0'), ','),
            rtrim(rtrim(number_format((float) $this->length_cm, 2, ',', ''), '0'), ','),
        );
    }

    public function getColorLabelAttribute(): string
    {
        return $this->color instanceof BoardColor
            ? $this->color->getLabel()
            : (string) $this->color;
    }

    public function getOriginLabelAttribute(): string
    {
        return $this->origin instanceof AntRemnantOrigin
            ? $this->origin->getLabel()
            : AntRemnantOrigin::Registered->getLabel();
    }

    /**
     * ¿Esta pieza puede cubrir el corte requerido (con rotación)?
     */
    public function canCover(float $requiredWidthCm, float $requiredHeightCm, int $requiredThicknessMm): bool
    {
        if ((int) $this->thickness_mm !== $requiredThicknessMm) {
            return false;
        }

        $w = (float) $this->width_cm;
        $l = (float) $this->length_cm;

        return ($w + 0.009 >= $requiredWidthCm && $l + 0.009 >= $requiredHeightCm)
            || ($w + 0.009 >= $requiredHeightCm && $l + 0.009 >= $requiredWidthCm);
    }

    public static function nextCode(): string
    {
        $codes = static::query()
            ->where('code', 'like', 'S-%')
            ->pluck('code');

        $max = 0;
        foreach ($codes as $code) {
            if (preg_match('/^S-(\d+)$/i', (string) $code, $matches) === 1) {
                $max = max($max, (int) $matches[1]);
            }
        }

        return sprintf('S-%02d', $max + 1);
    }
}
