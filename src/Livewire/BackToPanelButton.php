<?php

namespace TerpDev\CubeWikiPackage\Livewire;

use Filament\Facades\Filament;
use Livewire\Component;
use TerpDev\CubeWikiPackage\CubeWikiPackageServiceProvider;

class BackToPanelButton extends Component
{
    public array $panels = [];

    public function mount(): void
    {
        $this->panels = $this->getAvailablePanels();
    }

    protected function getAvailablePanels(): array
    {
        $panels = [];

        $preferred = session('cubewiki_return_panel');

        foreach (Filament::getPanels() as $panel) {
            if ($panel->getId() === CubeWikiPackageServiceProvider::$cubeWikiPanelPath) {
                continue;
            }
            $panels[] = [
                'id' => $panel->getId(),
                'label' => $panel->getBrandName(),
                'url' => $panel->getUrl(),
            ];
        }

        return $panels;
    }

    public function render()
    {
        return view('cubewikipackage::livewire.back-to-panel-button', [
            'panels' => $this->panels,
            'singlePanel' => $this->panels[0] ?? null,
            'hasMultiplePanels' => count($this->panels) > 1,
        ]);
    }
}
