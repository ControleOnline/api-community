<?php
namespace App\Library\Utils;
/**
 * Classe com Funções para Manipulação de Strings e Arrays
 */
class Str
{
    private static function retira_acentos($texto)
    {
        $array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç", "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç");
        $array2 = array("a", "a", "a", "a", "a", "e", "e", "e", "e", "i", "i", "i", "i", "o", "o", "o", "o", "o", "u", "u", "u", "u", "c", "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C");
        return str_replace($array1, $array2, $texto);
    }
    /**
     * Remove caracteres especiais, retira acentos, troca espaços para '-' entre outras coisas mais
     * Ideal para ser utilizado em URLs e nomes de arquivos
     *
     * @param $String
     * @return string
     */
    public static function removeSpecial($String): string
    {
        $String = mb_strtolower($String, 'UTF-8');
        $String = self::retira_acentos($String);
        $Separador = "-";
        $String = trim($String); //Removendo espaços do inicio e do fim da string
        $String = strip_tags($String); //Retirando as tags HTML e PHP da string
        $String = preg_replace("/[[:space:]]/i", $Separador, $String); //Substituindo todos os espaços por $Separador
        $String = preg_replace("/[çÇ]/i", "c", $String); //Substituindo caracteres especiais pela letra respectiva
        $String_tmp = preg_replace("/[ª]/i", "a", $String);
        $String = str_ireplace("aa", "a", $String_tmp);
        $String = preg_replace("/[áÁäÄàÀãÃâÂ]/i", "a", $String);
        $String = preg_replace("/[éÉëËèÈêÊ]/i", "e", $String);
        $String = preg_replace("/[íÍïÏìÌîÎ]/i", "i", $String);
        $String = preg_replace("/[óÓöÖòÒõÕôÔ]/i", "o", $String);
        $String = preg_replace("/[úÚüÜùÙûÛ]/i", "u", $String);
        $String = preg_replace("/(\()|(\))/i", $Separador, $String); //Substituindo outros caracteres por "$Separador"
        $String = preg_replace("/(\/)|(\\\)/i", $Separador, $String);
        $String = preg_replace("/(\[)|(\])/i", $Separador, $String);
        $String = preg_replace("/[@#\$%&\*\+=\|º]/i", $Separador, $String);
        $String = preg_replace("/[;:'\"<>,\.?!_-]/i", $Separador, $String);
        $String = preg_replace("/[“”]/i", $Separador, $String);
        $String = preg_replace("/(ª)+/i", $Separador, $String);
        $String = preg_replace("/[`´~^°]/i", $Separador, $String);
        $String = preg_replace("/[^a-z0-9_\s-]/", $Separador, $String); // Remove carácteres indesejáveis que não estão no padrão
        $String = preg_replace("/($Separador)+/i", $Separador, $String); //Removendo o excesso de "$Separador" por apenas um
        $String = substr($String, 0, 100); //Quebrando a string para um tamanho pré-definido
        return preg_replace("/(^($Separador)+)|(($Separador)+$)/i", "", $String);
    }
}
