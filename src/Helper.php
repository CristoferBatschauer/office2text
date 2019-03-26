<?php

namespace nsbr;
/**
 * Helper functions
 */
class Helper {

    private static $publicKey = 'awevas14525!@#$nmkmcHNHATGokmg83bnck,!@';
    private static $feriados;
    private static $jsonConfigDefault = [
        'grid' => 'col-sm-6',
        'type' => 'text',
        'class' => '',
        'ro' => 'false',
        'tip' => ''
    ];


    public function __construct() {

    }
    

    public static function formatDate($data, $escolha = 'arrumar', $datahora = false, $alterarTimeZone = false) {
        //2017-05-04T02:59:59.000Z
        //$data = '2017-05-08T19:20:34-00:00';
        /*
         * TimeZone:
         * No banco de dados, esta sendo salvo horario de brasilia. Se vier do banco, precisa acrescentar 3 horas
         * 
         *          
         */

        if ($data !== 'NOW') {
            if (strlen($data) < 6) {
                return false;
            }
            $data = str_replace('"', '', $data);
            $t = explode('.', $data);
            $data = str_replace("T", " ", $t[0]);
            $hora = '12:00:00';
            $t = explode(' ', $data);
            if (count($t) > 1) {
                $data = $t[0];
                $hora = $t[1];
            }
            $c = (string) substr($data, 2, 1);
            if (!is_numeric($c)) {
                $data = substr($data, 6, 4) . '-' . substr($data, 3, 2) . '-' . substr($data, 0, 2);
            }
            $data = $data . 'T' . $hora . '-00:00';
            //Log::logTxt('debug', $data);
        }

        try {
            $date = new DateTime($data);
            if ($alterarTimeZone) {
                $date->setTimezone(new DateTimeZone('America/Sao_Paulo'));
            } else {
                //$date->setTimezone(new DateTimeZone('+0300'));
                //$date->setTimezone(new DateTimeZone());
            }
        } catch (Exception $e) {
            $backtrace = debug_backtrace();
            $origem = $backtrace[0]['file'] . ' [' . $backtrace[1]['class'] . '::' . $backtrace[1]['function'] . ' (' . $backtrace[0]['line'] . ')]';
            Log::logTxt('debug', 'ERROR DATE: ' . $e->getMessage() . '||' . $origem . __METHOD__ . __LINE__);
            return false;
        }
        if ($escolha === 'arrumar') { // Consertar o que vem form para inserir no BD
            if ($datahora) {
                return $date->format('Y-m-d H:i:s');
                //return date('Y-m-d h:i:s', strtotime($data));
            } else {
                return $date->format('Y-m-d');
                //return date('Y-m-d', strtotime($data));
            }
        } elseif ($escolha === "mostrar") { // Arrumar o que vem do Banco para imprimir na data pt-BR
            if ($datahora) {
                return $date->format('d/m/Y H:i:s');
                //return date('d/m/Y h:i:s', strtotime($data));
            } else {
                return $date->format('d/m/Y');
                //return date('d/m/Y', strtotime($data));
            }
        } elseif ($escolha === 'extenso') {
            return strftime('%d de %B de %Y', $date->getTimestamp());
        } else {
            return $date->format('Y-m-d');
            //return date('Y-m-d', strtotime($data));
        }
    }

    public static function formatFone($fone) {
        $fone = Helper::parseInt($fone);
        $ddd = '(' . substr($fone, 0, 2) . ') ';
        $fone = substr($fone, 2, strlen($fone) - 2);
        $out = $ddd . substr($fone, 0, 4) . substr($fone, 4, 8);
        if (strlen($fone) === 9) { // nono digito
            $out = $ddd . substr($fone, 0, 5) . substr($fone, 5, 9);
        }
        return $out;
    }

    public static function formatCep($cep) {
        $cep = Helper::parseInt($cep);
        return substr($cep, 0, 5) . '-' . substr($cep, 5, 8);
    }

    public static function decimalFormat($var) {
        if (stripos($var, ',') > -1) { // se achar virgula, veio da view, com formato. da base, nao vem virgula
            $var = Helper::parseInt($var);
            $var = substr($var, 0, strlen($var) - 2) . "." . substr($var, strlen($var) - 2, 2);
        }
        return $var;
    }

    public static function parseInt($var) {
        return preg_replace("/[^0-9]/", "", $var);
    }

    public static function dateToMktime($date = false) {
        if (!$date) {
            $date = time();
            return $date;
        }
        $date = Helper::formatDate($date, 'arrumar', true);
        $dt = DateTime::createFromFormat('Y-m-d H:i:s', $date);
        $timestamp = $dt->getTimestamp();
        return $timestamp;
    }

    public static function dateMoreDays($date, $days, $operation = '+') {
        $d = Helper::formatDate($date);
        $d = date_parse($d);
        $d = mktime($d['hour'], $d['minute'], $d['second'], $d['month'], (($operation === '+') ? $d['day'] + $days : $d['day'] - $days), $d['year']);
        $date = date('Y-m-d', $d);
        return $date;
    }

    public static function print_rr($var, $dump = null) {
        if (is_array($var) || is_object($var)) {
            echo "<pre>";
            if ($dump) {
                var_dump($var);
            } else {
                print_r($var);
            }
            echo "</pre>";
        }
    }

    public static function name2CamelCase($string, $prefixo = false) {
        $prefixo = array('mem_', 'sis_', 'anz_', 'aux_', 'app_');
        if (is_array($string)) {
            foreach ($string as $key => $value) {
                $out[Helper::name2CamelCase($key)] = $value;
            }
            return $out;
        }
        if (is_array($prefixo)) {
            foreach ($prefixo as $val) {
                $string = str_replace($val, "", $string);
            }
        }
        // new 26/02/2018
        $string = str_replace('_', ' ', $string);
        $out = str_replace(' ', '', ucwords($string));
        $out{0} = mb_strtolower($out{0});
        return $out;
    }

    public static function reverteName2CamelCase($string) {
        $out = '';
        for ($i = 0; $i < strlen($string); $i++) {
            if ($string[$i] === mb_strtoupper($string[$i]) && $string[$i] !== '.') {
                $out .= (($i > 0) ? '_' : '');
                $string[$i] = mb_strtolower($string[$i]);
            }
            $out .= $string[$i];
        }
        return $out;
    }

    private static function createTreeDir($filename) {
        $dir = str_replace('library', '', SistemaLibrary::getPath());
        $path = str_replace('/', DIRECTORY_SEPARATOR, $filename);
        $path = str_replace($dir, '', $path);
        $parts = explode(DIRECTORY_SEPARATOR, $path);
        $file = array_pop($parts);
        foreach ($parts as $part) {
            if (!is_dir($dir .= "/$part")) {
                mkdir($dir, 0777) or die('Can\'t create dir: ' . $dir);
            }
        }
        return (object) ['path' => $dir, 'name' => $file];
    }

    public static function saveFile($filename, $name = false, $template = '<?=php Header("Location:/")', $mode = "w+") {
        $filename = $filename . (($name) ? '/' . $name : '');
        $file = Helper::createTreeDir($filename);
        if (file_exists($filename) && $mode !== 'SOBREPOR') {
            $file->name = '__NEW__' . $file->name;
        }
        $save = str_replace('/', DIRECTORY_SEPARATOR, $file->path . DIRECTORY_SEPARATOR . $file->name);
        unset($filename);
        file_put_contents($save, $template);
        return file_exists($save);
    }

    /**
     * 
     * @param type $filename
     * @param type $apagarDiretorio
     * @param type $trash
     * @return boolean
     */
    public static function deleteFile($filename, $apagarDiretorio = false) {
        $filename = str_replace('/', DIRECTORY_SEPARATOR, $filename);
        $t = explode(DIRECTORY_SEPARATOR, $filename);
        $file = $t[count($t) - 1];

        if (is_dir($filename)) {
            $dir = dir($filename);
            while ($arquivo = $dir->read()) {
                if ($arquivo != '.' && $arquivo != '..') {
                    Helper::deleteFile($filename . '/' . $arquivo, false, $trash);
                }
            }
            $dir->close();
            if ($apagarDiretorio) {
                rmdir($filename);
            }
        } else {
            if (file_exists($filename)) {
                $logName = ' removido definitivamente';
                unlink($filename);
                $log = $filename . $logName;
            }
        }
        if (!file_exists($filename)) {
            return false;
        }
    }

    public static function deleteDir($dirPath) {
        try {
            if (!is_dir($dirPath)) {
                throw new InvalidArgumentException("$dirPath must be a directory");
            }
            if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
                $dirPath .= '/';
            }
            $files = glob($dirPath . '*', GLOB_MARK);
            foreach ($files as $file) {
                if (is_dir($file)) {
                    Helper::deleteDir($file);
                } else {
                    unlink($file);
                }
            }
            rmdir($dirPath);
        } catch (Exception $exc) {

        }
    }

    public static function crypto($action, $string) {
        $output = false;

        $encrypt_method = "AES-256-CBC";
        $secret_key = Helper::$publicKey;
        $secret_iv = $secret_key . '_IV';

        // hash
        $key = hash('sha256', $secret_key);

        // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
        $iv = substr(hash('sha256', $secret_iv), 0, 16);

        if ($action == 'encrypt') {
            $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
            $output = base64_encode($output);
        } else if ($action == 'decrypt') {
            $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
        }

        return $output;
    }

    public static function codifica($texto, $iv_len = 16) {
        return Helper::crypto('encrypt', $texto);
    }

    public static function decodifica($Enc_Texto, $iv_len = 16) {
        return Helper::crypto('decrypt', $Enc_Texto);
    }

    public static function escreveTemplate($template, $array) {
        foreach ($array as $key => $value) {
            $template = str_replace('%' . $key . '%', $value, $template);
        }
        return $template;
    }

    public static function getDiaSemana($data) {
        $diaSemana = (int) date('N', strtotime($data));
        $nomesSemana = array("", "Segunda", "Terça", "Quarta", "Quinta", "Sexta", "Sábado", 'Domingo');
        return $nomesSemana[$diaSemana];
    }

    // Define uma função que poderá ser usada para validar e-mails usando regexp
    public static function validaEmail($email) {
        return
        $er = "/^(([0-9a-zA-Z]+[-._+&])*[0-9a-zA-Z]+@([-0-9a-zA-Z]+[.])+[a-zA-Z]{2,6}){0,1}$/";
        if (preg_match($er, $email)) {
            return true;
        } else {
            return false;
        }
    }

    public static function sanitize($str) {
        return str_replace(" ", "_", preg_replace("/&([a-z])[a-z]+;/i", "$1", htmlentities(trim($str))));
    }

    public static function formatNumber($number, $sinalNoFim = false, $prefixo = 'R$', $color = true) {
        $out = $prefixo . number_format($number, 2, ',', '.');
        if ($sinalNoFim) {
            $out = $prefixo . number_format(abs($number), 2, ',', '.') . (($number < 0) ? '-' : '');
        }
        if ($color && $number < 0) {
            $out = '<span class="text-red">' . $out . '</span>';
        }

        return $out;
    }

    public static function formatCpfCnpj($var) {
        $var = Helper::parseInt($var);
        if (strlen($var) === 11) { // cpf
            $out = substr($var, 0, 3) . '.' . substr($var, 3, 3) . '.' . substr($var, 6, 3) . '-' . substr($var, 9, 2);
        } else if (strlen($var) === 14) { // cnpj
            $out = substr($var, 0, 2) . '.' . substr($var, 2, 3) . '.' . substr($var, 5, 3) . '/' . substr($var, 8, 4) . '-' . substr($var, 12, 2);
        } else {
            $out = $var;
        }
        return $out;
    }

    public static function upperByReference(&$var) {
        $var = mb_strtoupper((string) $var, 'UTF-8');
    }

    public static function upper($dados) {
        if (is_array($dados)) {
            foreach ($dados as $key => $value) {
                if (is_array($value)) {
                    continue;
                } else {
                    $dados[$key] = mb_strtoupper($value, 'UTF-8');
                }
            }
        } else {
            $dados = mb_strtoupper($dados, 'UTF-8');
        }
        return $dados;
    }

    public static function lower(&$dados) {
        if (is_array($dados)) {
            foreach ($dados as $key => $value) {
                if (is_array($value)) {
                    continue;
                } else {
                    $dados[$key] = mb_strtolower($value, 'UTF-8');
                }
            }
        } else {
            $dados = mb_strtolower($dados, 'UTF-8');
        }
        //return $dados;
    }

    public static function thumbsOnName($filename) {
        $fileOriginal = $filename;
        $filename = str_replace(DIRECTORY_SEPARATOR, '/', $filename);
        $t = explode('/', $filename);
        if (count($t) > 1) {
            $filename = $t[count($t) - 1];
            unset($t[count($t) - 1]);
            return implode('/', $t) . '/thumbs/' . $filename;
        } else {
            return 'thumbs/' . $fileOriginal;
        }
    }

    public static function compareString($str1, $str2, $case = false) {
        if (!$case) {
            Helper::upperByReference($str1);
            Helper::upperByReference($str2);
        }
        return ($str1 === $str2);
    }

    public static function validaCpfCnpj($val) {
        $val = (string) Helper::parseInt($val);
        if (strlen($val) === 11) {
            return Helper::validaCPF($val);
        }
        if (strlen($val) === 14) {
            return Helper::validaCnpj($val);
        }
        return 'Preencha corretamente CPF/CNPJ';
    }

    private static function validaCPF($cpf = null) {
        // Verifica se um número foi informado
        if (empty($cpf) || $cpf === '') {
            return 'CPF Inválido: Vazio';
        }
        // Elimina possivel mascara
        $cpf = Helper::parseInt($cpf);
        // Verifica se o numero de digitos informados é igual a 11 
        if (strlen($cpf) != 11) {
            return 'CPF Inválido: Menor que 11 digitos';
        }
        // Verifica se nenhuma das sequências invalidas abaixo 
        // foi digitada. Caso afirmativo, retorna falso
        else if ($cpf == '00000000000' ||
            $cpf == '11111111111' ||
            $cpf == '22222222222' ||
            $cpf == '33333333333' ||
            $cpf == '44444444444' ||
            $cpf == '55555555555' ||
            $cpf == '66666666666' ||
            $cpf == '77777777777' ||
            $cpf == '88888888888' ||
            $cpf == '99999999999') {
            return 'CPF Inválido: Número Sequencial';
            // Calcula os digitos verificadores para verificar se o
            // CPF é válido
    } else {

        for ($t = 9; $t < 11; $t++) {

            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf{$c} * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf{$c} != $d) {
                return 'CPF Inválido: Digito verificador não é válido';
            }
        }
        return true;
    }
}

private static function validaCnpj($cnpj = null) {
    $cnpj = Helper::parseInt($cnpj);
    if (empty($cnpj) || $cnpj === '') {
        return 'CNPJ Inválido: Vazio';
    }
    if (strlen($cnpj) != 14) {
        return 'CNPJ Inválido: Menor que 14 digitos';
    }
    if ($cnpj === '00000000000000') {
        return 'CNPJ Inválido: Número sequencial';
    }
    $cnpj = (string) $cnpj;
    $cnpj_original = $cnpj;
    $primeiros_numeros_cnpj = substr($cnpj, 0, 12);
    if (!function_exists('multiplica_cnpj')) {

        function multiplica_cnpj($cnpj, $posicao = 5) {
                // Variável para o cálculo
            $calculo = 0;
                // Laço para percorrer os item do cnpj
            for ($i = 0; $i < strlen($cnpj); $i++) {
                    // Cálculo mais posição do CNPJ * a posição
                $calculo = $calculo + ( $cnpj[$i] * $posicao );
                    // Decrementa a posição a cada volta do laço
                $posicao--;
                    // Se a posição for menor que 2, ela se torna 9
                if ($posicao < 2) {
                    $posicao = 9;
                }
            }
                // Retorna o cálculo
            return $calculo;
        }

    }

        // Faz o primeiro cálculo
    $primeiro_calculo = multiplica_cnpj($primeiros_numeros_cnpj);

        // Se o resto da divisão entre o primeiro cálculo e 11 for menor que 2, o primeiro
        // Dígito é zero (0), caso contrário é 11 - o resto da divisão entre o cálculo e 11
    $primeiro_digito = ( $primeiro_calculo % 11 ) < 2 ? 0 : 11 - ( $primeiro_calculo % 11 );

        // Concatena o primeiro dígito nos 12 primeiros números do CNPJ
        // Agora temos 13 números aqui
    $primeiros_numeros_cnpj .= $primeiro_digito;

        // O segundo cálculo é a mesma coisa do primeiro, porém, começa na posição 6
    $segundo_calculo = multiplica_cnpj($primeiros_numeros_cnpj, 6);
    $segundo_digito = ( $segundo_calculo % 11 ) < 2 ? 0 : 11 - ( $segundo_calculo % 11 );

        // Concatena o segundo dígito ao CNPJ
    $cnpj = $primeiros_numeros_cnpj . $segundo_digito;

        // Verifica se o CNPJ gerado é idêntico ao enviado
    if ($cnpj === $cnpj_original) {
        return true;
    } else {
        return 'CNPJ Inválido: Cálculo do dígito verificador inválido';
    }
}

public static function getThumbsByFilename($filename) {
    $t = explode('.', $filename);
    $extensao = Helper::upper($t[count($t) - 1]);
    switch ($extensao) {
        case 'XLSX':
        case 'XLS':
        $out = 'file-excel-o';
        break;
        case 'PDF':
        $out = 'file-pdf-o';
        break;
        case 'PNG':
        case 'JPG':
        case 'GIF':
        case 'JPEG':
        $out = 'file-image-o';
        break;
        case 'ZIP':
        $out = 'file-archive-o';
        break;
        case 'MP3':
        case 'AAC':
        $out = 'file-audio-o';
        break;
        case 'AVI':
        case 'MP4':
        $out = 'file-video-o';
        break;
        default:
        $out = 'file';
    }
    return $out;
}

public static function jsonRecebeFromView(&$dados, &$campoJson) {
    foreach ($campoJson as $cpo) {
        $dados[$cpo] = json_decode(str_replace('&#34;', '"', $dados[$cpo]), true);
    }
}


    /**
     * Método que encapsula uma chamada GET a uma url
     * @param string $url
     * @param array $params
     * @param string $method
     * @return Array
     */
    public static function curlCall($url, $params = [], $method = 'GET', $header = ['Content-Type:application/json']) {
        $time = new Eficiencia('[curlCall]' . $url);
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POST => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0', //set user agent
            CURLOPT_COOKIEFILE => "cookie.txt", //set cookie file
            CURLOPT_COOKIEJAR => "cookie.txt", //set cookie jar
            CURLOPT_RETURNTRANSFER => true, // return web page
            CURLOPT_FOLLOWLOCATION => true, // follow redirects
            CURLOPT_ENCODING => "", // handle all encodings
            CURLOPT_AUTOREFERER => true, // set referer on redirect
            CURLOPT_CONNECTTIMEOUT => 30, // timeout on connect
            CURLOPT_TIMEOUT => 15, // timeout on response
            CURLOPT_MAXREDIRS => 10, // stop after 10 redirects
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HEADER => false
        ];
        $options[CURLOPT_HTTPHEADER] = $header;
        $options[CURLOPT_VERBOSE] = true;
        switch ($method) {
            case 'POST':
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = json_encode($params);
            break;
            default:
            if (count($params) > 0) {
                $url = sprintf("%s?%s", $url, http_build_query($params));
            }
            $options[CURLOPT_URL] = $url;

            //$options[CURLOPT_POSTFIELDS] = json_encode($params);
            /*
              if (count($params) > 0) {
              $url = sprintf("%s?%s", $url, http_build_query($params));
              }
              $options[CURLOPT_URL] = $url;
             * 
             */
          }
          $ch = curl_init();
          curl_setopt_array($ch, $options);
          $content = curl_exec($ch);
        //Log::logTxt('geral', curl_getinfo($ch, CURLINFO_EFFECTIVE_URL));
        //Log::logTxt('geral', $content);
        //echo curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
          $ret = (object) [
            'content' => $content,
            'errorCode' => curl_errno($ch),
            'error' => ((curl_error($ch)) ? curl_error($ch) : false),
            'status' => curl_getinfo($ch)['http_code']
        ];
        curl_close($ch);
        $time->end(2);
        return $ret;
    }

    public static function curlTrataRetornoApi($json) {
        $dd = json_decode(json_encode(json_decode($json->content, true)));
        //return $dd;
        $dd->content = (object) Helper::name2CamelCase((array) $dd->content);
        return $dd;
    }


    /**
     * Aplica o separador correto de diretório conforme o ambiente
     * @param type $var
     */
    public static function directorySeparator(&$var) {
        str_replace('/', DIRECTORY_SEPARATOR, $var);
    }

    /**
     * Método que retorna um array com as diferenças entre dois arrays
     * @param array $arrayNew
     * @param array $arrayOld
     * @return array
     */
    public static function arrayDiff($arrayNew, $arrayOld) {
        $out = [];
        $alteradosNovo = array_diff_assoc($arrayNew, $arrayOld);
        $alteradosAntigo = array_diff_assoc($arrayOld, $arrayNew);
        unset($alteradosNovo['error']);
        //include Config::getData('path') . '/src/config/aliases_fields.php';
        if (count($alteradosNovo) > 0) {
            foreach ($alteradosNovo as $key => $value) {
                $out[] = [
                    'field' => $key,
                    'campo' => Config::getAliasesField($key),
                    'old' => $alteradosAntigo[$key],
                    'new' => $value
                ];
            }
        }
        return $out;
    }

    /**
     * Cria uma máscara para o email, com asteriscos para não exibir o email completo
     * @param type $email
     * @return type
     */
    public static function emailMask($email) {
        $m = explode('@', $email);
        for ($i = 4; $i < strlen($m[0]); $i++) {
            $m[0][$i] = '*';
        }
        for ($i = 4; $i < strlen($m[0]); $i++) {
            $m[1][$i] = '*';
        }
        return implode('@', $m);
    }

    /**
     * Método para remover os 'undefined' que o javascript insere em valores null, ou entre aspas
     */
    public static function removeUndefinedFromJavascript($dados) {
        $out = [];
        $dados = ((is_array($dados)) ? $dados : []);
        foreach ($dados as $key => $value) {
            if ($value === 'undefined' || $value === 'null') {
                continue;
            }
            $out[$key] = $value;
            $out[$key] = str_replace('NS21', '&', $out[$key]);
        }
        return $out;
    }

    public static function jsonToArrayFromView($json) {
        return json_decode(str_replace('&#34;', '"', $json), true);
    }

    public static function vencimentoMais30dias($data) {
        $d = explode('-', Helper::formatDate($data));
        $ano = $d[0];
        $mes = $d[1];
        $dia = $d[2];
        $ultimoDiaMes = date("t", mktime(0, 0, 0, $mes + 1, '01', $ano));
        if ($dia <= $ultimoDiaMes) { // para meses onde a data realmente existe
            return date('Y-m-d', mktime(0, 0, 0, $mes + 1, $dia, $ano));
        } else {
            return date('Y-m-d', mktime(0, 0, 0, $mes + 1, $ultimoDiaMes, $ano));
        }
    }

    public static function ultimoDiaMes($mes, $ano) {
        $ultdia = date("t", mktime(0, 0, 0, $mes, '01', $ano));
        return date('Y-m-d', mktime(0, 0, 0, $mes, $ultdia, $ano));
    }

    public static function trataPeriodo($periodo = false) {
        if ($periodo) {
            $_GET['periodo'] = $periodo;
        }
        // relação de periodos disponíveis
        $periodos = array();
        for ($index = 0; $index < 15; $index++) {
            $date = date('m/Y', mktime(0, 0, 0, date('m') - $index, date(15), date('Y')));
            $datekey = str_replace("/", "_", $date);
            $periodos[$datekey] = $date;
        }

        // peridoo
        if ($_GET['periodo']) {
            $t = explode('_', $_GET['periodo']);
            $ultimo_dia = date("t", mktime(0, 0, 0, $t[0], '01', $t[1]));
            $dataInicial = date('Y-m-d', mktime(0, 0, 0, $t[0], 1, $t[1]));
            $dataFinal = date('Y-m-d', mktime(0, 0, 0, $t[0], $ultimo_dia, $t[1]));
        } else {
            if ($_GET['dataInicial']) {
                $dataInicial = Helper::formatDate($_GET['dataInicial']);
                if ($_GET['dataFinal'] != '') {
                    $dataFinal = Helper::formatDate($_GET['dataFinal']);
                } else {
                    $ultimo_dia = date("t", Helper::dateToMktime($dataInicial));
                    $dataFinal = date('Y-m-d', mktime(0, 0, 0, date('m', Helper::dateToMktime($dataInicial)), $ultimo_dia, date('Y', Helper::dateToMktime($dataInicial))));
                }
            } else {
                $_GET['periodo'] = date('m') . '_' . date('Y');
                $t = explode('_', $_GET['periodo']);
                $ultimo_dia = date("t", mktime(0, 0, 0, $t[0], '01', $t[1]));
                $dataInicial = date('Y-m-d', mktime(0, 0, 0, date('m'), 1, date('Y')));
                $dataFinal = date('Y-m-d', mktime(0, 0, 0, date('m'), $ultimo_dia, date('Y')));
            }
        }
        return array(
            'ultimodia' => $ultimo_dia,
            'dataInicial' => $dataInicial,
            'dataFinal' => $dataFinal,
            'dataInicialF' => Helper::formatDate($dataInicial, 'mostrar'),
            'dataFinalF' => Helper::formatDate($dataFinal, 'mostrar'),
            'periodo' => $_GET['periodo'],
            'periodos' => $periodos,
        );
    }

    /**
     * Método para popuplar os campos extrasJSON com algum array padrão
     * @param type array $arrayFonte contendo o default
     * @param type array $arrayExtras os dados já configurados
     * @return type
     */
    public static function extrasJson($arrayFonte, $arrayExtras) {
        $configDefault = Helper::getJsonConfigDefault();
        
        if (!is_array($arrayExtras)) {
            $arrayExtras = json_decode($arrayExtras, true);
        }
        if (!is_array($arrayFonte)) {
            $arrayFonte = json_decode($arrayFonte, true);
        }

        $out = $arrayFonte;

        foreach ($arrayFonte as $key => $value) {
            // configurações
            $config[$key] = $configDefault;
            if (is_array($value)) { // se vier config, setar valores enviados
                //Log::logTxt('debug', $key);
                foreach ($configDefault as $k => $v) {
                    //Log::logTxt('debug', $k);
                    $config[$key][$k] = $value[$k]; // valor de value na chave de config
                }
            }

            // valores
            $out[$key] = $value['default'];
            if (isset($arrayExtras[$key])) {
                $out[$key] = $arrayExtras[$key];
            }
        }
        $out['config'] = $config;
        return $out;
    }

    /*
    public static function pdfFromHtmlCreate($html, $filename, $paper = ['a4', 'portrait']) {
        $h = Minify::html($html);
        $p = implode(', ', $paper);
        $dompdf = new Dompdf();
        $dompdf->setBasePath(Config::getData('pathView') . '/css');
        $dompdf->loadHtml($h);
        $dompdf->setPaper($p);
        $dompdf->render();
        $pdf = $dompdf->output();
        Helper::saveFile($filename, false, $pdf, 'SOBREPOR');

        // nome do arquivo
        $t = explode('/', $filename);
        $name = $t[count($t) - 1];
        sleep(0.1); // concorrencia disco
        if (file_exists($filename)) {
            return [
                'tmp_name' => $filename,
                'type' => 'application/pdf',
                'name' => $name
            ];
        } else {
            return false;
        }
    }
    */

    /** Valida se a data em questão é um dia útil. Faz leitura de fim de semana, e a tabela de feriados de locale
     * 
     * @param type $data
     */
    public static function isDiaUtil($date) {
        $d = Helper::formatDate($date);
        $w = date('w', Helper::dateToMktime($d));
        if ($w === '0' || $w === '6') { // se for sabado ou domingo, já foi... é não util
            return false;
        } else {
            return !Helper::isFeriado($d); // validar a tabela de feriados
        }
    }

    public static function isFeriado($date) {
        $year = explode('-', $date)[0];
        $d = Helper::formatDate($date, 'mostrar');
        $link = 'https://api.calendario.com.br/?json=true&ano=' . $year . '&token=Y3Jpc3RvZmVyLmJhdHNjaGF1ZXJAZ21haWwuY29tJmhhc2g9MTMzODY3NTU2';
        if (!Helper::$feriados) {
            Helper::$feriados = json_decode(Helper::curlCall($link)->content, true);
            Log::logTxt('feriados', Helper::$feriados);
        }
        foreach (Helper::$feriados as $item) {
            if (Helper::compareString($item['date'], $d)) {
                return true;
            }
        }
        return false;
    }

    public static function getProximoDiaUtil($date, $passado = false) {
        $switch = (($passado) ? '-' : '+');
        $amanha = Helper::dateMoreDays($date, 1, $switch);
        while (!Helper::isDiaUtil($amanha)) {
            $amanha = Helper::dateMoreDays($amanha, 1, $switch);
        }
        return $amanha;
    }

}
