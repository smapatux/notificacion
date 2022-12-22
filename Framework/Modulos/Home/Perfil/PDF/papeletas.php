<?php
$mpdf->SetHTMLHeader('<img src="/public/Generales/img/cabeceraPdfSmapa.png" style="width:100%;"/>');
$mpdf->WriteHTML(saltosDeLinea(5));
$contador = 0;
foreach ($data as $key => $value) {
	$mpdf->WriteHTML("<pre style='font-size:9px;'>" . $value->informacion . "</pre>");

	if ($contador % 2 == 1 && end($data) != $value) {
		$mpdf->AddPage();
		$mpdf->WriteHTML(saltosDeLinea(5));
	} else {
		if (end($data) != $value) {
			$mpdf->WriteHTML(saltosDeLinea(4) . "<hr style='border: 1px dotted black; width:100%; border-style: dotted;'/>" . saltosDeLinea(4));
		}

	}

	$contador++;
}

function saltosDeLinea($num) {
	$cadena = "";

	for ($x = 0; $x < $num; $x++) {
		$cadena .= "<br/>";
	}

	return $cadena;
}
?>
