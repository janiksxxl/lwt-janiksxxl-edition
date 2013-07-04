<?php

/**************************************************************
"Learning with Texts" (LWT) is released into the Public Domain.
This applies worldwide.
In case this is not legally possible, any entity is granted the
right to use this work for any purpose, without any conditions, 
unless such conditions are required by law.

Developed by J. P. in 2011, 2012, 2013.
***************************************************************/

/**************************************************************
Call: edit_languages.php?....
      ... refresh=[langid] ... reparse all texts in lang
      ... del=[langid] ... do delete
      ... op=Save ... do insert new 
      ... op=Change ... do update 
      ... new=1 ... display new lang. screen 
      ... chg=[langid] ... display edit screen 
Manage languages
***************************************************************/

require_once( 'settings.inc.php' );
require_once( 'connect.inc.php' );
require_once( 'dbutils.inc.php' );
require_once( 'utilities.inc.php' );

pagestart('My Languages',true);
$message = '';

// REFRESH 

if (isset($_REQUEST['refresh'])) {
	$id = $_REQUEST['refresh'] + 0;
	$message2 = runsql('delete from ' . $tbpref . 'sentences where SeLgID = ' . $id, 
		"Sentences deleted");
	$message3 = runsql('delete from ' . $tbpref . 'textitems where TiLgID = ' . $id, 
		"Text items deleted");
	adjust_autoincr('sentences','SeID');
	adjust_autoincr('textitems','TiID');
	$sql = "select TxID, TxText from " . $tbpref . "texts where TxLgID = " . $id . " order by TxID";
	$res = do_mysql_query($sql);
	while ($record = mysql_fetch_assoc($res)) {
		$txtid = $record["TxID"];
		$txttxt = $record["TxText"];
		splitCheckText($txttxt, $id, $txtid );
	}
	mysql_free_result($res);
	$message = $message2 . " / " . $message3 . " / Sentences added: " . get_first_value('select count(*) as value from ' . $tbpref . 'sentences where SeLgID = ' . $id) . " / Text items added: " . get_first_value('select count(*) as value from ' . $tbpref . 'textitems where TiLgID = ' . $id);
}

// DEL

if (isset($_REQUEST['del'])) {
	$anztexts = get_first_value(
		'select count(TxID) as value from ' . $tbpref . 'texts where TxLgID = ' . 
		$_REQUEST['del']);
	$anzarchtexts = get_first_value(
		'select count(AtID) as value from ' . $tbpref . 'archivedtexts where AtLgID = ' . 
		$_REQUEST['del']);
	$anzwords = get_first_value(
		'select count(WoID) as value from ' . $tbpref . 'words where WoLgID = ' . 
		$_REQUEST['del']);
	if ( $anztexts > 0 || $anzarchtexts > 0 || $anzwords > 0) {
		$message = 'You must first delete texts, archived texts and words with this language!';
	} else {
		$message = runsql('delete from ' . $tbpref . 'languages where LgID = ' . $_REQUEST['del'], 
			"Deleted");
		adjust_autoincr('languages','LgID');
	}
}

// INS/UPD

elseif (isset($_REQUEST['op'])) {
	
	// INSERT
	
	if ($_REQUEST['op'] == 'Save')
		$message = runsql('insert into ' . $tbpref . 'languages (LgName, LgDict1URI, LgDict2URI, LgGoogleTranslateURI, LgExportTemplate, LgTextSize, LgCharacterSubstitutions, LgRegexpSplitSentences, LgExceptionsSplitSentences, LgRegexpWordCharacters, LgRemoveSpaces, LgSplitEachChar, LgRightToLeft) values(' . 
		convert_string_to_sqlsyntax($_REQUEST["LgName"]) . ', ' .
		convert_string_to_sqlsyntax($_REQUEST["LgDict1URI"]) . ', '. 
		convert_string_to_sqlsyntax($_REQUEST["LgDict2URI"]) . ', '.
		convert_string_to_sqlsyntax($_REQUEST["LgGoogleTranslateURI"]) . ', '.
		convert_string_to_sqlsyntax($_REQUEST["LgExportTemplate"]) . ', '.
		$_REQUEST["LgTextSize"] . ', '.
		convert_string_to_sqlsyntax_notrim_nonull($_REQUEST["LgCharacterSubstitutions"]) . ', '.
		convert_string_to_sqlsyntax($_REQUEST["LgRegexpSplitSentences"]) . ', '.
		convert_string_to_sqlsyntax_notrim_nonull($_REQUEST["LgExceptionsSplitSentences"]) . ', '.
		convert_string_to_sqlsyntax($_REQUEST["LgRegexpWordCharacters"]) . ', '.
		$_REQUEST["LgRemoveSpaces"] . ', '.
		$_REQUEST["LgSplitEachChar"] . ', '.
		$_REQUEST["LgRightToLeft"] . 
		')', 'Saved');
	
	// UPDATE
	
	elseif ($_REQUEST['op'] == 'Change') {
		// Get old values
		$sql = "select * from " . $tbpref . "languages where LgID=" . $_REQUEST["LgID"];
		$res = do_mysql_query($sql);
		$record = mysql_fetch_assoc($res);
		if ($record == FALSE) my_die("Cannot access language data: $sql");
		$oldCharacterSubstitutions = $record['LgCharacterSubstitutions'];
		$oldRegexpSplitSentences = $record['LgRegexpSplitSentences'];
		$oldExceptionsSplitSentences = $record['LgExceptionsSplitSentences'];
		$oldRegexpWordCharacters = $record['LgRegexpWordCharacters'];
		$oldRemoveSpaces = $record['LgRemoveSpaces'];
		$oldSplitEachChar = $record['LgSplitEachChar'];
		mysql_free_result($res);
	
		$needReParse = 
		(convert_string_to_sqlsyntax_notrim_nonull( $_REQUEST["LgCharacterSubstitutions"]) != convert_string_to_sqlsyntax_notrim_nonull( $oldCharacterSubstitutions)) 
			||
		(convert_string_to_sqlsyntax( $_REQUEST["LgRegexpSplitSentences"]) != convert_string_to_sqlsyntax( $oldRegexpSplitSentences)) 
			||
		(convert_string_to_sqlsyntax_notrim_nonull( $_REQUEST["LgExceptionsSplitSentences"]) != convert_string_to_sqlsyntax_notrim_nonull( $oldExceptionsSplitSentences)) 
			||
		(convert_string_to_sqlsyntax( $_REQUEST["LgRegexpWordCharacters"]) != convert_string_to_sqlsyntax( $oldRegexpWordCharacters)) 
			||
		($_REQUEST["LgRemoveSpaces"] != $oldRemoveSpaces) 
			||
		($_REQUEST["LgSplitEachChar"] != $oldSplitEachChar) 
			;
		$needReParse = ($needReParse ? 1 : 0);
	
		$message = runsql('update ' . $tbpref . 'languages set ' . 
		'LgName = ' . convert_string_to_sqlsyntax($_REQUEST["LgName"]) . ', ' . 
		'LgDict1URI = ' . convert_string_to_sqlsyntax($_REQUEST["LgDict1URI"]) . ', ' .
		'LgDict2URI = ' . convert_string_to_sqlsyntax($_REQUEST["LgDict2URI"]) . ', ' .
		'LgGoogleTranslateURI = ' . convert_string_to_sqlsyntax($_REQUEST["LgGoogleTranslateURI"]) . ', ' .
		'LgExportTemplate = ' . convert_string_to_sqlsyntax($_REQUEST["LgExportTemplate"]) . ', ' .
		'LgTextSize = ' . $_REQUEST["LgTextSize"] . ', ' .
		'LgCharacterSubstitutions = ' . convert_string_to_sqlsyntax_notrim_nonull($_REQUEST["LgCharacterSubstitutions"]) . ', ' .
		'LgRegexpSplitSentences = ' . convert_string_to_sqlsyntax($_REQUEST["LgRegexpSplitSentences"]) . ', ' .
		'LgExceptionsSplitSentences = ' . convert_string_to_sqlsyntax_notrim_nonull($_REQUEST["LgExceptionsSplitSentences"]) . ', ' .
		'LgRegexpWordCharacters = ' . convert_string_to_sqlsyntax($_REQUEST["LgRegexpWordCharacters"]) . ', ' .
		'LgRemoveSpaces = ' . $_REQUEST["LgRemoveSpaces"] . ', ' .
		'LgSplitEachChar = ' . $_REQUEST["LgSplitEachChar"] . ', ' . 
		'LgRightToLeft = ' . $_REQUEST["LgRightToLeft"] . 
		' where LgID = ' . $_REQUEST["LgID"], 'Updated');
		
		if ($needReParse) {
			$id = $_REQUEST["LgID"] + 0;
			runsql('delete from ' . $tbpref . 'sentences where SeLgID = ' . $id, 
				"Sentences deleted");
			runsql('delete from ' . $tbpref . 'textitems where TiLgID = ' . $id, 
				"Text items deleted");
			adjust_autoincr('sentences','SeID');
			adjust_autoincr('textitems','TiID');
			$sql = "select TxID, TxText from " . $tbpref . "texts where TxLgID = " . $id . " order by TxID";
			$res = do_mysql_query($sql);
			$cntrp = 0;
			while ($record = mysql_fetch_assoc($res)) {
				$txtid = $record["TxID"];
				$txttxt = $record["TxText"];
				splitCheckText($txttxt, $id, $txtid );
				$cntrp++;
			}
			mysql_free_result($res);
			$message .= " / Reparsed texts: " . $cntrp;
		} else {
			$message .= " / Reparsing not needed";
		}

	}

}

// NEW

if (isset($_REQUEST['new'])) {
	
	?>

	<h4>New Language <a target="_blank" href="info.htm#howtolang"><img src="icn/question-frame.png" title="Help" alt="Help" /></a> </h4>
	<form class="validate" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
	<table class="tab3" cellspacing="0" cellpadding="5">
	<tr>
	<td class="td1 right">Language:</td>
	<td class="td1"><input type="text" class="notempty setfocus" name="LgName" value="" maxlength="40" size="40" /> <img src="icn/status-busy.png" title="Field must not be empty" alt="Field must not be empty" /></td>
	</tr>
	<tr>
	<td class="td1 right">Dictionary 1 URI:</td>
	<td class="td1"><input type="text" class="notempty" name="LgDict1URI" value="glosbe_api.php?from=••&dest=••&phrase=###" maxlength="200" size="60" /> <img src="icn/status-busy.png" title="Field must not be empty" alt="Field must not be empty" /></td>
	</tr>
	<tr>
	<td class="td1 right">Dictionary 2 URI:</td>
	<td class="td1"><input type="text" name="LgDict2URI" value="" maxlength="200" size="60" /></td>
	</tr>
	<tr>
	<td class="td1 right">GoogleTranslate URI:</td>
	<td class="td1"><input type="text" name="LgGoogleTranslateURI" value="http://translate.google.com/?ie=UTF-8&sl=••&tl=••&text=###" maxlength="200" size="60" /></td>
	</tr>
	<tr>
	<td class="td1 right">Text Size:</td>
	<td class="td1"><select name="LgTextSize" class="notempty"><?php echo get_languagessize_selectoptions(150); ?></select></td>
	</tr>
	<tr>
	<td class="td1 right">Character Substitutions:</td>
	<td class="td1"><input type="text" name="LgCharacterSubstitutions" value="´='|`='|’='|‘='|...=…|..=‥" maxlength="500" size="60" /></td>
	</tr>
	<tr>
	<td class="td1 right">RegExp Split Sentences:</td>
	<td class="td1"><input type="text" class="notempty" name="LgRegexpSplitSentences" value=".!?:;" maxlength="500" size="60" /> <img src="icn/status-busy.png" title="Field must not be empty" alt="Field must not be empty" /></td>
	</tr>
	<tr>
	<td class="td1 right">Exceptions Split Sentences:</td>
	<td class="td1"><input type="text" name="LgExceptionsSplitSentences" value="Mr.|Dr.|[A-Z].|Vd.|Vds." maxlength="500" size="60" /></td>
	</tr>
	<tr>
	<td class="td1 right">RegExp Word Characters:</td>
	<td class="td1"><input type="text" class="notempty" name="LgRegexpWordCharacters" value="a-zA-ZÀ-ÖØ-öø-ȳ" maxlength="500" size="60" /> <img src="icn/status-busy.png" title="Field must not be empty" alt="Field must not be empty" /></td>
	</tr>
	<tr>
	<td class="td1 right">Make each character a word:</td>
	<td class="td1"><select name="LgSplitEachChar"><?php echo get_yesno_selectoptions(0); ?></select> (e.g. for Chinese, Japanese, etc.)</td>
	</tr>
	<tr>
	<td class="td1 right">Remove spaces:</td>
	<td class="td1"><select name="LgRemoveSpaces"><?php echo get_yesno_selectoptions(0); ?></select> (e.g. for Chinese, Japanese, etc.)</td>
	</tr>
	<tr>
	<td class="td1 right">Right-To-Left Script:</td>
	<td class="td1"><select name="LgRightToLeft"><?php echo get_yesno_selectoptions(0); ?></select> (e.g. for Arabic, Hebrew, Farsi, Urdu, etc.)</td>
	</tr>
	<tr>
	<td class="td1 right">Export Template <img class="click" src="icn/question-frame.png" title="Help" alt="Help" onclick="oewin('info_export_template.htm');" /> :</td>
	<td class="td1"><input type="text" name="LgExportTemplate" value="" maxlength="1000" size="60" /></td>
	</tr>
	<tr>
	<td class="td1 right" colspan="2"><input type="button" value="Cancel" onclick="location.href='edit_languages.php';" /> 
	<input type="submit" name="op" value="Save" /></td>
	</tr>
	</table>
	</form>
	
	<?php
	
}

// CHG

elseif (isset($_REQUEST['chg'])) {
	
	$sql = 'select * from ' . $tbpref . 'languages where LgID = ' . $_REQUEST['chg'];
	$res = do_mysql_query($sql);
	if ($record = mysql_fetch_assoc($res)) {
	
		?>
	
		<h4>Edit Language <a target="_blank" href="info.htm#howtolang"><img src="icn/question-frame.png" title="Help" alt="Help" /></a> </h4>
		<form class="validate" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
		<input type="hidden" name="LgID" value="<?php echo $_REQUEST['chg']; ?>" />
		<table class="tab3" cellspacing="0" cellpadding="5">
		<tr>
		<td class="td1 right">ID:</td>
		<td class="td1"><?php echo $_REQUEST['chg']; ?></td>
		</tr>
		<tr>
		<td class="td1 right">Language:</td>
		<td class="td1"><input type="text" class="notempty setfocus" name="LgName" maxlength="40" size="40" value="<?php echo tohtml($record['LgName']); ?>" /> <img src="icn/status-busy.png" title="Field must not be empty" alt="Field must not be empty" /></td>
		</tr>
		<tr>
		<td class="td1 right">Dictionary 1 URI:</td>
		<td class="td1"><input type="text" class="notempty" name="LgDict1URI" value="<?php echo tohtml($record['LgDict1URI']); ?>"  maxlength="200" size="60" /> <img src="icn/status-busy.png" title="Field must not be empty" alt="Field must not be empty" /></td>
		</tr>
		<tr>
		<td class="td1 right">Dictionary 2 URI:</td>
		<td class="td1"><input type="text" name="LgDict2URI" value="<?php echo tohtml($record['LgDict2URI']); ?>"  maxlength="200" size="60" /></td>
		</tr>
		<tr>
		<td class="td1 right">GoogleTranslate URI:</td>
		<td class="td1"><input type="text" name="LgGoogleTranslateURI" value="<?php echo tohtml($record['LgGoogleTranslateURI']); ?>" maxlength="200" size="60" /></td>
		</tr>
		<tr>
		<td class="td1 right">Text Size:</td>
		<td class="td1"><select name="LgTextSize"><?php echo get_languagessize_selectoptions($record['LgTextSize']); ?></select></td>
		</tr>
		<tr>
		<td class="td1 right">Character Substitutions:</td>
		<td class="td1"><input type="text" name="LgCharacterSubstitutions" value="<?php echo tohtml($record['LgCharacterSubstitutions']); ?>" maxlength="500" size="60" /></td>
		</tr>
		<tr>
		<td class="td1 right">RegExp Split Sentences:</td>
		<td class="td1"><input type="text" class="notempty" name="LgRegexpSplitSentences" value="<?php echo tohtml($record['LgRegexpSplitSentences']); ?>" maxlength="500" size="60" /> <img src="icn/status-busy.png" title="Field must not be empty" alt="Field must not be empty" /></td>
		</tr>
		<tr>
		<td class="td1 right">Exceptions Split Sentences:</td>
		<td class="td1"><input type="text" name="LgExceptionsSplitSentences" value="<?php echo tohtml($record['LgExceptionsSplitSentences']); ?>" maxlength="500" size="60" /></td>
		</tr>
		<tr>
		<td class="td1 right">RegExp Word Characters:</td>
		<td class="td1"><input type="text" class="notempty" name="LgRegexpWordCharacters" value="<?php echo tohtml($record['LgRegexpWordCharacters']); ?>" maxlength="500" size="60" /> <img src="icn/status-busy.png" title="Field must not be empty" alt="Field must not be empty" /></td>
		</tr>
		<tr>
		<td class="td1 right">Make each character a word:</td>
		<td class="td1"><select name="LgSplitEachChar"><?php echo get_yesno_selectoptions($record['LgSplitEachChar']); ?></select> (e.g. for Chinese, Japanese, etc.)</td>
		</tr>
		<tr>
		<td class="td1 right">Remove spaces:</td>
		<td class="td1"><select name="LgRemoveSpaces"><?php echo get_yesno_selectoptions($record['LgRemoveSpaces']); ?></select> (e.g. for Chinese, Japanese, etc.)</td>
		</tr>
		<tr>
		<td class="td1 right">Right-To-Left Script:</td>
		<td class="td1"><select name="LgRightToLeft"><?php echo get_yesno_selectoptions($record['LgRightToLeft']); ?></select> (e.g. for Arabic, Hebrew, Farsi, Urdu,  etc.)</td>
		</tr>
		<tr>
		<td class="td1 right">Export Template <img class="click" src="icn/question-frame.png" title="Help" alt="Help" onclick="oewin('info_export_template.htm');" /> :</td>
		<td class="td1"><input type="text" name="LgExportTemplate" value="<?php echo tohtml($record['LgExportTemplate']); ?>" maxlength="1000" size="60" /></td>
		</tr>
		<tr>
		<td class="td1 right" colspan="2"><input type="button" value="Cancel" onclick="location.href='edit_languages.php';" /> 
		<input type="submit" name="op" value="Change" /></td>
		</tr>
		</table>
		<p class="smallgray"><b>Warning:</b> Changing certain language settings (e.g. RegExp Word Characters, etc.)<br />may cause partial or complete loss of improved annotated texts!</p>
		</form>
		<?php

	}
	mysql_free_result($res);
}

// DISPLAY

else {
	
	echo error_message_with_hide($message,0);
	
	$current = (int) getSetting('currentlanguage');
	
	$recno = get_first_value('select count(*) as value from ' . $tbpref . 'languages'); 
	
?>

<p><a href="<?php echo $_SERVER['PHP_SELF']; ?>?new=1"><img src="icn/plus-button.png" title="New" alt="New" /> New Language ...</a></p>

<?php
if ($recno==0) {
?>
<p>No languages found.</p>
<?php
} else {
?>

<table class="sortable tab1" cellspacing="0" cellpadding="5">
<tr>
<th class="th1 sorttable_nosort">Curr.<br />Lang.</th>
<th class="th1 sorttable_nosort">Test<br />↓↓↓</th>
<th class="th1 sorttable_nosort">Actions</th>
<th class="th1 clickable">Language</th>
<th class="th1 sorttable_numeric clickable">Texts,<br />Reparse</th>
<th class="th1 sorttable_numeric clickable">Arch.<br />Texts</th>
<th class="th1 sorttable_numeric clickable">Terms</th>
<th class="th1 sorttable_nosort">Export<br />Template?</th>
</tr>

<?php

$sql = 'select LgID, LgName, LgExportTemplate from ' . $tbpref . 'languages order by LgName';
if ($debug) echo $sql;
$res = do_mysql_query($sql);
while ($record = mysql_fetch_assoc($res)) {
	$textcount = get_first_value('select count(TxID) as value from ' . $tbpref . 'texts where TxLgID=' . $record['LgID']);
	$archtextcount = get_first_value('select count(AtID) as value from ' . $tbpref . 'archivedtexts where AtLgID=' . $record['LgID']);
	$wordcount = get_first_value('select count(WoID) as value from ' . $tbpref . 'words where WoLgID=' . $record['LgID']);
	echo '<tr>';
	if ( $current == $record['LgID'] ) {
		$tdth = 'th';
		echo '<th class="th1"><img src="icn/exclamation-red.png" title="Current Language" alt="Current Language" /></th>';
	} else {
		$tdth = 'td';
		echo '<td class="td1 center"><a href="save_setting_redirect.php?k=currentlanguage&amp;v=' . $record['LgID'] . '&amp;u=edit_languages.php"><img src="icn/tick-button.png" title="Set as Current Language" alt="Set as Current Language" /></a></td>';
	}
	echo '<' . $tdth . ' class="' . $tdth . '1 center"><a href="do_test.php?lang=' . $record['LgID'] . '"><img src="icn/question-balloon.png" title="Test" alt="Test" /></a></' . $tdth . '>';
	echo '<' . $tdth . ' class="' . $tdth . '1 center" nowrap="nowrap">&nbsp;<a href="' . $_SERVER['PHP_SELF'] . '?chg=' . $record['LgID'] . '"><img src="icn/document--pencil.png" title="Edit" alt="Edit" /></a>';
	if ($textcount == 0 && $archtextcount == 0 && $wordcount == 0) 
		echo '&nbsp; <span class="click" onclick="if (confirm (\'Are you sure?\')) location.href=\'' . $_SERVER['PHP_SELF'] . '?del=' . $record['LgID'] . '\';"><img src="icn/minus-button.png" title="Delete" alt="Delete" /></span>';
	else 
		echo '&nbsp; <img src="icn/placeholder.png" title="Delete not possible" alt="Delete not possible" />';
	echo '&nbsp;</' . $tdth . '>';
	echo '<' . $tdth . ' class="' . $tdth . '1 center">' . tohtml($record['LgName']) . '</' . $tdth . '>';
	if ($textcount > 0) 
		echo '<' . $tdth . ' class="' . $tdth . '1 center"><a href="edit_texts.php?page=1&amp;query=&amp;filterlang=' . $record['LgID'] . '">' . $textcount . '</a> &nbsp;&nbsp; <a href="' . $_SERVER['PHP_SELF'] . '?refresh=' . $record['LgID'] . '"><img src="icn/lightning.png" title="Reparse Texts" alt="Reparse Texts" /></a>';
	else
		echo '<' . $tdth . ' class="' . $tdth . '1 center">0 &nbsp;&nbsp; <img src="icn/placeholder.png" title="No texts to reparse" alt="No texts to reparse" />';
	echo '</' . $tdth . '>';
	echo '<' . $tdth . ' class="' . $tdth . '1 center">' . ($archtextcount > 0 ? '<a href="edit_archivedtexts.php?page=1&amp;query=&amp;filterlang=' . $record['LgID'] . '">' . $archtextcount . '</a>' : '0' ) . '</' . $tdth . '>';
	echo '<' . $tdth . ' class="' . $tdth . '1 center">' . ($wordcount > 0 ? '<a href="edit_words.php?page=1&amp;query=&amp;text=&amp;status=&amp;filterlang=' . $record['LgID'] . '&amp;status=&amp;tag12=0&amp;tag2=&amp;tag1=">' . $wordcount . '</a>' : '0' ) . '</' . $tdth . '>';
	echo '<' . $tdth . ' class="' . $tdth . '1 center">' . (isset($record['LgExportTemplate']) ? '<img src="icn/status.png" title="Yes" alt="Yes" />' : '<img src="icn/status-busy.png" title="No" alt="No" />' ) . '</' . $tdth . '>';
	echo '</tr>';
}
mysql_free_result($res);

?>

</table>

<?php

}
}

pageend();

?> 
