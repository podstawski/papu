<?php

class AlterTableEventsAddVip extends Doctrine_Migration_Base
{
    private $_tableName = 'events';
    protected $_columnName1 = '_vip';

    
    public function up()
    {
        $this->addColumn($this->_tableName, $this->_columnName1, 'Integer', null, array('notnull' => false ));
        $this->addIndex($this->_tableName,$this->_tableName.'_vip_key',array('fields'=>array($this->_columnName1)));
    }

    public function down()
    {
        $this->removeIndex($this->_tableName,$this->_tableName.'_vip_key');
        $this->removeColumn($this->_tableName, $this->_columnName1);
    }
}
