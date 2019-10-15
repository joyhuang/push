<?php

namespace joyhuang\Push;

use Redis as RedisBase;

class Redis extends RedisBase
{
	public function __construct($config)
	{
		parent::__construct();

        if ($config['pconnect']) {
            $this->pconnect($config['host'], $config['port']);
        } else {
            $this->connect($config['host'], $config['port']);
        }

        $this->auth($config['auth']);
        $this->select($config['db']);
	}

}