<?php
require_once __DIR__ . '/Book.php';
$dbConfig = require __DIR__ . '/db.php';

try{
    // Create database
    $pdo = new PDO("mysql:host={$dbConfig['host']}", $dbConfig['root_username'], $dbConfig['root_password']);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbConfig['database']}`");
    
    // Create user for a database
    $pdo->exec("CREATE USER IF NOT EXISTS '{$dbConfig['new_username']}'@'{$dbConfig['host']}' IDENTIFIED BY '{$dbConfig['new_password']}'");
    $pdo->exec("GRANT ALL PRIVILEGES ON `{$dbConfig['database']}`.* TO '{$dbConfig['new_username']}'@'{$dbConfig['host']}'");
    $pdo->exec("FLUSH PRIVILEGES");
    
    // Create table
    $pdo = new PDO("mysql:host={$dbConfig['host']};dbname={$dbConfig['database']}", $dbConfig['new_username'], $dbConfig['new_password']);
    $createTableSQL = "CREATE TABLE IF NOT EXISTS `{$dbConfig['table']}` (";
    foreach(Book::$schema as $field => $properties){
        $createTableSQL .= "`$field` $properties, ";
    }
    reset(Book::$schema); // Assume, PK is first key
    $createTableSQL .= "PRIMARY KEY (`".key(Book::$schema)."`)";
    $createTableSQL .= ") ENGINE=InnoDB DEFAULT CHARACTER SET=utf8mb4;";
    $pdo->exec($createTableSQL);
    
    // Parse file
    $file = fopen('books.txt', 'r') or die('Cannot open file!');

    $books = [];
    $bookFields = Book::getFields(); // fields without id;

    while(!feof($file)){
        $line = fgets($file);
        $book = new Book();

        // Extract text between tags
        foreach($bookFields as $field){
            
            $openingTag = "#$field#";
            $closingTag = "#\/$field#";
            $regex = "/(?<={$openingTag}).*?(?={$closingTag})/";
            preg_match($regex, $line, $match);

            $book->{$field} = reset($match);
        }
        $books[] = $book;
    }
    fclose($file);

    // Batch insert into DB
    $insertBooksSQL = "INSERT INTO `{$dbConfig['table']}` (".implode(', ', array_map(function($f){
        return "`$f`";
    }, $bookFields)).") VALUES ";
    foreach($books as $book){
        $insertBooksSQL .= '('.implode(', ', array_map(function($f) use($book){
            return $book->formatField($f);
        }, $bookFields)).')';
        $insertBooksSQL .= next($books) ? ', ' : '';
    }
    $pdo->exec($insertBooksSQL);

}catch(Exception $e){
    die($e->getMessage());
}