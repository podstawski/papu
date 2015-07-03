<?php

class AlterTableEventsAddUnlistedFullhouse extends Doctrine_Migration_Base
{
    private $_tableName = 'events';
    protected $_columnName1 = 'unlisted';
    protected $_columnName2 = 'fullhouse';
    
    
    public function up()
    {
        $this->addColumn($this->_tableName, $this->_columnName1, 'Integer', null, array('notnull' => false ));
        $this->addColumn($this->_tableName, $this->_columnName2, 'Integer', null, array('notnull' => false ));

    }

    public function down()
    {
        $this->removeColumn($this->_tableName, $this->_columnName2);
        $this->removeColumn($this->_tableName, $this->_columnName1);
    }
}
