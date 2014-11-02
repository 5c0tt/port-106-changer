<html>
<?php 

    $server = 'mailhost.icompute.com';
    $whoamI = 'mercy.icompute.com';
    $pwport = 106;
    $smtpport = 25;
    $verboseMode = 0;   // 1 to have exchange output to page, 0 to not.

    $FormData = $HTTP_POST_VARS;

    $errMsg = '';
    if ($FormData['action'] == 'changeit')
    {
        
        $emailAddress = $FormData['emailAddress'];
        $oldPWD = $FormData['oldPWD'];
        $newPWD = $FormData['newPWD'];
        $newPWDConfirm = $FormData['newPWDConfirm'];
//
//      error checks
//
        if (empty($emailAddress))
           $errMsg .= 'No e-mail address entered.<br>';
        if (empty($oldPWD))
           $errMsg .= 'No current password entered.<br>';
        if (empty($newPWD))
           $errMsg .= 'No new password entered.<br>';
        if (empty($newPWDConfirm))
           $errMsg .= 'No confirmation password entered.<br>';
        if ($newPWD != $newPWDConfirm)
           $errMsg .= 'Passwords entered do not match.<br>';
//
//      Whitespace in fields poses security risk
//
        if (countwhite($emailAddress) > 0)
           $errMsg .= 'Whitespace cannot be in email address.<br>';
        if (countwhite($oldPWD) > 0)
           $errMsg .= 'Whitespace cannot be in password.<br>';
        if (countwhite($newPWD) > 0)
           $errMsg .= 'Whitespace cannot be in new password.<br>';

        if (empty($errMsg))    {
            $errMsg = ChangePWD($server, $emailAddress, $oldPWD, $newPWD);
        }
	$success = 'Password changed successfully.';
        if (empty($errMsg)) {
           $changeStatus = '<font color="green">'. $success.'</font>';
        } else {
           $changeStatus = '<font color="red">'. $errMsg .'</font>';
        }

        $tmpPWD = $newPWD;
        $newPWD = $oldPWD;
        $oldPWD = $tmpPWD;
    }


function countwhite($haystack)
{
   $wschars = " \t\r\n";
   $wsp = 0;

   for ( $i = 0 ; $i < strlen($wschars) ; $i++ ) {
	if (strpos($haystack, $wschars{$i}) !== false) $wsp++;
   }
   return $wsp;
}

function EchoStream($pipe_IN, $bufSize, $theCmd)
{ 
    global $verboseMode;

    $theResponse = fgets($pipe_IN, $bufSize);
    list($respCode, $respText) = sscanf($theResponse,'%d %s');
    if ($verboseMode)
    {
        echo ('Command: '. htmlspecialchars($theCmd) .'<BR>Response: '.nl2br($theResponse) . "\n");     //modified command for prettyness
    }
    return $respCode;
}


function ChangePWD($pwdServer, $userAddr, $oldPWD, $newPWD)
{
    global $whoamI;
    global $verboseMode;
    global $pwport;
    global $smtpport;

    $sockBuffer = 4096;
    
    $errMsg = '';
    sleep(5);
    if ($smtpSock = fsockopen($pwdServer, $smtpport, $errno, $errstr, 30)) {
        EchoStream($smtpSock, $sockBuffer, '(open)');
        $theCmd = 'HELO '.$whoamI."\r\n";
        fputs($smtpSock, $theCmd, strlen($theCmd));
        $heloResponse = EchoStream($smtpSock, $sockBuffer, $theCmd);
        if ($heloResponse != 250) {
            $errMsg = 'HELO rejected';
        } else {

           $domain = strrchr($userAddr, '@');
           if ($domain ==false || strlen($domain) < 2 ) { // @ not found
              $errMsg = 'error: invalid email address "'.$userAddr.'"';
           } else {
              $bogoAddr='BogusAddrBetterBeBad'.$domain;
              $theCmd = 'mail from: <'. $bogoAddr.">\r\n";
              fputs($smtpSock, $theCmd);
              $verifyResponse = EchoStream($smtpSock, $sockBuffer, $theCmd);
              if ($verifyResponse != 550) {
                  $errMsg = 'Email domain appears invalid, or not found.';
              }
           }
        }

        $theCmd = "QUIT\r\n";
        fputs($smtpSock, $theCmd);
        EchoStream($smtpSock, $sockBuffer, $theCmd);

        fclose($smtpSock);
    }
    else
    {
        $errMsg = "error connecting to $pwdServer 25<br>";
        $errMsg .= "$errstr ($errno)<br>";
    }

    if (!empty($errMsg)) return $errMsg;

    $errMsg = '';
    if ($pwdSock = fsockopen($pwdServer, $pwport, $errno, $errstr, 30)) {
        EchoStream($pwdSock, $sockBuffer, '(open)');
        $theCmd = 'USER '.$userAddr."\r\n";
        fputs($pwdSock, $theCmd, strlen($theCmd));
        $usrResponse = EchoStream($pwdSock, $sockBuffer, $theCmd);
        if ($usrResponse != 300) {
            $errMsg = 'Email Address Not valid!?';
        } else {

           $theCmd = 'PASS '. $oldPWD."\r\n";
           fputs($pwdSock, $theCmd);
           $verifyResponse = EchoStream($pwdSock, $sockBuffer, $theCmd);
           if ($verifyResponse != 200) {
               $errMsg = 'Email Address/Password Not valid.';
           } else {
               $theCmd = 'NEWPASS '. $newPWD."\r\n";
               fputs($pwdSock, $theCmd);
               $newPassResponse = EchoStream($pwdSock, $sockBuffer, $theCmd);
               if ($newPassResponse != 200) {
                   $errMsg = 'New password not accepted.';
               }
           }
        }

        $theCmd = "QUIT\r\n";
        fputs($pwdSock, $theCmd);
        EchoStream($pwdSock, $sockBuffer, $theCmd);

        fclose($pwdSock);
    }
    else
    {
        $errMsg = "error connecting to $pwdServer<br>";
        $errMsg .= "$errstr ($errno)<br>";
    }

    return $errMsg;
}
?>

<head>
    <title>Change Email Password on iCompute.com mail server</title>
		<meta http-equiv="content-type" content="text/html;charset=ISO-8859-1">
</head>
<body>
<br>
		You can use this form to change your e-mail password on the iCompute.com e-mail server.<br>
		<br>
		<center><b>Change Email Password</b></center>
		<?php    if (!empty($changeStatus))    echo "$changeStatus";    ?>
    <form action="<?= $_SERVER['PHP_SELF']    ?>" method="POST">
        <input type="hidden" name="action" value="changeit">       
        <table>
        	<tr>	<td align=right>Complete Email Address<BR>(i.e. user@domain.com):</td>
				<td><input type="text" size="48"  name="emailAddress" value="<?= $emailAddress?>"></td>
			</tr>
      	  <tr>	<td align=right>Current Password:</td>
			<td><input type="password" name="oldPWD" size="16"></td>
			</tr>
      	  <tr>	<td align=right>New Password:</td>
				<td><input type="password" name="newPWD" size="16"></td>
			</tr>
	     	<tr>	<td align=right>Confirm New Password:</td>
				<td><input type="password" name="newPWDConfirm" size="16"></td>
			</tr>
			<tr>	<td></td>
				<td><input type="submit" value="Change It"></td>
			</tr>
        </table>
        
    </form>
    
</body>
</html>
