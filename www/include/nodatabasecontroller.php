<?php
class NoDatabaseController{
    public function __set($name, $value)
    {
        debug('Error: Unable to set DBController property, '.$name.'! There is currently no database access.');
		return null;
    }

    public function __get($name)
    {
         debug('Error: Unable to get DBController property, '.$name.'! There is currently no database access.');
		 return null;
    }

    /**  As of PHP 5.1.0  */
    public function __isset($name)
    {
         debug('Error: Unable to check isset of DBController property, '.$name.'! There is currently no database access.');
		 return false;
    }

    /**  As of PHP 5.1.0  */
    public function __unset($name)
    {
         debug('Error: Unable to unset DBController property, '.$name.'! There is currently no database access.');
		 return null;
    }
	
	public function __call($name, $arguments)
    {
		debug('Error: Unable to call DBController function, '.$name.'()! There is currently no database access.');
		 return false;
    }
}
