<?php

class AlterTablesAddAgreementMessage extends Doctrine_Migration_Base
{
    private $_tableName1 = 'users';
    private $_tableName2 = 'guests';
    protected $_columnName1 = 'd_host_agreement';
    protected $_columnName2 = 'd_guest_agreement';
    protected $_columnName3 = 'message';
    
    
    
    public function up()
    {
        $this->addColumn($this->_tableName1, $this->_columnName1, 'Integer', null, array('notnull' => false ));
        $this->addColumn($this->_tableName1, $this->_columnName2, 'Integer', null, array('notnull' => false ));
        $this->addColumn($this->_tableName2, $this->_columnName3, 'Text', null, array('notnull' => false ));
    }

    public function down()
    {
        $this->removeColumn($this->_tableName2, $this->_columnName3);
        $this->removeColumn($this->_tableName1, $this->_columnName2);
        $this->removeColumn($this->_tableName1, $this->_columnName1);
    }
}
