<?php

include('db.php');
//On vérifie que le formulaire est soumis

//on crée un tableau qui contiendra les erreurs
$errors = array();

if (isset($_POST['submit'])) {

    //le htmlspecialchars permtet d'echapper les caractères html qui purrait constituer un soucis de sécurité
    $nom = htmlspecialchars($_POST['nom']);
    $prenom = htmlspecialchars($_POST['prenom']);
    $email = htmlspecialchars($_POST['mail']);
    $age = intval($_POST["years"]);
    $taille = intval($_POST["size"]);
    $poids = intval($_POST["weight"]);
    $sexe = $_POST['sexe'];
    $sport = $_POST['activity'];
    $objectif = $_POST['objectif'];

    $sexConstant = null;
    $goalConstant = null;
    $activityConstant = null;

    if (strlen($nom) == 0 or strlen($prenom) == 0 or strlen($email) == 0) {
        $errors['fill_inputs'] = "veuillez remplir tous les champs";
    }
    if ($age <= 0) {
        $errors['age'] = "veuillez choisir une valeur normale";
    }
    if ($poids <= 0) {
        $errors['poids'] = "veuillez choisir une valeur normale";
    }
    if ($taille <= 0) {
        $errors['taille'] = "veuillez choisir une valeur normale";
    }

    //si pas d'erreurs
    if (sizeof($errors) == 0) {
        function getFinalMessage($resultatCalcul, $objectif)
        {
            $message = "";
            if ($objectif == "perte") {
                $message = "Si vous souhaitez perdre du poids vous devez consommer " . $resultatCalcul . " kcal maximum par jours.";
            } else {
                if ($objectif == "stagner") {
                    $message = "Si vous souhaitez gardez votre ligne vous devez consommer au maximum " . $resultatCalcul . " kcal par jours.";
                } else {
                    if ($objectif == "masse") {
                        $message = "Si vous souhaitez prendre en masse vous devez consommer plus ou moins " . $resultatCalcul . " kcal par jours.";
                    } else {
                        //afficher une erreur
                    }
                }
            }
            return $message;
        }

        if ($sexe == "sexehomme") {
            $sexConstant = 5;
        } elseif ($sexe = "sexefemme") {
            $sexConstant = -161;
        } else {
            //erreur
        }

        if ($sport == "little") {
            $activityConstant = 1.20;
        } elseif ($sport == "regular") {
            $activityConstant = 1.37;
        } elseif ($sport == "sometimes") {
            $activityConstant = 1.55;
        } elseif ($sport == "allthetime") {
            $activityConstant = 1.75;
        } elseif ($sport == "athlete") {
            $activityConstant = 1.90;
        } else {
            //erreur
        }

        if ($objectif == "perte") {
            $goalConstant = -500;
        } elseif ($objectif == "stagner") {
            $goalConstant = 0;
        } elseif ($objectif == "masse") {
            $goalConstant = +500;
        } else {
            //erreur
        }

        $calcul = ((10 * $poids + 6.25 * $taille - 5 * $age + $sexConstant) * $activityConstant) + $goalConstant;

        $messageFinal = getFinalMessage($calcul, $objectif);

        echo $messageFinal;

        $calories = $calcul;


        // cette ligne permet de faire en sorte que le PDO montre les erreurs s'il yen a (pour faciliter le deboggage) (vous pouvez la mettre dans le fichier db.php)
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        //on exécute la requete qui insere l'utilisateur

        if (isset($_POST['yes'])) {
            //ce type d'insert fait un update si la clé unique de la table existe déja. Il vous faudra donc définir au moins une clé unique à votre table client (email en l'occurrence car normalenemt deux enregistrement ne devrait pas avoir le même email)
            $sql = "INSERT INTO clients(nom,prenom,mail) 
                    VALUES ('$nom','$prenom','$email')
                    ON DUPLICATE KEY UPDATE
                    nom='$nom', prenom='$prenom'
                    ";


            //ce sript permet de recuperer le bon id qu'il s'agisse d'un insert ou d'un update
            $dbh->exec($sql);


            //et pour récupérer l'id de l'utilistateur nouvellement entré ou mis à jour
            $stmt = $dbh->prepare("SELECT id FROM clients WHERE mail='$email' LIMIT 1");
            $stmt->execute();
            $row = $stmt->fetch();
            $user_id = intval($row["id"]);


            //on exécute la requete qui insere le calcul
            $sql2 = "INSERT INTO calcul(age,poids,taille,calories,sport,objectif) VALUES ('$age','$poids','$taille','$calories','$sport','$objectif')";
            $dbh->exec($sql2);
            //on récupere l'id
            $calcul_id = $dbh->lastInsertId(); //id dernier calcul


            //ce dernier insert crée le lien entre le user et le calcul dans la bdd en enregistrant l'id du client et celui du calcul dans la table historique_utilisateur
            //le NOW() permet d'utliser la date courante
            $sql3 = "INSERT INTO historique_utilisateur(user_id,calcul_id,date) VALUES ('$user_id','$calcul_id',NOW())";
            $dbh->exec($sql3);

            $infos = "SELECT age,poids,taille,calories,sport,objectif FROM calcul";
            $data = json_encode($infos);
        }
    }
}

//on affichera le formualre s'il na pas encore été soumis ou s'il a été soumis mais avec des erreurs
if (!isset($_POST['submit']) or (isset($_POST['submit']) and sizeof($errors) != 0)) {
?>
    <!DOCTYPE html>
    <html lang="fr">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="calculateur.css">
        <title>Calculateur de calories</title>
        <!--<canvas id="myChart"></canvas>
        <script src="https://cdn.jsdelivr.net/npm/chart.js@2.8.0"></script>-->
    </head>

    <body>
        <div id="conteneur">
            <div class="image_container">
                <img id="image" alt="etapes" class="images" />
            </div>
            <div class="etapes">
                <div>
                    <form id="calcul" class="cal" method="post">
                        <div class="forms">
                            <div class="infos">
                                <div>
                                    <input type="radio" name="sexe" class="sir" id="sir" value="sexehomme" checked>
                                    <label for="sir" class="sirr">♂ Homme</label>

                                    <input type="radio" name="sexe" class="mrs" id="mrs" value="sexefemme">
                                    <label for="mrs" class="mrss">♀ Femme</label>
                                </div>

                                <?php
                                if (isset($errors['fill_inputs'])) {
                                ?>
                                    <p style="color: red"><span><?= $errors['fill_inputs'] ?></span></p>
                                <?php
                                }
                                ?>
                                <!--                            le  isset($_POST['prenom'])?$_POST['prenom']:'' permet d'afficher les valeurs précédentes si elle existe ; j'utilise les conditions ternaires : https://www.pierre-giraud.com/php-mysql-apprendre-coder-cours/operateur-ternaire-fusion-null/ -->
                                <p><span>Prénom:<br>
                                    </span> <input class="prenom" name="prenom" type="text" id="weight" placeholder="Votre prénom" value="<?= isset($_POST['prenom']) ? $_POST['prenom'] : '' ?>">

                                    <p><span>Nom:<br>
                                        </span> <input class="nom" name="nom" type="text" id="weight" placeholder="Votre nom" value="<?= isset($_POST['nom']) ? $_POST['nom'] : '' ?>">

                                        <!--                            ici c'est type="email" et pas type="mail"-->
                                        <p><span>Mail:<br>
                                            </span> <input class="mail" type="email" id="weight" placeholder="Votre mail" name="mail" value="<?= isset($_POST['mail']) ? $_POST['mail'] : '' ?>">

                                            <?php
                                            if (isset($errors['age'])) {
                                            ?>
                                                <p style="color: red"><span><?= $errors['age'] ?></span></p>
                                            <?php
                                            }
                                            ?>
                                            <p><span>Âge:<br>
                                                </span> <input class="age" type="number" pattern="\d*" name="years" id="years" placeholder="Votre âge" value="<?= isset($_POST['years']) ? $_POST['years'] : '' ?>">

                                                <?php
                                                if (isset($errors['taille'])) {
                                                ?>
                                                    <p style="color: red"><span><?= $errors['taille'] ?></span></p>
                                                <?php
                                                }
                                                ?>
                                                <p><span>Taille(cm):<br>
                                                    </span> <input class="cm" type="number" pattern="\d*" id="size" name="size" placeholder="Votre taille" value="<?= isset($_POST['size']) ? $_POST['size'] : '' ?>">

                                                    <?php
                                                    if (isset($errors['poids'])) {
                                                    ?>
                                                        <p style="color: red"><span><?= $errors['poids'] ?></span></p>
                                                    <?php
                                                    }
                                                    ?>
                                                    <p><span>Poids(kg):<br>
                                                        </span> <input class="kg" type="number" pattern="\d*" name="weight" id="weight" placeholder="Votre poids" value="<?= isset($_POST['prenom']) ? $_POST['prenom'] : '' ?>">

                            </div>

                            <div class="activiter">
                                <h5 class="A"> Votre activité physique </h5>

                                <label class="sport">
                                    <input type="radio" name="activity" id="little" value="little" checked="checked">
                                    Peu d'activité physique/sédentaire<br>
                                </label>


                                <label class="sport">
                                    <input type="radio" name="activity" id="regular" value="regular">
                                    1 à 3 fois par semaine<br>
                                </label>

                                <label class="sport">
                                    <input type="radio" name="activity" id="sometimes" value="sometimes">
                                    3 à 5 fois par semaine<br>
                                </label>

                                <label class="sport">
                                    <input type="radio" name="activity" id="allthetime" value="allthetime">
                                    6 à 7 fois par semaine<br>
                                </label>

                                <label class="sport">
                                    <input type="radio" name="activity" id="athlete" value="athlete">
                                    Athlète<br>
                                </label>
                            </div>

                            <div class="objectif">
                                <h5 class="O">Votre objectif</h5>

                                <label for="perte">
                                    <input type="radio" name="objectif" id="perte" checked="checked" value="perte">
                                    Perdre du poids <br>
                                </label>

                                <label for="stagner">
                                    <input type="radio" name="objectif" id="stagner" value="stagner">
                                    Stabiliser<br>
                                </label>

                                <label for="masse">
                                    <input type="radio" name="objectif" id="masse" value="masse">
                                    Prendre en masse
                                </label>
                            </div>
                        </div>

                        <p style="color: red;" id="error" class="error"></p>
                        <div class="checkbox">
                            <input type="checkbox" name="yes" value="yes">
                            <label class="checkbox">En cochant cette case je consens à ce que mes données soit utilisées
                                dans le cadre de
                                statistiques.</label>
                        </div>

                        <div class="boutons">
                            <button type="reset" id="reset" class="reset">Recommencer</button>
                            <button type="submit" name="submit" id="calculBtn" class="calculer">Calculer</button>
                        </div>
                    </form>

                </div>
                <div class="plan">
                    <img src="plan.png" alt="plan" class="plan">
                </div>

                <div class="rte">
                </div>
            </div>
        </div>
    </body>
    <script>
        var ctx = document.getElementById('myChart').getContext('2d');
        var chart = new Chart(ctx, {
            // The type of chart we want to create
            type: 'line',

            // The data for our dataset
            data: {
                labels: ['January', 'February', 'March', 'April', 'May', 'June', 'July'],
                datasets: [{
                    label: 'My Stats',
                    backgroundColor: 'rgb(255, 99, 132)',
                    borderColor: 'rgb(255, 99, 132)',
                    data: ['$age', '$poids', '$taille', '$calories', '$sport', '$objectif']
                }]
            },

            // Configuration options go here
            options: {}
        });
    </script>

    </html>

<?php
}
?>