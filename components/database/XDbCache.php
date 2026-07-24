<?php

class XDbCache extends CDbCache
{
	private $_sqliteGced=false;

	protected function setValue($key,$value,$expire)
	{
		if($this->getDbConnection()->getDriverName()!=='sqlite')
			return parent::setValue($key,$value,$expire);

		$this->runSqliteGc();
		$expire=$expire>0 ? $expire+time() : 0;
		$sql="INSERT OR REPLACE INTO {$this->cacheTableName} (id, expire, value) VALUES (:id, :expire, :value)";

		try
		{
			$command=$this->getDbConnection()->createCommand($sql);
			$command->bindValue(':id',$key);
			$command->bindValue(':expire',$expire);
			$command->bindValue(':value',$value,PDO::PARAM_LOB);
			$command->execute();
			return true;
		}
		catch(Exception $e)
		{
			return false;
		}
	}

	protected function addValue($key,$value,$expire)
	{
		if($this->getDbConnection()->getDriverName()!=='sqlite')
			return parent::addValue($key,$value,$expire);

		$this->runSqliteGc();
		$expire=$expire>0 ? $expire+time() : 0;

		try
		{
			$sql="INSERT OR IGNORE INTO {$this->cacheTableName} (id, expire, value) VALUES (:id, :expire, :value)";
			$command=$this->getDbConnection()->createCommand($sql);
			$command->bindValue(':id',$key);
			$command->bindValue(':expire',$expire);
			$command->bindValue(':value',$value,PDO::PARAM_LOB);
			if($command->execute()>0)
				return true;

			$sql="DELETE FROM {$this->cacheTableName} WHERE id=:id AND expire>0 AND expire<=:time";
			$command=$this->getDbConnection()->createCommand($sql);
			$command->bindValue(':id',$key);
			$command->bindValue(':time',time());
			if($command->execute()<=0)
				return false;

			$sql="INSERT OR IGNORE INTO {$this->cacheTableName} (id, expire, value) VALUES (:id, :expire, :value)";
			$command=$this->getDbConnection()->createCommand($sql);
			$command->bindValue(':id',$key);
			$command->bindValue(':expire',$expire);
			$command->bindValue(':value',$value,PDO::PARAM_LOB);
			return $command->execute()>0;
		}
		catch(Exception $e)
		{
			return false;
		}
	}

	protected function runSqliteGc()
	{
		if(!$this->_sqliteGced && mt_rand(0,1000000)<$this->getGCProbability())
		{
			$this->gc();
			$this->_sqliteGced=true;
		}
	}

	/**
	 * Removes expired cache rows without turning optional SQLite maintenance
	 * contention into an application error.
	 *
	 * CDbCache::gc() uses CDbCommand, which logs an error before throwing when
	 * SQLite is busy. Cache garbage collection is best-effort, so use PDO
	 * directly and let a later request retry it.
	 */
	protected function gc()
	{
		$db=$this->getDbConnection();
		if($db->getDriverName()!=='sqlite')
		{
			parent::gc();
			return;
		}

		try
		{
			$sql="DELETE FROM {$this->cacheTableName} WHERE expire>0 AND expire<".time();
			$db->getPdoInstance()->exec($sql);
		}
		catch(Exception $e)
		{
			// SQLite cache cleanup is opportunistic; cache reads remain valid.
		}
	}
}
