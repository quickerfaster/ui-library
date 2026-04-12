<?php

namespace QuickerFaster\UILibrary\Traits\FieldTypes;

use Illuminate\Support\Facades\View;

trait HasBladeRendering
{
    protected function renderBlade(string $view, array $data = []): string
    {
        return View::make($view, $data)->render();
    }
}
