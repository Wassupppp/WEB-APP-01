<?php
require_once('framework.php');
$title = "Accueil";

if (!isset($_SESSION['user.id'])) { // PAS AUTHENTIFIE => CONNEXION
    http_redirection('login.php');
}


$card_one_html = html_card("Liste des clients", html_clients_random_table());

$main_html =<<<END
<div class="row cols-sm-12 cols-md-6">
$card_one_html
</div>
END;

html_send_page($title,$main_html);

function html_clients_random_table(){
    $number_of_lines = random_int(10,20);
    for($i = 1; $i <= $number_of_lines; $i++) {
        $lines[]=["Nom $i", "Prénom $i", random_int(0,9)];
    }
    return html_table(["Nom","Prénom","Commandes"],$lines);
}
