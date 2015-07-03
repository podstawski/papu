<?php

class AlterTableEventsAddImg extends Doctrine_Migration_Base
{
    private $_tableName = 'events';
    private $_fkName1 = 'fk_events_img';
    protected $_columnName1 = 'img';
    
    
    public function up()
    {
        $this->addColumn($this->_tableName, $this->_columnName1, 'Integer', null, array('notnull' => false ));
        
        $this->createForeignKey($this->_tableName, $this->_fkName1, array(
             'local'         => $this->_columnName1,
             'foreign'       => 'id',
             'foreignTable'  => 'images',
             'onDelete'      => 'SET NULL',
             'onUpdate'      => 'CASCADE'
        ));

          
    }

    public function down()
    {
        $this->dropForeignKey($this->_tableName, $this->_fkName1);
        $this->removeColumn($this->_tableName, $this->_columnName1);
    }
}
