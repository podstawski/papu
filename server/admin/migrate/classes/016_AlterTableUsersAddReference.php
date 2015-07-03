<?php

class AlterTableUsersAddReference extends Doctrine_Migration_Base
{
    private $_tableName = 'users';
    protected $_columnName1 = 'ref_login';
    protected $_columnName2 = 'ref_site';
    protected $_columnName3 = 'ref_user';
    
    
    public function up()
    {
        $this->addColumn($this->_tableName, $this->_columnName1, 'Varchar(32)', null, array('notnull' => false ));
        $this->addColumn($this->_tableName, $this->_columnName2, 'Varchar(32)', null, array('notnull' => false ));
        $this->addColumn($this->_tableName, $this->_columnName3, 'Integer', null, array('notnull' => false ));         
    }

    public function down()
    {
        $this->removeColumn($this->_tableName, $this->_columnName3);
        $this->removeColumn($this->_tableName, $this->_columnName2);
        $this->removeColumn($this->_tableName, $this->_columnName1);
    }
}
