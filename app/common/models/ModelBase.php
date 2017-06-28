<?php
namespace App\Models;

class ModelBase extends \Phalcon\Mvc\Model
{
	public function defaultValue()
    {
		return new \Phalcon\Db\RawValue('default');
	}

	public static function phquery($q, $bind = false)
	{
		if (empty($q)) {
			return false;
		}

		$di = new self();
		$di = $di->getDi();

		return $di['modelsManager']->executeQuery($q, $bind);
	}

	public static function rawquery($q)
	{
		if (empty($q)) {
			return false;
		}

		$model = get_called_class();
		$model = new $model;
		return new \Phalcon\Mvc\Model\Resultset\Simple(null, $model, $model->getReadConnection()->query($q));
	}

	public static function getCache($key)
	{
		if (empty($key)) {
			return false;
		}

		$di = new self();
		$di = $di->getDi();

		if (empty($di['cache'])) {
			return false;
		}

		return $di['cache']->get($di['config']->cache->key . '~' . $key);
	}

	public static function saveCache($key, $value)
	{
		if (empty($key) || empty($value)) {
			return false;
		}

		$di = new self();
		$di = $di->getDi();

		if (empty($di['cache'])) {
			return false;
		}

		$di['cache']->save($di['config']->cache->key . '~' . $key, $value);

		return true;
	}

	public static function builder()
	{
		return new \Phalcon\Mvc\Model\Query\Builder;
	}

	public static function getTransaction()
	{
		$manager = new \Phalcon\Mvc\Model\Transaction\Manager();
	   	return $manager->get();
	}

	public static function execute($q)
	{
		$di = new self();
		$di = $di->getDi();
		$di['db']->execute($q);
		return $di['db']->affectedRows();
	}

	public static function getBuilder()
	{
		$di = new self();
		$di = $di->getDi();

		return $di['modelsManager']->createBuilder();
	}

	# Logger attached to eventlistener
	public function notSaved()
	{
		$di = new self();
		$di = $di->getDi();

		foreach ($this->getMessages() as $error) {
			$di['logger']->error($error);
		}
	}
}