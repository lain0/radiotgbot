<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Illuminate\Database\Capsule\Manager as Capsule;

require_once  __DIR__ . "/../../Http/Controllers/radiotgbot/init.php"; 

class LoadSongsCommand extends Command {
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'loadsongs';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "loadsongs actions";
    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
		$data = array();
		
		$url = 'http://muzis.ru';
$search_values = '/api/stream_from_values.api';
$file_url = 'http://f.muzis.ru/';

				$genres = [ 32489, 22575, 23515, 23508, 23509, 5258, 245, 202, 11, 31961, 32496, 10, 269, 8, 4362, 31961 ];
				
				foreach( $genres as $g ){
$vars = array("values" => (string)$g, "size" => "1000");
$api_url = $url . $search_values;

	$ch = curl_init( $api_url );

curl_setopt($ch, CURLOPT_POSTFIELDS,  http_build_query( $vars) );
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_POST, 1);

 curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

$result = json_decode( curl_exec($ch) );
//var_dump( $result );
$songs = $result->songs;

$user = new \User( 125651325 );
		    $vars = new \Vars($user->base["lang"]);
		    $tgbot = new \TGBot(125651325);
			
			
			
foreach( $songs as $song )
{
	$c = \App\Song::where('muzis_id', $song->id )->count();
		if( $c > 0 )
		{
			$songS = \App\Song::where('muzis_id', $song->id )->first();
			$tags = $song->values_all;
			foreach( $tags as $tag )
			{
				$ct = \App\SongTag::where( "song_id", $songS->id )->where('tag_id', $tag)->count();
				if( 0 == $ct )
				{
						$st = new \App\SongTag;
						$st->song_id = $songS->id;
						$st->tag_id = $tag;
						$st->save();
				}
			}
			continue;
		}
		$filename = $file_url . $song->file_mp3;
		$t = file_get_contents( $filename );
				$dfilepath = STORAGE_PATH . "/songs/" .$song->file_mp3;
		file_put_contents( $dfilepath, $t );
		$response = json_decode( json_encode( $tgbot->createAudio( $dfilepath, $song->track_name ) ) )->data;
		//var_dump( $response );
			//				$response = json_decode($i["message"]);
			$toSend = (array) $response;
			//if( isset( $response->reply_markup ) )
				//$toSend["reply_markup"] = new Zelenin\Telegram\Bot\Type\ReplyKeyboardMarkup($response->reply_markup);
			unset( $toSend["reply_markup"] );
			$att = "";
			if( isset($response->att_id) )
				$att = $response->att_id;
			else if( isset($response->att_filename) )
				$att = fopen( $response->att_filename , 'r');
			//var_dump($response->att_filename);
			unset( $toSend["att_id"] );
			unset( $toSend["att_filename"] );
				$ret = "";
				$toSend["audio"] = $att;
				//var_dump( $toSend );
				$ret = $tgbot->api->sendAudio( $toSend );
				//var_dump(33);
				//var_dump( $ret->audio );
				$telegram_id = $ret->audio->file_id;
				$muzis_id = $song->id;
				$song = new \App\Song;
				$song->telegram_id = $telegram_id;
				$song->muzis_id = $muzis_id;
				$song->genre = $g;
				$song->save();
				
				
		//$tgbot->sendMyMessage( $tgbot->createAudio( $dfilepath, $song->track_name ) );
}
				}
        /*$sql = "SELECT `i`.`uid` FROM `user_info` `i` LEFT JOIN `questionaries` as `b` ON `i`.`uid`=`b`.`uid` WHERE `b`.`uid` IS NULL";
        global $db;
		$result = $db->query($sql);
		while ($i=$result->fetch_array()){
		    $user = new \User($i["uid"]);
		    $vars = new \Vars($user->base["lang"]);
		    $tgbot = new \TGBot($i["uid"]);
			$user->changeState( "questionary_1" );
		    $tgbot->sendMyMessage( $tgbot->createTextMessage( $vars->texts["questionary_1"], $vars->menus["questionary_1"] ) );
		    $sql = "UPDATE user_base SET sended = 3 WHERE uid = '" . $i["uid"] . "'";
		    $db->query($sql);
		    usleep(300000);
		}*/
    		
    }
}
