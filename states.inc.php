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
 * states.inc.php
 *
 * RememberWhen game states description
 *
 */

/*
   Game state machine is a tool used to facilitate game developpement by doing common stuff that can be set up
   in a very easy way from this configuration file.

   Please check the BGA Studio presentation about game state to understand this, and associated documentation.

   Summary:

   States types:
   _ activeplayer: in this type of state, we expect some action from the active player.
   _ multipleactiveplayer: in this type of state, we expect some action from multiple players (the active players)
   _ game: this is an intermediary state where we don't expect any actions from players. Your game logic must decide what is the next game state.
   _ manager: special type for initial and final state

   Arguments of game states:
   _ name: the name of the GameState, in order you can recognize it on your own code.
   _ description: the description of the current game state is always displayed in the action status bar on
                  the top of the game. Most of the time this is useless for game state with "game" type.
   _ descriptionmyturn: the description of the current game state when it's your turn.
   _ type: defines the type of game states (activeplayer / multipleactiveplayer / game / manager)
   _ action: name of the method to call when this game state become the current game state. Usually, the
             action method is prefixed by "st" (ex: "stMyGameStateName").
   _ possibleactions: array that specify possible player actions on this step. It allows you to use "checkAction"
                      method on both client side (Javacript: this.checkAction) and server side (PHP: self::checkAction).
   _ transitions: the transitions are the possible paths to go from a game state to another. You must name
                  transitions in order to use transition names in "nextState" PHP method, and use IDs to
                  specify the next game state for each transition.
   _ args: name of the method to call to retrieve arguments for this gamestate. Arguments are sent to the
           client side to be used on "onEnteringState" or to set arguments in the gamestate description.
   _ updateGameProgression: when specified, the game progression is updated (=> call to your getGameProgression
                            method).
*/

//    !! It is not a good idea to modify this file when a game is running !!

$machinestates = array(

    // The initial state. Please do not modify.
    1 => array(
        "name" => "gameSetup",
        "description" => clienttranslate("Game setup"),
        "type" => "manager",
        "action" => "stGameSetup",
        "transitions" => array( "" => 20 )
    ),
    
    /// New hand
    20 => array(
        "name" => "newHand",
        "description" => "",
        "type" => "game",
        "action" => "stNewHand",
        "updateGameProgression" => true,   
        "transitions" => array( "" => 21 )
    ),  
	
	21 => array(
        "name" => "chooseRandomObject",
        "description" => clienttranslate('${actplayer} must choose a random object'),
        "descriptionmyturn" => clienttranslate('${you} must choose a random object'),
        "updateGameProgression" => true,   
        "type" => "activeplayer",
        "possibleactions" => array( "chooseRandomObject" ),
        "transitions" => array( "chooseRandomObject" => 28 , "zombiePass" => 53 )
    ), 	 
    28 => array(
        "name" => "drawActions",
        "description" => "",
        "type" => "game",
        "action" => "stDrawActions",
        "transitions" => array( "" => 29 )
    ),  	
	29 => array(
        "name" => "chooseAction",
        "description" => clienttranslate('${actplayer} must choose an action.'),
        "descriptionmyturn" => clienttranslate('${you} must choose an action.'),
        "updateGameProgression" => true,   
        "type" => "activeplayer",
        "possibleactions" => array( "chooseAction" ),
        "transitions" => array( "chooseAction" => 22 , "zombiePass" => 53 )
    ), 	
    22 => array(
        "name" => "chooseRole",
        "description" => clienttranslate('${actplayer} must choose a role.'),
        "descriptionmyturn" => clienttranslate('${you} must choose a role.'),
        "updateGameProgression" => true,   
        "type" => "activeplayer",
        "possibleactions" => array( "chooseRole" ),
        "transitions" => array( "chooseRole" => 30 , "zombiePass" => 53 )
    ), 	

    30 => array(       
        "name" => "giveCards",
        "description" => clienttranslate('Some players are choosing cards to give to the current memory.'),
        "descriptionmyturn" => clienttranslate('${you} must choose a card to give to <span style="color: #${player_color};">${direction}</span>'),
        "updateGameProgression" => true,   
        "type" => "multipleactiveplayer",
        "action" => "stGiveCards",
        "args" => "argGiveCards",
        "possibleactions" => array( "giveCards" ),
        "transitions" => array( "giveCards" => 31 )       
    ), 
     
    31 => array(
        "name" => "completeSentence",
        "description" => "",
        "type" => "game",
        "action" => "stCompleteSentence",
        "transitions" => array( "" => 32)
    ),  

	32 => array(
        "name" => "arrangeSentence",
        "description" => clienttranslate('${actplayer} must arrange the cards into a completed memory.'),
        "descriptionmyturn" => clienttranslate('${you} must arrange the cards into a completed memory.'),
        "updateGameProgression" => true,   
        "type" => "activeplayer",
        "possibleactions" => array( "arrangeSentence" ),
        "transitions" => array( "arrangeSentence" => 50  , "zombiePass" => 53)
    ), 	

    
    40 => array(
        "name" => "scoreSentence",
        "description" => "",
        "type" => "game",
        "action" => "stScoreSentence",
        "transitions" => array( "" => 50  )
    ),        

    
    50 => array(       
        "name" => "vote",
        "description" => clienttranslate('Some players must vote for the best memory.'),
        "descriptionmyturn" => clienttranslate('${you} must vote for the best memory.'),
        "updateGameProgression" => true,   
        "type" => "multipleactiveplayer",
        "action" => "stVote",
        "possibleactions" => array( "vote" ),
        "transitions" => array( "vote" => 51 )        
    ), 

    51 => array(
        "name" => "countVotes",
        "description" => "",
        "type" => "game",
        "action" => "stCountVotes",
        "transitions" => array( "tieBreak" => 52, "endHand" => 53 , "stats" => 91 )
    ),    
   
	52 => array(
        "name" => "tieBreak",
        "description" => clienttranslate('${actplayer} must vote for the best memory.'),
        "descriptionmyturn" => clienttranslate('${you} must vote for the best memory.'),
        "type" => "activeplayer",
        "action" => "stTieBreak",
        "possibleactions" => array( "vote" ),
        "transitions" => array( "vote" => 51 , "zombiePass" => 53 )
    ), 	
    
    53 => array(
        "name" => "endHand",
        "description" => "",
        "type" => "game",
        "action" => "stEndHand",
        "transitions" => array(  "newHand" => 20 , "stats" => 91 )
    ),  
    91 => array(
        "name" => "calcStats",
        "description" => clienttranslate("Calculating statistics"),
        "type" => "game",
        "action" => "stCalcStats",
        "transitions" => array( "gameover" => 99 )
    ),
    
    // Final state.
    // Please do not modify.

    99 => array(
        "name" => "gameEnd",
        "description" => clienttranslate("End of game"),
        "type" => "manager",
        "action" => "stGameEnd",
        "args" => "argGameEnd"
    )

);


