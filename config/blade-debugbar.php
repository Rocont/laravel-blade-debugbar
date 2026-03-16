<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Group variables by view
    |--------------------------------------------------------------------------
    |
    | When enabled, variables will be grouped by the Blade view name.
    | When disabled, all variables are shown in a flat list.
    |
    */
    'group_by_view' => false,

    /*
    |--------------------------------------------------------------------------
    | Excluded variables
    |--------------------------------------------------------------------------
    |
    | Additional variables to exclude from the debugbar tab.
    | System variables (__env, __data, __path, app, errors, etc.)
    | are always excluded automatically.
    |
    */
    'excluded_variables' => [],

    /*
    |--------------------------------------------------------------------------
    | Shared variables mode
    |--------------------------------------------------------------------------
    |
    | How to handle variables added via View::share() or service providers:
    | - "mark": show them with a [shared] prefix
    | - "hide": exclude them completely
    | - "show": show them without any distinction
    |
    */
    'shared_mode' => 'mark',

];