<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $autore_segnalante = $_POST['autore_segnalante'];
    $autore_segnalato = $_POST['autore_segnalato'];
    $motivo = $_POST['motivo'];
    $tipo = isset($_POST['tipo']) ? $_POST['tipo'] : '';
    $contenuto = isset($_POST['contenuto']) ? $_POST['contenuto'] : '';

    // carichiamo il file XML esistente o ne creiamo uno nuovo
    $xml_file = '../xml/segnalazioni.xml';
    if (file_exists($xml_file)) {
        $xml = simplexml_load_file($xml_file);
    } else {
        $xml = new SimpleXMLElement('<segnalazioni></segnalazioni>');
    }

    // aggiungiamo la segnalazione fatta
    $segnalazione = $xml->addChild('segnalazione');
    $segnalazione->addChild('username_segnalante', htmlspecialchars($autore_segnalante));
    $segnalazione->addChild('username_segnalato', htmlspecialchars($autore_segnalato));
    $segnalazione->addChild('motivo', htmlspecialchars($motivo));
    $segnalazione->addChild('tipo', htmlspecialchars($tipo));
    $segnalazione->addChild('contenuto', htmlspecialchars($contenuto));

    // salviamo
    $xml->asXML($xml_file);
    echo "Segnalazione inviata.";
}
?>
