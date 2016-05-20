<?php

session_start();

$mailboxes_opts = $rows = $stats = '';
if (!empty($_POST['email'])) {
    $_SESSION['email'] = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) ? $_POST['email'] : '';
    $_SESSION['password'] = $_POST['password'];
    $_SESSION['imap_host'] = $_POST['imap_host'];
    $_SESSION['imap_port'] = $_POST['imap_port'];
}

if (!empty($_SESSION['email'])) {

    $mailbox = !empty($_POST['mailbox']) ? $_POST['mailbox'] : '{'.$_SESSION['imap_host'].':'.$_SESSION['imap_port'].'/imap/ssl/novalidate-cert}';

    $imap = imap_open("{$mailbox}", $_SESSION['email'], $_SESSION['password']) or die('Cannot connect to imap.websupport.sk: '.imap_last_error());

    $mailboxes = imap_list($imap, $mailbox, '*');

    /**
     * Docs at http://php.net/manual/en/function.imap-search.php
     **/
    $stats_array = array();
    $emails = imap_search($imap, 'ALL');
    foreach($emails as $email_id) {
        $header = imap_headerinfo($imap, $email_id, 0);
        $to_address = isset($header->toaddress) ? $header->toaddress : '';
        $to_domain = isset($header->to[0]->host) ? $header->to[0]->host : '';
        $reply_to_domain = isset($header->reply_to[0]->host) ? $header->reply_to[0]->host : '';
        $rows .= '<tr>
            <td>'.$email_id.'</td>
            <td>'.date('d.m.Y H:i', strtotime($header->date)).'</td>
            <td>'.$header->fromaddress.'</td>
            <td>'.$to_address.'</td>
            <td>'.$to_domain.'</td>
            <td>'.$header->reply_toaddress.'</td>
            <td>'.$reply_to_domain.'</td>
        </tr>';

        if (empty($stats_array[$to_domain])) {
            $stats_array[$to_domain] = 0;
        }
        if (empty($stats_array[$reply_to_domain])) {
            $stats_array[$reply_to_domain] = 0;
        }

        $stats_array[$to_domain] += 1;
        $stats_array[$reply_to_domain] += 1;
    }

    imap_close($imap);

    $counter = 1;
    arsort($stats_array);
    foreach ($stats_array as $domain => $count) {
        $stats .= '<tr>
            <td>'.$counter++.'</td>
            <td>'.$domain.'</td>
            <td>'.$count.'</td>
        </tr>';
    }

    foreach ($mailboxes as $mailbox) {
        $mailbox_label = preg_replace('/\{.*\}/', '', $mailbox);
        $selected = '';
        $mailboxes_opts .= sprintf('<option value="%s"%s>%s</option>',
             $mailbox
            ,$selected
            ,$mailbox_label
        );
    }
}

?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>IMAP filter</title>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">
</head>
<body>
<div class="container-fluid">
    <form action="index.php" method="post">
    <?php if (!isset($_SESSION['email'])): ?>
        <input type="email" name="email" placeholder="Email" autocomplete="off" autofocus>
        <input type="password" name="password" placeholder="Password">
        <input type="text" name="imap_host" placeholder="IMAP host" value="imap.host.sk">
        <input type="text" name="imap_port" placeholder="IMAP port" value="993">
        <button type="submit">Fetch emails from IMAP</button>
    <?php else: ?>
        <select onchange="document.forms[0].submit()" name="mailbox">
            <?php echo $mailboxes_opts ?>
        </select>
    <?php endif ?>
    </form>

    <table class="table table-striped table-bordered table-hover table-condensed">
        <tr>
            <th>ID</th>
            <th>Domain</th>
            <th>Email count</th>
        </tr>
        <?php echo $stats; ?>
    </table>
    <table class="table table-striped table-bordered table-hover table-condensed">
        <tr>
            <th>ID</th>
            <th>Date</th>
            <th>From</th>
            <th>To</th>
            <th>To domain</th>
            <th>ReplyTo</th>
            <th>ReplyTo domain</th>
        </tr>
        <?php echo $rows; ?>
    </table>
</div>
</body>
</html>
