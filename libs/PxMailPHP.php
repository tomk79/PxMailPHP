<?php

/**
 * メール送信クラス PxMailPHP
 * @author Tomoya Koyanagi.
 */
class PxMailPHP{

	private $from = array( 'name' => null , 'address' => null );
	private $sender = array();
	private $to = array();
	private $cc = array();
	private $bcc = array();
	private $subject = '';
	private $messages = array(
		'text'=>null ,
		'html'=>null
	);
	private $error_to = array();
	private $return_to = array();
	private $attach = array();
	private $message_charset = 'UTF-8';//←古い日本語の形式に合わせるなら ISO-2022-JP

	/**
	 * コンストラクタ
	 */
	public function __construct(){
	}

	/**
	 * 送信者(From)を設定する
	 */
	public function set_from( $address , $name = null ){
		if( !$this->check_email( $address ) ){
			return	false;
		}
		$this->from['address'] = $address;
		$this->from['name'] = $name;
		return	true;
	}

	/**
	 * 送信者(Sender)を設定する
	 */
	public function set_sender( $address , $name = null ){
		if( !$this->check_email( $address ) ){
			return	false;
		}
		$this->sender['address'] = $address;
		$this->sender['name'] = $name;
		return	true;
	}

	/**
	 * error_toを設定する
	 */
	public function set_error_to( $address , $name = null ){
		if( !$this->check_email( $address ) ){
			return	false;
		}
		$this->error_to['address'] = $address;
		$this->error_to['name'] = $name;
		return	true;
	}

	/**
	 * return_toを設定する
	 */
	public function set_return_to( $address , $name = null ){
		if( !$this->check_email( $address ) ){
			return	false;
		}
		$this->return_to['address'] = $address;
		$this->return_to['name'] = $name;
		return	true;
	}

	/**
	 * サブジェクトを設定する
	 */
	public function set_subject( $subject ){
		$this->subject = $subject;
		return	true;
	}

	/**
	 * 本文(textパート)を設定する
	 */
	public function set_text_message( $text ){
		$this->messages['text'] = $text;
		return true;
	}

	/**
	 * 本文(HTMLパート)を設定する
	 */
	public function set_html_message( $src ){
		$this->messages['html'] = $src;
		return true;
	}

	/**
	 * 本文を消去する
	 */
	public function clear_messages(){
		$this->messages = array(
			'text'=>null ,
			'html'=>null
		);
		return true;
	}

	/**
	 * 添付ファイルを追加する
	 */
	public function put_attach( $name , $contenttype , $encode , $body , $disposition = null ){
		#	$name → 添付ファイル名
		#	$contenttype → MIME
		#	$encode → 7bit、base64、file
		#	$body → データ本体。encodeがfileの場合はファイルのパス。
		#	$disposition → 任意。添付かどうかを指定？

		if($encode == 'file'){
			$body = @realpath($body);
			if(!@is_file($body)){
				return	false;
			}
		}
		$MEMO['name'] = $name;
		$MEMO['Content-type'] = $contenttype;
		$MEMO['encode'] = $encode;
		$MEMO['body'] = $body;
		$MEMO['Content-Disposition'] = $disposition;
		array_push( $this->attach , $MEMO );
		return	true;
	}

	/**
	 * 宛先を追加する
	 */
	public function put_to( $address , $name = null ){
		if( !$this->check_email( $address ) ){
			//メールアドレスの形式が不正です
			return	false;
		}
		array_push( $this->to , array( 'address' => $address , 'name' => $name ) );
		return	true;
	}

	/**
	 * CCを追加する
	 */
	public function put_cc( $address , $name = null ){
		if( !$this->check_email( $address ) ){
			//メールアドレスの形式が不正です
			return	false;
		}
		array_push( $this->cc , array( 'address' => $address , 'name' => $name ) );
		return	true;
	}

	/**
	 * BCCを追加する
	 */
	public function put_bcc( $address , $name = null ){
		if( !$this->check_email( $address ) ){
			//メールアドレスの形式が不正です
			return	false;
		}
		array_push( $this->bcc , array( 'address' => $address , 'name' => $name ) );
		return	true;
	}

	/**
	 * メールアドレスの形式をチェック
	 */
	private function check_email( $str ){
		//注：このロジックは概ね拾えるが、完全ではないかも知れない。
		if( !preg_match( '/^[\-\_\.a-zA-Z0-9]+\@[a-zA-Z0-9\-\_][\-\_\.a-zA-Z0-9]*[a-zA-Z0-9]+\.[a-zA-Z]{2,}$/i' , $str ) ){
			return	false;
		}
		return	true;
	}

	/**
	 * Eメールを送信する
	 */
	public function send(){
		$charset = $this->message_charset;
		$is_attached_files = false;

		if( count( $this->attach ) ){
			$is_attached_files = true;
		}
		$str_boundary = '----_PX'.md5( time() ).'_MULTIPART_MIXED_';


		$head = '';
		#--------------------------------------
		#	From
		if( strlen( $this->from['name'] ) && strlen( $this->from['address'] ) ){
			$head .= 'From: '.mb_encode_mimeheader( $this->from['name'] , $charset , 'B' ).' <'.$this->from['address'].'>'."\n";
		}elseif( strlen( $this->from['address'] ) ){
			$head .= 'From: '.$this->from['address'].''."\n";
		}
		#--------------------------------------
		#	Sender
		if( strlen( $this->sender['name'] ) && strlen( $this->sender['address'] ) ){
			$head .= 'Sender: '.mb_encode_mimeheader( $this->sender['name'] , $charset , 'B' ).' <'.$this->sender['address'].'>'."\n";
		}elseif( strlen( $this->sender['address'] ) ){
			$head .= 'Sender: '.$this->sender['address'].''."\n";
		}
		#--------------------------------------
		#	宛先系
		if( count($this->to) ){
			$head .= 'To: ';
			foreach( $this->to as $Line ){
				if( strlen( $Line['name'] ) && strlen( $Line['address'] ) ){
					$head .= ''.mb_encode_mimeheader( $Line['name'] , $charset , 'B' ).' <'.$Line['address'].'>,';
				}elseif( strlen( $Line['address'] ) ){
					$head .= ''.$Line['address'].',';
				}
			}
			$head = preg_replace( '/,+$/' , '' , $head );
			$head .= "\n";
		}
		if( count($this->cc) ){
			$head .= 'Cc: ';
			foreach( $this->cc as $Line ){
				if(strlen($Line['name'])){
					$head .= ''.mb_encode_mimeheader( $Line['name'] , $charset , 'B' ).' <'.$Line['address'].'>,';
				}else{
					$head .= ''.$Line['address'].',';
				}
			}
			$head = preg_replace( '/,+$/' , '' , $head );
			$head .= "\n";
		}
		if( count($this->bcc) ){
			$head .= 'Bcc: ';
			foreach( $this->bcc as $Line ){
				if(strlen($Line['name'])){
					$head .= ''.mb_encode_mimeheader( $Line['name'] , $charset , 'B' ).' <'.$Line['address'].'>,';
				}else{
					$head .= ''.$Line['address'].',';
				}
			}
			$head = preg_replace( '/,+$/' , '' , $head );
			$head .= "\n";
		}
		#--------------------------------------
		#	返信先
		if( count($this->return_to) ){
			$head .= 'Reply-To: ';
			if(strlen($this->return_to['name'])){
				$head .= mb_encode_mimeheader( $this->return_to['name'] , $charset , 'B' ).' <'.$this->return_to['address'].'>,';
			}else{
				$head .= $this->return_to['address'].',';
			}
			$head = preg_replace( '/,+$/' , '' , $head );
			$head .= "\n";
		}

		if( is_string( $this->messages['text'] ) && is_string( $this->messages['html'] ) ){
			$head .= 'Content-Type: multipart/alternative; boundary="'.$str_boundary.'"'."\n";
		}elseif( $is_attached_files ){
			$head .= 'Content-Type: multipart/mixed; boundary="'.$str_boundary.'"'."\n";
		}else{
			$head .= 'Content-Type: text/plain; charset="'.$charset.'"'."\n";
		}

		#--------------------------------------
		#	ここから本文作成
		$body_fin = '';//本文の初期化
		if( $is_attached_files || is_string( $this->messages['text'] ) && is_string( $this->messages['html'] ) ){
			#--------------------------------------
			#	添付ファイルが存在する場合(マルチパート)

			$body_fin .= "\n";
			if( is_string( $this->messages['text'] ) ){
				$body_fin .= '--'.$str_boundary."\n";
				$body_fin .= 'Content-type: text/plain; charset="'.$charset.'"'."\n";
				$body_fin .= 'Content-Transfer-Encoding: base64'."\n";
				$body_fin .= "\n";
				$body_fin .= base64_encode( mb_convert_encoding( $this->messages['text'] , $charset , mb_internal_encoding().',UTF-8,SJIS,EUC-JP,JIS' ) )."\n";
				$body_fin .= "\n";
			}
			if( is_string( $this->messages['html'] ) ){
				$body_fin .= '--'.$str_boundary."\n";
				$body_fin .= 'Content-type: text/html; charset="'.$charset.'"'."\n";
				$body_fin .= 'Content-Transfer-Encoding: base64'."\n";
				$body_fin .= "\n";
				$body_fin .= base64_encode( mb_convert_encoding( $this->messages['html'] , $charset , mb_internal_encoding().',UTF-8,SJIS,EUC-JP,JIS' ) )."\n";
				$body_fin .= "\n";
			}
			$body_keys = array_keys( $this->attach );
			foreach( $body_keys as $Line ){
				if( !$this->attach[$Line]['encode'] ){
					$this->attach[$Line]['encode'] = '7bit';
				}
				switch( $this->attach[$Line]['encode'] ){
					#--------------------------------------
					case '7bit':
					case '':
					case null:
						$body_fin .= "\n";
						$body_fin .= '--'.$str_boundary."\n";
						$body_fin .= 'Content-type: '.$this->attach[$Line]['Content-type'].'; charset="'.$charset.'"'."\n";
						if( $this->attach[$Line]['Content-Disposition'] ){
							if( !strlen( $this->attach[$Line]['name'] ) ){
								$this->attach[$Line]['name'] = basename( $this->attach[$Line]['body'] );
							}
							$body_fin .= 'Content-Disposition: '.$this->attach[$Line]['Content-Disposition'].'; filename="'.mb_encode_mimeheader( $this->attach[$Line]['name'] , $charset ).'"'."\n";
						}
						$body_fin .= 'Content-Transfer-Encoding: '.$this->attach[$Line]['encode']."\n";
						$body_fin .= "\n";

						#	JISに変換して本文にセット
						$body_fin .= $this->attach[$Line]['body'];
						break;

					#--------------------------------------
					case 'base64':
						$body_fin .= "\n";
						$body_fin .= '--'.$str_boundary."\n";
						$body_fin .= 'Content-type: '.$this->attach[$Line]['Content-type'].';'."\n";
						if( $this->attach[$Line]['Content-Disposition'] ){
							if( !strlen( $this->attach[$Line]['name'] ) ){
								$this->attach[$Line]['name'] = basename( $this->attach[$Line]['body'] );
							}
							$body_fin .= 'Content-Disposition: '.$this->attach[$Line]['Content-Disposition'].'; filename="'.mb_encode_mimeheader( $this->attach[$Line]['name'] , $charset , 'B' ).'"'."\n";
						}
						$body_fin .= 'Content-Transfer-Encoding: '.$this->attach[$Line]['encode']."\n";
						$body_fin .= "\n";

						$body_fin .= $this->attach[$Line]['body'];
						break;

					#--------------------------------------
					case 'file':
						$body_fin .= "\n";
						$body_fin .= '--'.$str_boundary."\n";
						$body_fin .= 'Content-type: '.$this->attach[$Line]['Content-type'].';'."\n";
						if( $this->attach[$Line]['Content-Disposition'] ){
							if( !strlen( $this->attach[$Line]['name'] ) ){
								$this->attach[$Line]['name'] = basename( $this->attach[$Line]['body'] );
							}
							$body_fin .= 'Content-Disposition: '.$this->attach[$Line]['Content-Disposition'].'; filename="'.mb_encode_mimeheader( $this->attach[$Line]['name'] , $charset , 'B' ).'"'."\n";
						}
						$body_fin .= 'Content-Transfer-Encoding: base64'."\n";
						$body_fin .= "\n";

						if( @is_file( $this->attach[$Line]['body'] ) ){
							$body_fin .= base64_encode( file_get_contents( $this->attach[$Line]['body'] ) );
						}else{
							$body_fin .= 'ファイルがありません。'."\n";
						}
						break;

					#--------------------------------------
					default:
						$body_fin .= "\n";
						$body_fin .= '--'.$str_boundary."\n";
						$body_fin .= 'Content-type: '.$this->attach[$Line]['Content-type'].';'."\n";
						if( $this->attach[$Line]['Content-Disposition'] ){
							if( !strlen( $this->attach[$Line]['name'] ) ){
								$this->attach[$Line]['filename'] = basename( $this->attach[$Line]['body'] );
							}
							$body_fin .= 'Content-Disposition: '.$this->attach[$Line]['Content-Disposition'].'; filename="'.mb_encode_mimeheader( $this->attach[$Line]['name'] , $charset ).'"'."\n";
						}
						$body_fin .= 'Content-Transfer-Encoding: '.$this->attach[$Line]['encode']."\n";
						$body_fin .= "\n";
						$body_fin .= "\n";
						break;
				}
			}
			$body_fin .= "\n";
			$body_fin .= '--'.$str_boundary.'--'."\n";

		}else{
			#--------------------------------------
			#	添付ファイルがない場合
			$body_fin .= mb_convert_encoding( $this->messages['text'] , $charset , mb_internal_encoding().',UTF-8,SJIS,EUC-JP,JIS' )."\n";

		}

		$head = trim( $head );

		#--------------------------------------
		#	メールを発信する
		$results = $this->mail(
			null,	//	[to] は、$headの中に書かれています。
			mb_encode_mimeheader( $this->subject , $charset , 'B' ),
			$body_fin,
			$head
		);
		return	$results;
	}

	/**
	 * メールを発信する
	 */
	private function mail( $to , $subject , $message , $additional_headers = null , $additional_parameters = null ){
		#	基本的に、PHPの mail()関数の、ただのラッパです。
		#	サーバのPHPに設定されたメール送信プログラムが、
		#	特殊なパラメータなどを特に要求する場合には、
		#	このメソッドを拡張することで対応してください。
		return	mail( $to , $subject , $message , $additional_headers , $additional_parameters );
	}

}

?>