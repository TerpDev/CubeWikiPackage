# WikiCube Knowledge Base for Filament (Nederlandstalig)

Een gebruiksvriendelijke Filament-plugin waarmee je je WikiCube-kennisbank direct in het Filament-adminpanel toont. Je kunt meerdere tenants verbinden via API-tokens en de kennisbank bekijken met een georganiseerde, toegankelijke interface.

Belangrijkste features

- Koppel met één of meerdere WikiCube-tenant(s) via API-token.
- Toon applicaties, categorieën en pagina's overzichtelijk in Filament.
- Integratie met Filament-panels en -schemas (hint/important pages etc.).
- Caching en refresh-functionaliteit.

Vereisten

- PHP 8.1+
- Laravel 11.28+
- Filament v4+

Installatie

Voeg het pakket toe met Composer:

```bash
composer require terpdev/cubewikipackage
```

Registratie (optioneel)

Standaard wordt het pakket automatisch geregistreerd als je het via Composer installeert en gebruikt binnen een Filament-panel. Als je handmatig wilt registreren (bijvoorbeeld in je eigen `AdminPanelProvider`), doe je dat zo:

```php
use TerpDev\CubeWikiPackage\Filament\CubeWikiPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->default()
        ->id('admin')
        ->path('admin')
        ->plugins([
            CubeWikiPlugin::make()
        ]);
}
```

Configuratie

Publiceer en controleer de config (optioneel):

```bash
php artisan vendor:publish --tag=cubewikipackage-config
```

Voeg de volgende variabelen toe aan je `.env` (of controleer ze in `config/cubewikipackage.php`):

```env
WIKICUBE_API_URL=https://wikicube.example
WIKICUBE_API_TOKEN=je-api-token
WIKICUBE_APPLICATION_NAME=je-standaard-applicatie
```

Uitleg van instellingen

- `WIKICUBE_API_URL`: De basis-URL van de WikiCube API van je tenant.
- `WIKICUBE_API_TOKEN`: API-token om de knowledge base te lezen. Kan ook via UI ingevoerd worden als je dat zo wilt bouwen.
- `WIKICUBE_APPLICATION_NAME`: Naam van de standaard applicatie die getoond wordt.

Gebruik

- In je Filament panel zie je een Documentation/Help-knop (meestal onderin de sidebar) die naar de knowledge base leidt.
- Binnen de knowledge base kun je applicaties kiezen en vervolgens categorieën en pagina's doorbladeren.
- Er is een refresh-knop om cache te legen en gegevens opnieuw van de API te laden.

Plugin-opties (voorbeeld)

Je kunt de plugin configureren met belangrijke/adviezen pagina's zodat deze als shortcuts of hints beschikbaar zijn:

```php
CubeWikiPlugin::make()
    ->importantPages([
       ['slug' => 'your-slug-name', 'title' => 'Custom Title']
    ])
    ->hintPages([
       ['slug' => 'your-slug-name', 'title' => 'Custom Title']
    ])
```

Hints in Filament Form Components

Voorbeeld: een `TextInput` dat een hint toont via een Livewire-component van dit pakket:

```php
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\TextInput;

return $schema
    ->components([
        Section::make()
            ->schema([
                TextInput::make('slug')
                    ->label('Slug')
                    ->disabled()
                    ->dehydrated()
                    ->required()
                    ->maxLength(255)
                    ->unique(Post::class, 'slug', ignoreRecord: true)
                    ->hint(function () {
                        return new HtmlString(
                            Blade::render(
                                '<livewire:cubewikipackage-hintaction variant="hint" slug="your-slug-name" label="Custom Title" />'
                            )
                        );
                    }),
            ]),
    ]);
```

Troubleshooting

- "Geen data zichtbaar": controleer `WIKICUBE_API_URL` en `WIKICUBE_API_TOKEN`.
- "Sticky sidebar werkt niet": dit is meestal CSS/JS-gerelateerd. De plugin levert standaard structuur; als je custom theme of Tailwind-config hebt, controleer dan of `.sticky`-class en position: sticky toegestaan zijn binnen de container en dat er geen overflow: hidden is op een ouder-element.
- "Breadcrumbs te vaak zichtbaar": als je breadcrumbs alleen wilt tonen op paginapagina's (niet op index/list views), verplaats de rendering van breadcrumbs naar de specifieke page-view of guard ermee via een conditie (bijv. alleen renderen als er een huidige pagina-slug aanwezig is).

Ontwikkeling & testen

- Run unit tests / pest: `composer test`
- Run phpunit: `./vendor/bin/phpunit`

Code stijl en linting

Volg de repository-conventies; gebruik PHP CS Fixer / Rector indien geconfigureerd in het project.

Contributie

1. Fork de repo
2. Maak een feature-branch
3. Maak je wijzigingen en tests
4. Open een pull request

License

Dit project valt onder de MIT-licentie. Zie `LICENSE.md` voor details.

Contact

Voor vragen of issues, open een GitHub issue in de originele repository of neem contact op via de repository-eigenaar.

---

Als je wilt, kan ik ook specifiek een korte sectie toevoegen met:
- Markdown-voorbeeld voor `env`-bestand
- Snelle debugging-checklist voor sticky/breadcrumb-problemen (met concrete CSS/JS fixes)
- Voorstellen voor unit tests rond de View-logic (bijv. breadcrumbs alleen tonen wanneer een pagina is geselecteerd)

Laat weten welke van die extra's je wil, dan voeg ik ze direct toe.
