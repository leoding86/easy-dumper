<?php

interface ISaveable extends \IEvents
{
    public function setSaveDir($save_dir);
    public function download();
    public function save($service);
}