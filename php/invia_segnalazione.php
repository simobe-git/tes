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
    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;

    if (file_exists($xml_file)) {
        $dom->load($xml_file);
    } else {
        $root = $dom->createElement('segnalazioni');
        $dom->appendChild($root);
    }
    // Controllo duplicato: stessa segnalazione da stesso utente su stesso contenuto e tipo
    $duplicato = false;
    foreach ($dom->getElementsByTagName('segnalazione') as $segnalazione) {
        $segnala = $segnalazione->getElementsByTagName('username_segnalante')->item(0)->nodeValue ?? '';
        $segnalato = $segnalazione->getElementsByTagName('username_segnalato')->item(0)->nodeValue ?? '';
        $tipo_senalazione = $segnalazione->getElementsByTagName('tipo')->item(0)->nodeValue ?? '';
        $contenuto_segnalato = $segnalazione->getElementsByTagName('contenuto')->item(0)->nodeValue ?? '';

        if ( $segnala === $autore_segnalante && $segnalato === $autore_segnalato && $tipo_senalazione === $tipo && $contenuto_segnalato === $contenuto) {
            $duplicato = true;
            break;
        }
    }

    // Se è un duplicato segnaliamo
    if ($duplicato){
        echo "Hai già segnalato questo messaggio.";
        exit;
    }

    // aggiungiamo la segnalazione fatta
    $segnalazione = $dom->createElement('segnalazione');
    $segnalazione->appendChild($dom->createElement('username_segnalante', htmlspecialchars($autore_segnalante)));
    $segnalazione->appendChild($dom->createElement('username_segnalato', htmlspecialchars($autore_segnalato)));
    $segnalazione->appendChild($dom->createElement('motivo', htmlspecialchars($motivo)));
    $segnalazione->appendChild($dom->createElement('tipo', htmlspecialchars($tipo)));
    $segnalazione->appendChild($dom->createElement('contenuto', htmlspecialchars($contenuto)));

    // salviamo
    $dom->documentElement->appendChild($segnalazione);
    $dom->save($xml_file);
}
?>
