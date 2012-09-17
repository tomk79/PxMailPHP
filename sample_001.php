<?php

	//  PxMailPHPをロード
	require_once( './libs/PxMailPHP.php' );
	$mail = new PxMailPHP();


	//  送信者を登録
	$mail->set_from( 'sample_from@example.com' , 'テストメッセージ送信者名' );

	//  宛先を登録
	$mail->put_to( 'sample_to@example.com' , 'テストメッセージ受信者名' );

	//  件名を登録
	$mail->set_subject( 'TEXT×HTMLのメールを送るテスト' );


	//  テキストメールのBODYを登録
	$textMsgBody = '';
	ob_start(); ?>
【TEXTメール本文メッセージ】
これはテストメッセージです。

<?php
	$textMsgBody .= ob_get_clean();
	$mail->set_text_message( $textMsgBody );


	//  HTMLメールのBODYを登録
	$htmlMsgBody = '';
	ob_start(); ?>
<!doctype html>
<html>
<head>
<meta charset="UTF-8" />
<title>HTMLメール本文メッセージ</title>
</head>
<body>
<h1>HTMLメール本文メッセージ</h1>
<p>これはテストメッセージです。</p>
</body>
</html>
<?php
	$htmlMsgBody .= ob_get_clean();
	$mail->set_html_message( $htmlMsgBody );



	$result = $mail->send();//メール送信
	var_dump($result);

?>