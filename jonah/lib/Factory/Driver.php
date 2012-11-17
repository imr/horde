<?php
/**
 * Jonah_Driver factory.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @author Ben Klang <ben@alkaloid.net>
 * @package Jonah
 */
class Jonah_Factory_Driver extends Horde_Core_Factory_Injector
{
    /**
     * Instances.
     *
     * @var array
     */
    private $_instances = array();

    /**
     * Return the driver instance.
     *
     * @param string $driver  The concrete driver to return
     * @param array $params   An array of additional driver parameters.
     *
     * @return Jonah_Driver
     * @throws Jonah_Exception
     */
    public function create($name = '')
    {
        $driver = Horde_String::ucfirst($GLOBALS['conf']['news']['storage']['driver']);
        $driver = basename($driver);
        $params = Horde::getDriverConfig(array('news', 'storage'), $driver);

        if (isset($this->_instances[$name])) {
            return $this->_instances[$name];
        }

        $class = 'Jonah_Driver_' . $driver;
        if (class_exists($class)) {
            $object = new $class($name, $params);
            $this->_instances[$name] = $object;
        } else {
            throw new Jonah_Exception(sprintf(_("No such backend \"%s\" found"), $driver));
        }

        return $this->_instances[$name];
    }
}
