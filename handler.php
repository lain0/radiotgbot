<?php
require_once __DIR__ . "/../../../../../vendor/autoload.php";

use \App\UserInfo;
use \App\Song;
use \App\Questionary;
use \App\UserBase;
use \App\Photo;

use Illuminate\Database\Capsule\Manager as Capsule;

use Carbon\Carbon;

$capsule = new Capsule;

$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => DB_HOST,
    'database'  => DB_NAME,
    'username'  => DB_USER,
    'password'  => DB_PASSWORD,
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix'    => '',
]);

// Make this Capsule instance available globally via static methods
$capsule->setAsGlobal();

// Setup the Eloquent ORM
$capsule->bootEloquent();

class Handler extends HandlerBase{

	public $userInfo = false;
    function __construct($vars, $user, $chat_id){
		parent::__construct($vars, $user, $chat_id);
    }

    public function parse_input ($text, $message_original){
        $hash = "";
		$ret = array();
		$hash = "";
		$c = $this->detect_command( $text );
		$m = $message_original->object->message;
		
		if ( $this->user->isNew )
		{
			$this->user->startbot();
			$l = "ru";
			$this->user->set_language( $l );
			$this->vars = new Vars( $l );						
		}

		if( ( "start" == $c["command"] ) && ( ! isset( $c["arg"] ) ) ) 
		{
			$this->user->changeState( "questionary_1" );
			$ret[] = $this->tgbot->createTextMessage( $this->vars->texts["questionary_1"], $this->vars->menus["questionary_1"] );
		}
		else if( "start" == $c["command"] && isset( $c["arg"]) )
		{
				$uh = User::get_user_by_hash( $c["arg"] );
				$this->user->set_ref_data( $c["arg"] );
				
				if( $uh["uid"] /*&& $uh["uid"] != $this->user->tid*/ )
				{
					$friendUid = $uh["uid"];
					
					$this->user->changeState( "friend_music_" . $friendUid );
		
					$toSend = $this->vars->texts["friend_music"];
					$toSend = str_replace( "%name%", $uh["first_name"] . " " . $uh["last_name"] , $toSend );
					$ret[] = $this->tgbot->createTextMessage( $toSend, $this->vars->menus["stream_friend"] );
				}
		}
		else if( preg_match("/^friend_music_(\d*)$/", $this->user->base["state"], $matches ) )
		{
			$friendUid = $matches[1];
			if( "stream_friend" == $c["command"] )
			{	
				$user = new \User( $friendUid );
				$toSend = $this->vars->texts["friend_music"];
				$toSend = str_replace( "%name%", $user->base["first_name"] . " " . $user->base["last_name"] , $toSend );
				$ret[] = $this->tgbot->createTextMessage( $toSend, $this->vars->menus["stream_friend"] );
						
				$songs = \Recommendations\getRecommendationSongsForUser( $friendUid );
				foreach( $songs as $song )
				{
					$ret[] = $this->tgbot->createAudio( $song->telegram_id, "Hello!", $this->vars->menus["stream_friend"] );
				}
				\Recommendations\streamSended( $this->user->tid, $songs );	
			}
			else if( "finish_stream_friend" == $c["command"] )
			{
				$this->user->changeState( "" );
				$ret[] = $this->tgbot->createTextMessage( $this->vars->texts["help"], $this->vars->menus["default"] );
			}
			else
			{
				$user = new \User( $friendUid );
				$toSend = $this->vars->texts["friend_music"];
				$toSend = str_replace( "%name%", $user->base["first_name"] . " " . $user->base["last_name"] , $toSend );
				$ret[] = $this->tgbot->createTextMessage( $toSend, $this->vars->menus["stream_friend"] );
				
			}
		}
		else if(  "questionary_1" == $this->user->base["state"] )
		{
				$maleGenres = [ 5258, 245, 202, 11, 31961 ];
				
				$femaleGenres = [ 32496, 10, 269, 8, 4362, 31961 ];
					
				if( in_array( $c["command"], array("male", "female") ) )
				{
					$q = new Questionary;
					$q->uid = $this->user->tid;
					$q->sex = $c["command"];
					if( "male" == $q->sex)
					{
						\Recommendations\setUserGenres( $this->user->tid, $maleGenres );
					}
					if( "female" == $q->sex)
					{
						\Recommendations\setUserGenres( $this->user->tid, $femaleGenres );
					}
					
					$q->save();
					$this->user->changeState( "" );
					$ret[] = $this->tgbot->createTextMessage( $this->vars->texts["welcome"], $this->vars->menus["default"] );
					$ret[] = $this->tgbot->createTextMessage( $this->vars->texts["help"], $this->vars->menus["default"] );
				}
				else if( $c["command"] == "finish_questionary" )
				{
					$this->user->changeState( "" );
					
					\Recommendations\setUserGenres( $this->user->tid, array_merge( $femaleGenres, $maleGenres ) );
					
					$ret[] = $this->tgbot->createTextMessage( $this->vars->texts["welcome"], $this->vars->menus["default"] );
					$ret[] = $this->tgbot->createTextMessage( $this->vars->texts["help"], $this->vars->menus["default"] );
				}
				else
				{
					$t = $this->vars->texts["questionary_error"] . "\n" . $this->vars->texts["delimeter"]  ."\n" . $this->vars->texts["questionary_1"];
					$ret[] = $this->tgbot->createTextMessage( $t, $this->vars->menus["questionary_1"] );
				}
		}
		else if(  "importvk" == $this->user->base["state"] )
		{
			$textIndex = "";
			if( "cancel" == $c["command"] )
			{
				$textIndex = "importvk_cancel";
			}
			else
			{
				$this->user->addFeedback( $text );
				$textIndex = "importvk_thanks";	
			}
			$ret[] = $this->tgbot->createTextMessage( $this->vars->texts[$textIndex], $this->vars->menus["default"] );
			$this->user->changeState( "" );
			
		}
		else if( "stream" == $c["command"] )
		{
			$songs = \Recommendations\getRecommendationSongsForUser( $this->user->tid );
			$ret[] = $this->tgbot->createTextMessage( $this->vars->texts["rate_stream"], $this->vars->menus["stream"] );
			foreach( $songs as $song )
			{
				$ret[] = $this->tgbot->createAudio( $song->telegram_id, "Hello!", $this->vars->menus["stream"] );
			}
			\Recommendations\streamSended( $this->user->tid, $songs );
		}
		else if( in_array( $c["command"], array("emojilove", "emojirussia", "emojiwinter", "emojisport", "emojiparty") ) ) 
		{
			$songs = \Recommendations\getRecommendationSongsForUserForEmoji( $this->user->tid, $c["command"] );
			$ret[] = $this->tgbot->createTextMessage( $this->vars->texts["rate_stream_emoji"], $this->vars->menus["stream_emoji"] );
			foreach( $songs as $song )
			{
				$ret[] = $this->tgbot->createAudio( $song->telegram_id, "Hello!", $this->vars->menus["stream_emoji"] );
			}
			\Recommendations\streamSended( $this->user->tid, $songs );
		}
		else if( "stream_emoji" == $c["command"] )
		{
			$ret[] = $this->tgbot->createTextMessage( $this->vars->texts["stream_emoji"], $this->vars->menus["stream_emoji"] );
		}
		else if( "sharemusic" == $c["command"] )
		{
			$descr = str_replace( "%reflink%", $this->user->generate_reflink(), $this->vars->texts["template_share"] );
			$ph = Photo::find( "template_share" );
			$ret[] = $this->tgbot->createPhoto( $ph->photo_id, $descr, $this->vars->menus["default"] );

			$textshare = str_replace( "%reflink%", $this->user->generate_reflink(), $this->vars->texts["sharemusic"] );
			$ret[] = $this->tgbot->createTextMessage( $textshare, $this->vars->menus["default"] );
		}
		else if( "importvk" == $c["command"] )
		{
			$this->user->changeState( "importvk" );
			$ret[] = $this->tgbot->createTextMessage( $this->vars->texts["importvk"], $this->vars->menus["importvk"] );
		}
		else if( "like" == $c["command"] )
		{
			if( isset( $m->reply_to_message ) )
			{
				if( isset( $m->reply_to_message->audio->file_id ) )
				{
					\Recommendations\voteForSong( $this->user->tid, $m->reply_to_message->audio->file_id, +10 ); // TODO magic constant
				}
			}
			else
			{
				\Recommendations\voteForLastStream( $this->user->tid, +10 ); // TODO magic constant
			}
			$ret[] = $this->tgbot->createTextMessage( $this->vars->texts["after_like"], $this->vars->menus["default"] );
			
		}
		else if( "dislike" == $c["command"])
		{
			if( isset( $m->reply_to_message ) )
			{
				if( isset( $m->reply_to_message->audio->file_id ) )
				{
					\Recommendations\voteForSong( $this->user->tid, $m->reply_to_message->audio->file_id, -10 ); // TODO magic constant
				}
			}
			else
			{
				\Recommendations\voteForLastStream( $this->user->tid, -10 ); // TODO magic constant
			}
			$ret[] = $this->tgbot->createTextMessage( $this->vars->texts["after_dislike"], $this->vars->menus["default"] );
		}
		if( empty( $ret ) )
		{
			$da = $this->process_default_actions( $c["command"], $c["arg"] );
			if( $da )
			{
				$ret[] = $da;
			}
		}

	
		if ( count( $ret ) == 0 ){
			if( $text != "" )
				$this->user->addFeedback( $text );
		
			$ret[] = $this->tgbot->createTextMessage( $this->vars->texts["help"], $this->vars->menus["default"] );
		}
        return $ret;
    }

}
