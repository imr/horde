<?php

class Jonah_Factory_Shares extends Horde_Core_Factory_Injector
{
    public function create(Horde_Injector $injector)
    {
        return $injector->getInstance('Horde_Core_Factory_Share')->create();
    }
}
