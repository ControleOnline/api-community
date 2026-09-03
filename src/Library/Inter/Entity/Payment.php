<?php

namespace App\Library\Inter\Entity;

/**
 * Itau Payment Entity
 * 
 * - Tipo de pagamento escolhido pelo comprador
 * Numérico com 02 posições:
 *  • 00 para pagamento ainda não escolhido*
 *  • 01 para pagamento à vista (TEF e CDC)
 *  • 02 para boleto
 *  • 03 para cartão de crédito
 * 
 * - Situação de pagamento do pedido
 * Numérico com 02 posições:
 *  • 00 para pagamento efetuado
 *  • 01 para situação de pagamento não finalizada (tente novamente)
 *  • 02 para erro no processamento da consulta (tente novamente)
 *  • 03 para pagamento não localizado (consulta fora de prazo ou pedido não registrado no banco)
 *  • 04 para boleto emitido com sucesso
 *  • 05 para pagamento efetuado, aguardando compensação
 *  • 06 para pagamento não compensado
 *  • 07 para pagamento parcial
 */
class Payment
{
  /**
   * Response params from Itau
   *
   * @var array
   */
  private $params = [];

  public function __construct(array $params)
  {
    $this->params = $params;
  }

  public function getStatus(): string
  {
    switch ($this->params['sitPag']) {
      case '00':
        return 'paid';
      break;

      case '01':
        return 'no_finished';
      break;

      case '02':
        return 'request_error';
      break;

      case '03':
        return 'not_found';
      break;

      case '04':
        return 'created';
      break;

      case '05':
        return 'paid_waiting_compensation';
      break;

      case '06':
        return 'paid_no_compensation';
      break;

      case '07':
        return 'paid_partial';
      break;

      default:
        return 'unknow';
      break;
    }
  }

  public function hasError(): bool
  {
    switch ($this->getStatus()) {
      case 'no_finished'         :
      case 'request_error'       :
      case 'not_found'           :
      case 'paid_no_compensation':
        return true;
      break;
      default:
        return false;
      break;
    }
  }

  public function isPaid(): bool
  {
    return $this->getStatus() == 'paid';
  }

  public function getPaymentType(): string
  {
    if (!isset($this->params['tipPag']))
      return 'unknow';

    switch ($this->params['tipPag']) {
      case '00':
        return 'choose_pending';
      break;

      case '01':
        return 'cash_payment';
      break;

      case '02':
        return 'billet';
      break;

      case '03':
        return 'credit_card';
      break;

      default:
        return 'unknow';
      break;
    }
  }

  public function getAsArray(): array
  {
    return [
      'orderId'       => $this->params['Pedido'],
      'amount'        => $this->params['Valor'],
      'paymentType'   => $this->getPaymentType(),
      'paymentStatus' => $this->getStatus(),
      'paidAmount'    => $this->params['ValorPago'],
      'paymentDate'   => $this->params['dtPag'],
    ];
  }

  public function getOriginalDataArray(): array
  {
    return $this->params;
  }

  public function billetIsPaid(): bool
  {
    return $this->getPaymentType() === 'billet' && $this->isPaid();
  }
}
