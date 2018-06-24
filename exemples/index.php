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
            echo 'Inserir apenas 1 Item';
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
            
            echo 'Array de itens';
            $usuario = array(
                array(
                    'nome'=>'Virgilio1',
                    'email'=>'vrgiliocp@outlook.com',
                    'senha'=>'password1'
                ),
                array(
                    'nome'=>'Virgilio2',
                    'email'=>'vrgiliocpo@outlook.com',
                    'senha'=>'password2'
                ),
               
            );
            var_dump($this->insert($usuario));
            
            echo'Atualizar dados de um Item';
            $usuario = array(
                'nome'=>'Virgilio'
            );
            var_dump($this->update($usuario,$campoWhere));
            var_dump($this->select($campoWhere));
            
            echo 'Deletando um item';
            var_dump($this->delete($campoWhere));
            var_dump($this->select($campoWhere));
            
            echo 'Campo Nulo e mais de um argumento';
            $campoWhere = array(
                'nome !='=>'NULL',
                'senha'=>'password2'
            );

            var_dump($this->select($campoWhere));
            //Campos Nulos
            $campoWhere = array(
                'nome ='=>'NULL'
            );
            var_dump($this->select($campoWhere));
            
            echo 'Toda a Tabela';
            var_dump($this->select());
        }

		function insert($array){
			if(isset($array[0])){
                $result = $this->mysql->insert_batch($array);
            }else{
                $result = $this->mysql->insert($array);
            }
            
			return $result; //ID || false
        }

        function select($array=''){

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