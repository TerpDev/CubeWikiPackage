<?php

namespace TerpDev\CubeWikiPackage\Filament\Resources\Pages;

use Filament\Resources\Pages\ListRecords;
use TerpDev\CubeWikiPackage\Filament\Resources\KnowledgeBaseResource;

class ListKnowledgeBases extends ListRecords
{
    protected static string $resource = KnowledgeBaseResource::class;
}
