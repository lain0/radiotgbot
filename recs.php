<?php


namespace Recommendations
{

use \App\UserInfo;
use \App\UserToken;
use \Exception;
use \User;
use \App\Song;
use \App\UserSong;
use \App\SongVote;
use \App\Stream;
use \App\StreamVote;
use \App\UserTag;

	function setUserGenres( $uid, $genres )
	{
		foreach( $genres as $genre )
		{
			$ut = new \App\UserTag;
			$ut->uid = $uid;
			$ut->type = 1;
			$ut->tag_id = $genre;
			$ut->rating = 100.;
			$ut->save();
		}
		return true;
	}

	function getRecommendationSongsForUserForEmoji( $uid, $emojiCode )
	{
		$tagsForEmojies = [
			"emojilove"=>[23515,23508,23509],
			"emojirussia"=>[22453,],
			"emojiwinter"=>[32489,],
			"emojisport"=>[22575,]
		];
	
		$tags = $tagsForEmojies[ $emojiCode ];
	    $dislikesVotes = \App\SongVote::where('uid', $uid)->where("vote", "<", 0 )->get();
		$dislikeIds = [];
		foreach( $dislikesVotes as $dv )
		{
			$dislikeIds[] = $dv->id;
		}
		
		/*$userTags = \App\UserTag::where( "uid", $uid )->where('rating', ">", 80 )->get(); // TODO hardcoded
		$userTagsIds = array();
		foreach( $userTags as $ut )
		{
			$userTagsIds[] = $ut->tag_id;
		}*/
		
		$songsTags = \App\SongTag::whereIn('tag_id', $tags )/*->whereIn('tag_id', $userTagsIds )*/->get();
		$songsIdsArray = array();
		foreach( $songsTags as $songTag )
		{
			if( ! in_array( $songTag->song_id, $dislikeIds ) )
			{
				$songsIdsArray[] = $songTag->song_id;
			}
		}
		shuffle( $songsIdsArray );
		$songsIdsToShow = array_slice( $songsIdsArray, 0, 5 );
		$songsToReturn = \App\Song::whereIn('id', $songsIdsToShow )->get() ;
		$songsArray = array();
		foreach( $songsToReturn as $s )
		{
			$songsArray[] = $s;
			
		}
		return $songsArray;
		
	}
	function getRecommendationSongsForUser( $uid )
	{
		
		$userTags = \App\UserTag::where( "uid", $uid )->get();
		$p = array();
		$s = 0.0;
		foreach( $userTags as $ut )
		{
			$s += $ut->rating;
		}
		$tagRand = rand( 0, $s );
		$userTagId = 0;
		$s = 0;
		foreach( $userTags as $ut )
		{
			$s += $ut->rating;
			if( $s > $tagRand )
			{
				$userTagId = $ut->tag_id;
				break;
			}
		}
		$dislikesVotes = \App\SongVote::where('uid', $uid)->where("vote", "<", 0 )->get();
		$dislikeIds = [];
		foreach( $dislikesVotes as $dv )
		{
			$dislikeIds[] = $dv->id;
		}
		$songsTags = \App\SongTag::where('tag_id', $userTagId )->get();
		$songsIdsArray = array();
		foreach( $songsTags as $songTag )
		{
			if( ! in_array( $songTag->song_id, $dislikeIds ) )
			{
				$songsIdsArray[] = $songTag->song_id;
			}
		}
		shuffle( $songsIdsArray );
		$songsIdsToShow = array_slice( $songsIdsArray, 0, 5 );
		$songsToReturn = \App\Song::whereIn('id', $songsIdsToShow )->get() ;
		$songsArray = array();
		foreach( $songsToReturn as $s )
		{
			$songsArray[] = $s;
			
		}
		return $songsArray;
	}
	
	function streamSended( $uid, $songs )
	{
		$stream = new \App\Stream;
		$stream->uid = $uid;
		$stream->save();
		foreach( $songs as $song )
		{
			$userSong = new \App\UserSong;
			$userSong->uid = $uid;
			$userSong->stream_id = $stream->id;
			$userSong->song_id = $song->id;
			$userSong->save();
		}
	}
	
	function voteForSong( $uid, $songTgId, $vote )
	{
		$song = \App\Song::where( "telegram_id", $songTgId )->first();
		return voteForSongObj( $uid, $song, $vote );
	}
	
	function voteForSongObj( $uid, $song, $vote )
	{
		$songVote = new \App\SongVote;
	
		$songVote->song_id = $song->id;
		$songVote->uid = $uid;
		$songVote->vote = $vote;
		$songVote->save();
		$songTags = \App\SongTag::where("song_id", $song->id)->get();
		foreach( $songTags as $songTag )
		{
			$tag_id = $songTag->tag_id;
			$c = \App\UserTag::where( 'uid', $uid )->where( 'tag_id', $tag_id )->where('type', 1 )->count();
			if( $c > 0 )
			{
				$userTag = \App\UserTag::where( 'uid', $uid )->where( 'tag_id', $tag_id )->where('type', 1 )->first();
				$userTag->rating *= ( 1 + $vote / 100. ); // TODO magic constant
				$userTag->save();
			}
			else if( $vote > 0 )
			{
					$userTag = new \App\UserTag;
					$userTag->uid = $uid;
					$userTag->tag_id = $tag_id;
					$userTag->rating = 10 + $vote;
					$userTag->type = 1;
					$userTag->save();
			}
		}
		return true;
	}
	
	function voteForLastStream( $uid, $vote )
	{
		$streamId = \App\Stream::where('uid', $uid )->orderBy("created_at", "DESC")->first()->id;
		return voteForStream( $uid, $streamId, $vote );
	}
	
	function voteForStream( $uid, $streamId, $vote )
	{
		$stream = \App\Stream::find( $streamId );
		$stream->vote = $vote;
		$stream->save();
		$userSongs = \App\UserSong::where('stream_id', $stream->id )->get();
		foreach( $userSongs as $userSong )
		{
			voteForSongObj( $uid, \App\Song::find( $userSong->song_id ), $vote / 5 ); // TODO magic constant
		}
		return true;
	}
}
