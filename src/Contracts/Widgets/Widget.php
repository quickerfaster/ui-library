<?php

namespace QuickerFaster\UILibrary\Contracts\Widgets;

interface Widget
{
    public function __construct(array $definition);
    public function setData(): void;
    public function render(): string;
    public function getTitle(): ?string;
    public function getWidth(): int;
}