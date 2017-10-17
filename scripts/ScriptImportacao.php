<?php
require_once '../includes/wpr.php';

// $obj = new Model_Verden_Cron_MagentoCron();
// $obj->CadastraPedidosSaidaMagento();

//$obj = new Model_Wpr_Cron_VtexCron();
//$obj->CadastraPedidosVtex();

$obj = new Model_Wpr_Cron_KplCron();
$obj->atualizaStatusPedido();


// $obj = new Model_Wpr_Cron_MagentoCron();
// $obj->CadastraPedidosSaidaMagento();