#Sql Comments example from the global mysql documentation

/*
this is a
multiple-line comment before instructions
*/
SELECT 1+1, "ciao; #good text --, testo";      # This comment; continues to the end of line
SELECT 1+1, "ciao; #good text --, testo", 1 /* this is an in-line comment */ * 1;
SELECT 1+1, "ciao; #good text --, testo";     -- This comment continues to the end of line
SELECT 1-
/*
this is a
multiple-line comment
*/
1;

#THIS IS A COMMENT :)

SELECT * FROM table1 WHERE a=1;

--SELECT COMMENTED 2 
SELECT * FROM
    table2
    where a=2;

#INSERTING VALUES
INSERT IGNORE INTO `VERSIONS`(`release`,`revision`,`name`,`lastUpdate`) VALUES ( '1','0','SqlParser',NOW()); 

#CREATING TABLES
CREATE TABLE `TESTS` (
  `Id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `Name` varchar(80) NOT NULL DEFAULT '',
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

#CUSTOMS OPERATIONS
INSERT INTO `TESTS`(`Id`,`Name`) VALUES ( '1','test valiue'); 
UPDATE `TEST` SET `Name`='test value update' WHERE `Id`='1'; 
