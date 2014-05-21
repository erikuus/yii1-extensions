<?php
/**
 * Run: yiic migrate --migrationPath=ext.modules.page.migrations
 */
class m121017_115305_create_table_article extends CDbMigration
{
	public function up()
	{
		$this->execute("
			CREATE TABLE tbl_page_article
			(
				id serial NOT NULL,
				menu_id integer,
				position integer,
				lang character varying(5),
				title character varying(256),
				content text,
				CONSTRAINT pk_page_article PRIMARY KEY (id),
				CONSTRAINT fk_page_menu_id FOREIGN KEY (menu_id)
	  				REFERENCES tbl_page_menu (id) MATCH SIMPLE
	  				ON UPDATE CASCADE ON DELETE CASCADE
			)
		");
	}

	public function down()
	{
		echo "m121017_115305_create_table_article does not support migration down.\n";
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