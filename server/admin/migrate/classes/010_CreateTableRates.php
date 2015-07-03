<?php

class CreateTableRates extends Doctrine_Migration_Base
{
    private $_tableName = 'rates';
    private $_fkName1 = 'fk_rates_user';
    private $_fkName2 = 'fk_rates_event';
    private $_fkName3 = 'fk_rates_host';
    
    
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
            'host' => array(
                'type' => 'integer',
                'notnull' => true,
            ),            
            'd_create' => array(
                'type' => 'integer',
                'notnull' => true,
            ),
            'food' => array(
                'type' => 'DECIMAL(3,1)',
                'notnull' => false,
            ),
            'cleanliness' => array(
                'type' => 'DECIMAL(3,1)',
                'notnull' => false,
            ),
            'atmosphere' => array(
                'type' => 'DECIMAL(3,1)',
                'notnull' => false,
            ),
            'overall' => array(
                'type' => 'DECIMAL(3,1)',
                'notnull' => false,
            ),
            'title' => array(
                'type' => 'text',
                'notnull' => false,
            ),

            'description' => array(
                'type' => 'text',
                'notnull' => false,
            ),

        ), array('charset'=>'utf8'));
        
        $this->addIndex($this->_tableName,$this->_tableName.'_user_fk',array('fields'=>array('user','event','host')));
        
        
        
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

        $this->createForeignKey($this->_tableName, $this->_fkName3, array(
             'local'         => 'host',
             'foreign'       => 'id',
             'foreignTable'  => 'users',
             'onDelete'      => 'CASCADE',
             'onUpdate'      => 'CASCADE'
        ));

          
    }

    public function down()
    {
        $this->dropForeignKey($this->_tableName, $this->_fkName3);
        $this->dropForeignKey($this->_tableName, $this->_fkName2);
        $this->dropForeignKey($this->_tableName, $this->_fkName1);
        $this->dropTable($this->_tableName);
    }
}
