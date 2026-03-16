<?php

namespace Rocont\BladeDebugbar;

use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\View\View;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;

class BladeVariablesCollector extends DataCollector implements Renderable
{
    /** @var array<string, array<string, mixed>> */
    protected array $views = [];

    protected bool $groupByView;

    protected string $sharedMode;

    /** @var list<string> */
    protected array $excludedVariables;

    /** @var list<string> */
    protected const SYSTEM_VARIABLES = [
        '__env',
        '__data',
        '__path',
        '__currentLoopData',
        '__empty',
        'app',
        'errors',
        'obLevel',
        'slot',
        '__laravel_slots',
        'attributes',
        'component',
        '__componentOriginal',
    ];

    public function __construct(bool $groupByView = true, array $excludedVariables = [], string $sharedMode = 'mark')
    {
        $this->groupByView = $groupByView;
        $this->sharedMode = $sharedMode;
        $this->excludedVariables = array_unique(array_merge(self::SYSTEM_VARIABLES, $excludedVariables));
    }

    public function addView(View $view): void
    {
        $name = $view->name();
        $data = $view->getData();

        foreach ($this->excludedVariables as $key) {
            unset($data[$key]);
        }

        // Remove variables starting with __ (internal Blade variables)
        $data = array_filter($data, function (string $key) {
            return !str_starts_with($key, '__');
        }, ARRAY_FILTER_USE_KEY);

        // Handle shared variables
        $sharedKeys = array_keys($this->getSharedVariables());
        if ($this->sharedMode === 'hide') {
            $data = array_diff_key($data, array_flip($sharedKeys));
        }

        // Convert models and collections to arrays
        $data = array_map([$this, 'normalizeValue'], $data);

        // Sort by key
        ksort($data);

        $this->views[$name] = [
            'data' => $data,
            'shared_keys' => $sharedKeys,
        ];
    }

    protected function getSharedVariables(): array
    {
        $shared = ViewFacade::getShared();

        foreach ($this->excludedVariables as $key) {
            unset($shared[$key]);
        }

        return array_filter($shared, function (string $key) {
            return !str_starts_with($key, '__');
        }, ARRAY_FILTER_USE_KEY);
    }

    protected function normalizeValue(mixed $value): mixed
    {
        if ($value instanceof Model) {
            return $value->toArray();
        }

        if ($value instanceof Collection) {
            return $value->toArray();
        }

        if (is_array($value)) {
            return array_map([$this, 'normalizeValue'], $value);
        }

        return $value;
    }

    public function collect(): array
    {
        if ($this->groupByView) {
            return $this->collectGrouped();
        }

        return $this->collectFlat();
    }

    protected function dumpVar(mixed $value): string
    {
        $cloner = new VarCloner();
        $dumper = new CliDumper();

        return (string) $dumper->dump($cloner->cloneVar($value), true);
    }

    protected function collectGrouped(): array
    {
        $result = [];

        foreach ($this->views as $viewName => $viewInfo) {
            foreach ($viewInfo['data'] as $key => $value) {
                $prefix = $this->getVariablePrefix($key, $viewInfo['shared_keys']);
                $label = $viewName . ' → ' . $prefix . '$' . $key;
                $result[$label] = $this->dumpVar($value);
            }
        }

        ksort($result);

        return $result;
    }

    protected function collectFlat(): array
    {
        $result = [];

        foreach ($this->views as $viewInfo) {
            foreach ($viewInfo['data'] as $key => $value) {
                $prefix = $this->getVariablePrefix($key, $viewInfo['shared_keys']);
                $label = $prefix . '$' . $key;
                $result[$label] = $this->dumpVar($value);
            }
        }

        ksort($result);

        return $result;
    }

    protected function getVariablePrefix(string $key, array $sharedKeys): string
    {
        if ($this->sharedMode === 'mark' && in_array($key, $sharedKeys, true)) {
            return '[shared] ';
        }

        return '';
    }

    public function getName(): string
    {
        return 'blade_variables';
    }

    public function getWidgets(): array
    {
        return [
            'Blade Variables' => [
                'icon' => 'code',
                'widget' => 'PhpDebugBar.Widgets.VariableListWidget',
                'map' => 'blade_variables',
                'default' => '{}',
            ],
        ];
    }
}
