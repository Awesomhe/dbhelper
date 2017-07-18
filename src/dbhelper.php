<?php
namespace vielhuber\dbhelper;

use PDO;

class dbhelper
{

    public $sql = null;

    public function connect($driver, $engine = null, $host = null, $username = null, $password = null, $database = null, $port = 3306)
    {
        switch ($driver)
        {
            case 'pdo':
                if ($engine == 'mysql')
                {
                    $sql = new PDO('mysql:host=' . $host . ';port=' . $port . ';dbname=' . $database, $username, $password, array(
                        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
                        PDO::ATTR_EMULATE_PREPARES => false,
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING
                    ));
                }
                if ($engine == 'postgres')
                {
                    $sql = new PDO('pgsql:host=' . $host . ';port=' . $port . ';dbname=' . $database, $username, $password);
                    $sql->query('SET NAMES UTF8');
                }
                break;

            case 'mysqli':
                $sql = new mysqli($host, $username, $password, $database, $port);
                mysqli_set_charset($sql, "utf8");
                if ($sql->connect_errno)
                {
                    die('SQL Connection failed: ' . $sql->connect_error);
                }
                break;

            case 'wordpress':
            	global $wpdb;
				$wpdb->show_errors = true;
				$wpdb->suppress_errors = false;
                $sql = $wpdb;
                break;

            case 'joomla':
                // TODO
                break;

        }
        $sql->driver = $driver;
        $sql->engine = $engine;
        $this->sql = $sql;
    }

    public function fetch_all($query)
    {
        $data = array();
        $params = func_get_args();
        unset($params[0]);
        $params = array_values($params);
        list($query, $params) = $this->preparse_query($query, $params);

        switch ($this->sql->driver)
        {

            case 'pdo':
                $stmt = $this->sql->prepare($query);
                $stmt->execute($params);
                if ($stmt->errorCode() != 0)
                {
                    $errors = $stmt->errorInfo();
                    echo($errors[2]);
                    return false;
                }
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;

            case 'mysqli':
                // do not use mysqlnd
                $result = $this->sql->query($query);
                while ($row = $result->fetch_assoc())
                {
                    $data[] = $row;
                }
                break;

            case 'wordpress':
                // TODO
                break;

            case 'joomla':
                // TODO
                break;

        }

        return $data;
    }

    public function fetch_row($query)
    {
        $data = array();
        $params = func_get_args();
        unset($params[0]);
        $params = array_values($params);
        list($query, $params) = $this->preparse_query($query, $params);

        switch ($this->sql->driver)
        {

            case 'pdo':
                $stmt = $this->sql->prepare($query);
                $stmt->execute($params);
                if ($stmt->errorCode() != 0)
                {
                    $errors = $stmt->errorInfo();
                    echo($errors[2]);
                    return false;
                }
                $data = $stmt->fetch(PDO::FETCH_ASSOC);
                break;

            case 'mysqli':
                $data = $this->sql->query($query)->fetch_assoc();
                break;

            case 'wordpress':
                // TODO
                break;

            case 'joomla':
                // TODO
                break;

        }

        return $data;
    }

    public function fetch_col($query)
    {
        $data = array();
        $params = func_get_args();
        unset($params[0]);
        $params = array_values($params);
        list($query, $params) = $this->preparse_query($query, $params);

        switch ($this->sql->driver)
        {

            case 'pdo':
                $stmt = $this->sql->prepare($query);
                $stmt->execute($params);
                if ($stmt->errorCode() != 0)
                {
                    $errors = $stmt->errorInfo();
                    echo($errors[2]);
                    return false;
                }
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (!empty($data))
                {
                    $data_tmp = array();
                    foreach ($data as $dat)
                    {
                        $data_tmp[] = $dat[ array_keys($dat)[0] ];
                    }
                    $data = $data_tmp;
                }
                break;

            case 'mysqli':
                // TODO
                break;

            case 'wordpress':
                // TODO
                break;

            case 'joomla':
                // TODO
                break;

        }

        return $data;
    }

    public function fetch_var($query)
    {
        $data = array();
        $params = func_get_args();
        unset($params[0]);
        $params = array_values($params);
        list($query, $params) = $this->preparse_query($query, $params);

        switch ($this->sql->driver)
        {

            case 'pdo':
                $stmt = $this->sql->prepare($query);
                $stmt->execute($params);
                if ($stmt->errorCode() != 0)
                {
                    $errors = $stmt->errorInfo();
                    echo($errors[2]);
                    return false;
                }
                $data = $stmt->fetchObject();
                if (empty($data))
                {
                    return null;
                }
                $data = (Array)$data;
                $data = current($data);
                break;

            case 'mysqli':
                $data = $this->sql->query($query)->fetch_object();
                if (empty($data))
                {
                    return null;
                }
                $data = (Array)$data;
                $data = current($data);
                break;

            case 'wordpress':
            	$data = $this->sql->get_var($query);
                break;

            case 'joomla':
                // TODO
                break;

        }

        return $data;
    }

    public function query($query)
    {
        $data = array();
        $params = func_get_args();
        unset($params[0]);
        $params = array_values($params);
        list($query, $params) = $this->preparse_query($query, $params);

        switch ($this->sql->driver)
        {

            case 'pdo':
                $stmt = $this->sql->prepare($query);
                $stmt->execute($params);
                if ($stmt->errorCode() != 0)
                {
                    $errors = $stmt->errorInfo();
                    echo($errors[2]);
                    return false;
                }
                return $stmt->rowCount();
                break;

            case 'mysqli':
                $this->sql->query($query);
                break;

            case 'wordpress':
                // TODO
                break;

            case 'joomla':
                // TODO
                break;

        }

    }

    public function insert($table, $data)
    {
        $query = "";
        $query .= "INSERT INTO ";
        $query .= "`" . $table . "`";
        $query .= "(";
        foreach ($data as $key => $value)
        {
            if ($this->sql->engine == 'mysql')
            {
                $query .= '`' . $key . '`';
            }
            if ($this->sql->engine == 'postgres')
            {
                $query .= '"' . $key . '"';
            }
            end($data);
            if ($key !== key($data))
            {
                $query .= ',';
            }
        }
        $query .= ") VALUES(";
        $query .= str_repeat("?,", count($data) - 1) . "?";
        $query .= ")";
        $args = array();
        $args[] = $query;
        foreach ($data as $d)
        {
            // because pdo can't insert true/false values so easily, convert them to 1/0
            if ($d === true)
            {
                $d = 1;
            }
            if ($d === false)
            {
                $d = 0;
            }
            $args[] = $d;
        }
        call_user_func_array([$this, 'query'], $args);
        return $this->last_insert_id();
    }

    public function last_insert_id()
    {
        $last_insert_id = null;
        switch ($this->sql->driver)
        {

            case 'pdo':
                if ($this->sql->engine == 'mysql')
                {
                    $last_insert_id = $this->fetch_var("SELECT LAST_INSERT_ID();");
                }
                if ($this->sql->engine == 'postgres')
                {
                    $last_insert_id = $this->fetch_var("SELECT LASTVAL();");
                }
                break;

            case 'mysqli':
                $last_insert_id = mysqli_insert_id($this->sql);
                break;

            case 'wordpress':
                // TODO
                break;

            case 'joomla':
                // TODO
                break;

        }

        return $last_insert_id;
    }

    public function total_count()
    {
        $total_count = 0;
        switch ($this->sql->driver)
        {

            case 'pdo':
                $total_count = $this->fetch_var("SELECT FOUND_ROWS();");
                break;

            case 'mysqli':
                $total_count = $this->fetch_var("SELECT FOUND_ROWS();");
                break;

            case 'wordpress':
                // TODO
                break;

            case 'joomla':
                // TODO
                break;

        }

        return $total_count;
    }

    public function debug($query)
    {

        $params = func_get_args();
        unset($params[0]);
        $params = array_values($params);

        $keys = array();
        $values = $params;
        foreach ($params as $key => $value)
        {
            // check if named parameters (':param') or anonymous parameters ('?') are used
            if (is_string($key))
            {
                $keys[] = '/:' . $key . '/';
            }
            else
            {
                $keys[] = '/[?]/';
            }
            // bring parameter into human-readable format
            if (is_string($value))
            {
                $values[ $key ] = "'" . $value . "'";
            }
            elseif (is_array($value))
            {
                $values[ $key ] = implode(',', $value);
            }
            elseif (is_null($value))
            {
                $values[ $key ] = 'NULL';
            }
        }
        $query = preg_replace($keys, $values, $query, 1, $count);
        echo '<pre>';
        print_r($query);
        echo '</pre>';
        die();

    }

    public function preparse_query($query, $params)
    {

        $return = $query;

        // NULL values are treated specially: modify the query
        if (strpos($query, "UPDATE") === false)
        {
            $pos = 0;
            foreach ($params as $x_Key => $param)
            {
                // no more ?s are left
                if (($pos = strpos($query, '= ?', $pos + 1)) === false)
                {
                    break;
                }
                // if param is not null, nothing must be done
                if (!is_null($param))
                {
                    continue;
                }

                // convert != ? to IS NOT NULL and = ? to IS NULL
                $return = substr($return, 0, ($pos - 5)) . preg_replace("/\!= \?/", "IS NOT NULL", substr($return, ($pos - 5)), 1);
                $return = substr($return, 0, ($pos - 5)) . preg_replace("/= \?/", "IS NULL", substr($return, ($pos - 5)), 1);

                // delete param
                unset($params[ $x_Key ]);

            }
            $params = array_values($params);
        }

        return array($return, $params);

    }

    public function update($table, $data, $condition)
    {
        $query = "";
        $query .= "UPDATE ";
        $query .= "`" . $table . "`";
        $query .= " SET ";
        foreach ($data as $key => $value)
        {
            $query .= "`" . $key . "`";
            $query .= " = ";
            $query .= "?";
            end($data);
            if ($key !== key($data))
            {
                $query .= ', ';
            }
        }
        $query .= " WHERE ";
        foreach ($condition as $key => $value)
        {
            $query .= "`" . $key . "`";
            $query .= " = ";
            $query .= "? ";
            end($condition);
            if ($key !== key($condition))
            {
                $query .= ' AND ';
            }
        }
        $args = array();
        $args[] = $query;
        foreach ($data as $d)
        {
            if ($d === true)
            {
                $d = 1;
            }
            if ($d === false)
            {
                $d = 0;
            }
            $args[] = $d;
        }
        foreach ($condition as $c)
        {
            if ($c === true)
            {
                $c = 1;
            }
            if ($c === false)
            {
                $c = 0;
            }
            $args[] = $c;
        }
        return call_user_func_array([$this, 'query'], $args); // returns the affected row counts
    }

    public function delete($table, $condition)
    {
        $query = "";
        $query .= "DELETE FROM ";
        $query .= "`" . $table . "`";
        $query .= " WHERE ";
        foreach ($condition as $key => $value)
        {
            $query .= "`" . $key . "`";
            $query .= " = ";
            $query .= "? ";
            end($condition);
            if ($key !== key($condition))
            {
                $query .= ' AND ';
            }
        }
        $args = array();
        $args[] = $query;
        foreach ($condition as $c)
        {
            if ($c === true)
            {
                $c = 1;
            }
            if ($c === false)
            {
                $c = 0;
            }
            $args[] = $c;
        }
        return call_user_func_array([$this, 'query'], $args); // returns the affected row counts
    }

    public static function get_combined_query($input)
    {
        $query = 'UPDATE '.$input[0].' SET'.PHP_EOL;
        foreach($input[1][0] as $col__key=>$col__value)
        {
            $query .= $col__key.' = CASE'.PHP_EOL;
            foreach($input as $input__key=>$input__value)
            {
                if($input__key === 0) continue;
                $query .= 'WHEN (';
                $where = [];
                foreach($input__value[1] as $where__key=>$where__value)
                {
                    $where[] = $where__key.' = '.((is_string($where__value))?('\''):('')).$where__value.((is_string($where__value))?('\''):(''));                
                }
                $query .= implode(' AND ',$where).')'.' THEN '.((is_string($input__value[0][$col__key]))?('\''):('')).$input__value[0][$col__key].((is_string($input__value[0][$col__key]))?('\''):('')).PHP_EOL;
            }
            $query .= 'END';
            if( array_keys($input[1][0])[count(array_keys($input[1][0]))-1] !== $col__key ) $query .= ',';
            $query .= PHP_EOL;
        }
        $query .= 'WHERE ';
        $where = [];
        foreach($input[1][1] as $where__key=>$where__value)
        {
            $where_values = [];
            foreach($input as $input__key=>$input__value)
            {
                if($input__key === 0) continue;
                $where_values[] = ((is_string($input__value[1][$where__key]))?('\''):('')).$input__value[1][$where__key].((is_string($input__value[1][$where__key]))?('\''):(''));
            }
            $where_values = array_unique($where_values);
            $where[] = $where__key.' IN ('.implode(',',$where_values).')';
        }
        $query .= implode(' AND ', $where).';';
        return $query;
    }

}

print_r(dbhelper::get_combined_query(['table',
    [['col1' => 'var1', 'col2' => 1], ['id' => 1, 'key' => '1']],
    [['col1' => 'var2', 'col2' => 2], ['id' => 2, 'key' => '2']],
    [['col1' => 'var3', 'col2' => 3], ['id' => 3, 'key' => '3']]
]));