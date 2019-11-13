<?php

/**
 * @author  : Principe Orazio (orazio.principe@gmail.com)
 * @websites: http://principeorazio.wordpress.com http://www.dbpersister.com
 * @version : 1.1
 * @date    : 24/10/2011
 * 
 * Purpose : Parse a valid SQL file with MySql syntax
 * Release : Released under GNU Public License
 * 
 * Updates: 1.1 Excluding inline, end of line and multiline comments
 */
class SqlParser {

    /**
     * Take off comments from an sql string
     * 
     * Referring documentation at:
     * http://dev.mysql.com/doc/refman/5.6/en/comments.html
     * 
     * @return string Query without comments
     */
    public static function takeOffComments($query)
    {
        /* 
         * Commented version
         * $sqlComments = '@
         *     (([\'"]).*?[^\\\]\2) # $1 : Skip single & double quoted expressions
         *     |(                   # $3 : Match comments
         *         (?:\#|--).*?$    # - Single line comments
         *         |                # - Multi line (nested) comments
         *          /\*             #   . comment open marker
         *             (?: [^/*]    #   . non comment-marker characters
         *                 |/(?!\*) #   . ! not a comment open
         *                 |\*(?!/) #   . ! not a comment close
         *                 |(?R)    #   . recursive case
         *             )*           #   . repeat eventually
         *         \*\/             #   . comment close marker
         *     )\s*                 # Trim after comments
         *     |(?<=;)\s+           # Trim after semi-colon
         *     @msx';
         */
        $sqlComments = '@(([\'"]).*?[^\\\]\2)|((?:\#|--).*?$|/\*(?:[^/*]|/(?!\*)|\*(?!/)|(?R))*\*\/)\s*|(?<=;)\s+@ms';
                        
        $query = trim( preg_replace( $sqlComments, '$1', $query ) );
        
        //Eventually remove the last ;
        if(strrpos($query, ";") === strlen($query) - 1) {
            $query = substr($query, 0, strlen($query) - 1);
        }
        
        return $query;
    }
    
    
    /**
     * @purpose : Parses SQL file
     * @params string $content Text containing sql instructions
     * @return array List of sql parsed from $content
     */
    public static function parse($content) {

        $sqlList = array();
        
        // Processing the SQL file content	 		
        $lines = explode("\n", $content);

        $query = "";
        
        // Parsing the SQL file content			 
        foreach ($lines as $sql_line):
            $sql_line = trim($sql_line);
            if($sql_line === "") continue;
            else if(strpos($sql_line, "--") === 0) continue;
            else if(strpos($sql_line, "#") === 0) continue;
                
            $query .= $sql_line;
            // Checking whether the line is a valid statement
            if (preg_match("/(.*);/", $sql_line)) {
                $query = trim($query);
                $query = substr($query, 0, strlen($query) - 1);

                $query = SqlParser::takeOffComments($query);
                
                //store this query
                $sqlList[] = $query;
                //reset the variable
                $query = "";
            }
            
        endforeach;

        return $sqlList;
    }

//End of function
}

//End of class