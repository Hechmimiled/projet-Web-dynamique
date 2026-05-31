<?php
// Mon compte (profil) avec modification + dashboard utilisateur
session_start();
include "connexion.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];
$erreur = "";
$succes = "";

// Modification du profil
if (isset($_POST["btn_modifier"])) {
    $nom = isset($_POST["nom"]) ? $_POST["nom"] : "";
    $prenom = isset($_POST["prenom"]) ? $_POST["prenom"] : "";
    $email = isset($_POST["email"]) ? $_POST["email"] : "";
    $telephone = isset($_POST["telephone"]) ? $_POST["telephone"] : "";

    if (empty($nom)) { $erreur .= "Le nom est requis.<br>"; }
    if (empty($prenom)) { $erreur .= "Le prenom est requis.<br>"; }
    if (empty($email)) { $erreur .= "L'email est requis.<br>"; }
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur .= "L'email n'est pas valide.<br>";
    }

    if ($erreur == "") {
        $sql = "UPDATE Utilisateurs
                SET Nom = '$nom', Prenom = '$prenom', Email = '$email', Telephone = '$telephone'
                WHERE ID = $user_id";
        if (mysqli_query($conn, $sql)) {
            $_SESSION["user_nom"] = $nom;
            $_SESSION["user_prenom"] = $prenom;
            $_SESSION["user_email"] = $email;
            $succes = "Profil mis a jour avec succes.";
        } else {
            $erreur = "Erreur : " . mysqli_error($conn);
        }
    }
}

// Modification du mot de passe
if (isset($_POST["btn_mdp"])) {
    $ancien = isset($_POST["ancien"]) ? $_POST["ancien"] : "";
    $nouveau = isset($_POST["nouveau"]) ? $_POST["nouveau"] : "";
    $nouveau2 = isset($_POST["nouveau2"]) ? $_POST["nouveau2"] : "";

    if (empty($ancien) || empty($nouveau) || empty($nouveau2)) {
        $erreur = "Tous les champs sont requis.";
    } else if ($nouveau != $nouveau2) {
        $erreur = "Les nouveaux mots de passe ne correspondent pas.";
    } else if (strlen($nouveau) < 6) {
        $erreur = "Le mot de passe doit faire au moins 6 caracteres.";
    } else {
        $ancien_hash = md5($ancien);
        $sql_check = "SELECT * FROM Utilisateurs WHERE ID = $user_id AND MotDePasse = '$ancien_hash'";
        $res_check = mysqli_query($conn, $sql_check);
        if (mysqli_num_rows($res_check) == 0) {
            $erreur = "Ancien mot de passe incorrect.";
        } else {
            $nouveau_hash = md5($nouveau);
            $sql = "UPDATE Utilisateurs SET MotDePasse = '$nouveau_hash' WHERE ID = $user_id";
            mysqli_query($conn, $sql);
            $succes = "Mot de passe modifie.";
        }
    }
}

// Recuperer les infos utilisateur
$sql = "SELECT * FROM Utilisateurs WHERE ID = $user_id";
$resultat = mysqli_query($conn, $sql);
$user = mysqli_fetch_assoc($resultat);

// Compteurs pour le dashboard
$sql1 = "SELECT COUNT(*) AS nb FROM Reservations WHERE Utilisateur_ID = $user_id AND Statut = 'confirmee'";
$res1 = mysqli_query($conn, $sql1);
$nb_resa = mysqli_fetch_assoc($res1)["nb"];

$sql_att = "SELECT COUNT(*) AS nb FROM Reservations WHERE Utilisateur_ID = $user_id AND Statut = 'en_attente'";
$res_att = mysqli_query($conn, $sql_att);
$nb_attente = mysqli_fetch_assoc($res_att)["nb"];

$sql2 = "SELECT COUNT(*) AS nb FROM Inscriptions WHERE Utilisateur_ID = $user_id";
$res2 = mysqli_query($conn, $sql2);
$nb_inscr = mysqli_fetch_assoc($res2)["nb"];

$sql3 = "SELECT COUNT(*) AS nb FROM Notifications WHERE Utilisateur_ID = $user_id AND Lue = 0";
$res3 = mysqli_query($conn, $sql3);
$nb_notif = mysqli_fetch_assoc($res3)["nb"];

// 3 derniers RDV
$sql_rdv = "SELECT r.*, s.Nom AS ServiceNom, d.Date_dispo, d.Heure_debut
            FROM Reservations r
            JOIN Services s ON s.ID = r.Service_ID
            JOIN Disponibilites d ON d.ID = r.Disponibilite_ID
            WHERE r.Utilisateur_ID = $user_id
            ORDER BY r.Created_at DESC LIMIT 3";
$res_rdv = mysqli_query($conn, $sql_rdv);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>VitaCare - Mon compte</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header>
    <a href="index.php"><img src="images/logo.svg" alt="VitaCare" style="height:50px;"></a>
    <div>
        <a href="profil.php">Mon compte</a>
        <a href="logout.php">Deconnexion</a>
    </div>
</header>

<nav>
    <a href="index.php">Accueil</a>
    <a href="services.php">Services</a>
    <a href="activites.php">Activites</a>
    <a href="mes_reservations.php">Mes reservations</a>
    <a href="mes_inscriptions.php">Mes activites</a>
    <a href="panier.php">Mon panier</a>
    <a href="notifications.php">Notifications</a>
</nav>

<main>
    <h2>Mon tableau de bord</h2>

    <?php if ($erreur != "") { ?>
        <div class="erreur"><?php echo $erreur; ?></div>
    <?php } ?>
    <?php if ($succes != "") { ?>
        <div class="succes"><?php echo $succes; ?></div>
    <?php } ?>

    <div class="cartes">
        <div class="carte">
            <h3><?php echo $nb_resa; ?></h3>
            <p>Reservations confirmees</p>
            <a href="mes_reservations.php"><button>Voir</button></a>
        </div>
        <div class="carte">
            <h3><?php echo $nb_attente; ?></h3>
            <p>En attente de validation</p>
        </div>
        <div class="carte">
            <h3><?php echo $nb_inscr; ?></h3>
            <p>Activites inscrites</p>
            <a href="mes_inscriptions.php"><button>Voir</button></a>
        </div>
        <div class="carte">
            <h3><?php echo $nb_notif; ?></h3>
            <p>Notifications non lues</p>
            <a href="notifications.php"><button>Voir</button></a>
        </div>
    </div>

    <h3>Mes 3 dernieres reservations</h3>
    <?php if (mysqli_num_rows($res_rdv) == 0) { ?>
        <p>Aucune reservation pour le moment.</p>
    <?php } else { ?>
        <table>
            <tr><th>Service</th><th>Date</th><th>Heure</th><th>Statut</th></tr>
            <?php while ($r = mysqli_fetch_assoc($res_rdv)) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($r["ServiceNom"]); ?></td>
                    <td><?php echo date("d/m/Y", strtotime($r["Date_dispo"])); ?></td>
                    <td><?php echo substr($r["Heure_debut"], 0, 5); ?></td>
                    <td><?php echo htmlspecialchars($r["Statut"]); ?></td>
                </tr>
            <?php } ?>
        </table>
    <?php } ?>

    <h2>Modifier mes informations</h2>
    <form method="post" action="profil.php">
        <label>Nom :</label>
        <input type="text" name="nom" value="<?php echo htmlspecialchars($user["Nom"]); ?>">

        <label>Prenom :</label>
        <input type="text" name="prenom" value="<?php echo htmlspecialchars($user["Prenom"]); ?>">

        <label>Email :</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($user["Email"]); ?>">

        <label>Telephone :</label>
        <input type="tel" name="telephone" value="<?php echo htmlspecialchars($user["Telephone"]); ?>">

        <button type="submit" name="btn_modifier">Enregistrer les modifications</button>
    </form>

    <h2>Changer mon mot de passe</h2>
    <form method="post" action="profil.php">
        <label>Ancien mot de passe :</label>
        <input type="password" name="ancien">

        <label>Nouveau mot de passe :</label>
        <input type="password" name="nouveau">

        <label>Confirmer le nouveau mot de passe :</label>
        <input type="password" name="nouveau2">

        <button type="submit" name="btn_mdp">Changer le mot de passe</button>
    </form>

    <p style="margin-top: 30px; color: #666;"><em>Compte cree le <?php echo date("d/m/Y", strtotime($user["Created_at"])); ?> - Role : <?php echo htmlspecialchars($user["Role"]); ?></em></p>
</main>

<footer>
    <p>&copy; 2026 VitaCare - Projet ECE ING2</p>
</footer>

</body>
</html>
<?php mysqli_close($conn); ?>
