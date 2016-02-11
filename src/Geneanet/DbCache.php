<?php

namespace Geneanet;

/**
 *  PRAGMA auto_vacuum = FULL;
 *  DROP TABLE 'cache';
 *  CREATE TABLE 'cache' (
 *      'key'      VARCHAR,
 *      'size'     INT,
 *      'time'     INT,
 *      'category' VARCHAR,
 *      'extra'    VARCHAR,
 *      'content'  VARCHAR
 *  );
 *  CREATE INDEX cache_key ON 'cache'('key');
 *  CREATE INDEX cache_time ON 'cache'('time');
 *  CREATE INDEX cache_category ON 'cache'('category');
 */
class DbCache
{

    protected $db;
    protected $table_name = 'cache';

    public function __construct($db_file)
    {
        $this->db = new DbSqlite($db_file);
    }

    public function getFromCache($key, $age)
    {
        $time = time() - $age;

        if ($age === null) {
            $where = '';
        } else {
            $where = sprintf(' AND time>=%d', $time);
        }

        $sql = sprintf("SELECT count(key) FROM %s WHERE key='%s' %s", $this->table_name, $key, $where);
        if ($this->db->getOne($sql) == 0) {
            return false;
        }

        $sql = sprintf("SELECT content FROM %s WHERE key='%s' %s", $this->table_name, $key, $where);
        $content = $this->db->getOne($sql);
        return unserialize($content);
    }

    public function delete($key, $category = 'default')
    {
        $sql = sprintf(
            "DELETE FROM '%s' WHERE key='%s' AND category='%s';",
            $this->table_name,
            $this->db->escape($key),
            $this->db->escape($category)
        );

        $this->db->exec($sql);
    }

    public function cleanup($age = 0, $category = 'default')
    {
        $time = time() - $age;
        $sql = sprintf(
            "DELETE FROM '%s' WHERE time<='%s' AND category='%s';",
            $this->table_name,
            $time,
            $this->db->escape($category)
        );

        $this->db->exec($sql);
    }

    public function purgeByCalid($calid)
    {
        $sql = sprintf(
            "DELETE FROM '%s' WHERE extra='%s';",
            $this->table_name,
            $this->db->escape($calid)
        );

        $this->db->exec($sql);
    }

    public function updateCache($key, $content, $category = 'default', $extra = '')
    {
        $this->delete($key, $category);
        $this->insertIntoCache($key, $content, $category, $extra);
    }

    public function insertIntoCache($key, $content, $category = 'default', $extra = '')
    {

        $content = serialize($content);

        $sql = sprintf(
            "INSERT INTO '%s' ('key', 'size', 'time', 'category', 'extra', 'content') VALUES ( '%s', %d, %d, '%s', '%s', '%s');",
            $this->table_name,
            $this->db->escape($key),
            strlen($content),
            time(),
            $this->db->escape($category),
            $this->db->escape($extra),
            $this->db->escape($content)
        );

        $this->db->exec($sql);
        return false;
    }

    public function count()
    {
        $sql = sprintf(
            "SELECT count(*) FROM %s",
            $this->table_name
        );

        return $this->db->getOne($sql);
    }


    public function age($key)
    {
        $sql = sprintf(
            "SELECT time FROM %s WHERE key='%s'",
            $this->table_name,
            $this->db->escape($key)
        );

        return time() - $this->db->getOne($sql);
    }

    public function ageMn($key)
    {
        return intval($this->age($key) / 60);
    }

    public function __toText()
    {
        $sql = sprintf(
            "SELECT * FROM %s",
            $this->table_name
        );

        return $this->db->getArray($sql);
    }
}
/*

    $cache = new DbCache('var/cache.sqlite');
    
    # $cache->insertIntoCache('azerty', 'some content');
    $cache->updateCache('azerty', 'some content ...');
    $cache->cleanup($age=60*10);
    
    # $content = $cache->getFromCache('azerty', 2*60);
    # print_r($content);
    
    printf("count : %s\n", $cache->count());
    print_r( $cache->__toText());

*/
