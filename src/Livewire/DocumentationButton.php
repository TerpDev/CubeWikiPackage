<?php

namespace TerpDev\CubeWikiPackage\Livewire;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Components\Wizard\Step;
use Illuminate\Support\Str;
use Livewire\Component;
use TerpDev\CubeWikiPackage\Filament\CubeWikiPlugin;
use TerpDev\CubeWikiPackage\Services\WikiCubeApiService;

class DocumentationButton extends Component implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    public function createAction(): Action
    {
        return Action::make('create')
            ->label(CubeWikiPlugin::$buttonLabel)
            ->icon(CubeWikiPlugin::$buttonIcon)
            ->steps([
                Step::make('Api Token')
                    ->description('Give your Api Token')
                    ->schema([
                        TextInput::make('token')
                            ->label('API Token')
                            ->required()

                            ->helperText('Enter your WikiCube API token to continue'),
                    ])
                    ->columns(1),
                Step::make('Application')
                    ->description('Kies hier de applicatie waar je de informatie van wilt zien')
                    ->schema([
                        Select::make('application_id')
                            ->label('Application')
                            ->required()
                            ->searchable()
                            ->options(function (callable $get) {
                                $token = $get('token');

                                if (!$token) {
                                    return [];
                                }

                                try {
                                    $apiService = app(WikiCubeApiService::class);
                                    $data = $apiService->fetchKnowledgeBase($token);

                                    // Applications are at the root level, not nested in 'data'
                                    if (isset($data['applications']) && is_array($data['applications'])) {
                                        $applications = [];
                                        foreach ($data['applications'] as $app) {
                                            $applications[$app['id']] = $app['name'];
                                        }
                                        return $applications;
                                    }

                                    return [];
                                } catch (\Exception $e) {
                                    return [];
                                }
                            })
                            ->placeholder('Select an application')
                            ->helperText('Choose the application you want to view documentation for'),
                    ]),
            ])
            ->action(function (array $data) {
                // Store the token and application_id in session
                session([
                    'cubewiki_token' => $data['token'],
                    'cubewiki_application_id' => $data['application_id'],
                ]);

                // Redirect to the CubeWiki panel
                return redirect()->to('/' . CubeWikiPlugin::$cubeWikiPanelPath);
            });
    }

    public function render()
    {
        return view('cubewikipackage::livewire.documentation-button', [
            'url' => '/' . CubeWikiPlugin::$cubeWikiPanelPath,
            'label' => CubeWikiPlugin::$buttonLabel,
            'icon' => CubeWikiPlugin::$buttonIcon,
        ]);
    }
}

