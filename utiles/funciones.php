<?php
	
/**
 * FUNCIÓN QUE CONECTA CON BBDD
 */
  function conectarPDO(string $host, string $user, string $password, string $bbdd): PDO 
    {
        try 
        {
          $mysql="mysql:host=$host;dbname=$bbdd;charset=utf8";
          $conexion = new PDO($mysql, $user, $password);
          // set the PDO error mode to exception
          $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
        } 
        catch (PDOException $exception) 
        {
           exit($exception->getMessage());
        }
        return $conexion;    
    }	
	
?>