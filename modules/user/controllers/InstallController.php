<?php

class InstallController extends Controller
{
	public function actionIndex()
	{
		$this->render('index');
	}

	public function actionCreate()
	{
		if(Yii::app()->request->isPostRequest)
		{
			$this->createTable();
			$this->render('done');
		}
		else
			throw new CHttpException(400);
	}

	protected function createTable()
	{
		if($db=Yii::app()->db)
		{
			// table name
			$userTable=Yii::app()->controller->module->userTable;

			// create table
			$sql="
				CREATE TABLE $userTable
				(
					id serial NOT NULL,
					username character varying(64) NOT NULL,
					password character varying(64) NOT NULL,
					role character varying(16),
					salt character varying(64),
					firstname character varying(64),
					lastname character varying(64),
					usergroup character varying(64),
					CONSTRAINT pk_user PRIMARY KEY (id)
				);
			";
			$db->createCommand($sql)->execute();

			// insert into table
			$sql="
				INSERT INTO $userTable (username,password,salt,firstname,lastname)
				VALUES ('admin','1960f7b94a93fad395b6f68580886a70','4ca5ebd1c90968.70671105','Super','Admin');
			";
			$db->createCommand($sql)->execute();
		}
		else
			throw new CException('Database connection is not working!');
	}
}