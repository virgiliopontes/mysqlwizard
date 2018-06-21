<?php
namespace MySQLWizard;

class MySQLWizard{
    /**
     * @param array $database_sets Array com os dados de conexão com o banco;
     */ 
	function __construct($database_sets=''){
		if($database_sets!=''){
			$this->database_sets = $database_sets;
		}else{
			$this->database_sets = (object)array(
				'host'=>'127.0.0.1',
				'user'=>'root',
				'password'=>'',
				'database'=>'teste'
			);
		}
		$this->table = '';
        $this->connect = $this->connect();
    }
    
    private function connect(){
        $connect = mysqli_connect($this->database_sets->host,$this->database_sets->user,$this->database_sets->password,$this->database_sets->database);
        return $connect;
    }

    function query($query){
        $result = mysqli_query($this->connect,$query);
        return $result;
	}
	
	function set_table($tabela){
		$this->table = $tabela;
	}

	/**
	 * Insere uma linha na tabela do banco de dados
	 * @param array $dados Array com os dados a serem inseridos no banco de dados
	 * @param string $tabela tabela em que os dados serão inseridos
	 * @return bool|int
	 */
    function insert($dados,$tabela=''){
		if($tabela==''){
			$tabela = $this->table;
		}
		$this->verificaTabela($tabela);
		
        $query = "INSERT INTO ".$tabela;

        $campos =' (';
        $valores =' (';

        foreach($dados as $key=>$value){
            $campos.= $key.',';
            $valores.= $this->isNumeric($value).',';
        }
        $campos = $this->removeVirgula($campos);
        $valores = $this->removeVirgula($valores);

        $campos.=') ';
        $valores.=') ';
        $query.= $campos.'VALUES'.$valores;

        $result = $this->query($query);
        return $result==false ? $result : mysqli_insert_id($this->connect);

    }

	/**
	 * Insere diversas linhas na tabela do banco de dados
	 * @param array $dados Possui os dados das linhas que devem ser inseridas na tabela do banco de dados;
	 * @param string $tabela tabela em que os dados serão inseridos
	 * @return bool|array
	 */
    function insert_batch($dados,$tabela=''){
		if($tabela==''){
			$tabela = $this->table;
		}
		$this->verificaTabela($tabela);

        $query = "INSERT INTO".$tabela;

        $campos =' (';
        foreach ($dados[0] as $key=>$value){
            $campos.= $key.','; 
        }
        $campos = $this->removeVirgula($campos);
        $campos.=') ';
    
        foreach($dados as $key=>$value){
            $valores =' (';
            foreach ($value as $key=>$value){                
                $valores.= $this->isNumeric($value).',';
            }
            $valores = $this->removeVirgula($valores);
            $valores.='), ';
        }
        $valores = $this->removeVirgula($valores);
        
        $query.= $campos.'VALUES'.$valores;

        $result = $this->query($query);
        return $result==false ? $result : mysqli_insert_id($this->connect);
	}

	/**
	 * @param array $campo Array com o campo e valor que deve ser usado para buscar os dados no banco
	 * @param string $tabela tabela que comtem os dados que devem ser selecionados
	 * @return array Retorna um Array de objetos
	 */
	function select($campoWhere,$tabela=''){
		if($tabela==''){
			$tabela = $this->table;
		}
		$this->verificaTabela($tabela);

        $query = "SELECT * FROM ".$tabela." WHERE ";

        if(is_array($campoWhere)){
            $query.=$this->make_where($campoWhere);
        }elseif(is_string($campoWhere)){
            $query.=$campoWhere;
        }
        
        $result = $this->query($query);
        if(!$result){
            return $result;
		}
		
		while($dados = mysqli_fetch_assoc($result)){
			$array[] = (object)$dados;
		}

        return $array;
	}
	
	/**
	 * Atualiza atravez do $campoWhere os dados de uma determianda linha no banco de dados
	 * @param array $dados Colunas e valores que devem ser atualizados
	 * @param string $tabela tabela que comtem os dados que devem ser atualizados
	 * @return bool
	 */
    function update($dados,$tabela='',$campoWhere){
		if($tabela==''){
			$tabela = $this->table;
		}
		$this->verificaTabela($tabela);

        $query = "UPDATE ".$tabela." SET ";

        $valores ='';

        foreach ($dados as $key=>$value){
            if($key!=$campoWhere){
                $valores.= $key.'='.$this->isNumeric($value).', ';
            }
        }
        $valores = $this->removeVirgula($valores);

        $query.=" WHERE ".$campoWhere."=".$this->isNumeric($dados[$campoWhere]);

        $result = $this->query($query);
        return $result;

    }

    function delete($campoWhere,$soft=true,$tabela=''){
		if($tabela==''){
			$tabela = $this->table;
		}
		$this->verificaTabela($tabela);

        if($soft){
            $query = "UPDATE ".$tabela." SET ativo=0 ";
        }else{
            $query = "DELETE FROM ".$tabela;
        }
        $query.= " WHERE ".$campoWhere['campo']." = ".$this->isNumeric($campoWhere["valor"]);

        $result = $this->query($query);
        return $result;
    }

    private function isNumeric($value){
        if(!is_numeric($value)&&!is_null($value)){
            $value = "'".$value."'";
        }
        return $value;
    }

    private function removeVirgula($string){
        return substr($string,0, strlen($string)-1);
    }

    private function make_where($array){
        if(!is_array($array)){
            return false;
        }
        $where = '';
        foreach($array as $key=>$value){
            $keys = explode(' ',$key);
            if($where!=''){
                $where.= ' and ';
            }
            $where .= $keys[0].(isset($keys[1]) ? $keys[1]:'=').$this->isNumeric($value);
        }
        
        return $where;
	}
	
	function verificaTabela($tabela){
		if($this->table=''&&$tabela==''){
			throw new Exception('A tabela para executar a operação não está setada, utilize set_table("usuarios").');
		}
	}
}