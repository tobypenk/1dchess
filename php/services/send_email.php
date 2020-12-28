<?php
	
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
    
	include_once "../libraries/db_functions.php";
    
    $subject = $_GET["subject"];
    $message = $_GET["message"];
    $recipient_email = $_GET["recipient_email"];
    $recipient_username = $_GET["recipient_username"];
    
    include "email_credentials.php";
    
    require '../mailer/vendor/autoload.php';
    
    $mail = new PHPMailer(true);                              	// Passing `true` enables exceptions
    try {

	    $host_base = "a2plcpnl0058";
	    
        //Server settings
        //$mail->SMTPDebug = 2;                                 	// Enable verbose debug output
        $mail->isSMTP();                                      	// Set mailer to use SMTP
        $mail->Host = $host_base . '.prod.iad2.secureserver.net';  // Specify main and backup SMTP servers
        $mail->SMTPAuth = true;                             	// Enable SMTP authentication
        
        $mail->Username = $email_username;      		
        $mail->Password = $email_password;  
        $mail->SMTPSecure = 'ssl';                            	// Enable TLS encryption, `ssl` also accepted
        $mail->Port = 465;                                    	// TCP port to connect to

        //Outgoing
        // Server = p3plcpnl0884.prod.phx3.secureserver.net
        // IMAP: Port 993
        // POP3: Port 995

        //Recipients
        $mail->setFrom('admin@1dchess.com', '1dchess');
        $mail->addAddress(
        	$recipient_email,
        	$recipient_username
        );     // Add a recipient; name optional
        //$mail->addReplyTo('info@example.com', 'Information');
        $mail->addBCC('tobypenk@gmail.com');
        //$mail->addBCC('bcc@example.com');

        //Attachments
        //$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name

        //Content
        $mail->isHTML(true);                                  // Set email format to HTML
        $mail->Subject = $subject;
        $mail->Body    = $message;
        $mail->AltBody = $message; // plain text

        $mail->send();
        $success_message = 'Message has been sent.'; //.json_encode($order_details);
        $success_flag = 1;
    } catch (Exception $e) {
        $success_message = 'Message could not be sent. Mailer Error: '. $mail->ErrorInfo;
        $success_flag = 0;
    }
    
    if (!isset($prevent_echo)) echo json_encode(["message"=>$success_message]);

?>




