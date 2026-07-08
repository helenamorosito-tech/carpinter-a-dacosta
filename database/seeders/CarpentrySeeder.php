<?php

namespace Database\Seeders;

use App\Enums\MaterialType;
use App\Models\FurniturePreset;
use App\Models\Material;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CarpentrySeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@carpinteriadacosta.com'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('password'),
            ],
        );

        $presets = [
            [
                'name' => 'Closet básico',
                'slug' => 'closet-basico',
                'description' => 'Mueble tipo closet con baldas interiores.',
                'default_shelf_count' => 3,
            ],
            [
                'name' => 'Cocina entre paredes',
                'slug' => 'cocina-entre-paredes',
                'description' => 'Módulo de cocina instalado entre dos paredes.',
                'default_shelf_count' => 2,
            ],
            [
                'name' => 'Librero costado visto',
                'slug' => 'librero-costado-visto',
                'description' => 'Librero con costado visible.',
                'default_shelf_count' => 4,
            ],
        ];

        foreach ($presets as $preset) {
            FurniturePreset::query()->updateOrCreate(
                ['slug' => $preset['slug']],
                [
                    'name' => $preset['name'],
                    'description' => $preset['description'],
                    'default_shelf_count' => $preset['default_shelf_count'],
                    'default_shelf_thickness_cm' => 1.6,
                    'is_active' => true,
                ],
            );
        }

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
    }
}
