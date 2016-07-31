<?php
require_once __DIR__ . "/../../../../../vendor/autoload.php";

use \App\UserInfo;
use \App\Song;
use \App\Questionary;
use \App\UserBase;

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
		
		if ( ( $this->user->isNew ) || ( "start" == $c["command"] ) ){
			$l = "ru";
			$this->user->set_language( $l );
			$this->vars = new Vars( $l );						
			$this->user->changeState( "questionary_1" );
			$ret[] = $this->tgbot->createTextMessage( $this->vars->texts["questionary_1"], $this->vars->menus["questionary_1"] );
			
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
		else if( in_array( $c["command"], array("emojilove", "emojirussia", "emojiwinter", "emojisport") ) ) 
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
