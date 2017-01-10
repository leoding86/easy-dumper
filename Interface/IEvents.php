<?php

interface IEvents
{
    public function registerHook($event, $hook);
    public function dispatch($event, $arguments = []);
}