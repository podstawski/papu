<?php

class AlterTableEventsAddRestaurant extends Doctrine_Migration_Base
{
    private $_tableName = 'events';
    protected $_columnName1 = 'restaurant';

    
    public function up()
    {
        $this->addColumn($this->_tableName, $this->_columnName1, 'Integer', null, array('notnull' => false ));
    }

    public function down()
    {
        $this->removeColumn($this->_tableName, $this->_columnName1);
    }
}
