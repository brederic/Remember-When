<?php
 /**
  *------
  * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
  * RememberWhen implementation : © <Your name here> <Your email address here>
  * 
  * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
  * See http://en.boardgamearena.com/#!doc/Studio for more information.
  * -----
  * 
  * rememberwhen.game.php
  *
  * This is the main file for your game logic.
  *
  * In this PHP file, you are going to defines the rules of the game.
  *
  */


require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );


class RememberWhen extends Table
{
	function RememberWhen( )
	{
        	
 
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();self::initGameStateLabels( array( 
            //    "my_first_global_variable" => 10,
            //    "my_second_global_variable" => 11,
            //      ...
            //    "my_first_game_variant" => 100,
            //    "my_second_game_variant" => 101,
            //      ...
        ) );
		
		// Create the eight card decks
		
		$this->cards = self::getNew( "module.common.deck" );
        $this->cards->init( "card" );
		// These should be in material.inc.php, but I can't get it to load from there in the studio.  Will this work when it goes live?
		/*$this->colors = array(
			1 => array( 'name' => clienttranslate('When'),
						'nametr' => self::_('When'),
						'num_cards' => 200/4),
			2 => array( 'name' => clienttranslate('Where'),
						'nametr' => self::_('Where'),
						'num_cards' => 408/4),
			3 => array( 'name' => clienttranslate('How'),
						'nametr' => self::_('How') ,
						'num_cards' => 204/4),
			4 => array( 'name' => clienttranslate('Did What'),
						'nametr' => self::_('Did What') ,
						'num_cards' => 412/4),
			5 => array( 'name' => clienttranslate('Whose'),
						'nametr' => self::_('Whose'),
						'num_cards' => 212/4),
			6 => array( 'name' => clienttranslate('What Kind'),
						'nametr' => self::_('What Kind'),
						'num_cards' => 404/4),
			7 => array( 'name' => clienttranslate('To What'),
						'nametr' => self::_('To What'),
						'num_cards' => 508/4 ),
			8 => array( 'name' => clienttranslate('Why'),
						'nametr' => self::_('Why'),
						'num_cards' => 414/4 )
		);
        */
	}
	
    protected function getGameName( )
    {
        return "rememberwhen";
    }	

    /*
        setupNewGame:
        
        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame( $players, $options = array() )
    {    
        $sql = "DELETE FROM player WHERE 1 ";
        self::DbQuery( $sql ); 

        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        $default_colors = array( "ff0000", "008000", "0000ff", "ffa500", "773300" );

 
        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
        $values = array();
        foreach( $players as $player_id => $player )
        {
            $color = array_shift( $default_colors );
            $values[] = "('".$player_id."','$color','".$player['player_canal']."','".addslashes( $player['player_name'] )."','".addslashes( $player['player_avatar'] )."')";
        }
        $sql .= implode( $values, ',' );
        self::DbQuery( $sql );
        self::reloadPlayersBasicInfos();
        
        /************ Start the game initialization *****/

        // Init global values with their initial values
        //self::setGameStateInitialValue( 'my_first_global_variable', 0 );
        
        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        //self::initStat( 'table', 'table_teststat1', 0 );    // Init a table statistics
        //self::initStat( 'player', 'player_teststat1', 0 );  // Init a player statistics (for all players)

        // TODO: setup the initial game situation here
        // Create cards
        $start_cards = array(); // these will form the first sentence
        foreach( $this->colors as  $color_id => $color ) // spade, heart, diamond, club
        {
			$cards = array();
			
            for( $value=2; $value<=$color['num_cards']+2; $value++ )   //  2, 3, 4, ... K, A
            {
                $cards[] = array( 'type' => $color_id, 'type_arg' => $value, 'nbr' => 1);
            }
			$this->cards->createCards( $cards, 'deck-'.$color_id );
			$this->cards->shuffle( 'deck-'.$color_id );
			$this->cards->pickCardForLocation('deck-'.$color_id, 'top_sentence', rand(1,4) );
		}
       

        // Activate first player (which is in general a good idea :) )
        $this->activeNextPlayer();

        /************ End of the game initialization *****/
    }

    /*
        getAllDatas: 
        
        Gather all informations about current game situation (visible by the current player).
        
        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas() 
    {
        $result = array( 'players' => array() );
    
        $current_player_id = self::getCurrentPlayerId();    // !! We must only return informations visible by this player !!
    
        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $sql = "SELECT player_id id, player_score score FROM player ";
        $result['players'] = self::getCollectionFromDb( $sql );
  
        // TODO: Gather all information about current game situation (visible by player $current_player_id).
		
		// Cards in player hand      
        $result['hand'] = $this->populateCards($this->cards->getCardsInLocation( 'hand', $current_player_id ));
        
          
        // Cards in top sentence
        $result['top_sentence'] = $this->populateCards($this->cards->getCardsInLocation( 'top_sentence' ));
		
  
        return $result;
    }

    /*
        getGameProgression:
        
        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started) and
        100 (= the game is finished or almost finished).
    
        This method is called each time we are in a game state with the "updateGameProgression" property set to true 
        (see states.inc.php)
    */
    function getGameProgression()
    {
        // TODO: compute and return the game progression

        return 0;
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////    

    /*
        In this space, you can put any utility methods useful for your game logic
    */
	
		function doesPlayerHaveCardType($playerId, $cardType)
	{
		$playerhand = $this->cards->getPlayerHand( $playerId );
		// look at each card and check
		foreach ($this->cards->getPlayerHand($playerId) as $card)
		{
			if ($card['type'] == $cardType) 
			{
				return true;
			}
		}
		return false;
	}

    /*
        populateCard: 
        
        Add card text to card object
        
     
    */
    protected function populateCard($card)
    {
		//$color = $this->colors[$card['type']]['name'];
		//$startIndex = $card['value']-2*4+2;
		//$endIndex = $card['value']-2*4+5;
		//if ($startIndex >=2 && $endIndex < 
<<<<<<< HEAD
/**		$card['text_1'] = $this->values_label[ $card['type'] ]['2'];//[strval(($card['value']-1)*4+2)];
		$card['text_2'] = $this->values_label[ $card['type'] ]['3'];//[strval(($card['value']-1)*4+3)];
		$card['text_3'] = $this->values_label[ $card['type'] ]['4'];//[strval(($card['value']-1)*4+4)];
		$card['text_4'] = $this->values_label[ $card['type'] ]['5'];//[strval(($card['value']-1)*4+5)];
		**/
		$result = array();
		$result['text_1'] = $this->values_label[ $card['type'] ][strval(($card['type_arg']-2)*4+2)];
		$result['text_2'] = $this->values_label[ $card['type'] ][strval(($card['type_arg']-2)*4+3)];
		$result['text_3'] = $this->values_label[ $card['type'] ][strval(($card['type_arg']-2)*4+4)];
		$result['text_4'] = $this->values_label[ $card['type'] ][strval(($card['type_arg']-2)*4)+5];
		return $result;
=======
		$card['text_1'] = $this->values_label[ $card['type'] ][strval(($card['type_arg']-2)*4+2)];
		$card['text_2'] = $this->values_label[ $card['type'] ][strval(($card['type_arg']-2)*4+3)];
		$card['text_3'] = $this->values_label[ $card['type'] ][strval(($card['type_arg']-2)*4+4)];
		$card['text_4'] = $this->values_label[ $card['type'] ][strval(($card['type_arg']-2)*4+5)];
        return $card;
		

>>>>>>> origin/master
	}
	/*
        populateCard: 
        
        Add card text to card object
        
     
    */
    protected function populateCards($cards)
    {
		$result = array();
		foreach($cards as $card) {
			$result[] = $this->populateCard($card);
		}
		return $result;
	}


//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
//////////// 

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in rememberwhen.action.php)
    */

    /*
    
    Example:

    function playCard( $card_id )
    {
        // Check that this is the player's turn and that it is a "possible action" at this game state (see states.inc.php)
        self::checkAction( 'playCard' ); 
        
        $player_id = self::getActivePlayerId();
        
        // Add your game logic to play a card there 
        ...
        
        // Notify all players about the card played
        self::notifyAllPlayers( "cardPlayed", clienttranslate( '${player_name} played ${card_name}' ), array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'card_name' => $card_name,
            'card_id' => $card_id
        ) );
          
    }
    
    */

    
//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    /*
    
    Example for game state "MyGameState":
    
    function argMyGameState()
    {
        // Get some values from the current game situation in database...
    
        // return values:
        return array(
            'variable1' => $value1,
            'variable2' => $value2,
            ...
        );
    }    
    */

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    /*
        Here, you can create methods defined as "game state actions" (see "action" property in states.inc.php).
        The action method of state X is called everytime the current game state is set to X.
    */
    
    /*
    
    Example for game state "MyGameState":

    function stMyGameState()
    {
        // Do some stuff ...
        
        // (very often) go to another gamestate
        $this->gamestate->nextState( 'some_gamestate_transition' );
    }    
    */
	    function stNewHand()
    {
        //self::incStat( 1, "handNbr" );
    
    
        // Make sure each player has one card  of each card type
        $players = self::loadPlayersBasicInfos();	
		
        foreach( $this->colors as  $color_id => $color ) // spade, heart, diamond, club
        {
			self::notifyAllPlayers('dealing', 'Dealing cards from deck-'.$color_id, array(
                    'player_id' => '',
                    'player_name' => ''
                ) );
			// Test deal
			//$cards = $this->cards->pickCards( 1, 'deck-'.$color_id, 1001 );
			// Normal deal
			foreach( $players as $player_id => $player )
			{
				if (!$this->doesPlayerHaveCardType($player_id, $color_id)) 
				{
					$cards = $this->cards->pickCards( 1, 'deck-'.$color_id, $player_id );
				}
            
				// Notify player about his cards
				self::notifyPlayer( $player_id, 'newCard', '', array( 
					'cards' => $cards
				) );
			}

		}        
        
        //self::setGameStateValue( 'alreadyPlayedHearts', 0 );

        $this->gamestate->nextState( "" );
    } 


//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    /*
        zombieTurn:
        
        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).
    */

    function zombieTurn( $state, $active_player )
    {
    	$statename = $state['name'];
    	
        if ($state['type'] == "activeplayer") {
            switch ($statename) {
                default:
                    $this->gamestate->nextState( "zombiePass" );
                	break;
            }

            return;
        }

        if ($state['type'] == "multipleactiveplayer") {
            // Make sure player is in a non blocking status for role turn
            $sql = "
                UPDATE  player
                SET     player_is_multiactive = 0
                WHERE   player_id = $active_player
            ";
            self::DbQuery( $sql );

            $this->gamestate->updateMultiactiveOrNextState( '' );
            return;
        }

        throw new feException( "Zombie mode not supported at this game state: ".$statename );
    }
}
