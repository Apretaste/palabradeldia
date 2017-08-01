<?php

use Goutte\Client;

class palabradeldia extends Service
{
	public function _main(Request $request)
	{
		// create a new Client
		$client = new Client();
		$guzzle = $client->getClient();
		$client->setClient($guzzle);

		// create a Crawler
		$crawler = $client->request('GET', 'http://feeds.feedblitz.com/english-word-of-the-day-for-spanish&x=1');

		// get the title
		$title = $crawler->filter('item title')->text();

		// get the text
		$text = $crawler->filter('item description')->text();

		// and strip style
		$description = preg_replace('/(<[^>]+) style=".*?"/i', '$1', $text);

		/*$description = str_ireplace(
				['Part of speech', 'Example sentence', 'Sentence meaning', '<table','<th', 'adjective', 'sustantive'],
				['Parte del habla', 'Oraci&oacute;n de ejemplo', 'Significado de la oraci&oacute;n','<table width="100%"', '<th align="right"', 'adjetivo','sustantivo'],
			$description
		);*/
		$description = $this->parse($description);

		$title = explode(":", $title);
		$title[0] = trim($title[0]);
		$title[1] = trim($title[1]);

		$description = get_object_vars($description->data);
		$description['Example sentence'] = str_replace($title[0],'<b>' . $title[0] . '</b>', $description['Example sentence']);
		$description['Sentence meaning'] = str_replace($title[1],'<b>' . $title[1] . '</b>', $description['Sentence meaning']);

		$content = array(
			'title' => $title,
			'description' => $description
		);

		$response = new Response();
		$response->setResponseSubject("Palabra en Inglés del día");
		$response->createFromTemplate("basic.tpl", $content);
		return $response;
	}

	public function parse($text)
	{
		$text = trim($text);
		$text = str_replace('<table>', '{"data":{', $text);
		$text = str_replace('</table>', '}}', $text);


		$text = str_replace('<th', '"<th', $text);
		$text = str_replace(':</th>', '":</th>', $text);

		$text = str_replace('<td', '"<td', $text);
		$text = str_replace('</td>', '"</td>,', $text);

		$text = strip_tags($text);
		$text = str_replace("\r", "",$text);
		$text = str_replace("\n", "",$text);

		$text = str_replace(",}}","}}", $text);

		$text = htmlentities($text);
		$text = str_replace('&quot;', '"', $text);

		return @json_decode($text);
	}
}
