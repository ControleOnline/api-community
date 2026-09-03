<?php

namespace App\Library\Utils;

class Formatter
{
    public static function document(string $cpf_cnpj): string
    {
        $documento = preg_replace("/[^0-9]/", "", $cpf_cnpj);
        $tipo_dado = null;

        if (strlen($documento) == 11) {
            $tipo_dado = "cpf";
        }

        if (strlen($documento) == 14) {
            $tipo_dado = "cnpj";
        }

        switch ($tipo_dado) {
            default:
                $cpf_cnpj_formatado = $cpf_cnpj;
            break;

            case "cpf":
                $bloco_1 = substr($documento, 0, 3);
                $bloco_2 = substr($documento, 3, 3);
                $bloco_3 = substr($documento, 6, 3);
                $dig_verificador    = substr($documento, -2);
                $cpf_cnpj_formatado = $bloco_1 . "." . $bloco_2 . "." . $bloco_3 . "-" . $dig_verificador;
            break;

            case "cnpj":
                $bloco_1 = substr($documento, 0, 2);
                $bloco_2 = substr($documento, 2, 3);
                $bloco_3 = substr($documento, 5, 3);
                $bloco_4 = substr($documento, 8, 4);
                $digito_verificador = substr($documento, -2);
                $cpf_cnpj_formatado = $bloco_1 . "." . $bloco_2 . "." . $bloco_3 . "/" . $bloco_4 . "-" . $digito_verificador;
            break;
        }

        return $cpf_cnpj_formatado;
    }

    public static function money(float $value)
    {
      return 'R$ ' . number_format($value, 2, ',', '.');
    }

    public static function mask($mask, $str): string
    {

        $str = str_replace(" ", "", $str);

        for ($i = 0; $i < strlen($str); $i++) {
            $mask[strpos($mask, "#")] = $str[$i];
        }

        return $mask;
    }
}
