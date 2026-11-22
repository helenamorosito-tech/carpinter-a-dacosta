<?php

namespace Database\Seeders;

use App\Enums\AntRemnantOrigin;
use App\Enums\BoardColor;
use App\Enums\BoardThickness;
use App\Enums\DoorType;
use App\Enums\FurnitureCabinetType;
use App\Enums\FurnitureWorkStatus;
use App\Enums\MaterialType;
use App\Enums\PlacementType;
use App\Models\AntFurnitureRemnant;
use App\Models\FurniturePreset;
use App\Models\FurnitureProject;
use App\Models\Material;
use App\Models\User;
use App\Support\BoardMaterialOptions;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CarpentrySeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->firstWhere('email', 'admin@gmail.com')
            ?? User::query()->firstWhere('email', 'admin@carpinteriadacosta.com')
            ?? new User();

        $admin->fill([
            'name' => 'Administrador',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('123456'),
        ]);
        $admin->save();

        $materials = [
            [
                'name' => 'Tablero textil estándar',
                'type' => MaterialType::Textil,
                'width_cm' => 122,
                'height_cm' => 244,
                'thickness_cm' => 1.6,
                'quantity' => 20,
            ],
            [
                'name' => 'Machembrado roble',
                'type' => MaterialType::Machembrado,
                'width_cm' => 122,
                'height_cm' => 244,
                'thickness_cm' => 1.8,
                'quantity' => 10,
            ],
            [
                'name' => 'Cartón piedra blanco',
                'type' => MaterialType::CartonPiedra,
                'width_cm' => 122,
                'height_cm' => 244,
                'thickness_cm' => 1.6,
                'quantity' => 8,
            ],
            [
                'name' => 'Melamina blanco 16 mm',
                'type' => MaterialType::Textil,
                'width_cm' => 122,
                'height_cm' => 244,
                'thickness_cm' => 1.6,
                'quantity' => 25,
            ],
        ];

        foreach ($materials as $material) {
            Material::query()->updateOrCreate(
                ['name' => $material['name']],
                [
                    'type' => $material['type'],
                    'width_cm' => $material['width_cm'],
                    'height_cm' => $material['height_cm'],
                    'thickness_cm' => $material['thickness_cm'],
                    'quantity' => $material['quantity'],
                    'is_active' => true,
                ],
            );
        }

        $materialIds = Material::query()->pluck('id')->all();
        $presets = $this->seedPresetExamples();
        $this->seedProjectExamples($admin, $presets, $materialIds);
        $this->seedRemnantExamples();
    }

    protected function seedRemnantExamples(): void
    {
        $pieces = [
            ['Balda sobrante blanca', 60.0, 40.0, 16, BoardColor::Blanco, AntRemnantOrigin::Registered],
            ['Lateral sapelly corto', 80.0, 55.0, 16, BoardColor::Sapelly, AntRemnantOrigin::SystemSobrante],
            ['Fondo 10 mm blanco', 100.0, 60.0, 10, BoardColor::Blanco, AntRemnantOrigin::SystemSobrante],
            ['Costado roble', 120.0, 50.0, 19, BoardColor::Roble, AntRemnantOrigin::Registered],
            ['Estante textil', 70.0, 35.0, 16, BoardColor::Textil, AntRemnantOrigin::Registered],
            ['Remate blanco estrecho', 45.0, 30.0, 16, BoardColor::Blanco, AntRemnantOrigin::SystemSobrante],
            ['Panel sapelly', 90.0, 48.0, 16, BoardColor::Sapelly, AntRemnantOrigin::Registered],
            ['Tapa roble 19 mm', 55.0, 42.0, 19, BoardColor::Roble, AntRemnantOrigin::SystemSobrante],
            ['Pieza textil media', 65.0, 38.0, 16, BoardColor::Textil, AntRemnantOrigin::Registered],
            ['Sobrante fondo 10 mm', 75.0, 50.0, 10, BoardColor::Blanco, AntRemnantOrigin::SystemSobrante],
        ];

        foreach ($pieces as $index => [$name, $width, $length, $thickness, $color, $origin]) {
            $code = sprintf('S-%02d', $index + 1);

            AntFurnitureRemnant::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'width_cm' => $width,
                    'length_cm' => $length,
                    'thickness_mm' => $thickness,
                    'color' => $color,
                    'status' => 'available',
                    'origin' => $origin,
                    'notes' => 'Pieza de demostración local',
                    'is_active' => true,
                ],
            );
        }
    }

    /**
     * @return list<FurniturePreset>
     */
    protected function seedPresetExamples(): array
    {
        $examples = [
            [
                'name' => 'Armario dormitorio 2 puertas abrir',
                'slug' => 'ejemplo-armario-2-abrir',
                'description' => 'Ejemplo local: armario estrecho de abrir entre paredes.',
                'furniture_type' => FurnitureCabinetType::Armario2PuertasAbrir->value,
                'side_type' => PlacementType::EntreParedes->value,
                'opening' => [2.40, 1.20, 60],
                'global' => [233.5, 107.0, 60.0],
                'shelves' => 3,
                'drawers' => 0,
                'bars' => 1,
                'color' => BoardColor::Blanco,
                'thickness' => 16,
            ],
            [
                'name' => 'Armario vestidor 3 puertas abrir',
                'slug' => 'ejemplo-armario-3-abrir',
                'description' => 'Ejemplo local: armario medio con baldas y cajones.',
                'furniture_type' => FurnitureCabinetType::Armario3PuertasAbrir->value,
                'side_type' => PlacementType::CostadoVisto->value,
                'opening' => [2.50, 1.80, 65],
                'global' => [243.5, 173.0, 65.0],
                'shelves' => 4,
                'drawers' => 2,
                'bars' => 1,
                'color' => BoardColor::Roble,
                'thickness' => 16,
            ],
            [
                'name' => 'Armario familiar 4 puertas abrir',
                'slug' => 'ejemplo-armario-4-abrir',
                'description' => 'Ejemplo local: armario ancho con maletero.',
                'furniture_type' => FurnitureCabinetType::Armario4PuertasAbrir->value,
                'side_type' => PlacementType::EntreParedes->value,
                'opening' => [2.60, 2.40, 70],
                'global' => [253.5, 227.0, 70.0],
                'shelves' => 5,
                'drawers' => 3,
                'bars' => 2,
                'color' => BoardColor::Sapelly,
                'thickness' => 19,
            ],
            [
                'name' => 'Armario correderas 2 puertas',
                'slug' => 'ejemplo-armario-2-correderas',
                'description' => 'Ejemplo local: correderas en pasillo estrecho.',
                'furniture_type' => FurnitureCabinetType::Armario2PuertasCorrederas->value,
                'side_type' => PlacementType::EntreParedes->value,
                'opening' => [2.35, 1.40, 55],
                'global' => [228.5, 127.0, 55.0],
                'shelves' => 3,
                'drawers' => 1,
                'bars' => 1,
                'color' => BoardColor::Blanco,
                'thickness' => 16,
            ],
            [
                'name' => 'Armario correderas 3 puertas textil',
                'slug' => 'ejemplo-armario-3-correderas',
                'description' => 'Ejemplo local: correderas con acabado textil.',
                'furniture_type' => FurnitureCabinetType::Armario3PuertasCorrederas->value,
                'side_type' => PlacementType::CostadoVisto->value,
                'opening' => [2.45, 2.00, 62],
                'global' => [238.5, 193.0, 62.0],
                'shelves' => 4,
                'drawers' => 2,
                'bars' => 2,
                'color' => BoardColor::Textil,
                'thickness' => 16,
            ],
            [
                'name' => 'Armario correderas 4 puertas roble',
                'slug' => 'ejemplo-armario-4-correderas',
                'description' => 'Ejemplo local: correderas grandes en suite.',
                'furniture_type' => FurnitureCabinetType::Armario4PuertasCorrederas->value,
                'side_type' => PlacementType::EntreParedes->value,
                'opening' => [2.70, 2.80, 75],
                'global' => [263.5, 267.0, 75.0],
                'shelves' => 6,
                'drawers' => 4,
                'bars' => 2,
                'color' => BoardColor::Roble,
                'thickness' => 19,
            ],
            [
                'name' => 'Closet infantil blanco',
                'slug' => 'ejemplo-closet-infantil',
                'description' => 'Ejemplo local: medidas reducidas para habitación infantil.',
                'furniture_type' => FurnitureCabinetType::Armario2PuertasAbrir->value,
                'side_type' => PlacementType::CostadoVisto->value,
                'opening' => [2.10, 1.00, 50],
                'global' => [203.5, 93.0, 50.0],
                'shelves' => 2,
                'drawers' => 1,
                'bars' => 1,
                'color' => BoardColor::Blanco,
                'thickness' => 16,
            ],
            [
                'name' => 'Armario oficina sapelly',
                'slug' => 'ejemplo-armario-oficina',
                'description' => 'Ejemplo local: almacenamiento de oficina con 3 módulos.',
                'furniture_type' => FurnitureCabinetType::Armario3PuertasAbrir->value,
                'side_type' => PlacementType::EntreParedes->value,
                'opening' => [2.20, 1.60, 45],
                'global' => [213.5, 147.0, 45.0],
                'shelves' => 5,
                'drawers' => 0,
                'bars' => 0,
                'color' => BoardColor::Sapelly,
                'thickness' => 16,
            ],
            [
                'name' => 'Vestidor abierto 10 mm fondo',
                'slug' => 'ejemplo-vestidor-abierto',
                'description' => 'Ejemplo local: fondo fino y barra de colgar larga.',
                'furniture_type' => FurnitureCabinetType::Armario2PuertasAbrir->value,
                'side_type' => PlacementType::CostadoVisto->value,
                'opening' => [2.55, 1.50, 58],
                'global' => [248.5, 143.0, 58.0],
                'shelves' => 2,
                'drawers' => 0,
                'bars' => 2,
                'color' => BoardColor::Blanco,
                'thickness' => 16,
                'back_thickness' => 10,
            ],
            [
                'name' => 'Armario lavadero utilitario',
                'slug' => 'ejemplo-armario-lavadero',
                'description' => 'Ejemplo local: mueble técnico con baldas densas.',
                'furniture_type' => FurnitureCabinetType::Armario2PuertasCorrederas->value,
                'side_type' => PlacementType::EntreParedes->value,
                'opening' => [2.30, 1.10, 48],
                'global' => [223.5, 97.0, 48.0],
                'shelves' => 6,
                'drawers' => 0,
                'bars' => 0,
                'color' => BoardColor::Textil,
                'thickness' => 16,
            ],
        ];

        $created = [];

        foreach ($examples as $example) {
            [$openH, $openW, $openD] = $example['opening'];
            [$globalH, $globalW, $globalD] = $example['global'];
            $thickness = (int) $example['thickness'];
            $color = $example['color'];
            $config = FurniturePreset::defaultConfiguration((float) $openD);
            $config['shelf_count'] = $example['shelves'];
            $config['module_count'] = max(1, (int) ceil($globalW / 90));
            $config['drawers_count'] = $example['drawers'];
            $config['drawer_width_cm'] = $example['drawers'] > 0 ? round($globalW / max(1, $example['drawers']) - 2, 2) : null;
            $config['drawer_height_cm'] = $example['drawers'] > 0 ? 18.0 : null;
            $config['drawer_depth_cm'] = $example['drawers'] > 0 ? round($globalD - 9.5, 2) : null;
            $config['hanging_bars_count'] = $example['bars'];
            $config['hanging_bar_length_cm'] = $example['bars'] > 0 ? round($globalW - 6.5, 2) : null;
            $config['shelf_depth_cm'] = round(max(0, $globalD - FurniturePreset::DEFAULT_SHELF_DEPTH_OFFSET_CM), 2);

            $created[] = FurniturePreset::query()->updateOrCreate(
                ['slug' => $example['slug']],
                [
                    'name' => $example['name'],
                    'description' => $example['description'],
                    'opening_height_m' => $openH,
                    'opening_width_m' => $openW,
                    'opening_depth_cm' => $openD,
                    'global_height_cm' => $globalH,
                    'global_width_cm' => $globalW,
                    'global_depth_cm' => $globalD,
                    'furniture_type' => $example['furniture_type'],
                    'side_type' => $example['side_type'],
                    'configuration' => $config,
                    'board_width_cm' => FurniturePreset::DEFAULT_BOARD_WIDTH_CM,
                    'board_height_cm' => FurniturePreset::DEFAULT_BOARD_HEIGHT_CM,
                    'selected_boards' => [BoardMaterialOptions::optionKey($color, BoardThickness::tryFrom($thickness) ?? BoardThickness::Mm16)],
                    'structure_board_thickness_mm' => $thickness,
                    'back_board_thickness_mm' => $example['back_thickness'] ?? FurniturePreset::DEFAULT_BACK_BOARD_THICKNESS_MM,
                    'back_board_quantity' => 1,
                    'default_shelf_count' => $example['shelves'],
                    'default_shelf_thickness_cm' => 1.6,
                    'is_active' => true,
                ],
            );
        }

        return $created;
    }

    /**
     * @param  list<FurniturePreset>  $presets
     * @param  list<int>  $materialIds
     */
    protected function seedProjectExamples(User $admin, array $presets, array $materialIds): void
    {
        $jobs = [
            ['Armario suite principal', 1850.00, FurnitureWorkStatus::EnObra, 0],
            ['Closet habitación niños', 980.50, FurnitureWorkStatus::EnProyecto, 6],
            ['Vestidor pasillo correderas', 2140.00, FurnitureWorkStatus::Terminado, 3],
            ['Armario oficina planta baja', 1260.00, FurnitureWorkStatus::EnProyecto, 7],
            ['Mueble lavadero utilitario', 740.00, FurnitureWorkStatus::EnObra, 9],
            ['Armario 4 puertas sala', 2650.75, FurnitureWorkStatus::Terminado, 2],
            ['Armario textil dormitorio 2', 1580.00, FurnitureWorkStatus::EnProyecto, 4],
            ['Closet invitados blanco', 890.00, FurnitureWorkStatus::Terminado, 0],
            ['Armario roble vestidor', 2390.00, FurnitureWorkStatus::EnObra, 5],
            ['Mueble almacenamiento cocina', 1125.25, FurnitureWorkStatus::EnProyecto, 1],
        ];

        foreach ($jobs as $index => [$name, $price, $status, $presetIndex]) {
            $preset = $presets[$presetIndex % count($presets)];
            $materialId = $materialIds[$index % count($materialIds)] ?? null;

            FurnitureProject::query()->updateOrCreate(
                [
                    'user_id' => $admin->id,
                    'reference' => 'DEMO-'.Str::padLeft((string) ($index + 1), 2, '0'),
                ],
                [
                    'furniture_preset_id' => $preset->id,
                    'material_id' => $materialId,
                    'name' => $name,
                    'height_cm' => $preset->global_height_cm ?? 240,
                    'width_cm' => $preset->global_width_cm ?? 120,
                    'depth_cm' => $preset->global_depth_cm ?? 60,
                    'placement_type' => $preset->side_type instanceof PlacementType
                        ? $preset->side_type
                        : (PlacementType::tryFrom((string) ($preset->side_type ?? '')) ?? PlacementType::CostadoVisto),
                    'door_type' => str_contains((string) ($preset->furniture_type ?? ''), 'corredera')
                        ? DoorType::Corredizas
                        : DoorType::Abatibles,
                    'shelf_count' => $preset->default_shelf_count ?? 3,
                    'shelf_thickness_cm' => 1.6,
                    'module_count' => (int) (($preset->configuration['module_count'] ?? 1)),
                    'door_count' => FurnitureCabinetType::tryFrom((string) $preset->furniture_type)?->doorCount() ?? 2,
                    'status' => $status,
                    'price' => $price,
                    'updated_at' => now()->subDays(9 - $index)->subHours($index),
                    'created_at' => now()->subDays(20 - $index),
                ],
            );
        }
    }
}
