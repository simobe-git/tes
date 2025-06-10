<?php
session_start();
require_once('connessione.php');

// verifica se l'utente è loggato e se è un cliente
if (!isset($_SESSION['statoLogin'])) {
    header("Location: login.php");
    exit();

}elseif($_SESSION['tipo_utente'] !== 'cliente'){
    header("Location: home.php");
    exit();
}


if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// recuperiamo i crediti attuali dell'utente
$query = "SELECT crediti FROM utenti WHERE username = ?";
$stmt = $connessione->prepare($query);
$stmt->bind_param("s", $_SESSION['username']);
$stmt->execute();
$result = $stmt->get_result();
$crediti_attuali = $result->fetch_assoc()['crediti'];

// Prendiamo le offerte di crediti dal file xml e le mettiamo in un array
$offerte_crediti = [];
$xml = simplexml_load_file('../xml/pacchetti_crediti.xml');
foreach ($xml->children() as $offerta) {
    $offerte_crediti[] = [
        'codice' => (int) $offerta->codice,
        'crediti' => (int) $offerta->crediti,
        'prezzo' => (float) $offerta->prezzo
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acquista_crediti'])) {
    $indice_offerta = $_POST['offerta'];
    if (isset($offerte_crediti[$indice_offerta])) {
        $offerta = $offerte_crediti[$indice_offerta];
        
        // aggiorniamo i crediti nel database
        $query = "UPDATE utenti SET crediti = crediti + ? WHERE username = ?";
        $stmt = $connessione->prepare($query);
        $stmt->bind_param("ds", $offerta['crediti'], $_SESSION['username']);
        
        if ($stmt->execute()) {
            // aggiorniamo i crediti attuali per la visualizzazione
            $crediti_attuali += $offerta['crediti'];
            
            // in questo caso non salviamo la richiesta nel file XML, poiché i crediti sono stati aggiunti direttamente
            // senza passare dall'admin
            $messaggio_successo = "Hai acquistato con successo {$offerta['crediti']} crediti!";
        } else {
            $errore = "Si è verificato un errore durante l'acquisto dei crediti.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Richiedi Crediti - GameShop</title>
    <link rel="stylesheet" href="../css/home.css">
    <style>
        .container {
            margin-top: 100px; 
            padding: 20px;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }

        .offerte-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            padding: 20px;
        }

        .offerta-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .offerta-card:hover {
            transform: translateY(-5px);
        }

        .crediti {
            font-size: 2em;
            color: #2ecc71;
            margin: 15px 0;
            font-weight: bold;
        }

        .prezzo {
            font-size: 1.4em;
            color: #333;
            margin: 15px 0;
        }

        .btn-acquista {
            display: block;
            width: 90%;
            margin: 20px auto 10px;
            padding: 12px;
            background-color: #ff6347;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            font-size: 1.1em;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn-acquista:hover {
            background-color: #ff4500;
        }

        .messaggio {
            text-align: center;
            padding: 15px;
            margin: 20px auto;
            max-width: 600px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <?php include('menu.php'); ?>
    
    <div class="container">
        <h1 style="text-align: center;">Richiedi Crediti</h1>

        <div class="offerte-grid">
            <?php foreach ($offerte_crediti as $indice => $offerta): ?>
                <div class="offerta-card">
                    <h2 class="crediti"><?php echo $offerta['crediti']; ?> crediti</h2>
                    <p class="prezzo">Prezzo: <?php echo $offerta['prezzo']; ?> €</p>
                    <form method="POST">
                        <input type="hidden" name="offerta" value="<?php echo $indice; ?>">
                        <button type="submit" name="acquista_crediti" class="btn-acquista">Acquista</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>

        <!--Controlla che il messaggio di sucesso sia impostato, se si riporta utente nel profilo altrimenti stampa errore-->
        <?php if (isset($messaggio_successo)): ?>
            <div class="messaggio successo"><?php echo $messaggio_successo; ?></div>
            <a href="profilo.php" class="btn-acquista">Torna al profilo</a>
        <?php elseif(isset($errore)): ?>
            <div class="messaggio errore"><?php echo $errore; ?></div>
        <?php endif; ?>

        
    </div>
</body>
</html>
