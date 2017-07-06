<?php

namespace Xiaoler\Blade\Contracts;

interface Renderable
{
    /**
     * Get the evaluated contents of the object.
     *
     * @return string
     */
    public function render();
}
