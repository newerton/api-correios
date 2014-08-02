<?php

/*
 * Correios Shipping - Cálculo de Frete dos Correios
 *
 * Precisando se conectar � API dos Correios para calcular o frete
 * de sua loja? Eu tamb�m, por isto fiz esta classe. Use-a, melhore-a
 * e contribua com a comunidade!
 *
 * @author Gustavo H. Mascarenhas Machado <guhemama@gmail.com>
 * @copyright Gustavo H. Mascarenhas Machado
 * @version	1.0 07/09/2011
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software Foundation,
 * Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA
 */

class CorreiosShipping
{
    /*
     * URL de consulta
     * @const string
     */

    const URL = 'http://ws.correios.com.br/calculador/CalcPrecoPrazo.asmx?WSDL';

    /*
     * C�digo administrativo para acesso ao webservice
     * @var string
     */

    private $authCode;

    /*
     * Senha para acesso ao webservice
     * @var string
     */
    private $authPass;

    /*
     * Servi�o
     * @var string
     */
    private $service;

    /*
     * CEP de origem
     * @var string
     */
    private $zipFrom;

    /*
     * CEP de destino
     * @var string
     */
    private $zipTo;

    /*
     * Peso, em quilogramas
     * @var float
     */
    private $itemWeight;

    /*
     * Formato da encomenda
     * 1 - Caixa/pacote
     * 2 - Rolo/prisma
     * @var int
     */
    private $itemForm;

    /*
     * Comprimento do item em cent�metros
     * @var int
     */
    private $itemLength;

    /*
     * Altura do item em cent�metros
     * @var int
     */
    private $itemHeight;

    /*
     * Largura do item em cent�metros
     * @var int
     */
    private $itemWidth;

    /*
     * Di�metro do item em cent�metros
     * @var int
     */
    private $itemDiameter;

    /*
     * Servi�os extras
     * - Servi�o M�o Pr�pria (S ou N)
     * - Servi�o Valor Declarado (R$, float)
     * - Servi�o AR (S ou N)
     * @var array
     */
    private $options = array(
        'MaoPropria' => 'N',
        'DeclaredValue' => 0,
        'Avis' => 'N',
    );

    /*
     * Resultado da requisi��o em XML
     * @var string
     */
    private $result;

    /*
     * Construtor, define o c�digo e senha de autentica��o no webservice
     * @param  string $authCode
     * @param  string $authPass
     * @return void
     * @access public
     */

    public function __construct($authCode = null, $authPass = null)
    {
        $this->authCode = $authCode;
        $this->authPass = $authPass;
    }

    /*
     * Setter de servi�o/m�todo de envio
     * @param  string $service
     * @return void
     * @access public
     */

    public function setService($service)
    {
        $servicesArr = array(
            'pacSC' => 41106, //PAC sem contrato
            'pacCC' => 41068, //PAC com contrato
            'sedexSC' => 40010, //SEDEX sem contrato
            'sedexCobrarSC' => 40045, //SEDEX a Cobrar, sem contrato
            'sedexCobrarCC' => 40126, //SEDEX a Cobrar, com contrato
            'sedex10SC' => 40215, //SEDEX 10, sem contrato
            'sedexHojeSC' => 40290, //SEDEX Hoje, sem contrato
            'sedexCC1' => 40096, //SEDEX com contrato
            'sedexCC2' => 40436, //SEDEX com contrato
            'sedexCC3' => 40444, //SEDEX com contrato
            'sedexCC4' => 40568, //SEDEX com contrato
            'sedexCC5' => 40606, //SEDEX com contrato
            'esedex1' => 81019, //e-SEDEX, com contrato
            'esedex2' => 81868, //(Grupo 1) e-SEDEX, com contrato
            'esedex3' => 81833, //(Grupo 2) e-SEDEX, com contrato
            'esedex4' => 81850, //(Grupo 3) e-SEDEX, com contrato
        );

        if ($srv = $servicesArr[$service]) {
            $this->service = $srv;
        } else {
            throw new Exception("O método de envio requisitado n�o existe");
        }
    }

    /*
     * Setter de CEP de origem
     * @param  string $zipcode
     * @return void
     * @access public
     */

    public function setZipFrom($zipcode)
    {
        $zipcode = preg_replace('/[^0-9]/', '', $zipcode);

        if (strlen($zipcode) != 8) {
            throw new Exception("O tamanho do CEP de origem é maior do que o esperado.");
        }

        $this->zipFrom = $zipcode;
    }

    /*
     * Setter de CEP de destino
     * @param  string $zipcode
     * @return void
     * @access public
     */

    public function setZipTo($zipcode)
    {
        $zipcode = preg_replace('/[^0-9]/', '', $zipcode);

        if (strlen($zipcode) != 8) {
            throw new Exception("O tamanho do CEP de destino é maior do que o esperado.");
        }

        $this->zipTo = $zipcode;
    }

    /*
     * Setter de peso do item
     * @param  string $value Valor em gramas
     * @return void
     * @access public
     */

    public function setItemWeight($value)
    {
        $this->itemWeight = $value / 1000;
    }

    /*
     * Setter de formato do item (pacote ou rolo/prisma)
     * @param  int  $value
     * @return void
     * @access public
     */

    public function setItemForm($value)
    {
        if (in_array($value, array(1, 2))) {
            $this->itemForm = $value;
        } else {
            throw new Exception("O formato do item não existe. Valores permitidos: 1, 2");
        }
    }

    /*
     * Setter de comprimento do item, em cent�metros
     * @param  int  $value
     * @return void
     * @access public
     */

    public function setItemLength($value)
    {
        if (is_int($value)) {
            $this->itemLength = $value;
        } else {
            throw new Exception("O comprimento do item não é um número inteiro.");
        }
    }

    /*
     * Setter de altura do item, em cent�metros
     * @param  int  $value
     * @return void
     * @access public
     */

    public function setItemHeight($value)
    {
        if (is_int($value)) {
            $this->itemHeight = $value;
        } else {
            throw new Exception("A altura do item não é um número inteiro.");
        }
    }

    /*
     * Setter de largura do item, em cent�metros
     * @param  int  $value
     * @return void
     * @access public
     */

    public function setItemWidth($value)
    {
        if (is_int($value)) {
            $this->itemWidth = $value;
        } else {
            throw new Exception("A largura do item não é um número inteiro.");
        }
    }

    /*
     * Setter de diâmetro do item, em centímetros
     * Utilizado somente no cálculo de frete para rolos ou prismas
     * @param  int  $value
     * @return void
     * @access public
     */

    public function setItemDiameter($value)
    {
        if (is_int($value)) {
            $this->itemDiameter = $value;
        } else {
            throw new Exception("O diâmetro do item não é um número inteiro.");
        }
    }

    /*
     * Setter de opções/serviços adicionais
     * @param  string $option
     * @param  string $value
     * @return void
     * @access public
     */

    public function setOption($option, $value)
    {
        if (isset($this->options[$option])) {
            $this->options[$option] = $value;
        } else {
            throw new Exception("A opção '{$option}' não existe e portanto não pode ser configurada.");
        }
    }

    /*
     * Checa as dimens�es da encomenda de acordo com sua forma (pacote ou rolo/prisma)
     * @return void
     * @access private
     */

    private function checkPackageDimensions()
    {
        if ($this->itemForm == 1) {
            if (($this->itemLength < 16)
                    || ($this->itemLength < 11)
                    || ($this->itemLength + $this->itemWidth + $this->itemHeight > 160)
                    || (($this->itemLength > 90) || ($this->itemWidth > 90) || ($this->itemHeight > 90))
                    || ($this->itemHeight < 2)) {
                throw new Exception("O tamanho do item ou de alguma de suas dimensões não é permitido.");
            }
        } else {
            if (($this->itemLength + 2 * $this->itemDiameter < 28)
                    || (($this->itemLength < 18) || ($this->itemLength > 90))) {
                throw new Exception("O tamanho do item ou de alguma de suas dimens�es não é permitido.");
            }
        }
    }

    /*
     * Efetua a consulta no webservice dos Correios via SOAP e salva o
     * resultado processado em uma string XML
     * @return void
     * @access public
     */

    public function request()
    {
        $this->checkPackageDimensions();

        $param = array(
            'nCdEmpresa' => $this->authCode,
            'sDsSenha' => $this->authPass,
            'nCdServico' => $this->service,
            'sCepOrigem' => $this->zipFrom,
            'sCepDestino' => $this->zipTo,
            'nVlPeso' => $this->itemWeight,
            'nCdFormato' => $this->itemForm,
            'nVlComprimento' => $this->itemLength,
            'nVlAltura' => $this->itemHeight,
            'nVlLargura' => $this->itemWidth,
            'nVlDiametro' => $this->itemDiameter,
            'sCdMaoPropria' => $this->options['MaoPropria'],
            'nVlValorDeclarado' => $this->options['DeclaredValue'],
            'sCdAvisoRecebimento' => $this->options['Avis'],
        );

        $webService = new SoapClient(self::URL);

        try {
            $result = $webService->CalcPrecoPrazo($param);
            $this->result = $result->CalcPrecoPrazoResult->Servicos->cServico;
        } catch (SoapFault $sf) {
            throw new Exception($sf->getCode() . ': ' . $sf->getMessage());
        }
    }

    /*
     * Retorna os resultados da consulta
     * @return string
     * @access public
     */

    public function getResult()
    {
        return $this->result;
    }

    /*
     * Retorna os resultados da consulta em um array associativo
     * @return array
     * @access public
     */

    public function getResultAsArray()
    {
        foreach ($this->result as $k => $v) {
            $arr[$k] = $v;
        }

        return $arr;
    }

}
