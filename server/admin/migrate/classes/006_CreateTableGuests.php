<?php

class CreateTableGuests extends Doctrine_Migration_Base
{
    private $_tableName = 'guests';
    private $_fkName1 = 'fk_guests_user';
    private $_fkName2 = 'fk_guests_event';
    
    
    public function up()
    {
        $this->createTable($this->_tableName, array(
            'id' => array(
                'type' => 'integer',
                'notnull' => true,
                'primary' => true,
                'autoincrement' => true,
            ),
            'user' => array(
                'type' => 'integer',
                'notnull' => true,
            ),
            'event' => array(
                'type' => 'integer',
                'notnull' => true,
            ),
            'd_create' => array(
                'type' => 'integer',
                'notnull' => true,
            ),
            'persons' => array(
                'type' => 'integer',
                'notnull' => true,
            ),            
            'd_payment' => array(
                'type' => 'integer',
                'notnull' => false,
            ),            
            'd_cancel' => array(
                'type' => 'integer',
                'notnull' => false,
            ),
            'canceler' => array(
                'type' => 'integer',
                'notnull' => false,
            ),                 
        ), array('charset'=>'utf8'));
        
        $this->addIndex($this->_tableName,$this->_tableName.'_user_key',array('fields'=>array('user')));
        $this->addIndex($this->_tableName,$this->_tableName.'_event_key',array('fields'=>array('event')));
        $this->addIndex($this->_tableName,$this->_tableName.'_fk_key',array('fields'=>array('user','event')));

        
        
        $this->createForeignKey($this->_tableName, $this->_fkName1, array(
             'local'         => 'user',
             'foreign'       => 'id',
             'foreignTable'  => 'users',
             'onDelete'      => 'CASCADE',
             'onUpdate'      => 'CASCADE'
        ));

        $this->createForeignKey($this->_tableName, $this->_fkName2, array(
             'local'         => 'event',
             'foreign'       => 'id',
             'foreignTable'  => 'events',
             'onDelete'      => 'CASCADE',
             'onUpdate'      => 'CASCADE'
        ));

          
    }

    public function down()
    {
        $this->dropForeignKey($this->_tableName, $this->_fkName2);
        $this->dropForeignKey($this->_tableName, $this->_fkName1);
        $this->dropTable($this->_tableName);
    }
}
