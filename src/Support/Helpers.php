<?php

use Iamshehzada\ActionConfirmation\Builders\ConfirmActionBuilder;

if (! function_exists('confirm')) {
    function confirm(): ConfirmActionBuilder
    {
        return app(ConfirmActionBuilder::class)->fresh();
    }
}
