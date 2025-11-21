<?php

namespace TerpDev\CubeWikiPackage\Livewire;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Livewire\Component;
use TerpDev\CubeWikiPackage\Filament\CubeWikiPlugin;

class DocumentationButton extends Component implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    public function createAction(): Action
    {
        return Action::make('create')
            ->label(CubeWikiPlugin::$buttonLabel)
            ->icon(CubeWikiPlugin::$buttonIcon)
            ->action(function () {
                $token = env('WIKICUBE_API_TOKEN') ?: config('cubewikipackage.token');

                $applicationName = env('WIKICUBE_APPLICATION_NAME')  ?: config('cubewikipackage.application_name');

                if ($token) {
                    session(['cubewiki_token' => $token]);
                }

                if ($applicationName) {
                    session(['cubewiki_application_name' => $applicationName]);
                }

                $url = '/' . CubeWikiPlugin::$cubeWikiPanelPath . '/knowledge-base';
                if (! empty($applicationName)) {
                    $url .= '?app=' . urlencode((string) $applicationName);
                }

                return redirect()->to($url);
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
