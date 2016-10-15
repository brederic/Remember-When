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
            				 "playerBuildingSentence" => 13,
        
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

        // Cards in current sentence
        $result['current_sentence'] = $this->populateCards($this->cards->getCardsInLocation( 'current_sentence' ));

        // Working cards
        $result['working_area'] = $this->populateCards($this->cards->getCardsInLocation( 'action_choice'));
        
		
  
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

		$card['text_1'] = $this->values_label[ $card['type'] ][strval(($card['type_arg']-2)*4+2)];
		$card['text_2'] = $this->values_label[ $card['type'] ][strval(($card['type_arg']-2)*4+3)];
		$card['text_3'] = $this->values_label[ $card['type'] ][strval(($card['type_arg']-2)*4+4)];
		$card['text_4'] = $this->values_label[ $card['type'] ][strval(($card['type_arg']-2)*4+5)];
        return $card;
		

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

    
    // Play a card from player hand
    function playCard( $card_id )
    {
        self::checkAction( "playCard" );
        
        $player_id = self::getActivePlayerId();
        
        // Get all cards in player hand
        // (note: we must get ALL cards in player's hand in order to check if the card played is correct)
        
        $playerhands = $this->cards->getCardsInLocation( 'hand', $player_id );

        $bFirstCard = ( count( $playerhands ) == 13 );
                
        $currentTrickColor = self::getGameStateValue( 'trickColor' ) ;
                
        // Check that the card is in this hand
        $bIsInHand = false;
        $currentCard = null;
        $bAtLeastOneCardOfCurrentTrickColor = false;
        $bAtLeastOneCardWithoutPoints = false;
        $bAtLeastOneCardNotHeart = false;
        foreach( $playerhands as $card )
        {
            if( $card['id'] == $card_id )
            {
                $bIsInHand = true;
                $currentCard = $card;
            }
            
            if( $card['type'] == $currentTrickColor )
                $bAtLeastOneCardOfCurrentTrickColor = true;

            if( $card['type'] != 2 )
                $bAtLeastOneCardNotHeart = true;
                
            if( $card['type'] == 2 || ( $card['type'] == 1 && $card['type_arg'] == 12  ) )
            {
                // This is a card with point
            }
            else
                $bAtLeastOneCardWithoutPoints = true;
        }
        if( ! $bIsInHand )
            throw new feException( "This card is not in your hand" );
            
        if( $this->cards->countCardInLocation( 'hand' ) == 52 )
        {
            // If this is the first card of the hand, it must be 2-club
            // Note: first card of the hand <=> cards on hands == 52

            if( $currentCard['type'] != 3 || $currentCard['type_arg'] != 2 ) // Club 2
                throw new feException( self::_("You must play the Club-2"), true );                
        }
        else if( $currentTrickColor == 0 )
        {
            // Otherwise, if this is the first card of the trick, any cards can be played
            // except a Heart if:
            // _ no heart has been played, and
            // _ player has at least one non-heart
            if( self::getGameStateValue( 'alreadyPlayedHearts')==0
             && $currentCard['type'] == 2   // this is a heart
             && $bAtLeastOneCardNotHeart )
            {
                throw new feException( self::_("You can't play a heart to start the trick if no heart has been played before"), true );
            }
        }
        else
        {
            // The trick started before => we must check the color
            if( $bAtLeastOneCardOfCurrentTrickColor )
            {
                if( $currentCard['type'] != $currentTrickColor )
                    throw new feException( sprintf( self::_("You must play a %s"), $this->colors[ $currentTrickColor ]['nametr'] ), true );
            }
            else
            {
                // The player has no card of current trick color => he can plays what he want to
                
                if( $bFirstCard && $bAtLeastOneCardWithoutPoints )
                {
                    // ...except if it is the first card played by this player during this hand
                    // (it is forbidden to play card with points during the first trick)
                    // (note: if player has only cards with points, this does not apply)
                    
                    if( $currentCard['type'] == 2 || ( $currentCard['type'] == 1 && $currentCard['type_arg'] == 12  ) )
                    {
                        // This is a card with point                  
                        throw new feException( self::_("You can't play cards with points during the first trick"), true );
                    }
                }
            }
        }
        
        // Checks are done! now we can play our card
        $this->cards->moveCard( $card_id, 'cardsontable', $player_id );
        
        // Set the trick color if it hasn't been set yet
        if( $currentTrickColor == 0 )
            self::setGameStateValue( 'trickColor', $currentCard['type'] );
        
        if( $currentCard['type'] == 2 )
            self::setGameStateValue( 'alreadyPlayedHearts', 1 );
        
        // And notify
        self::notifyAllPlayers( 'playCard', clienttranslate('${player_name} plays ${value_displayed} ${color_displayed}'), array(
            'i18n' => array( 'color_displayed', 'value_displayed' ),
            'card_id' => $card_id,
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'value' => $currentCard['type_arg'],
            'value_displayed' => $this->values_label[ $currentCard['type_arg'] ],
            'color' => $currentCard['type'],
            'color_displayed' => $this->colors[ $currentCard['type'] ]['name']
        ) );
        
        // Next player
        $this->gamestate->nextState( 'playCard' );
    }
    
    // Give some cards (before the hands begin)
    function chooseRandomObject( $choice )
    {
        self::checkAction( "chooseRandomObject" );
        
        // Here we have to get active player 
        $player_id = self::getActivePlayerId();
		
        self::setGameStateValue( 'playerBuildingSentence', $player_id );
		
		
		// get object card
		$card = $this->cards->pickCardForLocation('deck-7', 'current_sentence', $choice);
        $card = $this->populateCard($card);

        // TODO: save choice to player data??
	

        // And notify
        self::notifyAllPlayers( 
			'addCardToSentence', 
			clienttranslate('${player_name} randomly adds ${value_displayed} ${color_displayed} to the sentence.'), 
			array(
				'i18n' => array( 'color_displayed', 'value_displayed' ),
				'card_id' => $card['id'],
				'player_id' => $player_id,
				'player_name' => self::getActivePlayerName(),
				'value' => $card['type_arg'],
				'value_displayed' => $card['text_'.$choice],
                'choice' => $choice,
				'color' => $card['type'],
				'color_displayed' => $this->colors[ $card['type'] ]['name'],
                'card' => $card
			) 
		);

		
        // Choose action
        $this->gamestate->nextState( "chooseRandomObject" );

    } 
	
	    
    // Give some cards (before the hands begin)
    function chooseAction( $choice )
    {
        self::checkAction( "chooseAction" );
		
		$params = explode("_", $choice);
		$card_id = $params[0];
		$card_pos = $params[1];
        
        // Here we have to get active player 
        $player_id = self::getActivePlayerId();
		
		// TODO: save card_pos to player data
		
		// get object card
		$card = $this->cards->getCard( $card_id );
		$this->cards->moveCard($card_id, 'cardsontable', $player_id);
        
        // And notify
        self::notifyAllPlayers( 
			'addCardToSentence', 
			clienttranslate('${player_name} vaguely remembers doing ${value_displayed} ${color_displayed} to the object.  But when? where? why? how?'), 
			array(
				'i18n' => array( 'color_displayed', 'value_displayed' ),
				'card_id' => $card['id'],
				'player_id' => $player_id,
				'player_name' => self::getActivePlayerName(),
				'value' => $card['type_arg'],
				//'value_displayed' => $this->values_label[ $card['type_arg'] ],
				'color' => $card['type'],
				'color_displayed' => $this->colors[ $card['type'] ]['name']
			) 
		);

		
        // Choose action
        $this->gamestate->nextState( "chooseAction" );

    } 
	
    // Give some cards (before the hands begin)
    function giveCards( $choice )
    {
		//convert choice into card
		$params = explode("_", $choice);
		$card_id = $params[0];
		$card_pos = $params[1];
		
		// get object card
		$card = $this->cards->getCard( $card_id );
        self::checkAction( "giveCards" );
        
        // !! Here we have to get CURRENT player (= player who send the request) and not
        //    active player, cause we are in a multiple active player state and the "active player"
        //    correspond to nothing.
        $player_id = self::getCurrentPlayerId();
		
		
		$card_ids[] = $card_id;
        
        //if( count( $card_ids ) != 1 )
        //  throw new feException( self::_("You must give exactly 1 card") );
    
        // Check if these cards are in player hands
        $cards[] = $card;
        
        if( count( $cards ) != 1 )
            throw new feException( self::_("This card doesn't exist") );
        
        foreach( $cards as $card )
        {
            if( $card['location'] != 'hand' || $card['location_arg'] != $player_id )
                throw new feException( self::_("This card is not in your hand") );
        }
        

		$this->cards->moveCard($card_id, 'cardsontable', $player_id);		// add card to sentence
		
		// TODO: record the player's guess for helper scoring
        
        // And notify
        self::notifyAllPlayers( 
			'addCardToSentence', 
			clienttranslate('${player_name} vaguely remembers doing ${value_displayed} ${color_displayed} to the object.  But when? where? why? how?'), 
			array(
				'i18n' => array( 'color_displayed', 'value_displayed' ),
				'card_id' => $card['id'],
				'player_id' => $player_id,
				'player_name' => self::getActivePlayerName(),
				'value' => $card['type_arg'],
				//'value_displayed' => $this->values_label[ $card['type_arg'] ],
				'color' => $card['type'],
				'color_displayed' => $this->colors[ $card['type'] ]['name']
			) 
		);
		/*
        // To which player should I give these cards ?
        $player_to_give_cards = null;
        $player_to_direction = self::getPlayersToDirection();   // Note: current player is on the south
        $handType = self::getGameStateValue( "currentHandType" );
        if( $handType == 0 )
            $direction = 'W';
        else if( $handType == 1 )
            $direction = 'N';
        else if( $handType == 2 )
            $direction = 'E';
        foreach( $player_to_direction as $opponent_id => $opponent_direction )
        {
            if( $opponent_direction == $direction )
                $player_to_give_cards = $opponent_id;
        }
        if( $player_to_give_cards === null )
            throw new feException( self::_("Error while determining to who give the cards") );
        
        // Allright, these cards can be given to this player
        // (note: we place the cards in some temporary location in order he can't see them before the hand starts)
        $this->cards->moveCards( $card_ids, "temporary", $player_to_give_cards );
		*/

        // Notify the player so we can make these cards disapear
        self::notifyPlayer( $player_id, "giveCards", "", array(
            "cards" => $card_ids
        ) );

        // Make this player unactive now
        // (and tell the machine state to use transtion "giveCards" if all players are now unactive
        $this->gamestate->setPlayerNonMultiactive( $player_id, "giveCards" );
    }
    

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

        // Create deck list based on number of $players
        $player_count = self::getPlayersNumber();
        $deck_list = array(1, 2, 3, 6, 8);
        if ($player_count >= 7) {
            $deck_list[] = 4;
        }
        if ($player_count >= 8) {
            $deck_list[] = 7;
        }
        if ($player_count == 9) {
            $deck_list[] = 5;
        }
		
        foreach( $deck_list as  $color_id ) 
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
    
    function stDrawActions()
    {
       
		// Here we have to get player who is building the sentence 
        $player_id = self::getGameStateValue( 'playerBuildingSentence' );
		

        $cards = $this->cards->pickCardsForLocation(2, 'deck-4', 'action_choice', $player_id);
		 
		
		// Notify player about his cards
			self::notifyPlayer( $player_id, 'considerActions', '', array( 
				'player_id' => $player_id,
				'cards' => $cards
			) );
			
        $this->gamestate->nextState();
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
