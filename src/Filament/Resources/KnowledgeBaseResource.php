<?php

namespace TerpDev\CubeWikiPackage\Filament\Resources;

use Filament\Forms;
use Filament\Pages\Actions\ViewAction;
use Filament\Tables;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use TerpDev\CubeWikiPackage\Filament\Resources\Pages\ListKnowledgeBases;
use TerpDev\CubeWikiPackage\Services\WikiCubeApiService;

class KnowledgeBaseResource extends Resource
{
    protected static ?string $model = null;

    protected static bool $shouldRegisterNavigation = false;

//    protected static ?string $navigationLabel = 'WikiCube Knowledge Base';
    protected static ?string $slug = 'wikicube-knowledge-base';

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
//                TextColumn::make('title'),
//                TextColumn::make('category'),
            ])
            ->actions([
//                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKnowledgeBases::route('/'),
//            'view' => Pages\ViewKnowledgeBase::route('/{record}'),
        ];
    }
}
