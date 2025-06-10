<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $username = $_SESSION['username']; 
    $id_recensione = $data['id_recensione']; // Cambiato da 'id_risposta' a 'id_recensione'
    $stelle = $data['stelle']; 

    // caricamento file XML delle recensioni
    $xmlFile = '../xml/recensioni.xml';
    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->preserveWhiteSpace = false; 
    $dom->formatOutput = true; 

    if (file_exists($xmlFile)) {
        $dom->load($xmlFile);
    } else {
        echo json_encode(['success' => false, 'message' => 'File non trovato']);
        exit();
    }

    // Trova la recensione corrispondente
    $recensioni = $dom->getElementsByTagName('recensione');
    $recensioneTrovata = null;

    foreach ($recensioni as $recensione) {
        if ($recensione->getAttribute('id') == $id_recensione) {
            $recensioneTrovata = $recensione;
            break;
        }
    }

    if ($recensioneTrovata) {
        // Controlla se l'utente ha già valutato questa recensione
        $giudiziNode = $recensioneTrovata->getElementsByTagName('giudizi')->item(0);
        if ($giudiziNode) {
            foreach ($giudiziNode->getElementsByTagName('giudizio') as $giudizio) {
                $usernameVotante = $giudizio->getElementsByTagName('username_votante')->item(0)->textContent;
                $codiceGioco = $recensioneTrovata->getElementsByTagName('codice_gioco')->item(0)->textContent;

                // Se l'username votante è uguale e il codice gioco è lo stesso, non salvare la nuova valutazione
                if ($usernameVotante === $username) {
                    echo json_encode(['success' => false, 'message' => 'Hai già valutato questa recensione, non puoi farlo di nuovo.']);
                    exit();
                }
            }
        }

        // Crea i nodi per il giudizio
        if (!$giudiziNode) {
            $giudiziNode = $dom->createElement('giudizi');
            $recensioneTrovata->appendChild($giudiziNode);
        }

        $giudizioNode = $dom->createElement('giudizio');
        $giudizioNode->appendChild($dom->createElement('username_votante', htmlspecialchars($username)));
        $giudizioNode->appendChild($dom->createElement('stelle', htmlspecialchars($stelle)));

        // Aggiungi il giudizio ai giudizi
        $giudiziNode->appendChild($giudizioNode);

        // Salvataggio file XML
        $dom->save($xmlFile);

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Recensione non trovata']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Richiesta non valida']);
}
?>
