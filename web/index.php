<?php

function h($string){ return htmlspecialchars($string,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8',true); }

function htmlStart($me = null) {
?><!DOCTYPE html><html lang="fr">
<head>
<meta charset="utf-8">
<title>Secret Santa Evaneos</title>
<link rel="stylesheet" type="text/css" href="http://c.hurpeau.com/css/index.css"/>

<style>
body {
    background: #B52620;
}
.col.fixed, .col.variable {
    box-shadow: none;
    background: #FFF;
}
input.submit, button, .button {
    background: #D52623;
}

input.submit:hover, button:hover, .button:hover {
    background: #bf221f
}

input.submit:focus, button:focus, .button:focus {
    background: #bf221f
}

.input.text input, .input.text textarea {
    border-bottom: 1px solid #D52623;
    font-size: 1.3em;
}


</style>
</head>
<body>
<!--
       .     .                       *** **
                !      .           ._*.                       .
             - -*- -       .-'-.   !  !     .
    .    .      *       .-' .-. '-.!  !             .              .
               ***   .-' .-'   '-. '-.!    .
       *       ***.-' .-'         '-. '-.                   .
       *      ***$*.-'               '-. '-.     *
  *   ***     * ***     ___________     !-..!-.  *     *         *    *
  *   ***    **$** *   !__!__!__!__!    !    !  ***   ***    .   *   ***
 *** ****    * *****   !__!__!__!__!    !      .***-.-*** *     *** * #_
**********  * ****$ *  !__!__!__!__!    !-..--'*****   # '*-..---# ***
**** *****  * $** ***      .            !      *****     ***       ***
************ ***** ***-..-' -.._________!     *******    ***      *****
***********   .-#.-'           '-.-''-..!     *******   ****...     #
  # ''-.---''                           '-....---#..--'****** ''-.---''-
                  Joyeux Noël !                           # 
-->
  <div id="container">
    <div id="page">
      <div class="col fixed right w280">
        <div class="clearfix mb20">
          <?php if(!empty($me['picture'])): ?>
          <img src="<?= $me['picture'] ?>" style="width:75px; float:left; margin-right:10px"/>
          <?php endif; ?>
          <?= h($me['name']) ?><br/>
          <?= $me['email'] ?>
        </div>
        <div class="center">
            <img src="http://www.aufildulean.fr/wp-content/uploads/2013/12/pere-noel.jpg" width="200" height="200">
        </div>
      </div>
      <div class="col variable r280" style="right:290px">
<?php
}

function htmlEnd() {
?>
      </div>
    </div>
  </div>
</body>
</html>
<?php
}


require __DIR__.'/../google-api-php-client/src/Google_Client.php';
require __DIR__.'/../google-api-php-client/src/contrib/Google_Oauth2Service.php';

$participants = include __DIR__.'/../participants.php';

$client = new Google_Client();
$client->setClientId('**');
$client->setClientSecret('**');
$client->setRedirectUri('http://secretsantaevaneos.hurpeau.com/');
//$client->setDevelopperKey('');

$oauthService = new Google_Oauth2Service($client);

session_start();

if (isset($_REQUEST['logout'])) {
  unset($_SESSION['access_token']);
}


if (isset($_GET['code'])) {
  $client->authenticate();
  $_SESSION['access_token'] = $client->getAccessToken();
  header('Location: http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']);
}

if (isset($_SESSION['access_token'])) {
  $client->setAccessToken($_SESSION['access_token']);
}

if (!$client->getAccessToken()) {
  $authUrl = $client->createAuthUrl();
  htmlStart();
  echo '<div class="center">';
  echo '<a class="button big hugeinfo" href="' . $authUrl . '">Connexion avec Google</a>';
  echo '</div>';
?>
<pre class="mt20">
       .     .                       *** **
                !      .           ._*.                       .
             - -*- -       .-'-.   !  !     .
    .    .      *       .-' .-. '-.!  !             .              .
               ***   .-' .-'   '-. '-.!    .
       *       ***.-' .-'         '-. '-.                   .
       *      ***$*.-'               '-. '-.     *
  *   ***     * ***     ___________     !-..!-.  *     *         *    *
  *   ***    **$** *   !__!__!__!__!    !    !  ***   ***    .   *   ***
 *** ****    * *****   !__!__!__!__!    !      .***-.-*** *     *** * #_
**********  * ****$ *  !__!__!__!__!    !-..--'*****   # '*-..---# ***
**** *****  * $** ***      .            !      *****     ***       ***
************ ***** ***-..-' -.._________!     *******    ***      *****
***********   .-#.-'           '-.-''-..!     *******   ****...     #
  # ''-.---''                           '-....---#..--'****** ''-.---''-
                  Joyeux Noël !                           # 
</pre>
<?php
  htmlEnd();
  exit;
}

if (isset($_POST['to']) && !isset($participants[$_POST['to']])) {
  htmlStart();
  echo 'STOP trying to hack me';
  htmlEnd();
  exit;
}


mysqli_report(MYSQLI_REPORT_STRICT);
$connect = new mysqli('localhost', '****', '*****','secretsantaevaneos');

$me = $oauthService->userinfo_v2_me->get();

if( substr($me['email'],-12) !== '@evaneos.com' ) exit('forbidden');

$connect->query('INSERT INTO connections(`email`, `name`, `access_token`)'
        .' VALUES ("'.$connect->real_escape_string($me['email']).'", "'
                .$connect->real_escape_string($me['name']).'", "'
                .$connect->real_escape_string($_SESSION['access_token'])
        .'")');

if (empty($_POST) || empty($_POST['to']) || empty($participants[$_POST['to']])
        || empty($_POST['message']) ) {
  $messageSent = false;
} else {
  require __DIR__.'/../PHPMailer/PHPMailerAutoload.php';

  $mail = new PHPMailer;
  $mail->isSMTP();
  $mail->Host = 'localhost';
  $mail->SMTPsecure = 'tls';

  $mail->From = 'secretsantaevaneos@hurpeau.com';
  $mail->FromName = 'Secret Santa';
  $mail->addAddress($_POST['to'], $participants[$_POST['to']]);
  $mail->isHTML(true);

  $mail->Subject = empty($_POST['subject']) ? 'Message de ton S\'Santa' : $_POST['subject'];

  $message = trim(htmlentities($_POST['message'],ENT_QUOTES,'UTF-8',true));
  $message = str_replace("\n\n",'</p><p>',$message);
  $message = '<p>'.nl2br($message).'</p>'
      .'<p style="font-size:9px">'
        .' Sent by <a href="http://secretsantaevaneos.hurpeau.com/">http://secretsantaevaneos.hurpeau.com/</a>. Please do not respond.</p>';
  $mail->Body = '<html><body>'.$message.'</body></html>';
  $mail->AltBody = $message;

  if (!$mail->send()) {
    exit('Mailer error '.$mail->ErrorInfo);
  }

  $messageSent = true;
}

htmlStart($me);
?>

<?php if($messageSent): ?>
  <div class="message success">Votre message a bien été envoyé!</div>
  <a href="/">Retour</a>
<?php else: ?>
<form method="post" class="big">
<h3>1. Sélectionnez votre destinataire</h3>
<select name="to" required="required">
  <?php foreach($participants as $email => $name): ?>
  <option value="<?= h($email) ?>"><?= h($name) ?></option>
  <?php endforeach ?>
</select>

<h3>2. Facultatif : Saisissez le sujet du message</h3>
<div class="input text">
  <input type="text" name="subject" class="wp100"/>
</div>


<h3>3. Saisissez votre message</h3>
<div class="input text">
  <textarea required="required" name="message" rows="12" class="wp100"></textarea>
</div>

<h3>3. Envoyez-le !</h3>
<div class="submit">
  <input class="submit big biginfo" type="submit" value="Envoyer"/>
</div>

</form>
<?php endif; ?>

<?php 
  htmlEnd();
