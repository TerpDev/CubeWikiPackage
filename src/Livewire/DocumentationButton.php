<?php

namespace TerpDev\CubeWikiPackage\Livewire;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Livewire\Component;
use TerpDev\CubeWikiPackage\Filament\CubeWikiPlugin;

class DocumentationButton extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    public function createAction(): Action
    {
        return Action::make('create')
            ->label('Documentation')
            ->icon('heroicon-o-book-open')
            ->action(function () {
                $token = config('cubewikipackage.api_token');

                $applicationName = config('cubewikipackage.default_application');

                $currentPanel = Filament::getCurrentPanel();
                if ($currentPanel) {
                    session(['cubewiki_return_panel' => $currentPanel->getId()]);
                }

                if ($token) {
                    session(['cubewiki_token' => $token]);
                }

                if ($applicationName) {
                    session(['cubewiki_application_name' => $applicationName]);
                }

                $url = '/'.CubeWikiPlugin::$cubeWikiPanelPath.'/knowledge-base';
                if (! empty($applicationName)) {
                    $url .= '?app='.urlencode((string) $applicationName);
                }

                return redirect()->to($url);
            });
    }

    public function render()
    {
        return view('cubewikipackage::livewire.documentation-button', [
            'url' => '/'.CubeWikiPlugin::$cubeWikiPanelPath,
            'label' => 'Documentation',
            'icon' => 'heroicon-o-book-open',
        ]);
    }
}
