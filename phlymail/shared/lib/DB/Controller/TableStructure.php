<?php

/**
 * Methods for keeping the DB structure up to date. Used by runonce.php
 *
 * @package phlyLabs ClassCore
 * @package phlyMail Nahariya 4.0+
 * @author  Matthias Sommerfeld
 * @copyright 2002-2015 phlyLabs, Berlin http://phlylabs.de
 * @version 0.0.2 2015-03-27 
 */
class DB_Controller_TableStructure extends DB_Controller {

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Reads the structure of the currently used Database and returns it as an array structure
     * @param  void
     * @return  array  keys: table names, values: arrays with column names as keys and column definitions as values
     * @since  3.1.5
     */
    public function get()
    {
        $qid = $this->query('SHOW TABLE STATUS IN `' . $this->DB['database'] . '` LIKE "' . $this->DB['prefix'] . '_%"');
        $return = array();
        while ($table = $this->assoc($qid)) {
            // I need the plain table name without the prefix and the DB name and stuff
            $tbl = preg_replace('!^' . preg_quote($this->DB['prefix'] . '_', '!') . '!', '', $table['Name']);
            $return[$tbl] = $table['Engine'];
        }
        foreach ($return as $table => $v) {
            $return[$table] = array('engine' => $v, 'fields' => array(), 'index' => array());
            $qid = $this->query('SHOW COLUMNS FROM ' . $this->DB['db_pref'] . $table);
            while ($line = $this->assoc($qid)) {
                $return[$table]['fields'][$line['Field']] = array(
                        'type' => $line['Type'],
                        'null' => ($line['Null'] == 'NO') ? 0 : 1,
                        'key' => $line['Key'] == 'PRI' ? 1 : 0,
                        'default' => ($line['Key'] == 'PRI') ? false : ((is_null($line['Default'])) ? 'NULL' : $line['Default']),
                        'extra' => $line['Extra'] == 'auto_increment' ? 1 : 0
                );
            }
            $qid = $this->query('SHOW INDEX FROM ' . $this->DB['db_pref'] . $table);
            while ($line = $this->assoc($qid)) {
                if ($line['Key_name'] == 'PRIMARY') {
                    continue;
                }
                if ($line['Non_unique'] == 1) {
                    $return[$table]['index'][$line['Key_name']] = $line['Column_name'];
                } elseif (isset($return[$table]['unique'][$line['Key_name']])) {
                    $return[$table]['unique'][$line['Key_name']] .= ',`' . $line['Column_name'] . '`';
                } else {
                    $return[$table]['unique'][$line['Key_name']] = '`' . $line['Column_name'] . '`';
                }
            }
        }
        return $return;
    }

    /**
     * This method updates the DB structure. Takes two arguments, the first is the tables to add, the second the tables
     * to update. Be aware, that dropping either tables or fields is not possible, since this could (and probably would)
     * interfer with the idea of the owner of the licence also owning the data stored in the tables. Dropping unknown
     * or no longer necessary fields would by chance destroy data the client probably still needs
     *
     * @param  array  Tables to add; keys: table names, values: 2 arrays with key == 'fields' for the field definitions (keys
     *   are the field names, values
     * @param  array  keys: table names, values: arrays with column names as keys and column definitions as values, additionally
     *         the flag 'action' tells whether to add a field (value 'add') or alter it (value 'alter')
     * @param  array  list of statements to run (for more complex update tasks like rewriting values, if necessary)
     * @return  bool  true on success, false on failure
     * @since  3.1.5
     */
    public function update($add, $alter, $statement)
    {
        // Add new tables
        foreach ($add as $table => $def) {
            $query = 'CREATE TABLE ' . $this->DB['db_pref'] . $table . ' (';
            $i = 0;
            foreach ($def['fields'] as $field => $fdef) {
                if ($i) {
                    $query .= ', ';
                }
                $query .= '`' . $field . '` ' . $fdef['type'] . ' ' . ($fdef['null'] ? 'NULL' : 'NOT NULL')
                        . $this->updateGetDefaultValue($fdef['default'], $fdef['type'])
                        . ($fdef['key'] ? ' PRIMARY KEY' : '') . ($fdef['extra'] ? ' auto_increment' : '');
                $i++;
            }

            foreach (array('index' => 'INDEX', 'unique' => 'UNIQUE KEY', 'fulltext' => 'FULLTEXT INDEX') as $token => $keyType) {
                if (!empty($def[$token])) {
                    foreach ($def[$token] as $field => $fdef) {
                        if ($i) {
                            $query .= ', ';
                        }
                        if (false === strpos($fdef, '`')) {
                            $fdef = '`' . $fdef . '`';
                        }
                        $query .= ' '.$keyType.' `' . $field . '` (' . $fdef . ') ';
                        $i++;
                    }
                }
            }
            $query .= ') ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_general_ci';
            $this->query($query);
            $e = $this->error();
            if ($e) {
                echo $e . LF . $query . LF . LF;
            }
        }
        // Add new fields if necessary
        foreach ($alter as $table => $def) {
            $query = 'ALTER TABLE ' . $this->DB['db_pref'] . $table . ' ';
            $i = 0;
            foreach ($def['fields'] as $field => $fdef) {
                if ($i) {
                    $query .= ', ';
                }
                $query .= 'ADD `' . $field . '` ' . $fdef['type'] . ' ' . ($fdef['null'] ? 'NULL' : 'NOT NULL').
                        $this->updateGetDefaultValue($fdef['default'], $fdef['type']).
                        ($fdef['key'] ? ' PRIMARY KEY' : '') . ($fdef['extra'] ? ' auto_increment' : '');
                $i++;
            }
            foreach (array('index' => 'INDEX', 'unique' => 'UNIQUE KEY', 'fulltext' => 'FULLTEXT INDEX') as $token => $keyType) {
                if (!empty($def[$token])) {
                    foreach ($def[$token] as $field => $fdef) {
                        if ($i) {
                            $query .= ', ';
                        }
                        if (false === strpos($fdef, '`')) {
                            $fdef = '`' . $fdef . '`';
                        }
                        $query .= 'ADD '.$keyType.' `' . $field . '` (' . $fdef . ') ';
                        $i++;
                    }
                }
            }
            $this->query($query);
            $e = $this->error();
            if ($e) {
                echo $e . LF . $query . LF . LF;
            }
        }
        foreach ($statement as $query) {
            $query = str_replace('{prefix}', $this->DB['db_pref'], $query);
            $this->query($query);
            $e = $this->error();
            if ($e) {
                echo $e . LF . $query . LF . LF;
            }
        }
    }

    public function deepSync($structure, $script = array(), $sqls = array())
    {
        $orig_struct = $this->get();
        $add = array();
        $alter = array();

        foreach ($structure as $tbl => $def) {
            // Check tables
            if (!isset($orig_struct[$tbl])) {
                $add[$tbl] = $def;
            } else {
                // Check fields
                foreach ($def['fields'] as $field => $fdef) {
                    // ADD nonexisting
                    if (!isset($orig_struct[$tbl]['fields'][$field])) {
                        $alter[$tbl]['fields'][$field] = $fdef;
                        // ALTER those of the wrong type - obey scripting and run individual commands
                    } elseif ($orig_struct[$tbl]['fields'][$field]['type'] != $structure[$tbl]['fields'][$field]['type'] || $orig_struct[$tbl]['fields'][$field]['default'] != $structure[$tbl]['fields'][$field]['default'] || $orig_struct[$tbl]['fields'][$field]['null'] != $structure[$tbl]['fields'][$field]['null'] || $orig_struct[$tbl]['fields'][$field]['extra'] != $structure[$tbl]['fields'][$field]['extra']) {
                        if (isset($script[$tbl . '.' . $field][$orig_struct[$tbl]['fields'][$field]['type']]['sql'])) {
                            $sql = $script[$tbl . '.' . $field][$orig_struct[$tbl]['fields'][$field]['type']]['sql'];
                            unset($script[$tbl . '.' . $field][$orig_struct[$tbl]['fields'][$field]['type']]['sql']); // Processed
                            if (is_array($sql)) {
                                foreach ($sql as $line) {
                                    $sqls[] = $line;
                                }
                            } else {
                                $sqls[] = $sql;
                            }
                        } else { // If the types differ, but no specific SQL is given, we can safely CHANGE the column definition
                            if ($fdef['key']) {
                                $sqls[] = 'ALTER TABLE {prefix}' . $tbl . ' DROP PRIMARY KEY, '
                                        . ' MODIFY `' . $field . '` ' . $fdef['type'] . ' ' . ($fdef['null'] ? 'NULL' : 'NOT NULL')
                                        . ' PRIMARY KEY' . ($fdef['extra'] ? ' auto_increment' : '');
                            } else {
                                $sqls[] = 'ALTER TABLE {prefix}' . $tbl
                                        . ' MODIFY `' . $field . '` ' . $fdef['type'] . ' ' . ($fdef['null'] ? 'NULL' : 'NOT NULL')
                                        . $this->updateGetDefaultValue($fdef['default'], $fdef['type']);
                            }
                        }
                    }
                }
                // Check indexes
                if (!empty($def['index'])) {
                    foreach ($def['index'] as $field => $fdef) {
                        if (isset($orig_struct[$tbl]['unique'][$field])) {
                            if (false === strpos($fdef, '`')) {
                                $fdef = '`' . $fdef . '`';
                            }
                            $sqls[] = 'ALTER TABLE {prefix}' . $tbl . ' DROP KEY `' . $field . '`, ADD INDEX `' . $field . '` (' . $fdef . ')';
                        } elseif (!isset($orig_struct[$tbl]['index'][$field])) {
                            $alter[$tbl]['index'][$field] = $fdef;
                        }
                    }
                }
                // Check unique
                if (!empty($def['unique'])) {
                    foreach ($def['unique'] as $field => $fdef) {
                        if (isset($orig_struct[$tbl]['index'][$field])) {
                            if (false === strpos($fdef, '`')) {
                                $fdef = '`' . $fdef . '`';
                            }
                            $sqls[] = 'ALTER TABLE {prefix}' . $tbl . ' DROP INDEX `' . $field . '`, ADD UNIQUE KEY `' . $field . '` (' . $fdef . ')';
                        } elseif (!isset($orig_struct[$tbl]['unique'][$field])) {
                            $alter[$tbl]['unique'][$field] = $fdef;
                        }
                    }
                }
                // Fulltext indexes
                if (!empty($def['fulltext'])) {
                    foreach ($def['fulltext'] as $field => $fdef) {
                        if (isset($orig_struct[$tbl]['index'][$field])) {
                            if (false === strpos($fdef, '`')) {
                                $fdef = '`' . $fdef . '`';
                            }
                            $sqls[] = 'ALTER TABLE {prefix}' . $tbl . ' DROP INDEX `' . $field . '`, ADD FULLTEXT INDEX `' . $field . '` (' . $fdef . ')';
                        } elseif (!isset($orig_struct[$tbl]['fulltext'][$field])) {
                            $alter[$tbl]['fulltext'][$field] = $fdef;
                        }
                    }
                }

                // If anything set, define the rest, too
                if (!empty($alter[$tbl])) {
                    if (!isset($alter[$tbl]['index'])) {
                        $alter[$tbl]['index'] = array();
                    }
                    if (!isset($alter[$tbl]['fields'])) {
                        $alter[$tbl]['fields'] = array();
                    }
                    if (!isset($alter[$tbl]['unique'])) {
                        $alter[$tbl]['unique'] = array();
                    }
                }
            }
        }
        // Some SQL script entries don't get fired on newer table definitions, so they need to get processed now
        foreach ($orig_struct as $tbl => $def) {
            // Check fields
            foreach ($def['fields'] as $field => $fdef) {
                if (!isset($structure[$tbl]['fields'][$field]) && isset($script[$tbl . '.' . $field][$orig_struct[$tbl]['fields'][$field]['type']]['sql'])) {
                    $sql = $script[$tbl . '.' . $field][$orig_struct[$tbl]['fields'][$field]['type']]['sql'];
                    unset($script[$tbl . '.' . $field][$orig_struct[$tbl]['fields'][$field]['type']]['sql']); // Processed
                    if (is_array($sql)) {
                        foreach ($sql as $line) {
                            $sqls[] = $line;
                        }
                    } else {
                        $sqls[] = $sql;
                    }
                }
            }
        }

        // And go
        $this->update($add, $alter, $sqls);

        return $orig_struct;
    }

    /**
     * Takes the setting for a default value and returns the right SQL portion for it
     *
     * @param mixed $val
     * @return string
     * @since 4.0.5
     */
    protected function updateGetDefaultValue($val, $type)
    {
        if (in_array($type, array('blob', 'mediumblob', 'longblob', 'tinyblob', 'text', 'mediumtext', 'longtext', 'tinytext'))) {
            return '';
        }
        return (false !== $val ? ($val === 'NULL' ? ' DEFAULT NULL' : (is_int($val) ? ' DEFAULT ' . $val : ' DEFAULT "' . $val . '"')) : '');
    }

}