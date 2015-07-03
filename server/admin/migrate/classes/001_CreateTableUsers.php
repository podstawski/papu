<?php

class CreateTableUsers extends Doctrine_Migration_Base
{
    private $_tableName = 'users';

    public function up()
    {
        
        $this->createTable($this->_tableName, array(
            'id' => array(
                'type' => 'integer',
                'notnull' => true,
                'primary' => true,
                'autoincrement' => true,
            ),
            'email' => array(
                'type' => 'character varying(255)',
                'notnull' => true,
            ),
            'md5hash' => array(
                'type' => 'character varying(32)',
                'notnull' => true,
            ),
            'password' => array(
                'type' => 'character varying(32)',
                'notnull' => false,
            ),
            'firstname' => array(
                'type' => 'character varying(200)',
                'notnull' => true,
            ),
            'lastname' => array(
                'type' => 'character varying(200)',
                'notnull' => true,
            ),
            'url' => array(
                'type' => 'character varying(255)',
                'notnull' => true,
            ),
            'lang' => array(
                'type' => 'character varying(2)',
                'notnull' => false,
            ),
            'photo' => array(
                'type' => 'text',
                'notnull' => false,
            ),
            'country' => array(
                'type' => 'varchar(3)',
                'notnull' => true,
            ),
            'city' => array(
                'type' => 'varchar(200)',
                'notnull' => false,
            ),
            'address' => array(
                'type' => 'varchar(200)',
                'notnull' => false,
            ),
            'postal' => array(
                'type' => 'varchar(10)',
                'notnull' => false,
            ),
            'payment' => array(
                'type' => 'varchar(200)',
                'notnull' => false,
            ),
            'phone' => array(
                'type' => 'varchar(200)',
                'notnull' => false,
            ),
            'lat' => array(
                'type' => 'DECIMAL(18,9)',
                'notnull' => false,
            ),
            'lng' => array(
                'type' => 'DECIMAL(18,9)',
                'notnull' => false,
            ),
            'about' => array(
                'type' => 'text',
                'notnull' => false,
            ),

        ), array('charset'=>'utf8'));
        
        $this->addIndex($this->_tableName,$this->_tableName.'_email_key',array('type'=>'unique','fields'=>array('email')));
        $this->addIndex($this->_tableName,$this->_tableName.'_md5hash_key',array('type'=>'unique','fields'=>array('md5hash')));
        $this->addIndex($this->_tableName,$this->_tableName.'_url_key',array('type'=>'unique','fields'=>array('url')));
        $this->addIndex($this->_tableName,$this->_tableName.'_city_key',array('fields'=>array('country','city')));
        $this->addIndex($this->_tableName,$this->_tableName.'_geo_key',array('fields'=>array('lat','lng')));
        
    }

    public function down()
    {
        $this->dropTable($this->_tableName);
    }
}
