<?php

class CreateTableImages extends Doctrine_Migration_Base
{
    private $_tableName = 'images';
    private $_fkName1 = 'fk_images_user';

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
            'src' => array(
                'type' => 'varchar(255)',
                'notnull' => true,
            ),            
            'url' => array(
                'type' => 'varchar(255)',
                'notnull' => false,
            ),
            'thumbnail' => array(
                'type' => 'varchar(255)',
                'notnull' => false,
            ),
            'square' => array(
                'type' => 'varchar(255)',
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
            'd_taken' => array(
                'type' => 'Integer',
                'notnull' => false,
            ),
            'd_uploaded' => array(
                'type' => 'Integer',
                'notnull' => false,
            ),            
        ), array('charset'=>'utf8'));
        
        $this->addIndex($this->_tableName,$this->_tableName.'_user_key',array('fields'=>array('user')));
        
        
        $this->createForeignKey($this->_tableName, $this->_fkName1, array(
             'local'         => 'user',
             'foreign'       => 'id',
             'foreignTable'  => 'users',
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
