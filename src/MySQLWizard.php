<?php
namespace MySQLWizard;

class MySQLWizard
{
    /**
     * Construtor da Classe
     * 
     * @param array $database_sets Array com os dados de conexão com o banco;
     * 
     * @return void
     */ 
    public function __construct($database_sets=array())
    {
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

        if (empty($database_sets)) {
            $database_sets = (array)$database_sets;
            foreach ($database_sets_defalt as $key=>$value) {
                if (!isset($database_sets[$key])) {
                    $database_sets[$key] = $database_sets_defalt->{$key};
                }
            }
            $this->database_sets = (object)$database_sets;
        } else {
            $this->database_sets = $database_sets_defalt;
        }
       
        $this->table = '';
        $this->_connect();
    }

    /**
     * Cria a instancia de conexão com o banco de dados
     *
     * @return object
     */
    private function _connect()
    {
        $connect = mysqli_connect(
            $this->database_sets->host,
            $this->database_sets->user,
            $this->database_sets->password,
            $this->database_sets->database,
            $this->database_sets->port
        );

        $this->connect = $connect;
        if (isset($this->database_sets->charset)) {
            $this->connect->set_charset($this->database_sets->charset);
        }

        if (isset($this->database_sets->country) && $this->database_sets->country=='Brazil') {
            //Seta o tipo de Carcateres do Banco de Dados para UTF8
            $this->query("SET time_zone = '-3:00'");
            $this->query('SET sql_mode = ""');
            $this->query('SET NAMES utf8');
        }
      
        return $connect;
    }

    /**
     * Retorna o status para a mensagem de execução da transaction
     *
     * @param bool $status Status da etapa da transaction
     * 
     * @return string
     */
    private function _statusMsg($status)
    {
        return $status ? '<span style=" color:green">OK</span>':'<span style="color:red">ERRO</span>';
    }

    /**
     * Inicia uma Transaction
     *
     * @return void
     */
    public function trans_start()
    {
        $status = mysqli_begin_transaction(
            $this->connect,
            MYSQLI_TRANS_START_READ_WRITE
        );

        if ($this->database_sets->echo_msg) {
            echo'Início da transação Status: '.$this->_statusMsg($status).'<br>';
        } else {
            return $status;
        }
       
    }

    /**
     * Realiza o Commit de uma transaction
     *
     * @return void
     */
    public function trans_commit()
    {
        $status = mysqli_commit($this->connect);
        if ($this->database_sets->echo_msg) {
            echo'Commit Transação: '.$this->_statusMsg($status).'<br>';
        } else {
            return $status;
        }
        
    }

    /**
     * Realiza o Rollback de uma transaction
     *
     * @return void
     */
    public function trans_rollback()
    {
        $status = mysqli_rollback($this->connect);
        if ($this->database_sets->echo_msg) {
            echo'Voltar Transação: '.$this->_statusMsg($status).'<br>';
        } else {
            return $status;
        }
       
    }

    /**
     * Fecja a conexão com o banco de dados
     *
     * @return bool
     */
    public function close()
    {
        $status = mysqli_close($this->connect);
        if ($this->database_sets->echo_msg) {
            echo'Fechar Conexão: '.$this->_statusMsg($status).'<br>';
        } else {
            return $status;
        }
        
    }

    /**
     * Retorna a quantidade de linhas afetadas
     *
     * @return int
     */
    public function affected_rows()
    {
        return mysqli_affected_rows($this->connect);
    }

    /**
     * Retorna a quantidade de resgitros da tabela
     * Alias para qtdLinhasTabela()
     * 
     * @param string $table Tabela onde o comando sera executado
     *
     * @see $this->qtdLinhasTabela()
     * 
     * @return int
     */
    public function countRows($table)
    {
        return $this->qtdLinhasTabela($table);
    }
    
    /**
     * Retorna a quantidade de registros na tabela
     *
     * @param string $tabela Tabela onde o comando sera executado
     * 
     * @return int
     */
    public function qtdLinhasTabela($tabela = '')
    {
        $this->_verificaTabela($tabela);

        $result = $this->query('SELECT count(*) as total FROM '.$tabela);
        
        if (!$result) {
            throw new Exception('Erro ao obter o total de registros a tabela');
        }

        $qtdlinhas = mysqli_fetch_assoc($result)['total'];
        
        return $qtdlinhas; 
    }

    /**
     * Executa as Querys montadas pela Classe
     *
     * @param string $query Query que sera executada no Banco de Dados
     * 
     * @return void
     */
    public function query(string $query)
    {
        $result = mysqli_query($this->connect, $query);
        return $result;
    }
    
    /**
     * Configura tabela onde os comandos serão executados
     *
     * @param string $tabela Tabela que a classe ira interagir
     * 
     * @return void
     */
    public function set_table($tabela)
    {
        $this->table = $tabela;
    }

    /**
     * Insere uma linha na tabela do Banco
     * 
     * @param array  $dados  Array com os dados a serem inseridos no Banco
     * @param string $tabela tabela em que os dados serão inseridos
     * 
     * @return bool|int
     */
    function insert($dados,$tabela='')
    {

        if ($tabela=='') {
            $tabela = $this->table;
        }
        $this->_verificaTabela($tabela);
        
        $query = "INSERT INTO ".$tabela;

        $campos =' (';
        $valores =' (';

        foreach ($dados as $key=>$value) {
            $campos.= $key.',';
            $valores.= $this->_isNumeric($value).',';
        }
        $campos = $this->_removeVirgula($campos);
        $valores = $this->_removeVirgula($valores);

        $campos.=') ';
        $valores.=') ';
        $query.= $campos.'VALUES'.$valores;

        $result = $this->query($query);
        return $result==false ? $result : mysqli_insert_id($this->connect);

    }

    /**
     * Insere diversas linhas na tabela do banco de dados
     * 
     * @param array  $dados  Possui os dados das linhas que devem ser 
     *                       inseridas na tabela do banco de dados;
     * @param string $tabela Tabela em que os dados serão inseridos
     * 
     * @return bool|int
     */
    public function insert_batch($dados,$tabela='')
    {

        if ($tabela=='') {
            $tabela = $this->table;
        }
        $this->_verificaTabela($tabela);

        $query = "INSERT INTO ".$tabela;

        $campos =' (';
        foreach ($dados[0] as $key=>$value) {
            $campos.= $key.','; 
        }
        $campos = $this->_removeVirgula($campos);
        $campos.=') ';

        $valores = '';
        foreach ($dados as $key=>$value) {
            $valores .=' (';
            foreach ($value as $key=>$value) {                
                $valores.= $this->_isNumeric($value).',';
            }
            $valores = $this->_removeVirgula($valores);
            $valores.='),';
        }
        $valores = $this->_removeVirgula($valores);
        
        $query.= $campos.'VALUES'.$valores;

        $result = $this->query($query);
        return $result==false ? $result : mysqli_insert_id($this->connect);
    }

    /**
     * Realiza o select no banco de dados e retorna uma lista com os registros
     * 
     * @param array  $campoWhere Array com o campo e valor que deve ser usado para 
     *                           buscar os dados no banco, se vazio retorna 
     *                           todos os registros da tabela
     * @param string $tabela     Tabela que sera realizado o SELECT
     * 
     * @return array Retorna um Array de objetos
     */
    function select($campoWhere='',$tabela='')
    {
        if ($tabela=='') {
            $tabela = $this->table;
        }
        $this->_verificaTabela($tabela);

        $query = "SELECT * FROM ".$tabela;

        if (is_array($campoWhere)) {
            $query.=" WHERE ".$this->_makeWhere($campoWhere);
        } elseif (is_string($campoWhere)&&$campoWhere!='') {
            $query.=" WHERE ".$campoWhere;
        }

        $result = $this->query($query);
        if (!$result) {
            return $result;
        }
        
        while ($dados = mysqli_fetch_assoc($result)) {
            $array[] = (object)$dados;
        }

        return isset($array) ? $array : array();
    }
    
    /**
     * Atualiza atravez do $campoWhere os dados de uma determianda linha no banco
     * 
     * @param array  $dados  Colunas e valores que devem ser atualizados
     * @param array  $where  Coluna e valor que deve ser utilizado no WHERE
     * @param string $tabela Tabela que comtem os dados que devem ser atualizados
     * 
     * @return bool
     */
    function update($dados,$where,$tabela='')
    {
        if ($tabela=='') {
            $tabela = $this->table;
        }
        $this->_verificaTabela($tabela);

        $query = "UPDATE ".$tabela." SET ";

        /**
         * Armazena a String do SET
         * 
         * @var string
         */
        $valores ='';
        $fristKey = array_keys($where)[0];

        foreach ($dados as $key=>$value) {
            if ($key!=$fristKey) {
                $valores.= $key.'='.$this->_isNumeric($value).',';
            }
        }

        $valores = $this->_removeVirgula($valores);
        $query.= $valores;

        $query.= " WHERE ".$fristKey." = ".$this->_isNumeric($where[$fristKey]);

        $result = $this->query($query);
        return $result;

    }

    /**
     * Desabilita ou exclui um registro da tabela
     *
     * @param array  $campoWhere Array com os campos para a decisão da exclusão
     * @param string $soft       Configura se é um Soft Delete
     * @param string $tabela     Tabela onde esta o registro
     * 
     * @return bool
     */
    public function delete($campoWhere,$soft='',$tabela='')
    {
        if ($tabela=='') {
            $tabela = $this->table;
        }
        $this->_verificaTabela($tabela);

        if ($soft!='') {
            $query = "UPDATE ".$tabela." SET ".$soft."=0 ";
        } else {
            $query = "DELETE FROM ".$tabela;
        }

        $keys = array_keys($campoWhere);
        $query.= " WHERE ".$keys[0]." = ".$this->_isNumeric($campoWhere[$keys[0]]);

        $result = $this->query($query);
        return $result;
    }

    /**
     * Verifica se a String é numeral, se não for adiciona "" para INSERT e SELECT
     * 
     * @param string $value = 'Maria' || '1' || 'NOW()'
     * 
     * @return string '"Maria"' || '1' || 'NOW()'
     */
    private function _isNumeric($value)
    {
        if (!is_numeric($value) && !is_null($value) && $value!='NOW()') {
            $value = "'".$value."'";
        }
        return $value;
    }
    
    /**
     * Remove o ultimo caracter de uma string, 
     * utilizado para remover a virgula dos INSERT
     * 
     * @param string $string = 'Virgilio,'
     * 
     * @return string 'Virgilio'
     */
    private function _removeVirgula($string)
    {
        return substr($string, 0, strlen($string)-1);
    }

    /**
     * Cria e retorna a parte do WHERE de um SELECT
     *
     * @param array $array Array com os campos e regras do SELECT
     * 
     * @return string
     */
    private function _makeWhere($array)
    {
        if (!is_array($array)) {
            return false;
        }

        /**
         * Armazrna a string que sera retornada
         * 
         * @var string
         */
        $where = '';

        foreach ($array as $key=>$value) {
            $keys = explode(' ', $key);
            $campo = $keys[0];
            $operador = (isset($keys[1]) && is_array($keys)) ? ' '.$keys[1].' ' : ' = ' ;

            if ($where!='') {
                $where.= ' AND ';
            }

            if ($value!='NULL') {
                $where .= $campo.$operador.$this->_isNumeric($value);
            } else {
                $where .= '( '.$campo.($operador==' = ' ? ' IS ' : ' IS NOT ').$value.' )';
            }
            
        }
        
        return $where;
    }
    
    /**
     * Verifica se a Tabela esta configurada
     *
     * @param string $tabela Tabela
     * 
     * @return void
     */
    private function _verificaTabela($tabela)
    {
        if ($this->table=='' && $tabela=='') {
            throw new Exception('A tabela para executar a operação não está setada, utilize set_table("usuarios").');
        }
    }
}