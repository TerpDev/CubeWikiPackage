<?php

namespace TerpDev\CubeWikiPackage\Livewire;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Livewire\Component;

class HelpactionButton extends Component implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    public function createAction(): Action
    {
        return Action::make('create')
            ->label('Help')
            ->icon('heroicon-o-question-mark-circle')
            ->slideOver()
            ->form([
                Toggle::make('show_help')
                    ->label('Show help')
                    ->inline(false),
            ])
            ->action(function (array $data) {
            });
    }

    public function render()
    {
        return view('cubewikipackage::livewire.helpaction', [
            'label' => 'Help',
            'icon' => 'heroicon-o-question-mark-circle',
        ]);
    }
}
