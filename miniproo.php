<?php
$fichier = "miniproo.json";
$taches = [];

/* LECTURE SÉCURISÉE DU FICHIER JSON */
if (file_exists($fichier)) {
    $contenu = file_get_contents($fichier);
    $taches = json_decode($contenu, true);

    if (!is_array($taches)) {
        $taches = [];
    }
}

/* AJOUT D’UNE TÂCHE */
if (isset($_POST['btnAjouter'])) {
    $tache = [
        "id" => time(),
        "titre" => $_POST['titre'],
        "description" => $_POST['description'],
        "priorite" => $_POST['priorite'],
        "statut" => "à faire",
        "date_creation" => date("Y-m-d"),
        "date_limite" => $_POST['date_limite']
    ];

    $taches[] = $tache;
    file_put_contents($fichier, json_encode($taches, JSON_PRETTY_PRINT));

    header("Location: miniproo.php");
    exit;
}

/* CHANGER LE STATUT */
if (isset($_GET['changer'])) {
    foreach ($taches as &$t) {
        if ($t['id'] == $_GET['changer']) {
            if ($t['statut'] == "à faire") $t['statut'] = "en cours";
            else if ($t['statut'] == "en cours") $t['statut'] = "terminée";
        }
    }
    file_put_contents($fichier, json_encode($taches, JSON_PRETTY_PRINT));
    header("Location: miniproo.php");
    exit;
}

/* SUPPRIMER UNE TÂCHE */
if (isset($_GET['supprimer'])) {
    foreach ($taches as $i => $t) {
        if ($t['id'] == $_GET['supprimer']) {
            array_splice($taches, $i, 1);
            break;
        }
    }
    file_put_contents($fichier, json_encode($taches, JSON_PRETTY_PRINT));
    header("Location: miniproo.php");
    exit;
}

/* FILTRES */
$listeTaches = $taches;

if (!empty($_GET['q'])) {
    $mot = strtolower($_GET['q']);
    $listeTaches = array_filter($listeTaches, function ($t) use ($mot) {
        return str_contains(strtolower($t['titre']), $mot) ||
               str_contains(strtolower($t['description']), $mot);
    });
}

if (!empty($_GET['statut'])) {
    $listeTaches = array_filter($listeTaches, fn($t) => $t['statut'] == $_GET['statut']);
}

if (!empty($_GET['priorite'])) {
    $listeTaches = array_filter($listeTaches, fn($t) => $t['priorite'] == $_GET['priorite']);
}

/* STATISTIQUES */
$total = count($taches);
$terminees = count(array_filter($taches, fn($t) => $t['statut'] == "terminée"));
$retard = count(array_filter($taches, fn($t) =>
    $t['statut'] != "terminée" && $t['date_limite'] < date("Y-m-d")
));

$pourcentage = $total > 0 ? round(($terminees / $total) * 100, 2) : 0;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Mini Projet PHP - Gestion des Tâches</title>
<link rel="stylesheet" href="css/bootstrap.css">
</head>

<body class="bg-light">
<div class="container mt-4">

<h2 class="text-center mb-4">Gestion des Tâches</h2>

<!-- AJOUT -->
<form method="post" class="card p-3 mb-4">
    <h5>Ajouter une tâche</h5>
    <input class="form-control mb-2" name="titre" placeholder="Titre" required>
    <textarea class="form-control mb-2" name="description" placeholder="Description"></textarea>

    <select class="form-control mb-2" name="priorite">
        <option value="basse">Basse</option>
        <option value="moyenne">Moyenne</option>
        <option value="haute">Haute</option>
    </select>

    <input class="form-control mb-2" type="date" name="date_limite" required>
    <button class="btn btn-primary" name="btnAjouter">Ajouter</button>
</form>

<!-- FILTRES -->
<form method="get" class="row g-2 mb-3">
    <div class="col">
        <input class="form-control" name="q" placeholder="Recherche">
    </div>
    <div class="col">
        <select class="form-control" name="statut">
            <option value="">Tous statuts</option>
            <option>à faire</option>
            <option>en cours</option>
            <option>terminée</option>
        </select>
    </div>
    <div class="col">
        <select class="form-control" name="priorite">
            <option value="">Toutes priorités</option>
            <option>basse</option>
            <option>moyenne</option>
            <option>haute</option>
        </select>
    </div>
    <div class="col">
        <button class="btn btn-secondary w-100">Filtrer</button>
    </div>
</form>

<!-- LISTE -->
<?php foreach ($listeTaches as $t) { 
    $enRetard = ($t['statut'] != "terminée" && $t['date_limite'] < date("Y-m-d"));
?>
<div class="card mb-2 <?= $enRetard ? 'border-danger' : '' ?>">
    <div class="card-body">
        <h5><?= $t['titre'] ?></h5>
        <p><?= $t['description'] ?></p>
        <span class="badge bg-info"><?= $t['priorite'] ?></span>
        <span class="badge bg-secondary"><?= $t['statut'] ?></span>
        <span class="badge bg-dark"><?= $t['date_limite'] ?></span>

        <?php if ($enRetard) echo "<span class='badge bg-danger'>En retard</span>"; ?>

        <div class="mt-2">
            <a href="miniproo.php?changer=<?= $t['id'] ?>" class="btn btn-sm btn-warning">Changer statut</a>
            <a onclick="return confirm('Supprimer ?')" href="?supprimer=<?= $t['id'] ?>" class="btn btn-sm btn-danger">Supprimer</a>
        </div>
    </div>
</div>
<?php } ?>

<!-- STATISTIQUES -->
<div class="card mt-4 p-3">
    <h5>Statistiques</h5>
    <p>Total : <?= $total ?></p>
    <p>Terminées : <?= $terminees ?></p>
    <p>Pourcentage : <?= $pourcentage ?>%</p>
    <p>En retard : <?= $retard ?></p>
</div>

</div>
</body>
</html>