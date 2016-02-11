<?php

namespace Geneanet;

/* geany_encoding=ISO-8859-15 */

define('INDIVIDUALS', 'individuals');
define('FAMILIES', 'families');
define('NOTES', 'notes');
define('SOURCES', 'sources');
// define('REPOS',       'repos');
// define('OBJECT',      'object');


/*
 * this dictionnary help creating uniq UID for persons (INDividuals), FAMilies, NOTes, and sources (SRC).
 */

class GedcomDictionary
{

    protected $dict = array(
        INDIVIDUALS  => array(),
        FAMILIES     => array(),
        NOTES        => array(),
        SOURCES      => array()
    );

    protected $keys = array(
        INDIVIDUALS  => array(),
        FAMILIES     => array(),
        NOTES        => array(),
        SOURCES      => array()
    );
    
    // prefix for "id" - see $this->make_prefix()
    protected $prefix = array(
        INDIVIDUALS  => 'IND',
        FAMILIES     => 'FAM',
        NOTES        => 'NOT',
        SOURCES      => 'SRC'
    );

    public function __construct()
    {
    }
    
    public function add($type, $person, $key)
    {
        
        // we can insert only ONE value with $key (this is a uniq primary key)
        if (isset($this->keys[$type][$key])) {
            return $this->keys[$type][$key];
        }

        $this->checkType($type);
        $id = count($this->dict[$type])+1;
        $id = $this->makeId($type, $id);
        
        $this->dict[$type][$id] = $person;
        $this->keys[$type][$key] = $id;

        return $id;
    }

    public function get($type, $id)
    {
        $this->checkType($type);
        if ($this->_isset($type, $id)) {
            return $this->dict[$type][$id];
        }
        return false;
    }

    public function getall($type)
    {
        return $this->dict[$type];
    }

    public function _isset($type, $id)
    {
        return isset($this->dict[$type][$id]);
    }

    public function search($person)
    {
        throw new Exception("to be done");
    }
    
    protected function checkType($type)
    {
        switch($type){
            case INDIVIDUALS:
            case FAMILIES:
            case NOTES:
            case SOURCES:
                break;
            default:
                throw new Exception("unknow type $type");
        }
    }
    
    protected function makeId($type, $id)
    {
        return sprintf("%s%s", $this->prefix[$type], $id);
    }
}
