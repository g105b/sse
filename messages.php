<?php

use Gt\DomTemplate\HTMLDocument;

function getMessages(int $sinceTimestamp = null):array {
	$messages = [];

	if(is_null($sinceTimestamp)) {
// Default to last 60 minutes.
		$sinceTimestamp = time() - (60 * 60);
	}

	foreach(glob("data/*.chat") as $chat) {
		$timestamp = pathinfo(
			$chat,
			PATHINFO_FILENAME
		);

// Skip files older than the requested timestamp.
		if($timestamp <= $sinceTimestamp) {
			continue;
		}

		$contents = file_get_contents($chat);
		$contents = trim($contents);
		list($user, $message) = explode("\t", $contents);
		$messages []= [
			"timestamp" => $timestamp,
			"time" => date("H:i:s", $timestamp),
			"user" => $user,
			"message" => $message,
		];
	}

	return $messages;
}

function saveMessage(string $user, string $message):void {
	$now = time();
	if(!is_dir("data")) {
		mkdir("data");
	}

	file_put_contents(
		"data/$now.chat",
		"$user\t$message"
	);
}

function getMessageHtml(HTMLDocument $document, $message):string {
	$chatElement = $document->getTemplate("/html/body/form/ul/li");
	$chatElement->bind($message);
	$import = new HTMLDocument();
	$chatElement = $import->importNode($chatElement, true);
	$chatElement = $import->documentElement->appendChild($chatElement);
	$html = str_replace("\n", "", $chatElement->outerHTML);
}