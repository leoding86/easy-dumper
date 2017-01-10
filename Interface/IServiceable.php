<?php

interface IServiceable
{
    public function parseArgs($args);

    public function getTaskUniqueId();

    public function start();
}