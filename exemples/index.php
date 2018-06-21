<?php
	require_once(__DIR__.'/../src/mysqlwizard.php');
    /**
     * CREATE DATABASE IF NOT EXISTS teste;
     * USE teste;
     * CREATE TABLE IF NOT EXISTS `usuarios` ( `id` INT NOT NULL AUTO_INCREMENT , `nome` VARCHAR(120) NOT NULL , `email` VARCHAR(120) NOT NULL , `senha` VARCHAR(120) NOT NULL , `data_cad` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP , `ativo` TINYINT NOT NULL DEFAULT '1' , PRIMARY KEY (`id`)) ENGINE = InnoDB;
     */
	class suaClasse{
        function __construct(){
            $database_sets = (object)array(
				'host'=>'127.0.0.1',
				'user'=>'root',
				'password'=>'',
				'database'=>'teste'
            );
            
            $this->mysql = new MySQLWizard\MySQLWizard($database_sets);
          /*   $this->mysql->query(
                "CREATE DATABASE IF NOT EXISTS teste;
                USE teste;
                CREATE TABLE IF NOT EXISTS `usuarios` ( `id` INT NOT NULL AUTO_INCREMENT , `nome` VARCHAR(120) NOT NULL , `email` VARCHAR(120) NOT NULL , `senha` VARCHAR(120) NOT NULL , `data_cad` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP , `ativo` TINYINT NOT NULL DEFAULT '1' , PRIMARY KEY (`id`)) ENGINE = InnoDB;"
                ); */
            $this->mysql->set_table('usuarios');
            $usuario = array(
                'nome'=>'Virgilio Pontes',
                'email'=>'vrgiliocpontes@outlook.com',
                'senha'=>'password'
            );
            $id = $this->insert($usuario);

            $campoWhere = array(
                'id'=>$id //ID do Usuário
            );
            var_dump($campoWhere);
            var_dump($this->select($campoWhere));

            $usuario = array(
                'nome'=>'Virgilio'
            );

            var_dump($this->update($usuario,$campoWhere));
            var_dump($this->select($campoWhere));
            var_dump($this->delete($campoWhere));
            var_dump($this->select($campoWhere));
        }

		function insert($array){
			
            $result = $this->mysql->insert($array);
			return $result; //ID || false
        }

        function select($array){

            $result = $this->mysql->select($array);
			return $result; //array
		}
        
        function update($array,$campoWhere){
           
            $result = $this->mysql->update($array,$campoWhere);
            return $result; //true || false
        }
        
        function delete($array){

            $result = $this->mysql->delete($array,'ativo');
			return $result; //true || false
		}
    }

    $suaClasse = new suaClasse();