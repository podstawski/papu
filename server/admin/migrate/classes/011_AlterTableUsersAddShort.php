<?php

class AlterTableUsersAddShort extends Doctrine_Migration_Base
{
    private $_tableName = 'users';
    protected $_columnName1 = 'title';
    protected $_columnName2 = 'gender';
    
    public function up()
    {
        $this->addColumn($this->_tableName, $this->_columnName1, 'Text', null, array('notnull' => false ));
        $this->addColumn($this->_tableName, $this->_columnName2, 'Varchar(1)', null, array('notnull' => false )); 
    }

    public function down()
    {
        $this->removeColumn($this->_tableName, $this->_columnName2);
        $this->removeColumn($this->_tableName, $this->_columnName1);
    }
}
