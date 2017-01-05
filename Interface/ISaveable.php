<?php

interface ISaveable extends \IEvents
{
    public function download($save_path);
    public function save($service);
}