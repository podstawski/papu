<?php

class AlterTableUsersAddSocial extends Doctrine_Migration_Base
{
    private $_tableName = 'users';
    protected $_columnName1 = 'social';
    protected $_columnName2 = 'cover';
    
    public function up()
    {
        $this->addColumn($this->_tableName, $this->_columnName1, 'Text', null, array('notnull' => false ));
        $this->addColumn($this->_tableName, $this->_columnName2, 'Text', null, array('notnull' => false )); 
    }

    public function down()
    {
        $this->removeColumn($this->_tableName, $this->_columnName2);
        $this->removeColumn($this->_tableName, $this->_columnName1);
    }
}
