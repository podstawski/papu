<?php

class AlterTableUsersAddBirthyear extends Doctrine_Migration_Base
{
    private $_tableName = 'users';
    protected $_columnName1 = 'birthyear';

    
    
    public function up()
    {
        $this->addColumn($this->_tableName, $this->_columnName1, 'Integer', null, array('notnull' => false ));         
    }

    public function down()
    {
        $this->removeColumn($this->_tableName, $this->_columnName1);
    }
}
