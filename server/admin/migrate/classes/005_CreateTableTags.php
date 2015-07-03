<?php

class CreateTableTags extends Doctrine_Migration_Base
{
    private $_tableName = 'tags';
    private $_fkName1 = 'fk_tags_user';
    private $_fkName2 = 'fk_tags_event';

    public function up()
    {
        $this->createTable($this->_tableName, array(
            'id' => array(
                'type' => 'integer',
                'notnull' => true,
                'primary' => true,
                'autoincrement' => true,
            ),
            'name' => array(
                'type' => 'varchar(16)',
                'notnull' => true,
            ),            
            'user' => array(
                'type' => 'integer',
                'notnull' => false,
            ),
            'event' => array(
                'type' => 'integer',
                'notnull' => false,
            ),
            
        ), array('charset'=>'utf8'));
        
        $this->addIndex($this->_tableName,$this->_tableName.'_user_key',array('fields'=>array('user')));
        
        $this->addIndex($this->_tableName,$this->_tableName.'_event_key',array('fields'=>array('event')));
        
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
