<?php
/**
 * The db_sqlite class provides methods and functions for a sqlite database.
 *
 * PHP Version 5.2
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * @category  phpMyFAQ
 * @package   PMF_DB
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Johannes Schlüter <johannes@php.net>
 * @since     2005-06-27
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @copyright 2005-2010 phpMyFAQ Team
 */

/**
 * PMF_DB_Sqlite
 * 
 * @category  phpMyFAQ
 * @package   PMF_DB
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Johannes Schlüter <johannes@php.net>
 * @since     2005-06-27
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @copyright 2005-2010 phpMyFAQ Team
 */
class PMF_DB_Sqlite implements PMF_DB_Driver
{
    /**
     * The connection object
     *
     * @var   mixed
     * @see   connect(), query(), dbclose()
     */
    private $conn = false;

    /**
     * The query log string
     *
     * @var   string
     * @see   query()
     */
    private $sqllog = '';

    /**
     * Tables
     *
     * @var     array
     */
    public $tableNames = array();

    /**
     * Connects to the database.
     *
     * @param   string
     * @return  boolean
     */
    public function connect($host, $user = false, $passwd = false, $db = false)
    {
        $this->conn = sqlite_open($host, 0666);
        if (!$this->conn) {
            PMF_Db::errorPage(sqlite_error_string(sqlite_last_error($this->conn)));
            die();
        }
        return true;
    }

    /**
     * Sends a query to the database.
     *
     * @param   string $query
     * @return  mixed $result
     */
    public function query($query)
    {
        $this->sqllog .= pmf_debug($query);
        $result = sqlite_query($this->conn, $query);
        if (!$result) {
           $this->sqllog .= $this->error();
        }
        return $result;
    }

    /**
     * Escapes a string for use in a query
     *
     * @param   string
     * @return  string
     */
    public function escapeString($string)
    {
      return sqlite_escape_string($string);
    }

    /**
     * Fetch a result row as an object
     *
     * @param   mixed $result
     * @return object
     */
    public function fetchObject($result)
    {
        return sqlite_fetch_object($result);
    }



    /**
     * Fetch a result row as an associate array
     *
     * @param   mixed $result
     * @return  array
     */
    public function fetch_assoc($result)
    {
        return sqlite_fetch_array($result, SQLITE_ASSOC);
    }

    /**
     * Fetches a complete result as an object
     *
     * @param  resource      $result Resultset
     * @return PMF_DB_Sqlite
     */
    public function fetchAll($result)
    {
        $ret = array();
        if (false === $result) {
            throw new Exception('Error while fetching result: ' . $this->error());
        }
        
        while ($row = $this->fetchObject($result)) {
            $ret[] = $row;
        }
        
        return $ret;
    }
    
    /**
     * Number of rows in a result
     *
     * @param   mixed $result
     * @return  integer
     */
    public function numRows($result)
    {
        return sqlite_num_rows($result);
    }

    /**
     * Logs the queries
     *
     * @param   mixed $result
     * @return  integer
     */
    public function sqllog()
    {
        return $this->sqllog;
    }



    /**
     * Generates a result based on search a search string.
     *
     * @param  string $table       Table for search
     * @param  array  $assoc       Associative array with columns for the resulset
     * @param  string $joinedTable Table to do a JOIN, e.g. for faqcategoryrelations
     * @param  array  $joinAssoc   Associative array with comlumns for the JOIN
     * @param  string $string      Search term
     * @param  array  $cond        Conditions
     * @param  array  $orderBy     ORDER BY columns
     * @return mixed
     */
    public function search($table, Array $assoc, $joinedTable = '', Array $joinAssoc = array(), $match = array(), $string = '', Array $cond = array(), Array $orderBy = array())
    {
        $string = trim($string);
        $fields = '';
        $join = '';
        $joined = '';
        $where = '';

        foreach ($assoc as $field) {
            if (empty($fields)) {
                $fields = $field;
            } else {
                $fields .= ", ".$field;
            }
        }

        if (isset($joinedTable) && $joinedTable != '') {
            $joined .= ' LEFT JOIN '.$joinedTable.' ON ';
        }

        if (is_array($joinAssoc)) {
            foreach ($joinAssoc as $joinedFields) {
                $join .= $joinedFields.' AND ';
                }
            $joined .= PMF_String::substr($join, 0, -4);
        }

        $keys = PMF_String::preg_split("/\s+/", $string);
        $numKeys = count($keys);
        $numMatch = count($match);

        for ($i = 0; $i < $numKeys; $i++) {
            if (strlen($where) != 0 ) {
                $where = $where." OR";
            }
            $where = $where." (";
            for ($j = 0; $j < $numMatch; $j++) {
                if ($j != 0) {
                    $where = $where." OR ";
                }
                $where = $where.$match[$j]." LIKE '%".$keys[$i]."%'";
            }

            $where .= ")";
        }

        foreach ($cond as $field => $data) {
            if (empty($where)) {
                $where .= $field." = ".$data;
            } else {
                $where .= " AND ".$field." = ".$data;
            }
        }

        $query = "SELECT ".$fields." FROM ".$table.$joined." WHERE";

        if (!empty($where)) {
            $query .= " (".$where.")";
        }

        if (is_numeric($string)) {
            $query = "SELECT ".$fields." FROM ".$table.$joined." WHERE ".$match." = ".$string;
        }

        $firstOrderBy = true;
        foreach ($orderBy as $field) {
            if ($firstOrderBy) {
                $query .= " ORDER BY ".$field;
                $firstOrderBy = false;
            } else {
                $query .= ", ".$field;
            }
        }

        return $this->query($query);
    }

    /**
     * This function returns the table status.
     *
     * @return  array
     */
    public function getTableStatus()
    {
        $arr = array();

        $result = $this->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
        while ($row = $this->fetch_assoc($result)) {
            $num_result = $this->query('SELECT * FROM '.$row['name']);
            $arr[$row['name']] = $this->numRows($num_result);
        }

        return $arr;
    }

    /**
     * Returns the next ID of a table
     *
     * @param   string      the name of the table
     * @param   string      the name of the ID column
     * @return  int
     */
    public function nextID($table, $id)
    {
        $result = $this->query('SELECT max('.$id.') AS current_id FROM '.$table);
        $currentID = intval(sqlite_fetch_single($result));
        return ($currentID + 1);
    }

    /**
     * Returns the error string.
     * 
     * @return string
     */
    public function error()
    {
        if (0 == sqlite_last_error($this->conn)) {
            return;
        }
        return sqlite_error_string(sqlite_last_error($this->conn));
    }

    /**
     * Returns the libary version string.
     *
     * @return string
     */
    public function client_version()
    {
        return 'SQLite '.sqlite_libversion();
    }

    /**
     * Returns the libary version string.
     *
     * @return string
     */
    public function server_version()
    {
        return $this->client_version();
    }

    /**
     * Returns an array with all table names
     *
     * @return array
     */
    public function getTableNames($prefix = '')
    {
        // First, declare those tables that are referenced by others
        $this->tableNames[] = $prefix.'faquser';

        $result = $this->query("SELECT name FROM sqlite_master WHERE type='table' ".(('' == $prefix) ? '':  "AND name LIKE '".$prefix."%' ")."ORDER BY name");
        while ($row = $this->fetchObject($result)) {
            if (!in_array($row->name, $this->tableNames)) {
                $this->tableNames[] = $row->name;
            }
        }
    }

    /**
     * Move internal result pointer
     *
     * Moves the pointer within the query result to a specified location, or
     * to the beginning if nothing is specified.
     *
     * @param resource $result    Resultset
     * @param integer  $rowNumber Row number
     * 
     * @return boolean
     */
    public function resultSeek($result, $rowNumber)
    {
        return sqlite_seek($result, $rowNumber);
    }
    
    /**
     * Closes the connection to the database.
     * 
     * @return boolean
     */
    public function dbclose()
    {
        return sqlite_close($this->conn);
    }
}
