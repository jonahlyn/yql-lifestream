<?php

$feeds = Array(
		'diigo'=> Array(
		'index'=>0,
		'source'=>'diigo',
		'type'=>'rss',
		'message'=>'I saved an article on <a title="Jonahlyn\s bookmarks on Diigo" href="http://www.diigo.com/user/jonahlyn">Diigo</a>',
		'feed-uri'=>'select title,link,pubDate from rss(10) where url="http://www.diigo.com/rss/user/Jonahlyn";'
	),
	'greader'=> Array(
		'index'=>1,
		'source'=>'greader',
		'type'=>'atom',
		'message'=>'I shared a link on <a title="Jonahlyn\'s shared items on Google Reader" href="http://www.google.com/reader/shared/14979088214895012606">Google Reader</a>',
		'feed-uri'=>'select title,link,published from atom(10) where url="http://www.google.com/reader/public/atom/user%2F14979088214895012606%2Fstate%2Fcom.google%2Fbroadcast";'
	),
	'delicious'=> Array(
		'index'=>2,
		'source'=>'delicious',
		'type'=>'rss',
		'message'=>'I saved a bookmark to <a title="jgilstrap\'s bookmarks on Delicious" href="http://delicious.com/jgilstrap">Delicious</a>',
		'feed-uri'=>'select title,link,pubDate from rss where url="http://feeds.delicious.com/v2/rss/jgilstrap?count=10";'
	),
	'readitlater'=> Array(
		'index'=>3,
		'source'=>'readitlater',
		'type'=>'rss',
		'message'=>'I read an article on ReadItLater',
		'feed-uri'=>'select title,link,pubDate from rss(10) where url="http://readitlaterlist.com/users/jonahlyn/feed/read";'
	),
	'blipfm'=> Array(
		'index'=>4,
		'source'=>'blipfm',
		'type'=>'rss',
		'message'=>'I blipped a song on Blip.fm',
		'feed-uri'=>'select title,link,pubDate from rss(10) where url = "http://blip.fm/feed/jonahlyn";'
	),
	'digg'=> Array(
		'index'=>5,
		'source'=>'digg',
		'type'=>'rss',
		'message'=>'I dugg an article on Digg.com',
		'feed-uri'=>'select title,link,pubDate from rss(10) where url = "http://digg.com/users/jgilstrap/history.rss";'
	)
);

/* Concatenate the Feed URIs */
$query = '';
foreach( $feeds as $feed ){
	$query .= $feed['feed-uri'];
}

/* The YQL web service root with JSON as the output */
$root = 'http://query.yahooapis.com/v1/public/yql?format=json&env=store%3A%2F%2Fdatatables.org%2Falltableswithkeys';

/* Assemble the query */
$query = "select * from query.multi where queries='".$query."'";
$url = $root . '&q=' . urlencode($query);

/* Do the curl call (access the data just like a browser would) */
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$output = curl_exec($ch);
curl_close($ch);
$data = json_decode($output);
$results = $data->query->results->results;

/* Combine all links into one array */

$links = Array();

if( count($results) > 0 ){

	foreach( $feeds as $feed ){
		if( $feed['type'] == 'rss' ){

			if( count($results[$feed['index']]->item) > 1){
				foreach( $results[$feed['index']]->item as $r ){
					$tmp = Array('source' => $feed['source'],
						'title' => $r->title,
						'link' => $r->link,
						'origdate' => $r->pubDate, 
						'date' => strtotime($r->pubDate)
					);
					$links[] = $tmp;
				}
			} else {
				$tmp = Array('source' => $feed['source'],
					'title' => $results[$feed['index']]->item->title,
					'link' => $results[$feed['index']]->item->link,
					'origdate' => $results[$feed['index']]->item->pubDate, 
					'date' => strtotime($results[$feed['index']]->item->pubDate)
				);
				$links[] = $tmp;
			}

		}else if($feed['type'] == 'atom') {
			foreach( $results[$feed['index']]->entry as $r ){
				$tmp = Array('source' => $feed['source'],
					'title' => $r->title->content,
					'link' => $r->link->href, 
					'origdate' => $r->pubDate, 
					'date' => strtotime(substr($r->published, 0, 10) . ' ' . substr($r->published, 11, 8 ))
				);
				$links[] = $tmp;
			}
		}
	}

	/* Sort the array by date descending */
	function date_compare($x, $y){
		if($x['date'] == $y['date']){
			return 0;
		} else if ( $x['date'] < $y['date'] ){
			return 1;
		} else if ( $x['date'] > $y['date'] ){
			return -1;
		}
	}

	usort($links, 'date_compare');

}


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title>Jonahlyn's Recent Activity on the Web</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"></meta>
<link rel="stylesheet" type="text/css" href="styles/styles.css"></link>
</head>
<body>

<h1>Jonahlyn's Recent Activity on the Web</h1>

<ul id="activity-feed">
<?php
$count = 0;

foreach ($links as $l){

	$count = $count+1;

	echo '<li class="'.$l['source'];
	echo ($count%2==0)?' even':' odd';
	echo '">';

	echo "<h2>".$feeds[$l['source']]['message']."</h2>";

	// now create the DateTime object for this time
	$dtime = new DateTime( date('r', $l['date']) );

	echo '<p class="post-date">'.$dtime->format("F j, Y").'</p>';
	echo '<p class="post-link"><a href="'.htmlentities($l['link']).'">'.$l['title'].'</a></p>';
	echo '</li>';

	if( $count == 15 ){
		break;
	}
}

?>
</ul>

</body>
</html>
