<?php
/**
 * Run: yiic migrate --migrationPath=ext.modules.page.migrations
 */
class m121017_115122_create_table_menu extends CDbMigration
{
	public function up()
	{
		$this->execute("
			CREATE TABLE tbl_page_menu
			(
				id serial NOT NULL,
				type integer,
				position integer,
				lang character varying(5),
				title character varying(256),
				content text,
				url character varying(256),
				deleted boolean DEFAULT false,
				CONSTRAINT pk_page_menu PRIMARY KEY (id)
			)
		");
	}

	public function down()
	{
		echo "m121017_115122_create_table_menu does not support migration down.\n";
		return false;
	}

	/*
	// Use safeUp/safeDown to do migration with transaction
	public function safeUp()
	{
	}

	public function safeDown()
	{
	}
	*/
}