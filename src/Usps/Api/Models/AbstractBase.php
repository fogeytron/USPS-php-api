<?php

namespace Usps\Api\Models;

abstract class AbstractBase
{
    protected $data = [];

    protected $allowed = [];

    public function __construct($data = [])
    {
        if (!empty($data)) {
            foreach ($data as $k => $v) {
                $this->$k = $v;
            }
        }
    }

    public function __set($k, $v)
    {
        if (in_array($k, $this->allowed)) {
            $this->data[$k] = $v;
            
            return true;
        }

        $trace = debug_backtrace();
        trigger_error(
            'Undefined property via __set(): ' . $k .
            ' in ' . $trace[0]['file'] .
            ' on line ' . $trace[0]['line'],
            E_USER_NOTICE
        );
    }

    public function __get($k)
    {
        if (array_key_exists($k, $this->data)) {
            return $this->data[$k];
        }
        
        if (in_array($k, $this->allowed)) {
            return null;
        }

        $trace = debug_backtrace();
        trigger_error(
            'Undefined property via __get(): ' . $k .
            ' in ' . $trace[0]['file'] .
            ' on line ' . $trace[0]['line'],
            E_USER_NOTICE
        );

        return null;
    }

    public function data()
    {
        return $this->data;
    }
}
