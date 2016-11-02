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
             "topSentenceBuilder" => 12,
             "role" => 14, // 0 = Undecided,  1 = Hero, 2 = Villain
             "currentRound" => 15,
             "totalRounds" => 16,
        
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
        $default_colors = array( "ff0000", "00ff00", "0000ff", "888800", "008888", "880088" );

 
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
        $this->setGameStateInitialValue( 'playerBuildingSentence', 0 );
        $this->setGameStateInitialValue( 'topSentenceBuilder', 0 );
        $this->setGameStateInitialValue( 'role', 0 );
        $this->setGameStateInitialValue( 'currentRound', 0 );
        if (count($players) < 6) {
            $this->setGameStateInitialValue( 'totalRounds', count($players) *2 );
         } else {
            $this->setGameStateInitialValue( 'totalRounds', count($players) );
         }
        
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
			
            for( $value=2; $value<=$color['num_cards']+1; $value++ )   //  2, 3, 4, ... K, A
            {
                $cards[] = array( 'type' => $color_id, 'type_arg' => $value, 'nbr' => 1);
            }
			$this->cards->createCards( $cards, 'deck-'.$color_id );
			$this->cards->shuffle( 'deck-'.$color_id );
            if ($color_id != 5) { // no orange cards
			    $this->cards->pickCardForLocation('deck-'.$color_id, 'top_sentence', rand(1,4) );
            }
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

        
         // Active Player
        $result['sentence_builder'] = self::getGameStateValue( 'playerBuildingSentence' );
        $result['role'] = self::getGameStateValue( 'role' );

        if ($current_player_id == self::getGameStateValue( 'playerBuildingSentence' )) {
            // Working cards
            $result['action_choice'] = $this->populateCards($this->cards->getCardsInLocation( 'action_choice'));

        }

        $result['contribution'] = $this->getContributionMap();
        
		
  
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
		foreach ($playerhand as $card)
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

    protected function getContributionMap(  ) {
        $result = self::getCollectionFromDB( "SELECT player_id id, contribution contribution, guess guess FROM player" );
        //random data for testing
        /*foreach(array_keys($result) as $id) {
            $randomize = array('id'=> $id, 'contribution'=> rand(1,8));
            $result[$id] = $randomize;
        }*/
        return $result;
        /*
        Result:
        array(
        1234 => array( 'id'=>1234, 'name'=>'myuser0', 'score'=>1 ),
        1235 => array( 'id'=>1235, 'name'=>'myuser1', 'score'=>0 )
        )	
		*/
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
    
    function argGiveCards()
    {
	
        $players = self::loadPlayersBasicInfos();   
        $player_id = self::getGameStateValue( 'playerBuildingSentence' );
        $player_name = $players[ $player_id ]['player_name'];

        $player_color = $players[ $player_id ]['player_color'];
        $direction = $player_name;
        
        self::trace('argGiveCards $direction='.$direction);
        return array(
            "i18n" => array( 'direction'),
         "player_color" => $player_color,
            "direction" => $direction
        );
    
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
    function chooseRole( $choice )
    {
        self::checkAction( "chooseRole" );
		
	
        // Here we have to get active player 
        $player_id = self::getActivePlayerId();
		
		$this->setGameStateValue('role', $choice);

        if ($choice == 1) {
            $role_name = 'Hero';
        } else if ($choice == 2) {
            $role_name = 'Villain';
        } else {
            $role_name = 'Undecided';
        }
		
		
        // And notify
        self::notifyAllPlayers( 
			'chooseRole', 
			clienttranslate('${player_name} is acting as a ${role_name}'), 
			array(
				'i18n' => array( 'color_displayed', 'value_displayed' ),
				'player_id' => $player_id,
				'player_name' => self::getActivePlayerName(),
				'role_name' => $role_name,
                'choice' => $choice
			) 
		);

		
        // Choose action
        $this->gamestate->nextState( "chooseRole" );

    } 
	   function chooseAction( $choice )
    {
        self::checkAction( "chooseAction" );
		
		$params = explode("_", $choice);
		$card_id = $params[1];
		$card_pos = $params[2];
        
        // Here we have to get active player 
        $player_id = self::getActivePlayerId();
		
		// TODO: save card_pos to player data
		
		// get object card
		$card = $this->populateCard($this->cards->getCard( $card_id ));
		$this->cards->moveCard($card_id, 'current_sentence', $card_pos);
        // discard the other
        $discards = $this->cards->getCardsInLocation('action_choice');
        foreach ($discards as $discard) {
            $this->cards->playCard($discard['id']);
        }
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
				'value_displayed' => $card['text_'.$card_pos],
                'choice' => $card_pos,
				'color' => $card['type'],
				'color_displayed' => $this->colors[ $card['type'] ]['name'],
                'card' => $card
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
		$card_id = $params[1];
		$card_pos = $params[2];

        // make sure noone else has submitted this card $type
        $count = self::getUniqueValueFromDB("select count(p.contribution) from card c JOIN player p
  ON p.contribution = c.card_type  WHERE c.card_id='$card_id'");
        if ($count > 0) {
            throw new BgaUserException( self::_("Sorry! Someone submitted this card type before you.") );
        }
    
		
		// get object card
		$card = $this->cards->getCard( $card_id );
        $type = $card['type'];
        self::checkAction( "giveCards" );
        
        // !! Here we have to get CURRENT player (= player who send the request) and not
        //    active player, cause we are in a multiple active player state and the "active player"
        //    correspond to nothing.
        $current_player_id = self::getCurrentPlayerId();


        $players = self::loadPlayersBasicInfos();	
		$active_player_id = self::getGameStateValue( 'playerBuildingSentence' );
        $current_player_name = $players[ $current_player_id ]['player_name'];
		$active_player_name = $players[ $active_player_id ]['player_name'];
		
		
		$card_ids[] = $card_id;
        
        //if( count( $card_ids ) != 1 )
        //  throw new feException( self::_("You must give exactly 1 card") );
    
        // Check if these cards are in player hands
        $cards[] = $card;
        
        if( count( $cards ) != 1 )
            throw new feException( self::_("This card doesn't exist") );
        
        foreach( $cards as $card )
        {
            if( $card['location'] != 'hand' || $card['location_arg'] != $current_player_id )
                throw new feException( self::_("This card is not in your hand") );
        }
        

		$this->cards->moveCard($card_id, 'current_sentence', $current_player_id);		// add card to sentence
		
		// record the player's guess for helper scoring
        $sql = "
                UPDATE  player
                SET     contribution = $type,
                        guess = $card_pos
                WHERE   player_id =  $current_player_id
            ";
        self::DbQuery( $sql );
        
        // And notify
        self::notifyAllPlayers( 
			'addCardToSentence', 
			clienttranslate('${current_player_name} guessed ${color_displayed} ${active_player_name} did what they did. '), 
			array(
				'i18n' => array( 'color_displayed', 'value_displayed' ),
				'card_id' => $card['id'],
                'card' => $this->populateCard($card),
				'player_id' => $current_player_id,
				'current_player_name' => $current_player_name,
				'active_player_name' => $active_player_name,
				'color' => $card['type'],
				'color_displayed' => $this->colors[ $card['type'] ]['name']
			) 
		);


        // Notify the player so we can make these cards disapear
        self::notifyPlayer( $current_player_id, "cardGiven", "", array(
            "card" => $card
        ) );

        // Make this player unactive now
        // (and tell the machine state to use transtion "giveCards" if all players are now unactive
        $this->gamestate->setPlayerNonMultiactive( $current_player_id, "giveCards" );
    }
    
	
	
    // Give some cards (before the hands begin)
    function vote( $choice )
    {
        // !! Here we have to get CURRENT player (= player who send the request) and not
        //    active player, cause we are in a multiple active player state and the "active player"
        //    correspond to nothing.
        $current_player_id = self::getCurrentPlayerId();		

        //save vote
             $sql = "
                UPDATE  player
                SET     vote = $choice
                WHERE   player_id =  $current_player_id
            ";
        self::DbQuery( $sql );
        
        $players = self::loadPlayersBasicInfos();	
		$current_player_name = $players[ $current_player_id ]['player_name'];
      
        // And notify
        self::notifyAllPlayers( 
			'status', 
			clienttranslate('${current_player_name} has voted. '), 
			array(
				
				'current_player_name' => $current_player_name,
				
			) 
		);

        // Make this player unactive now
        // (and tell the machine state to use transtion "giveCards" if all players are now unactive
        $this->gamestate->setPlayerNonMultiactive( $current_player_id, "vote" );
        
    }
    
	
    // Active player has finished arranging the sentence
    function arrangeSentence( $choices )
    {
	
        //throw new BgaSystemException ( "Choices: ".http_build_query($choices,'',', '));
        // set positions on current_sentence cards
        $cards = $this->cards->getCardsInLocation('current_sentence');
        $contributions = self::getCollectionFromDB( "SELECT player_id id, contribution contribution, guess guess FROM player" );
         //throw new BgaSystemException ( "Choices: ".implode(" "));
       
        $description = '';

        foreach ($cards as $card) {
            $type = strval($card['type']);
            
            $rotation = $choices[$type];
            $this->cards->moveCard($card['id'],'current_sentence',$rotation);
            $description .= $type.': '.$rotation.';';
            
        }
        //throw new BgaSystemException ( "Choices: ".$description);
        $players = self::loadPlayersBasicInfos();	
        // score points
        foreach ($contributions as $player) {
            if ($player['contribution'] == 0)  continue; // this player did not make a contribution this round
            $actual_choice = $choices[strval($player['contribution'])];
            $prediction = $player['guess'];
            $current_player_name = $players[ $player['id'] ]['player_name'];
            $active_player_id = self::getGameStateValue( 'playerBuildingSentence' );
            $active_player_name = $players[ $active_player_id ]['player_name'];
            if ($actual_choice == $prediction) {
                 // add to player score
               self::DbQuery( "UPDATE player SET player_score=player_score+1 WHERE player_id='".$player['id']."'" );
                $score = self::getUniqueValueFromDB("select player_score from player WHERE player_id='".$player['id']."'");
                // notify everyone
                 self::notifyAllPlayers( 
                    'score', 
                    clienttranslate('${current_player_name} correctly guessed ${color_displayed} ${active_player_name} did what they did and scores a point.'), 
                    array(
                        'i18n' => array( 'color_displayed', 'value_displayed' ),
                        'player_id' => $player['id'],
                        'current_player_name' => $current_player_name,
                        'active_player_name' => $active_player_name,
                        'color' => $player['contribution'],
                        'color_displayed' => $this->colors[$player['contribution'] ]['name'],
                        'score' => $score,
                        'choice' => $prediction
                    ) 
                );
            } else {
                $score = self::getUniqueValueFromDB("select player_score from player WHERE player_id='".$player['id']."'");
                // notify everyone
                 self::notifyAllPlayers( 
                    'score', 
                    clienttranslate('${current_player_name} made an incorrect guess and does not score this round.'), 
                    array(
                        'i18n' => array( 'color_displayed', 'value_displayed' ),
                        'player_id' => $player['id'],
                        'current_player_name' => $current_player_name,
                        'active_player_name' => $active_player_name,
                        'color' => $player['contribution'],
                        'color_displayed' => $this->colors[$player['contribution'] ]['name'],
                        'score' => $score,
                        'choice' => $prediction
                    ) 
                );
            }
        }
        //notify card rotations
        // And notify
            self::notifyAllPlayers( 
                'revealCurrentSentence', 
                clienttranslate("It is time to vote! "), 
                array(
                    
                    'cards' => $this->populateCards($this->cards->getCardsInLocation('current_sentence')),
                    'contributions' => $this->getContributionMap()
                    
                ) 
            );  
        
        
        // Continue
        $this->gamestate->nextState( "" );
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
    
        self::incGameStateValue( 'currentRound', 1);

        // Make sure each player has one card  of each card type
        $players = self::loadPlayersBasicInfos();	

        self::setGameStateValue( 'playerBuildingSentence', self::getActivePlayerId() );
        $this->setGameStateInitialValue( 'role', 0 );
        

        self::notifyAllPlayers('newRound', 'Beginning round ${round} of ${roundCount}. it is ${player_name}\'s turn to reminisce.', array(
                'round' => self::getGameStateValue( 'currentRound'),
                'roundCount' => self::getGameStateValue( 'totalRounds'),
                'player_name' => self::getActivePlayerName(),
                'active_player' => self::getActivePlayerId()

            ) );

        // clear all previous contributions
            $sql = "
            UPDATE  player
            SET     contribution = 0,
                    guess = 0,
                    vote = 0,
                    vote_type = 0
        ";
        self::DbQuery( $sql );

        // Create deck list based on number of $players
        $player_count = self::getPlayersNumber();
        $deck_list = array('1', '2', '3', '6', '8');
        if ($player_count >= 7) {
            $deck_list[] = '4';
        }
        if ($player_count >= 8) {
            $deck_list[] = '7';
        }
        if ($player_count == 9) {
            $deck_list[] = '5';
        }
		
        foreach( $deck_list as  $color_id ) 
        {
			
			// Test deal
			//$cards = $this->cards->pickCards( 1, 'deck-'.$color_id, 1001 );
			// Normal deal
			foreach( $players as $player_id => $player )
			{
				if (!$this->doesPlayerHaveCardType($player_id, $color_id)) 
				{
					$cards = $this->cards->pickCards( 1, 'deck-'.$color_id, $player_id );
                     // only notify non-active players of their cards
                    if ($player_id != self::getGameStateValue( 'playerBuildingSentence' )) {
                
                        // Notify player about his cards
                        self::notifyPlayer( $player_id, 'newCard', '', array( 
                            'cards' => $this->populateCards($cards),
                        ) );
                    }
				}
               
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
				'cards' => $this->populateCards($cards)
			) );
			
        $this->gamestate->nextState();
    }


    function stGiveCards()
    {
        
        // Active all players (everyone has to choose 1 cards to give)
        $this->gamestate->setAllPlayersMultiactive();
		// (and keep the current sentence builder non-active)
		$player_id = self::getGameStateValue( 'playerBuildingSentence' );
        $this->gamestate->setPlayerNonMultiactive( $player_id , "giveCards" );

    }
    

    function stVote()
    {    
         // Active all players (everyone has to vote)
        $this->gamestate->setAllPlayersMultiactive();

        // clear all votes, guess = 1 means their vote will be counted
        $sql = "
                UPDATE  player
                SET     vote = 0,
                        vote_type = 1
            ";
        self::DbQuery( $sql );

        // Who is NOT voting?
        $playersVoting = self::getPlayersNumber();
        $topSentenceBuilder = self::getGameStateValue( 'topSentenceBuilder' );
        $currentSentenceBuilder = self::getGameStateValue( 'playerBuildingSentence' );
        if ($topSentenceBuilder != 0 && $topSentenceBuilder != $currentSentenceBuilder) {
            // (and keep the owner of the sentence builder non-active)
            $this->gamestate->setPlayerNonMultiactive( $topSentenceBuilder , "vote" );
            $playersVoting--;
             // mark not voting
            $sql = "
                    UPDATE  player
                    SET     
                            vote_type = 0
                    WHERE player_id = $topSentenceBuilder
                ";
            self::DbQuery( $sql );
        }
        // the active player will not be voting now
        $this->gamestate->setPlayerNonMultiactive( $currentSentenceBuilder , "vote" );
        // But if there are an even number of players voting, the active player may have to break a tie
        if (($playersVoting-1) % 2 != 0  ) {
            
            // mark not voting
            $sql = "
                    UPDATE  player
                    SET     
                            vote_type = 0
                    WHERE player_id = $currentSentenceBuilder
                ";
            self::DbQuery( $sql );
        } else {
            // mark guess = 2 for tiebreaker only
            $sql = "
                    UPDATE  player
                    SET     
                            vote_type = 2
                    WHERE player_id = $currentSentenceBuilder
                ";
            self::DbQuery( $sql );            
        }
        
    }
    
    function stTieBreak()
    {    
       // all preparation for this vote has already been done
        
    }
    
    
    function stCompleteSentence()
    {
        $current_player_id = self::getGameStateValue( 'playerBuildingSentence' );
        $current_player_hand = $this->cards->getCardsInLocation('hand', $current_player_id );
        $sentence = $this->cards->getCardsInLocation('current_sentence');
        $players = self::loadPlayersBasicInfos();	
		$current_player_name = $players[ $current_player_id ]['player_name'];
		
        // find any card types not already in the current sentence
        foreach( $this->colors as  $type => $color ) // spade, heart, diamond, club
        {
            // skip orange
            if ($type == 5) continue;

            $found = false;
            foreach ($sentence as $card) {
                if ($card['type'] == $type) {
                    $found = true;
                    break;
                }
            }
            // card types not found will be supplied from active players hand
            if (!$found) {
                foreach ($current_player_hand as $card) {
                    if ($card['type'] == $type) {
                      
                        // add card to sentence
                        $this->cards->moveCard($card['id'], 'current_sentence', $current_player_id);		
                        
                        // And notify
                        self::notifyAllPlayers( 
                            'addCardToSentence', 
                            clienttranslate('${current_player_name} added a ${color_displayed} card to the sentence. '), 
                            array(
                                'i18n' => array( 'color_displayed', 'value_displayed' ),
                                'card_id' => $card['id'],
                                'card' => $this->populateCard($card),
                                'player_id' => $current_player_id,
                                'current_player_name' => $current_player_name,
                                'color' => $card['type'],
                                'color_displayed' => $this->colors[ $card['type'] ]['name']
                            ) 
                        );  

                        // Notify the player so we can make these cards disapear
                        self::notifyPlayer( $current_player_id, "cardGiven", "", array(
                            "card" => $card
                        ) );
                    }
                }

            }
			
		}
        $this->gamestate->nextState("");
 
    }

    function getCardIds($cards) {
        $ids = array();
        foreach ($cards as $card) {
            $ids[]=$card['id'];
        }
        return $ids;
    }
    
    
    function stCountVotes()
    {

        // total votes for top sentence (vote = 1)
            $sql = "
                    SELECT count(*)  
                    FROM player    
                           
                    WHERE vote_type = 1 and vote = 1
                ";
        $top_sentence_votes = self::getUniqueValueFromDB( $sql );
        
        // total votes for current sentence (vote = 2)
            $sql = "
                    SELECT count(*)  
                    FROM player    
                           
                    WHERE vote_type = 1 and vote = 2
                ";
        $current_sentence_votes = self::getUniqueValueFromDB( $sql );

        $players = self::loadPlayersBasicInfos();	
        $topSentenceBuilder = self::getGameStateValue( 'topSentenceBuilder' );
        $currentSentenceBuilder = self::getGameStateValue( 'playerBuildingSentence' );
		$currentMemoryName = $players[ $currentSentenceBuilder ]['player_name'];
        if (self::getGameStateValue( 'topSentenceBuilder' ) != 0) {
		    $topMemoryName = $players[ $topSentenceBuilder ]['player_name'];
        } else {
            $topMemoryName = "Random Memory";
        }
        
        //Handle possible tiebreak
        $tiebreaker = false;
        if ($top_sentence_votes > $current_sentence_votes) {
           $winner = 1;
        } else if ($top_sentence_votes < $current_sentence_votes) {
           $winner = 2;
        } else { //tie
            $sql = "
                        SELECT vote  
                        FROM player    
                        WHERE vote_type = 2 
                    ";
            $winner = self::getUniqueValueFromDB( $sql );
            if ($winner == 0) {  // the active player will have to vote
                $this->gamestate->nextState("tieBreak");
                // notify
                self::notifyAllPlayers( "tieBreak", clienttranslate( 'There is a tie. ${player_name} will have to break the tie.' ), array(
                    'player_name' => $currentMemoryName,
                    
                ) );
                return;
            }
            $tiebreaker = true;
        }

		

        if ($winner == 1) { // Top memory won!!
            self::trace('Top memory won!');
            $winnerName = $topMemoryName;
            // clear out current sentence
            $old = $this->getCardIds($this->cards->getCardsInLocation('current_sentence'));
            $this->cards->moveCards($old, 'discard');
            
        } else {  // current sentence won!!
            self::trace('Current memory won!');
           $winnerName = $currentMemoryName;
            // clear out top sentence and replace it with current stCompleteSentence
            $old = $this->getCardIds($this->cards->getCardsInLocation('top_sentence'));
            $this->cards->moveCards($old, 'discard');
            $sql = "
                UPDATE  card
                SET     card_location = 'top_sentence'
                WHERE   card_location = 'current_sentence'
            ";
            self::DbQuery( $sql );
            //$new = $this->getCardIds($this->cards->getCardsInLocation('current_sentence'));
            //foreach ($new as $card) {
            //    self::dump("card:", implode(' ',$card));
            //    $this->cards->moveCard($card, 'top_sentence', $card['location_arg']);
            //}
            self::setGameStateValue('topSentenceBuilder', $currentSentenceBuilder);
            
        }
        // And notify
        $table = array();
        $firstRow = array( '' );
        foreach( $players as $player_id => $player )
        {
            $firstRow[] = array( 'str' => '${player_name}',
                                 'args' => array( 'player_name' => $player['player_name'] ),
                                 'type' => 'header'
                               );
        }
        $firstRow[] = "Totals";
        $table[] = $firstRow;

        // get vote data
        $data = self::getCollectionFromDB( "SELECT player_id id, vote vote, vote_type mode FROM player" );

        
        // Previous Champion Votes
        $secondRow = array(  );
        $secondRow[] = array( 'str' => 'Champion ${player_name}',
                                 'args' => array( 'player_name' => $topMemoryName ),
                                 'type' => 'header'
                               );
        foreach( $players as $player_id => $player )
        {
            if ($data[$player['player_id']]['vote'] == 1) { // match
                if ($data[$player['player_id']]['mode'] == 1) { // normal vote
                    $str = 'X';
                } else { // tie-break
                    $str = 'T';
                }
            } else {
                $str = "";
            }
            $secondRow[] = $str;
        }
        $secondRow[] = $top_sentence_votes;
        $table[] = $secondRow;

        // Challenger Votes
        $thirdRow = array(  );
        $thirdRow[] = array( 'str' => 'Challenger ${player_name}',
                                 'args' => array( 'player_name' => $currentMemoryName ),
                                 'type' => 'header'
                               );
        foreach( $players as $player_id => $player )
        {
            if ($data[$player['player_id']]['vote'] == 2) { // match
                if ($data[$player['player_id']]['mode'] == 1) { // normal vote
                    $str = 'X';
                } else { // tie-break
                    $str = 'T';
                }
            } else {
                $str = "";
            }
            $thirdRow[] = $str;
        }
        $thirdRow[] = $current_sentence_votes;
        $table[] = $thirdRow;
        
        $this->notifyAllPlayers( "tableWindow", '', array(
                    "id" => 'voteTotals',
                    "title" => "Voting Results - $winnerName wins!",
                    "table" => $table,
                    "winner" => $winnerName,
                    "footer" => '<div>T = tie-break vote</div>',
                    "closing" =>clienttranslate( 'Continue')
                ) ); 
        
        if ($winner == 2) {
            $this->notifyAllPlayers( "newTop", '', array(
                    
                    "topSentence" => $this->populateCards($this->cards->getCardsInLocation('top_sentence'))
                ) ); 
        }

        // before changing the active player, restore the current active player's hand
        self::notifyPlayer(  $currentSentenceBuilder, 'newCard', '', array( 
            'cards' => $this->populateCards($this->cards->getCardsInLocation('hand', $currentSentenceBuilder)),
            ) 
        );
        // change Active Player
        self::activeNextPlayer();
        self::setGameStateValue('playerBuildingSentence', self::getActivePlayerId());
            
        if ( self::getGameStateValue( 'currentRound' ) < self::getGameStateValue( 'totalRounds' ) ) {
            $this->gamestate->nextState("newHand");
        } else {
             $this->gamestate->nextState("gameOver");
        }
 
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
