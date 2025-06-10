<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $codice_gioco = $data['codice_gioco'];
    $contenuto = $data['contenuto'];
    $autore = $data['autore'];
    $contenutoDomanda = $data['contenuto_domanda'];

    // caricamento del file XML esistente o creazione di uno nuovo
    $xml_file = '../xml/domande.xml';
    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;

    // caricamento del file XML se esiste
    if (file_exists($xml_file)) {
        $dom->load($xml_file);
    } else {
        // se il file non esiste, creiamo la struttura di base
        $root = $dom->createElement('domande');
        $dom->appendChild($root);
    }

    // calcoliamo il prossimo ID per la risposta da inserire nel file xml
    $maxId = 0;
    foreach ($dom->getElementsByTagName('risposta') as $risposta) {
        $currentId = (int)$risposta->getAttribute('id');
        if ($currentId > $maxId) {
            $maxId = $currentId;
        }
    }
    $nextId = $maxId + 1; // incrementiamo l'ID

    // troviamo la domanda corrispondente all'ID
    $domandaTrovata = false;
    foreach ($dom->getElementsByTagName('domanda') as $domanda) {
        $contenutoDomandaXML = $domanda->getElementsByTagName('contenuto')->item(0)->nodeValue;
        if ($contenutoDomandaXML == $contenutoDomanda) {
            // Creazione della nuova risposta
            $risposta = $dom->createElement('risposta');
            $risposta->setAttribute('id', $nextId);
            $risposta->appendChild($dom->createElement('contenuto', htmlspecialchars($contenuto)));
            $risposta->appendChild($dom->createElement('autore', htmlspecialchars($autore)));
            $risposta->appendChild($dom->createElement('data', date('Y-m-d')));

            // Aggiungiamo la risposta alla domanda
            $domanda->appendChild($risposta);
            $domandaTrovata = true;
            break;
        }
    }

    if ($domandaTrovata) {
        // salvataggio dell'XML
        $dom->save($xml_file);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Domanda non trovata']);
    }
}
?>