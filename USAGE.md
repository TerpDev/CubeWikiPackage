# CubeWiki Package - Gebruiksinstructies

## CubeWiki Panel (Knowledge Base)

De CubeWiki package biedt een apart Filament panel voor je knowledge base dat automatisch toegankelijk is op `/cubewiki`.

Het panel is automatisch geregistreerd en hoeft geen extra configuratie.

## Documentatie Button in Sidebar

Om een documentatie knop toe te voegen aan je hoofdpanel die gebruikers naar `/cubewiki` brengt, voeg je de `CubeWikiPlugin` toe aan je panel:

```php
use TerpDev\CubeWikiPackage\Filament\CubeWikiPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->id('admin')
        ->path('admin')
        ->plugins([
            CubeWikiPlugin::make(),
        ]);
}
```

### Configuratie Opties

Je kunt de button aanpassen met de volgende opties:

```php
CubeWikiPlugin::make()
    ->cubeWikiPanelPath('documentation') // Wijzig het pad (default: 'cubewiki')
    ->buttonLabel('Help & Documentation') // Wijzig de label (default: 'Documentation')
    ->buttonIcon('heroicon-o-question-mark-circle') // Wijzig het icoon (default: 'heroicon-o-book-open')
```

De button verschijnt automatisch linksonder in de sidebar via een render hook op `PanelsRenderHook::SIDEBAR_FOOTER`.

## Hoe het werkt

De plugin gebruikt Filament's render hooks om een Livewire component in de sidebar footer te injecteren. Dit zorgt ervoor dat de button netjes geïntegreerd is in het Filament design systeem en consistent werkt met de rest van je applicatie.

