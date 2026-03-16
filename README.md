# Laravel Blade Debugbar

A [Laravel Debugbar](https://github.com/barryvdh/laravel-debugbar) extension that adds a **Blade Variables** tab showing all variables passed to Blade views.

## Features

- Displays all variables passed to each Blade template in a dump()-like format
- Automatically filters out Laravel system variables (`__env`, `app`, `errors`, etc.)
- Converts Eloquent models and collections to arrays for readable output
- Marks or hides shared variables (`View::share()`)
- Optional grouping by view name
- Alphabetical sorting

## Requirements

- PHP 8.0+
- Laravel 9, 10, 11, or 12
- [barryvdh/laravel-debugbar](https://github.com/barryvdh/laravel-debugbar) 3.7+

## Installation

```bash
composer require --dev rocont/laravel-blade-debugbar
```

The service provider is registered automatically via Laravel Package Discovery.

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=blade-debugbar-config
```

This creates `config/blade-debugbar.php`:

```php
return [

    // Group variables by Blade view name (e.g. "welcome → $title")
    'group_by_view' => false,

    // Additional variables to exclude (system variables are always excluded)
    'excluded_variables' => [],

    // How to handle View::share() variables: "mark", "hide", or "show"
    'shared_mode' => 'mark',

];
```

### Options

| Option | Values | Default | Description |
|---|---|---|---|
| `group_by_view` | `true` / `false` | `false` | Group variables by Blade view name |
| `excluded_variables` | `array` | `[]` | Additional variable names to exclude |
| `shared_mode` | `mark` / `hide` / `show` | `mark` | How to display shared variables |

#### Shared mode

- **`mark`** — shared variables are shown with a `[shared]` prefix, e.g. `[shared] $currentUser`
- **`hide`** — shared variables are excluded completely
- **`show`** — shared variables are shown without any distinction

## Usage

Once installed, open any page in your Laravel app. The Debugbar will show a new **Blade Variables** tab with all variables passed to each rendered Blade template.

### Example output

Flat mode (`group_by_view: false`):

```
$posts          → array (3) [...]
$title          → "Blog"
[shared] $user  → array ["id" => 1, "name" => "John"]
```

Grouped mode (`group_by_view: true`):

```
blog.index → $posts    → array (3) [...]
blog.index → $title    → "Blog"
layouts.app → [shared] $user → array ["id" => 1, "name" => "John"]
```

## Testing

```bash
composer test
```

Or directly:

```bash
vendor/bin/phpunit
```

## License

MIT
