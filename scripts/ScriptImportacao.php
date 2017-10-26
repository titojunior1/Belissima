<?php
require_once '../includes/wpr.php';

// $obj = new Model_Verden_Cron_MagentoCron();
// $obj->CadastraPedidosSaidaMagento();

//$obj = new Model_Wpr_Cron_VtexCron();
//$obj->CadastraPedidosVtex();

$obj = new Model_Wpr_Magento_MagentoWebService('https://www.vetorscan.com.br/api/v2_soap?wsdl=1', 'Integracao', 'integra2017vetor');
$obj->atualizaStatusPedido( 100000473, 'processing' );


// $obj = new Model_Wpr_Cron_MagentoCron();
// $obj->CadastraPedidosSaidaMagento();