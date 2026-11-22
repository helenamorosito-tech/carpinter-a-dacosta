<?php

namespace App\Support;

use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\TextColumn;

/**
 * Estilos visuales unificados en listados y fichas.
 * Alto = verde, Ancho = naranja, Tipo = azul, Precio = verde, Actualizado = naranja, Nombre = negrita.
 */
class FilamentUiBadges
{
    public static function nameColumn(TextColumn $column): TextColumn
    {
        return $column->weight('bold');
    }

    public static function typeColumn(TextColumn $column): TextColumn
    {
        return $column->badge()->color('info');
    }

    public static function heightColumn(TextColumn $column): TextColumn
    {
        return $column->badge()->color('success');
    }

    public static function widthColumn(TextColumn $column): TextColumn
    {
        return $column->badge()->color('warning');
    }

    public static function priceColumn(TextColumn $column): TextColumn
    {
        return $column->badge()->color('success');
    }

    public static function updatedColumn(TextColumn $column): TextColumn
    {
        return $column->badge()->color('warning');
    }

    public static function nameEntry(TextEntry $entry): TextEntry
    {
        return $entry->weight('bold');
    }

    public static function typeEntry(TextEntry $entry): TextEntry
    {
        return $entry->badge()->color('info');
    }

    public static function heightEntry(TextEntry $entry): TextEntry
    {
        return $entry->badge()->color('success');
    }

    public static function widthEntry(TextEntry $entry): TextEntry
    {
        return $entry->badge()->color('warning');
    }

    public static function priceEntry(TextEntry $entry): TextEntry
    {
        return $entry->badge()->color('success');
    }

    public static function updatedEntry(TextEntry $entry): TextEntry
    {
        return $entry->badge()->color('warning');
    }
}
