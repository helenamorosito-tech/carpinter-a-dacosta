<?php

namespace App\Services\Carpentry;

use App\Models\AntFurnitureRemnant;
use App\Support\MeasureFormat;

class AntFurnitureRemnantMatcher
{
    /**
     * Empareja piezas requeridas del mueble con sobrantes disponibles (Muebles Ant).
     * Prioriza el sobrante más ajustado que cubra el corte.
     *
     * @param  list<array{label?: string, width_cm?: float, height_cm?: float, quantity?: int, board_role?: string|null}>  $requiredPieces
     * @param  int|null  $defaultThicknessMm  Grosor por defecto si la pieza no indica uno
     * @return list<array{
     *     remnant: AntFurnitureRemnant,
     *     piece_label: string,
     *     required_width_cm: float,
     *     required_height_cm: float,
     *     thickness_mm: int,
     *     name: string,
     *     use: string,
     *     measures: string,
     *     thickness: string,
     *     code: string
     * }>
     */
    public function matchForPieces(array $requiredPieces, ?int $defaultThicknessMm = 16): array
    {
        $available = AntFurnitureRemnant::query()
            ->available()
            ->orderBy('thickness_mm')
            ->orderBy('width_cm')
            ->orderBy('length_cm')
            ->get();

        if ($available->isEmpty()) {
            return [];
        }

        $usedIds = [];
        $matches = [];

        foreach ($requiredPieces as $piece) {
            $label = (string) ($piece['label'] ?? 'Pieza');
            $qty = max(1, (int) ($piece['quantity'] ?? 1));
            $reqW = (float) ($piece['width_cm'] ?? 0);
            $reqH = (float) ($piece['height_cm'] ?? 0);

            if ($reqW <= 0 || $reqH <= 0) {
                continue;
            }

            $thicknessMm = $this->resolveThicknessMm($piece, $defaultThicknessMm);

            for ($i = 0; $i < $qty; $i++) {
                $best = null;
                $bestWaste = null;

                foreach ($available as $remnant) {
                    if (in_array($remnant->id, $usedIds, true)) {
                        continue;
                    }

                    if (! $remnant->canCover($reqW, $reqH, $thicknessMm)) {
                        continue;
                    }

                    $waste = ((float) $remnant->width_cm * (float) $remnant->length_cm) - ($reqW * $reqH);
                    if ($bestWaste === null || $waste < $bestWaste) {
                        $best = $remnant;
                        $bestWaste = $waste;
                    }
                }

                if ($best === null) {
                    continue;
                }

                $usedIds[] = $best->id;
                $pieceLabel = $qty > 1 ? "{$label} #".($i + 1) : $label;
                $originLabel = $best->origin_label;

                $matches[] = [
                    'remnant' => $best,
                    'piece_label' => $pieceLabel,
                    'required_width_cm' => $reqW,
                    'required_height_cm' => $reqH,
                    'thickness_mm' => $thicknessMm,
                    'code' => $best->code,
                    'origin' => $originLabel,
                    'name' => sprintf('%s — %s', $best->code, $best->name),
                    'use' => sprintf(
                        '%s · para %s (%s)',
                        $originLabel,
                        $pieceLabel,
                        $best->color_label,
                    ),
                    'measures' => MeasureFormat::pair((float) $best->width_cm, (float) $best->length_cm),
                    'thickness' => $best->thickness_mm.' mm',
                ];
            }
        }

        return $matches;
    }

    /**
     * @param  array<string, mixed>  $piece
     */
    protected function resolveThicknessMm(array $piece, ?int $defaultThicknessMm): int
    {
        if (isset($piece['thickness_mm']) && (int) $piece['thickness_mm'] > 0) {
            return (int) $piece['thickness_mm'];
        }

        $label = mb_strtolower((string) ($piece['label'] ?? ''));
        if (preg_match('/\((\d+)\s*mm\)/u', $label, $m) === 1) {
            return (int) $m[1];
        }

        if (($piece['board_role'] ?? null) === 'back') {
            return 10;
        }

        return max(1, (int) ($defaultThicknessMm ?? 16));
    }
}
