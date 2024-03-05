<?php
require_once('framework.php');
$title = "Connexion";
if (isset($_SESSION['user.id'])) { // AUTHENTIFIE => HOME
    http_redirection('home.php');
}

// PAS AUTHENTIFIE
switch(http_get_method()) {
    case 'GET':
        html_login_send_page($title,html_form($title));
    break;
    case 'POST':
        manage_post($title,$database);
    break;
}

function manage_post($title,$database) {
    if ($_POST['user'] !== '') { // UN USER SAISI
        manage_post_with_user($title,$database);
    } else { // PAS DE USER SAISI
        html_login_send_page($title,html_form($title,TRUE)); // => CONNEXION ERROR
    }
}

function manage_post_with_user($title,$database) {
    $user_access = user_access($database);
    
    if ($user_access) { // AUTHENTIFICATION Ok
        session_start();
        $_SESSION=[];
        $_SESSION['user.id'] = $user_access['id'];
        $_SESSION['user.firstName'] = $user_access['first_name'];
        $_SESSION['user.lastName'] = $user_access['last_name'];
        if($user_access['failures'] > 0) {
            user_raz_failures($database);
        }
        http_redirection('home.php'); // => ACCUEIL
    }
    $user_state = user_state($database);
    // AUTHENTIFICATION KO
    if (!$user_state) { // USER N'EXISTE PAS
        html_login_send_page($title,html_form($title,TRUE)); // => CONNEXION ERROR
    }
    // USER EXISTE : BLOQUE
    if ($user_state['blocked']) {
        http_redirection('blocked.php'); // => DESACTIVE
    }
    // USER EXISTE : ECHECS++
    user_increment_failures($database);
    $user_state['failures']++;
    if ($user_state['failures'] > 2) { // USER BLOQUE
        user_block($database);
        http_redirection('blocked.php'); // => BLOQUE
    }
    if ($user_state['failures'] === 2) {
        html_login_send_page($title,html_form($title,TRUE,TRUE)); // => CONNEXION ERROR & WARNING
    }
    html_login_send_page($title,html_form($title,TRUE)); // => CONNEXION ERROR
}

function user_block($database) {
    $user_block_query = <<<END
UPDATE user SET blocked = 1 WHERE user = '{$_POST['user']}'
END;
    sql_exec($database,$user_block_query);
}
function user_increment_failures($database) {
    $user_increment_failures_query = <<<END
UPDATE user SET failures = failures + 1 WHERE user = '{$_POST['user']}'
END;
        sql_exec($database,$user_increment_failures_query);
}

function user_state($database) {
    $user_state_query = <<<END
SELECT * FROM user WHERE user = '{$_POST['user']}'
END;
    $user_state = sql_select($database,$user_state_query);
    if (count($user_state) === 0) {
        return FALSE;
    }
    return $user_state[0];
}

function user_access($database) {
    $user_access_query = <<<END
SELECT * FROM user WHERE user = '{$_POST['user']}' AND password =  '{$_POST['password']}' AND blocked = 0
END;
    $sql_result=sql_select($database,$user_access_query);
    if ($sql_result) {
        //return sql_select($database,$user_access_query)[0];
        return $sql_result[0];
    }
    return FALSE;
}

function user_raz_failures($database) {
    $user_raz_failures_query = <<<END
UPDATE user SET failures = 0 WHERE user = '{$_POST['user']}'
END;
    sql_exec($database,$user_raz_failures_query);
} 

function html_login_send_page($title,$main_html) {
    html_send_page($title,$main_html,FALSE); // pas de header ni footer
}

function html_form($titre,$warning=FALSE,$last_warning=FALSE) {
$modal = "";
if ($warning) {
    $modal = html_form_modal($last_warning);
}
$legend = html_icon('user')." ".$titre;
$form = <<<END
$modal
<form method="POST" class="screen-centered">
<fieldset>
<legend>$legend</legend>
<div>
<label for="user" class>Compte</label>
<input type="text" id="user" name="user">
</div>
<div>
<label for="user">Mot de passe</label>
<input type="password" id="password" name="password">
</div>
<div>
<input type="submit" id="login" name="login" class="inverse" value="$titre">
</div>
</div>
</fieldset>
</form>
END;
    return $form;
}
function html_form_modal($last_warning=FALSE) {
// $last_warning = TRUE;

$message ='<ul><li id="error">Identifiant ou mot de passe incorrect</li>';

if ($last_warning) {
    $card_class = "error";
    $titre = "Avertissement ";
    $message .='<li><span id="warning">Dernier essai</span> avant blocage '.html_icon('lock','inverse').'</li>';
}
else {
    $card_class = "warning" ;
    $titre = "Attention ";
}
$message .= " </ul>";

return html_modal($titre,$message,"modal-control-1",$class=$card_class);
}