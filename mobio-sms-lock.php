<?PHP
/*
   Plugin Name: Mobio SMS Lock
   Plugin URI: 
   Description: Плъгина позволява да създавате платени статиите (постове), достъпни след заплащането чрез SMS към системата на Mobio.bg.
   Author: mlazarov
   Version: 1.3
   Author URI: http://www.mobio.bg
   */



function mobio_smslock_the_content($content) {

	global $wp_query;
	$post = $wp_query->post;

	$mobio_smslock_entercode_form = '
	<form name="mobio_smslock_check" method="POST" action="">
	<input type="text" class="code" name="mobio_smslock_smscode" size="10" style="border: 1px solid #C0C0C0;width:150px;margin-bottom:15px">
	<input type="submit" name="check_code" value=" Проверка на кода " style="width:150px"/>
	</form>
	';
	$options = get_option("MobioSMSLock");

	$mobio_smslock_invalid_code = stripslashes($options["mobio_smslock_invalid_code"]);
	$servID = intval($options["mobio_smslock_servID"]);
	$mobio_smslock_postIDs = $options["mobio_smslock_postIDs"];

	$intro = get_extended($post->post_content);
	$intro = $intro["main"];

	if(is_single()) { 

		if(is_array($mobio_smslock_postIDs) and $mobio_smslock_postIDs[$post->ID] != 0) {

			if($_POST["mobio_smslock_smscode"]) {
				$mobio_smslock_smscode = $_POST["mobio_smslock_smscode"];
			}elseif($_COOKIE["mobio_smslock_smscode"]) {
				$mobio_smslock_smscode = $_COOKIE["mobio_smslock_smscode"];
			}

			$mobio_smslock_entercode = stripslashes($options[$mobio_smslock_postIDs[$post->ID]]["mobio_smslock_formtext"]).$mobio_smslock_entercode_form;

			$mobio_smslock_invalid_code = stripslashes($options[$mobio_smslock_postIDs[$post->ID]]["mobio_smslock_invalid_code"]);

			if(!$mobio_smslock_smscode) {

				return $intro.$mobio_smslock_entercode;

			}else{
				$servIDs = split(',', $options[$mobio_smslock_postIDs[$post->ID]]["mobio_smslock_servIDs"]);

				while($servIDs) {
					$servID = array_pop($servIDs);

					if(mobio_smslock_checkcode($servID, $mobio_smslock_smscode)) {
						return '<script language="Javascript">mobio_setcookie("mobio_smslock_smscode", "'.$mobio_smslock_smscode.'", 10);</script>'.$content;
					}
				}
				return $intro.$mobio_smslock_invalid_code.$mobio_smslock_entercode;

			}
		}else{
			return $content;
		}
	}else{

		return $content;
	}

}

function mobio_smslock_add_pages() {

	add_options_page('SMS заключване', 'SMS заключване', 10, 'mobio_smslock_settings', 'mobio_smslock_settings');
	add_meta_box('mobio_smslock_id', 'Mobio SMS заключване', 'mobio_smslock_post_meta_box', 'post', 'side');

}

function mobio_smslock_post_meta_box() {

	$postID = $_REQUEST["post"];
	$options = get_option("MobioSMSLock");

	$mobio_smslock_postIDs = $options["mobio_smslock_postIDs"];
	$checked = (is_array($mobio_smslock_postIDs) and in_array($postID, $mobio_smslock_postIDs));
	if($checked) $checked = 'checked';
//	echo '<input type="checkbox" name="mobio_smslock"'.$checked.'/> платен достъп<br/><br/>';
	echo 'Платен достъп:<pre>';
	//print_r($options);

	$locked_postIDs = $options["mobio_smslock_postIDs"];
	$selected_pos = intval($options["mobio_smslock_postIDs"][$postID]);

	echo '</pre><select name="mobio_smslock_pos">';
	echo '<option value="0"'.($selected_pos == 0 ? ' selected' : '').'>-- изключен --</option>';
	if(is_array($options)) {

		$apos = 1;
		while($apos <= sizeof($options)) {

			if(is_array($options[$apos])) {
				echo '<option value="'.$apos.'"'.($selected_pos == $apos ? ' selected' : '').'>';
				echo $options[$apos]['mobio_smslock_name'];
				echo '</option>';
			}

			$apos += 1;
		}

	}
	echo '</select>';

}

function mobio_smslock_settings() {

	$options = get_option("MobioSMSLock");

	echo '<div class="wrap"><h2>Настройки на Mobio SMS заключване</h2>';

	if($_POST["posted"]) {
		$_GET["edit_tariff_form"] = "";
		$_GET["new_tariff_form"] = "";

		$pos = $_POST["pos"];

		if($pos == 0) {
			$maxpos = is_array($options) ? max(array_keys($options)) : 0;
			$pos = $maxpos + 1;
		}
		$options[$pos]['mobio_smslock_name'] = $_POST['mobio_smslock_name'];
		$options[$pos]['mobio_smslock_servIDs'] = $_POST['mobio_smslock_servIDs'];
		$options[$pos]['mobio_smslock_invalid_code'] = $_POST['mobio_smslock_invalid_code'];
		$options[$pos]['mobio_smslock_formtext'] = $_POST['mobio_smslock_formtext'];

		update_option("MobioSMSLock", $options);

		echo '<div id="message" class="updated fade"><p><strong>Настройките са запазени...</strong></p></div>';
		//print_r($options);
	}

	if($_GET["delete_tariff"] == 1) {

		$pos = $_GET["pos"];
		$options[$pos] = null;
		update_option("MobioSMSLock", $options);

	}elseif($_GET["edit_tariff"] == 1) {

		$pos = $_GET["pos"];

		$options[$pos]['mobio_smslock_name'] = $_POST['mobio_smslock_name'];
		$options[$pos]['mobio_smslock_servIDs'] = $_POST['mobio_smslock_servIDs'];
		$options[$pos]['mobio_smslock_invalid_code'] = $_POST['mobio_smslock_invalid_code'];
		$options[$pos]['mobio_smslock_formtext'] = $_POST['mobio_smslock_formtext'];

		update_option("MobioSMSLock", $options);

		echo '<div id="message" class="updated fade"><p><strong>Настройките са запазени...</strong></p></div>';

	}

	if($_GET["new_tariff_form"] or $_GET["edit_tariff_form"]) {
		if($_GET["edit_tariff_form"]) echo '<h3>Редактиране на тарифа</h3>';
		if($_GET["new_tariff_form"]) {

			echo '<h3>Нова тарифа</h3>';
			$pos = 0;
			$button_title = "Запиши тарифата";

			$values = array(
					'mobio_smslock_name' => 'SMS Номер: XXXX цена: 1.20лв.',
					'mobio_smslock_servIDs' => 29,
					'mobio_smslock_invalid_code' => '<font color="red">Невалиден или изтекъл SMS код</font><br />',
					'mobio_smslock_formtext' => 'За да получите достъп до статията изпратете SMS на номер ХХХХ със съдържание YYY (цена: 1.20лв.)'
			);

		}elseif($_GET["edit_tariff_form"]) {

			echo '<h3>Редактиране на тарифа</h3>';
			$pos = $_GET["pos"];

			$values = array(
					'mobio_smslock_name' => $options[$pos]['mobio_smslock_name'],
					'mobio_smslock_servIDs' => $options[$pos]['mobio_smslock_servIDs'],
					'mobio_smslock_invalid_code' => $options[$pos]['mobio_smslock_invalid_code'],
					'mobio_smslock_formtext' => $options[$pos]['mobio_smslock_formtext']
			);
			$button_title = "Промени тарифата";
		}
	
		echo '<form name="mobio_smslock_settings" method="post">';
		echo '<input type="hidden" name="posted" value="1"/>';
		echo '<input type="hidden" name="pos" value="'.$pos.'"/>';
		echo '<input type="hidden" name="new_tariff_form" value=""/>';


		echo '<fieldset><legend>Име:</legend><div><input type="text" class="code" size="35" name="mobio_smslock_name" value="'.$values['mobio_smslock_name'].'" id="mobio_smslock_name_id"/></div></fieldset><br/>';
		echo '<fieldset><legend>Mobio servIDs:</legend><div><input type="text" class="code" size="35" name="mobio_smslock_servIDs" value="'.$values['mobio_smslock_servIDs'].'" id="mobio_smslock_servIDs_id"/></div></fieldset><br/>';
		echo '<fieldset><legend>Отговор при невалиден код:</legend><div><textarea title="true" rows="2" cols="60" name="mobio_smslock_invalid_code" id="mobio_smslock_invalid_code_id">'.stripslashes($values['mobio_smslock_invalid_code']).'</textarea></div></fieldset><br/>';
		echo '<fieldset><legend>Указания за изпращане на SMS:</legend><div><textarea title="true" rows="2" cols="60" name="mobio_smslock_formtext" id="mobio_smslock_formtext_id">'.stripslashes($values['mobio_smslock_formtext']).'</textarea><br/></div></fieldset><br/>';
		echo '<input type="submit" name="submit" value=" '.$button_title.'" onclick="if(mobio_smslock_check()){return true;} return false;"/></form>';

		echo '<script language="Javascript">';
		echo 'function mobio_smslock_check() {

			var smslock_name = jQuery("#mobio_smslock_name_id");
			var smslock_servIDs = jQuery("#mobio_smslock_servIDs_id");
			var smslock_invalid_code = jQuery("#mobio_smslock_invalid_code_id");
			var smslock_formtext = jQuery("#mobio_smslock_formtext_id");

			smslock_name.css({"border" : "1px solid #dfdfdf","background-color" : "#FFFFFF" });
			smslock_servIDs.css({"border" : "1px solid #dfdfdf","background-color" : "#FFFFFF" });
			smslock_invalid_code.css({"border" : "1px solid #dfdfdf","background-color" : "#FFFFFF" });
			smslock_formtext.css({"border" : "1px solid #dfdfdf","background-color" : "#FFFFFF" });


			var ret = true;
			if(smslock_name.val() == "") {
				smslock_name.css({"border" : "1px solid red","background-color" : "#FFEBE8" });
				ret = false;
			}
			if(smslock_servIDs.val() == "") {
				smslock_servIDs.css({"border" : "1px solid red","background-color" : "#FFEBE8" });
				ret = false;
			}
			if(smslock_invalid_code.val() == "") {
				smslock_invalid_code.css({"border" : "1px solid red","background-color" : "#FFEBE8" });
				ret = false;
			}
			
			if(smslock_formtext.val() == "") {
				smslock_formtext.css({"border" : "1px solid red","background-color" : "#FFEBE8" });
				ret = false;
			}

			return ret;
			}
			';
		echo '</script>';
	}else{
		echo '<h3>Тарифи</h3>';

		echo '<a class="button" href="?page=mobio_smslock_settings&new_tariff_form=1">Нова тарифа</a><br/><br/>';

		echo '<table class="widefat" cellspacing="0">';
		echo '<thead><tr><th>Име</th><th>servIDs</th><th>Указания</th><th>При грешен код</th></tr></thear>';

		if(is_array($options)) {

			$apos = 1;
			while($apos <= sizeof($options)) {
				if(is_array($options[$apos])) {
					echo '<tr><td>';
					echo $options[$apos]['mobio_smslock_name'];
					echo '<div id="row-actions"><span class="0"><a class="edit" href="?page=mobio_smslock_settings&edit_tariff_form=1&pos='.$apos.'">Edit</a></span> | <span class="1"><a class="delete" href="?page=mobio_smslock_settings&delete_tariff=1&pos='.$apos.'" onclick="if ( confirm(\'Сигурни ли сте, че желаете да изтриете тази тарифа?\') ) { return true;}return false;">Delete</a></span></div></td><td>';
					echo $options[$apos]['mobio_smslock_servIDs'];
					echo '</td><td>';
					echo $options[$apos]['mobio_smslock_formtext'];
					echo '</td><td>';
					echo $options[$apos]['mobio_smslock_invalid_code'];
					echo '</td></tr>';
				}

				$apos += 1;
			}

		}
		echo '</table>';
	}

	echo '<h2>Инструкции:</h2>';
	echo '<p>За да ползвате модула за SMS заключване за Wordpress е необходимо първоначално да дефинирате <i>SMS тарифи</i>. При редактирането или създаването на постинг ще може да изберете SMS тарифата за всеки един постинг по отделно.</p>';
	echo '<p><i>Mobio servIDs</i> са уникалните ID номера на Вашатите услуги <a href="http://help.mobio.bg/show2" target="_blank">Проверка на код</a>. Може да задавате повече от един servID, като изпозлвате \',\' (запетая) за разделител. Например: <i>29,30,31</i>. За тестове може да използвате servID <b>29</b> и SMS код <b>T4JC6G</b>.<br/><br/>
		<i>Отговор при невалиден код</i> е съобщението за грешка, което потребителите ще виждат при въвеждане на невалиден код.<br/><br/>
		<i>Указания за изпращане на SMS</i> е текста, който ще виждат потребителите с указания за номера, към който трябва да се изпрати SMS и с какво съдържание. Важно е в указанията да включите и цената за изпращане на SMS към съответния кратък номер.</p><br/>
		<a href="http://mobio.bg" target="_blank">http://mobio.bg</a>
		';
	echo '</div>';
}

function mobio_smslock_save_post($postID) {

	$options = get_option("MobioSMSLock");
	$mobio_smslock_postIDs = $options["mobio_smslock_postIDs"];

	$post = get_post($postID);

	if($post->post_type == 'post') {
		if($_REQUEST["mobio_smslock_pos"]) {

			if(!$mobio_smslock_postIDs) $mobio_smslock_postIDs = array();

			$mobio_smslock_postIDs[$postID] = intval($_REQUEST["mobio_smslock_pos"]);
			$options["mobio_smslock_postIDs"] = $mobio_smslock_postIDs;

		}else{

			$options["mobio_smslock_postIDs"][$postID] = 0;
			//$options["mobio_smslock_postIDs"] = $new_mobio_smslock_postIDs;

		}

		update_option("MobioSMSLock", $options);
	}

}

function mobio_smslock_checkcode($servID, $code, $debug=0) {

	$ret = 0;

	$mobio_socket = fsockopen("www.mobio.bg", 80);

	if(!$mobio_socket) {
		if($debug)
			echo "Unable to connect to mobio.bg server\n";
		$ret = 0;
	}else{
		$request .= "GET http://www.mobio.bg/code/checkcode.php?servID=$servID&code=$code HTTP/1.0\r\n\r\n";
		fwrite($mobio_socket, $request);
		$result = fread($mobio_socket, 255);
		if(strstr($result, "PAYBG=OK")) {
			$ret = 1;
		}else{
			$ret = 0;
			if($debug)
				echo strstr($result, "PAYBG");
		}
		fclose($mobio_socket);
	}

	return $ret;


}

function mobio_smslock_wp_head() {

	echo '<script language="Javascript">function mobio_setcookie( name, value, expires, path, domain, secure ) {var today = new Date();today.setTime( today.getTime() );if(expires){expires = expires * 1000 * 60 * 60 * 24;}var expires_date = new Date( today.getTime() + (expires) );document.cookie = name + "=" +escape( value ) +( ( expires ) ? ";expires=" + expires_date.toGMTString() : "" ) + ( ( path ) ? ";path=" + path : "" ) + ( ( domain ) ? ";domain=" + domain : "" ) +( ( secure ) ? ";secure" : "" );}function mobio_getcookie(name){var start = document.cookie.indexOf( name + "=" );var len = start + name.length + 1;if((!start)&&(name!=document.cookie.substring(0,name.length))){return null;}if(start==-1)return null;var end = document.cookie.indexOf( ";", len );if ( end == -1 ) end = document.cookie.length;return unescape( document.cookie.substring( len, end ) );}</script>';

}

add_action('wp_head', 'mobio_smslock_wp_head');
add_filter('the_content', 'mobio_smslock_the_content');
add_action('admin_menu', 'mobio_smslock_add_pages');
add_action('save_post', 'mobio_smslock_save_post');

?>
