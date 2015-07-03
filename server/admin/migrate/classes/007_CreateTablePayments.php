<?php

class CreateTablePayments extends Doctrine_Migration_Base
{
    private $_tableName = 'payments';
    private $_fkName1 = 'fk_payments_guest';

    
    
    public function up()
    {
        $this->createTable($this->_tableName, array(
            'id' => array(
                'type' => 'integer',
                'notnull' => true,
                'primary' => true,
                'autoincrement' => true,
            ),
            'guest' => array(
                'type' => 'integer',
                'notnull' => true,
            ),
            'd_create' => array(
                'type' => 'integer',
                'notnull' => true,
            ),
            'amount' => array(
                'type' => 'DECIMAL(7,2)',
                'notnull' => true,
            ),            
            'd_response' => array(
                'type' => 'integer',
                'notnull' => false,
            ),            
            'channel' => array(
                'type' => 'varchar(16)',
                'notnull' => false,
            ),
            'order_id' => array(
                'type' => 'varchar(200)',
                'notnull' => false,
            ),
            
            'status' => array(
                'type' => 'integer',
                'notnull' => false,
            ),             
            'notify' => array(
                'type' => 'text',
                'notnull' => false,
            ),             
            'response' => array(
                'type' => 'text',
                'notnull' => false,
            ),
        ), array('charset'=>'utf8'));
        
        $this->addIndex($this->_tableName,$this->_tableName.'_guest_key',array('fields'=>array('guest')));
        
        
        
        $this->createForeignKey($this->_tableName, $this->_fkName1, array(
             'local'         => 'guest',
             'foreign'       => 'id',
             'foreignTable'  => 'guests',
             'onDelete'      => 'CASCADE',
             'onUpdate'      => 'CASCADE'
        ));

          
    }

    public function down()
    {

        $this->dropForeignKey($this->_tableName, $this->_fkName1);
        $this->dropTable($this->_tableName);
    }
}
