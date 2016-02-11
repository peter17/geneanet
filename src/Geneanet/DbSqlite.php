<?php

namespace Geneanet;

class DbSqlite
{
    public $filename;
    public $db;

    public function __construct($filename, $sql_file = '')
    {
        if (!file_exists($filename)) {
            $need_to_create = true;
        } else {
            $need_to_create = false;
        }
        $this->filename = $filename;
        if (!class_exists('SQLite3')) {
            trigger_error("You need to install sqlite3 php module");
        }
        $this->db = new SQLite3($filename);
        if ($this->db == false) {
            $this->error('Sqlite::open()');
        }
        
        if ($need_to_create && $sql_file != '') {
            $this->create($sql_file);
        }
          
        # speedup insertions
        # $this->query('PRAGMA default_synchronous = OFF');
    }

    public function query($sql)
    {
        $res = $this->db->query($sql);
        if ($res == false) {
            $this->error('Sqlite::query');
        }
        return $res;
    }

    /*
    public function multi_query($sql_list)
    {
        throw new Exception("not implemented");
    }
     */


    public function exec($sql)
    {
        $res = $this->db->exec($sql);
        if ($res == false) {
            $this->error('Sqlite::exec');
        }
        return $res;
    }


    public function close()
    {
        $this->db->close();
    }
    /*
    public function get_list($sql)
    {
        throw new Exception("not implemented");

    }
     */

    public function getArray($sql)
    {
        $res = $this->query($sql);
        # print_r($res);
        return  $res->fetchArray(SQLITE3_ASSOC);
    }

    public function getOne($sql)
    {
        $res = $this->query($sql);
        # print_r($res);
        $data =  $res->fetchArray(SQLITE3_NUM);
        # print_r($result);
        return $data[0];
    }

    /*
    public function list_tables()
    {
        throw new Exception("not implemented");
    }
    */

    public function create($file)
    {
        $content = file_get_contents($file);
        $lines = split("\n", $content);
        foreach ($lines as $line) {
            $line = preg_replace("/^#.*/", '', $line);
            $line = trim($line);
        }

        $content = join("\n", $lines);
        $lines = split(';', $content);
        
        foreach ($lines as $line) {
            $line = preg_replace("/^#.*/", '', $line);
            $line = trim($line);
            $len = strlen($line);
            if ($len > 0 && $line != ';') {
                $this->query($line);
            }
        }
    }

    /*
    public function fetch_array($res)
    {
        throw new Exception("not implemented");
    }
    */

    /*
    public function field_update_by_key($db, $key, $id, $field, $value, $type = 'string')
    {
        throw new Exception("not implemented");
    }
     */

    public function error($str)
    {
        error_log($str);
        throw new Exception("not implemented");
    }

    public function escape($str)
    {
        return $this->db->escapeString($str);
    }
}
