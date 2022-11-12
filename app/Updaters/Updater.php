<?php

namespace App\Updaters;

interface Updater
{
    public function run();

    public function getName();
}