<?php

class AlterTablesAddIcal extends Doctrine_Migration_Base
{
    private $_tableName1 = 'users';
    private $_tableName2 = 'events';
    protected $_columnName1 = 'ical';
    protected $_columnName2 = 'ical_id';
    
    
    
    public function up()
    {
        $this->addColumn($this->_tableName1, $this->_columnName1, 'Varchar(200)', null, array('notnull' => false ));
        $this->addColumn($this->_tableName2, $this->_columnName2, 'Varchar(200)', null, array('notnull' => false ));
        $this->addIndex($this->_tableName2,$this->_tableName2.'_ical_key',array('fields'=>array($this->_columnName2)));
          
    }

    public function down()
    {
        $this->removeColumn($this->_tableName2, $this->_columnName2);
        $this->removeColumn($this->_tableName1, $this->_columnName1);
    }
}
