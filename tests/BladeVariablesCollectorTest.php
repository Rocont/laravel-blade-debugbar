<?php

namespace Rocont\BladeDebugbar\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\View\View;
use Mockery;
use Orchestra\Testbench\TestCase;
use Rocont\BladeDebugbar\BladeVariablesCollector;

class BladeVariablesCollectorTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function makeCollector(
        bool $groupByView = false,
        array $excluded = [],
        string $sharedMode = 'show',
    ): BladeVariablesCollector {
        return new BladeVariablesCollector($groupByView, $excluded, $sharedMode);
    }

    protected function mockView(string $name, array $data): View
    {
        $view = Mockery::mock(View::class);
        $view->shouldReceive('name')->andReturn($name);
        $view->shouldReceive('getData')->andReturn($data);

        return $view;
    }

    public function test_filters_system_variables(): void
    {
        $collector = $this->makeCollector();

        $collector->addView($this->mockView('welcome', [
            'title' => 'Hello',
            '__env' => 'should be removed',
            'app' => 'should be removed',
            'errors' => 'should be removed',
            '__data' => 'should be removed',
            '__customInternal' => 'should be removed',
        ]));

        $result = $collector->collect();

        $this->assertArrayHasKey('$title', $result);
        $this->assertCount(1, $result);
    }

    public function test_filters_custom_excluded_variables(): void
    {
        $collector = $this->makeCollector(excluded: ['secret']);

        $collector->addView($this->mockView('page', [
            'title' => 'Hi',
            'secret' => 'should be removed',
        ]));

        $result = $collector->collect();

        $this->assertArrayHasKey('$title', $result);
        $this->assertCount(1, $result);
    }

    public function test_converts_model_to_array(): void
    {
        $model = new class extends Model {
            protected $guarded = [];
        };
        $model->forceFill(['id' => 1, 'name' => 'Test']);

        $collector = $this->makeCollector();
        $collector->addView($this->mockView('page', ['user' => $model]));

        $result = $collector->collect();

        $this->assertArrayHasKey('$user', $result);
        $this->assertStringNotContainsString('Model', $result['$user']);
    }

    public function test_converts_collection_to_array(): void
    {
        $collection = new Collection([1, 2, 3]);

        $collector = $this->makeCollector();
        $collector->addView($this->mockView('page', ['items' => $collection]));

        $result = $collector->collect();

        $this->assertArrayHasKey('$items', $result);
        $this->assertStringNotContainsString('Collection', $result['$items']);
    }

    public function test_sorts_variables_alphabetically(): void
    {
        $collector = $this->makeCollector();

        $collector->addView($this->mockView('page', [
            'zebra' => 'z',
            'alpha' => 'a',
            'middle' => 'm',
        ]));

        $result = $collector->collect();
        $keys = array_keys($result);

        $this->assertEquals(['$alpha', '$middle', '$zebra'], $keys);
    }

    public function test_flat_mode(): void
    {
        $collector = $this->makeCollector(groupByView: false);

        $collector->addView($this->mockView('header', ['title' => 'Header']));
        $collector->addView($this->mockView('footer', ['year' => 2026]));

        $result = $collector->collect();

        $this->assertArrayHasKey('$title', $result);
        $this->assertArrayHasKey('$year', $result);
    }

    public function test_grouped_mode(): void
    {
        $collector = $this->makeCollector(groupByView: true);

        $collector->addView($this->mockView('header', ['title' => 'Header']));
        $collector->addView($this->mockView('footer', ['year' => 2026]));

        $result = $collector->collect();

        $this->assertArrayHasKey('footer → $year', $result);
        $this->assertArrayHasKey('header → $title', $result);
    }

    public function test_get_name(): void
    {
        $collector = $this->makeCollector();

        $this->assertEquals('blade_variables', $collector->getName());
    }

    public function test_get_widgets(): void
    {
        $collector = $this->makeCollector();
        $widgets = $collector->getWidgets();

        $this->assertArrayHasKey('Blade Variables', $widgets);
        $this->assertEquals('PhpDebugBar.Widgets.VariableListWidget', $widgets['Blade Variables']['widget']);
    }

    public function test_normalizes_nested_models_in_arrays(): void
    {
        $model = new class extends Model {
            protected $guarded = [];
        };
        $model->forceFill(['id' => 5]);

        $collector = $this->makeCollector();
        $collector->addView($this->mockView('page', [
            'items' => [$model],
        ]));

        $result = $collector->collect();

        $this->assertArrayHasKey('$items', $result);
    }

    public function test_shared_mode_mark(): void
    {
        ViewFacade::share('globalVar', 'shared value');

        $collector = $this->makeCollector(sharedMode: 'mark');

        $collector->addView($this->mockView('page', [
            'globalVar' => 'shared value',
            'localVar' => 'local value',
        ]));

        $result = $collector->collect();

        $this->assertArrayHasKey('[shared] $globalVar', $result);
        $this->assertArrayHasKey('$localVar', $result);
    }

    public function test_shared_mode_hide(): void
    {
        ViewFacade::share('globalVar', 'shared value');

        $collector = $this->makeCollector(sharedMode: 'hide');

        $collector->addView($this->mockView('page', [
            'globalVar' => 'shared value',
            'localVar' => 'local value',
        ]));

        $result = $collector->collect();

        $this->assertArrayNotHasKey('$globalVar', $result);
        $this->assertArrayNotHasKey('[shared] $globalVar', $result);
        $this->assertArrayHasKey('$localVar', $result);
    }

    public function test_shared_mode_show(): void
    {
        ViewFacade::share('globalVar', 'shared value');

        $collector = $this->makeCollector(sharedMode: 'show');

        $collector->addView($this->mockView('page', [
            'globalVar' => 'shared value',
            'localVar' => 'local value',
        ]));

        $result = $collector->collect();

        $this->assertArrayHasKey('$globalVar', $result);
        $this->assertArrayHasKey('$localVar', $result);
    }
}
