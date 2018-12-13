<?php
namespace MySQLWizard;

class MySQLWizard{
    /**
     * @param array $database_sets Array com os dados de conexão com o banco;
     */ 
	function __construct($database_sets=''){
        $database_sets_defalt = (object)array(
            'host'=>'127.0.0.1',
            'port'=>'3306',
            'user'=>'root',
            'password'=>'',
            'database'=>'teste',
            'charset'=>'utf8',
            'country'=>'Brazil',
            'echo_msg'=>false
        );

		if($database_sets!=''){
            $database_sets = (array)$database_sets;
            foreach($$database_sets_defalt as $key=>$value){
                if(!isset($database_sets[$key])){
                    $database_sets[$key] = $database_sets_defalt->{$key};
                }
            }
            $this->database_sets = (object)$database_sets;
		}else{
			$this->database_sets = $database_sets_defalt;
        }
       
		$this->table = '';
        $this->connect();
    }

    private function connect(){
        $connect = mysqli_connect($this->database_sets->host,$this->database_sets->user,$this->database_sets->password,$this->database_sets->database,$this->database_sets->port);
        $this->connect = $connect;
        if(isset($this->database_sets->charset)){
            $this->connect->set_charset($this->database_sets->charset);
        }
        
        if(isset($this->database_sets->country)&&$this->database_sets->country=='Brazil'){
            //Seta o tipo de Carcateres do Banco de Dados para UTF8
            $this->query("SET time_zone = '-3:00'");
            $this->query('SET sql_mode = ""');
            $this->query('SET NAMES utf8');
        }
      
        return $connect;
    }

    function statusMsg($status){
        return $status ? '<span style=" color:green">OK</span>':'<span style="color:red">ERRO</span>';
    }

    function trans_start(){
        $status = mysqli_begin_transaction($this->connect,MYSQLI_TRANS_START_READ_WRITE);
        if($this->database_sets->echo_msg){
            echo'Início da transação Status: '.$this->statusMsg($status).'<br>';
        }else{
            return $status;
        }
       
    }

    function trans_commit(){
        $status = mysqli_commit($this->connect);
        if($this->database_sets->echo_msg){
            echo'Commit Transação: '.$this->statusMsg($status).'<br>';
        }else{
            return $status;
        }
        
    }

    function trans_rollback(){
        $status = mysqli_rollback($this->connect);
        if($this->database_sets->echo_msg){
            echo'Voltar Transação: '.$this->statusMsg($status).'<br>';
        }else{
            return $status;
        }
       
    }

    function close(){
        $status = mysqli_close($this->connect);
        if($this->database_sets->echo_msg){
            echo'Fechar Conexão: '.$this->statusMsg($status).'<br>';
        }else{
            return $status;
        }
        
    }

    function affected_rows(){
        return mysqli_affected_rows($this->connect);
    }

    function qtdLinhasTabela($tabela){
        $result = $this->query('SELECT count(*) as total FROM '.$tabela);
        $qtdlinhas = mysqli_fetch_assoc($result)['total'];
        return $qtdlinhas; 
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

        $query = "INSERT INTO ".$tabela;

        $campos =' (';
        foreach ($dados[0] as $key=>$value){
            $campos.= $key.','; 
        }
        $campos = $this->removeVirgula($campos);
        $campos.=') ';

        $valores = '';
        foreach($dados as $key=>$value){
            $valores .=' (';
            foreach ($value as $key=>$value){                
                $valores.= $this->isNumeric($value).',';
            }
            $valores = $this->removeVirgula($valores);
            $valores.='),';
        }
        $valores = $this->removeVirgula($valores);
        
        $query.= $campos.'VALUES'.$valores;

        $result = $this->query($query);
        return $result==false ? $result : mysqli_insert_id($this->connect);
	}

	/**
	 * @param array $campoWhere Array com o campo e valor que deve ser usado para buscar os dados no banco, se vazio retorna toda a tabela
	 * @param string $tabela tabela que comtem os dados que devem ser selecionados
	 * @return array Retorna um Array de objetos
	 */
	function select($campoWhere='',$tabela=''){
		if($tabela==''){
			$tabela = $this->table;
		}
		$this->verificaTabela($tabela);

        $query = "SELECT * FROM ".$tabela;

        if(is_array($campoWhere)){
            $query.=" WHERE ".$this->make_where($campoWhere);
        }elseif(is_string($campoWhere)&&$campoWhere!=''){
            $query.=" WHERE ".$campoWhere;
        }

        $result = $this->query($query);
        if(!$result){
            return $result;
		}
		
		while($dados = mysqli_fetch_assoc($result)){
			$array[] = (object)$dados;
		}

        return isset($array) ? $array : array();
	}
	
	/**
	 * Atualiza atravez do $campoWhere os dados de uma determianda linha no banco de dados
	 * @param array $dados Colunas e valores que devem ser atualizados
     * @param array $campoWhere Coluna e valor que deve ser utilizado no WHERE
	 * @param string $tabela tabela que comtem os dados que devem ser atualizados
	 * @return bool
	 */
    function update($dados,$campoWhere,$tabela=''){
		if($tabela==''){
			$tabela = $this->table;
		}
		$this->verificaTabela($tabela);

        $query = "UPDATE ".$tabela." SET ";

        $valores ='';
        $campoWhereS = array_keys($campoWhere)[0];

        foreach ($dados as $key=>$value){
            if($key!=$campoWhereS){
                $valores.= $key.'='.$this->isNumeric($value).',';
            }
        }
        $valores = $this->removeVirgula($valores);

        $query.= $valores." WHERE ".$campoWhereS." = ".$this->isNumeric($campoWhere[$campoWhereS]);

        $result = $this->query($query);
        return $result;

    }

    function delete($campoWhere,$soft='',$tabela=''){
		if($tabela==''){
			$tabela = $this->table;
		}
		$this->verificaTabela($tabela);

        if($soft!=''){
            $query = "UPDATE ".$tabela." SET ".$soft."=0 ";
        }else{
            $query = "DELETE FROM ".$tabela;
        }
        $query.= " WHERE ".array_keys($campoWhere)[0]." = ".$this->isNumeric($campoWhere[array_keys($campoWhere)[0]]);

        $result = $this->query($query);
        return $result;
    }

    /**
     * Verifica se a String é numeral, se não for adiciona "" para INSERT e SELECT
     * @param string $value = 'Maria' || '1' || 'NOW()'
     * @return string '"Maria"' || '1' || 'NOW()'
     */
    private function isNumeric($value){
        if(!is_numeric($value)&&!is_null($value)&&$value!='NOW()'){
            $value = "'".$value."'";
        }
        return $value;
    }
    
    /**
     * Remove o ultimo caracter de uma string, utilizado para remover a virgula dos INSERT
     * @param string $string = 'Virgilio,'
     * @return string 'Virgilio'
     */
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
            $campo = $keys[0];
            $operador = isset($keys[1])&&is_array($keys) ? ' '.$keys[1].' ' : ' = ' ;

            if($where!=''){
                $where.= ' AND ';
            }

            if($value!='NULL'){
                $where .= $campo.$operador.$this->isNumeric($value);
            }else{
                $where .= '( '.$campo.($operador==' = ' ? ' IS ' : ' IS NOT ').$value.' )';
            }
            
        }
        
        return $where;
	}
	
	function verificaTabela($tabela){
		if($this->table==''&&$tabela==''){
			throw new Exception('A tabela para executar a operação não está setada, utilize set_table("usuarios").');
		}
	}
}