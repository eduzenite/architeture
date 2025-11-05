<?php

namespace App\Infra\PrefeituraSaoPaulo;

class XmlRequestBuilder
{
    protected string $cnpj;
    protected string $municipalRegistration;

    public function __construct(string $cnpj, string $municipalRegistration)
    {
        $this->cnpj = $cnpj;
        $this->municipalRegistration = $municipalRegistration;
    }

    /**
     * Constrói XML para envio de RPS
     */
    public function buildSendRPSRequest(array $rpsData): string
    {
        $rpsNumber = $rpsData['numero'] ?? '';
        $serie = $rpsData['serie'] ?? 'RPS';
        $tipo = $rpsData['tipo'] ?? 1;
        $dataEmissao = $rpsData['dataEmissao'] ?? date('Y-m-d');
        $status = $rpsData['status'] ?? 'N';

        // Tributação
        $tributacao = $rpsData['tributacao'] ?? 'T';
        $valorServicos = number_format($rpsData['valorServicos'] ?? 0, 2, '.', '');
        $valorDeducoes = number_format($rpsData['valorDeducoes'] ?? 0, 2, '.', '');
        $aliquota = number_format($rpsData['aliquota'] ?? 0, 4, '.', '');

        // Prestador
        $cpfCnpjPrestador = $this->cnpj;
        $inscricaoMunicipalPrestador = $this->municipalRegistration;

        // Tomador
        $cpfCnpjTomador = $rpsData['tomador']['cpfCnpj'] ?? '';
        $razaoSocialTomador = $rpsData['tomador']['razaoSocial'] ?? '';
        $enderecoTomador = $rpsData['tomador']['endereco'] ?? '';
        $emailTomador = $rpsData['tomador']['email'] ?? '';

        // Serviço
        $codigoServico = $rpsData['codigoServico'] ?? '';
        $discriminacao = $rpsData['discriminacao'] ?? '';

        $loteId = uniqid('LOTE');

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<PedidoEnvioLoteRPS xmlns="http://www.prefeitura.sp.gov.br/nfe" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <Cabecalho Versao="1">
    <CPFCNPJRemetente>
      <CNPJ>{$cpfCnpjPrestador}</CNPJ>
    </CPFCNPJRemetente>
    <transacao>true</transacao>
    <dtInicio>{$dataEmissao}</dtInicio>
    <dtFim>{$dataEmissao}</dtFim>
    <QtdRPS>1</QtdRPS>
    <ValorTotalServicos>{$valorServicos}</ValorTotalServicos>
    <ValorTotalDeducoes>{$valorDeducoes}</ValorTotalDeducoes>
  </Cabecalho>
  <Lote Id="{$loteId}">
    <RPS>
      <Assinatura></Assinatura>
      <ChaveRPS>
        <InscricaoPrestador>{$inscricaoMunicipalPrestador}</InscricaoPrestador>
        <SerieRPS>{$serie}</SerieRPS>
        <NumeroRPS>{$rpsNumber}</NumeroRPS>
      </ChaveRPS>
      <TipoRPS>{$tipo}</TipoRPS>
      <DataEmissao>{$dataEmissao}</DataEmissao>
      <StatusRPS>{$status}</StatusRPS>
      <TributacaoRPS>{$tributacao}</TributacaoRPS>
      <ValorServicos>{$valorServicos}</ValorServicos>
      <ValorDeducoes>{$valorDeducoes}</ValorDeducoes>
      <CodigoServico>{$codigoServico}</CodigoServico>
      <AliquotaServicos>{$aliquota}</AliquotaServicos>
      <ISSRetido>false</ISSRetido>
      <CPFCNPJTomador>
        <CNPJ>{$cpfCnpjTomador}</CNPJ>
      </CPFCNPJTomador>
      <RazaoSocialTomador>{$razaoSocialTomador}</RazaoSocialTomador>
      <EnderecoTomador>{$enderecoTomador}</EnderecoTomador>
      <EmailTomador>{$emailTomador}</EmailTomador>
      <Discriminacao>{$discriminacao}</Discriminacao>
    </RPS>
  </Lote>
</PedidoEnvioLoteRPS>
XML;
    }

    /**
     * Constrói XML para consulta de NFSe
     */
    public function buildConsultNFeRequest(string $invoiceNumber, string $verificationCode): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<PedidoConsultaNFe xmlns="http://www.prefeitura.sp.gov.br/nfe">
  <Cabecalho Versao="1">
    <CPFCNPJRemetente>
      <CNPJ>{$this->cnpj}</CNPJ>
    </CPFCNPJRemetente>
  </Cabecalho>
  <Detalhe>
    <ChaveNFe>
      <InscricaoPrestador>{$this->municipalRegistration}</InscricaoPrestador>
      <NumeroNFe>{$invoiceNumber}</NumeroNFe>
      <CodigoVerificacao>{$verificationCode}</CodigoVerificacao>
    </ChaveNFe>
  </Detalhe>
</PedidoConsultaNFe>
XML;
    }

    /**
     * Constrói XML para cancelamento de NFSe
     */
    public function buildCancelNFeRequest(string $invoiceNumber, string $verificationCode): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<PedidoCancelamentoNFe xmlns="http://www.prefeitura.sp.gov.br/nfe">
  <Cabecalho Versao="1">
    <CPFCNPJRemetente>
      <CNPJ>{$this->cnpj}</CNPJ>
    </CPFCNPJRemetente>
  </Cabecalho>
  <Detalhe>
    <ChaveNFe>
      <InscricaoPrestador>{$this->municipalRegistration}</InscricaoPrestador>
      <NumeroNFe>{$invoiceNumber}</NumeroNFe>
      <CodigoVerificacao>{$verificationCode}</CodigoVerificacao>
    </ChaveNFe>
  </Detalhe>
</PedidoCancelamentoNFe>
XML;
    }

    /**
     * Constrói XML para consulta de lote
     */
    public function buildConsultBatchRequest(string $batchNumber): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<PedidoConsultaLote xmlns="http://www.prefeitura.sp.gov.br/nfe">
  <Cabecalho Versao="1">
    <CPFCNPJRemetente>
      <CNPJ>{$this->cnpj}</CNPJ>
    </CPFCNPJRemetente>
  </Cabecalho>
  <Detalhe>
    <InscricaoPrestador>{$this->municipalRegistration}</InscricaoPrestador>
    <NumeroLote>{$batchNumber}</NumeroLote>
  </Detalhe>
</PedidoConsultaLote>
XML;
    }

    /**
     * Constrói XML para consulta de CNPJ
     */
    public function buildConsultCNPJRequest(string $cnpj): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<PedidoConsultaCNPJ xmlns="http://www.prefeitura.sp.gov.br/nfe">
  <Cabecalho Versao="1">
    <CPFCNPJRemetente>
      <CNPJ>{$this->cnpj}</CNPJ>
    </CPFCNPJRemetente>
  </Cabecalho>
  <Detalhe>
    <CNPJ>{$cnpj}</CNPJ>
  </Detalhe>
</PedidoConsultaCNPJ>
XML;
    }
}
