<?php

namespace Geneanet;

/* geany_encoding=ISO-8859-15 */


/*
 * this dictionnary help creating uniq UID for persons (INDividuals), FAMilies, NOTes, and sources (SRC).
 */

class GedcomDictionary
{
    const INDIVIDUALS = 'individuals';
    const FAMILIES    = 'families';
    const NOTES       = 'notes';
    const SOURCES     = 'sources';
    const REPOS       = 'repos';

    protected $dict = array(
        self::INDIVIDUALS  => array(),
        self::FAMILIES     => array(),
        self::NOTES        => array(),
        self::SOURCES      => array()
    );

    protected $keys = array(
        self::INDIVIDUALS  => array(),
        self::FAMILIES     => array(),
        self::NOTES        => array(),
        self::SOURCES      => array()
    );
    
    // prefix for "id" - see $this->make_prefix()
    protected $prefix = array(
        self::INDIVIDUALS  => 'IND',
        self::FAMILIES     => 'FAM',
        self::NOTES        => 'NOT',
        self::SOURCES      => 'SRC'
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
            case static::INDIVIDUALS:
            case static::FAMILIES:
            case static::NOTES:
            case static::SOURCES:
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
